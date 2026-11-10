<?php

namespace Better_Payment\Lite\AI\Services;

use Better_Payment\Lite\AI\AIManager;
use Better_Payment\Lite\AI\Operations\OperationRegistry;
use Better_Payment\Lite\AI\Operations\OperationValidator;
use Better_Payment\Lite\AI\Prompt\PromptBuilder;
use Better_Payment\Lite\AI\Schema\CampaignSchema;
use Better_Payment\Lite\Campaign\Elements\ElementRegistry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Orchestrates a single AI turn end-to-end.
 *
 * build prompt → active provider->chat (with operation tools) → extract
 * candidate operations → validate/sanitise against the schema → return a clean
 * result the REST layer can hand to the client.
 *
 * Provider-agnostic: it only ever talks to {@see AIManager::active_provider()}.
 *
 * @see PromptBuilder
 * @see ResponseValidator
 * @see OperationValidator
 */
class AIService {

    /**
     * Modes that produce prose, not operations. They attach no operation tools,
     * never extract or force operations, and return an empty operations array —
     * the caller reads `assistant_message` as the whole result. 'brief' is the
     * wizard's pre-generation brief writer; 'analyze' still attaches tools because
     * it offers one-click-fix suggestions alongside its report.
     *
     * @var array<int, string>
     */
    private static $text_only_modes = [ 'brief' ];

    /**
     * Run one AI turn.
     *
     * @param string $mode        'generate' | 'edit' | 'analyze' | 'brief'
     * @param string $message     The user instruction.
     * @param array  $context     [ 'layout' => [...], 'meta' => [...] ] trusted current state.
     * @param array  $history     Prior turns [ [ 'role' => ..., 'content' => ... ], ... ].
     * @param array  $options     Per-call provider options (e.g. a larger max_tokens).
     * @param array  $target      Optional single-widget focus: [ 'id', 'type', 'settings' ].
     *                            When set it both narrows the prompt and scopes the
     *                            returned operations to that element id.
     * @return array|\WP_Error {
     *     @type string $assistant_message
     *     @type array  $operations   Validated, sanitised operations.
     *     @type array  $usage
     * }
     */
    public static function run( string $mode, string $message, array $context = [], array $history = [], array $options = [], array $target = [] ) {
        $provider = AIManager::active_provider();
        if ( null === $provider ) {
            return new \WP_Error( 'ai_no_provider', __( 'No AI provider is configured.', 'better-payment' ), [ 'status' => 400 ] );
        }
        if ( ! $provider->is_configured() ) {
            return new \WP_Error( 'ai_not_configured', __( 'The selected AI provider is missing its API key. Add it under Better Payment → Settings → AI.', 'better-payment' ), [ 'status' => 400 ] );
        }

        $text_only = in_array( $mode, self::$text_only_modes, true );

        // The campaign's currency, so the model reasons about amounts in it ('' = the site's).
        $currency = isset( $context['meta']['bpc_currency'] ) && is_string( $context['meta']['bpc_currency'] ) ? $context['meta']['bpc_currency'] : '';
        $system   = PromptBuilder::system_prompt( $mode, AIManager::system_prompt(), $target, $currency );
        $messages = self::build_messages( $history, PromptBuilder::user_message( $message, $context ) );

        // Per-call options (e.g. a larger max_tokens for full-layout generation)
        // override the provider defaults; the system prompt is always set here.
        // Operation tools are attached for every mode EXCEPT text-only ones — a
        // brief writer has nothing to call, and offering it the tools only invites
        // it to (which then goes nowhere).
        $chat_options = array_merge( $options, [ 'system' => $system ] );
        if ( ! $text_only ) {
            $chat_options['tools'] = OperationRegistry::as_tools();
        }

        $result = $provider->chat( $messages, $chat_options );

        if ( ! empty( $result['error'] ) ) {
            return new \WP_Error( 'ai_provider_error', (string) $result['error'], [ 'status' => 502 ] );
        }

        // A targeted turn may only touch the element it was pointed at. The prompt
        // says so too, but the prompt is a request; this is the guarantee.
        $scope = self::scope_for( $target );

        // A text-only mode never carries operations — skip extraction entirely so
        // a stray tool call the model made anyway cannot become an applied edit.
        // `$proposed` keeps what the model asked for before validation, so an edit
        // the validator dropped in full (e.g. a style key) can be reported as such.
        $proposed   = $text_only ? [] : ResponseValidator::extract_operations( $result );
        $operations = $text_only ? [] : OperationValidator::validate_batch( $proposed, $context, $scope );

        // A request to style one widget must never become a page-wide colour. Read
        // from the user's own words, because the operation itself is valid.
        $style_request = 'edit' === $mode && empty( $scope ) ? WidgetStyleGuard::detect( $message ) : [];

        // Robustness: sometimes the model *describes* the change ("I will update the
        // button label…") but never calls the tool, so nothing is applied. When we
        // got no operations but the reply reads like an intended edit, retry once
        // forcing a tool call so the change actually happens.
        //
        // Never in 'analyze' mode. A critique that has no one-click fix to offer is
        // a perfectly good critique; forcing a tool call there manufactures an edit
        // the user never asked for out of a sentence like "the story could be
        // stronger", and hands them an Apply button for it. Never in a text-only
        // mode either — there is no operation it could be trying to make. Nor for a
        // widget style request: the only operation it could force is a page colour.
        if ( ! $text_only && 'analyze' !== $mode && empty( $style_request ) && empty( $operations ) && self::narrates_change( (string) ( $result['text'] ?? '' ) ) ) {
            $forced = $provider->chat( $messages, array_merge( $chat_options, [ 'tool_choice' => 'required' ] ) );
            if ( empty( $forced['error'] ) ) {
                $forced_proposed = ResponseValidator::extract_operations( $forced );
                $proposed        = array_merge( $proposed, $forced_proposed );
                $forced_ops      = OperationValidator::validate_batch( $forced_proposed, $context, $scope );
                if ( ! empty( $forced_ops ) ) {
                    $result     = $forced;
                    $operations = $forced_ops;
                }
            }
        }

        $safe_operations = WidgetStyleGuard::without_page_paint( $operations, $style_request );
        $paint_dropped   = count( $safe_operations ) !== count( $operations );
        $operations      = $safe_operations;

        $assistant_message = trim( (string) ( $result['text'] ?? '' ) );
        if ( '' === $assistant_message ) {
            $assistant_message = self::fallback_message( $mode, $operations );
        }

        // The model's prose is written before validation and may claim an edit the
        // validator then dropped. An edit turn must never say "updated" when
        // nothing reached the campaign.
        if ( 'edit' === $mode ) {
            $assistant_message = self::reconcile_edit_message( $assistant_message, $proposed, $operations, $context, $style_request, $paint_dropped );
        }

        // A free install still gets the exact Pro widget it asked for — inserted as
        // a locked preview (see OperationValidator::validate_insert_block). Whatever
        // the model wrote, append a plain note that the widget is Pro-only so the
        // user is never surprised it is inactive on their live page. This is
        // enforcement, not a request: it does not depend on the model choosing to
        // mention the restriction. No-op when Pro is active or nothing was locked.
        $notice = self::pro_locked_notice(
            CampaignSchema::locked_pro_types_in_operations( $operations, $context )
        );
        if ( '' !== $notice ) {
            $assistant_message .= "\n\n" . $notice;
        }

        return [
            'assistant_message' => $assistant_message,
            'operations'        => $operations,
            'usage'             => $result['usage'] ?? [],
        ];
    }

    /**
     * The user-facing note appended when a free install just gained one or more
     * locked Pro widgets. Empty for an empty type list.
     *
     * Written as the small Markdown subset the assistant bubble renders
     * ({@see ai/reportFormat.js}) — a bold lead line, then plain prose. It names
     * the widgets by their human label, not their type key.
     *
     * @param array<int, string> $types Pro element type keys (see
     *                                   {@see CampaignSchema::locked_pro_types_in_operations()}).
     * @return string
     */
    public static function pro_locked_notice( array $types ): string {
        $labels = [];
        foreach ( $types as $type ) {
            if ( '' === $type ) {
                continue;
            }
            $schema  = ElementRegistry::get( $type );
            $labels[] = is_array( $schema ) && ! empty( $schema['label'] ) ? (string) $schema['label'] : $type;
        }
        $labels = array_values( array_unique( $labels ) );

        if ( empty( $labels ) ) {
            return '';
        }

        $names = self::join_labels( $labels );

        if ( count( $labels ) > 1 ) {
            return sprintf(
                /* translators: %s: comma-separated Pro widget names (e.g. "FAQ, Video and Donors Wall"). */
                __( '**Heads up — %s are Better Payment Pro widgets.** I added them to your campaign as you asked, but on the free plugin they show as a locked preview and stay inactive on your live page. Upgrade to Better Payment Pro to switch them on.', 'better-payment' ),
                $names
            );
        }

        return sprintf(
            /* translators: %s: a single Pro widget name (e.g. "FAQ"). */
            __( '**Heads up — %s is a Better Payment Pro widget.** I added it to your campaign as you asked, but on the free plugin it shows as a locked preview and stays inactive on your live page. Upgrade to Better Payment Pro to switch it on.', 'better-payment' ),
            $names
        );
    }

    /**
     * Join widget labels into a readable list: "A", "A and B", "A, B and C".
     *
     * @param array<int, string> $labels
     * @return string
     */
    private static function join_labels( array $labels ): string {
        $count = count( $labels );
        if ( 0 === $count ) {
            return '';
        }
        if ( 1 === $count ) {
            return $labels[0];
        }
        $last = array_pop( $labels );
        return implode( ', ', $labels ) . ' ' . __( 'and', 'better-payment' ) . ' ' . $last;
    }

    /**
     * Text to show when the model returned tool calls but no prose.
     *
     * This has to know the mode. 'analyze' does not apply anything — its
     * operations are held as suggestions the user accepts one at a time — so the
     * edit-mode wording ("Done — I applied the changes.") rendered directly above
     * a column of un-clicked **Apply** buttons, telling the user the work was
     * finished when nothing had happened at all.
     *
     * @param string $mode       'generate' | 'edit' | 'analyze' | 'brief'
     * @param array  $operations Validated operations for this turn.
     * @return string
     */
    private static function fallback_message( string $mode, array $operations ): string {
        // A brief writer that returned no prose produced nothing usable — there is
        // no sensible stand-in sentence to invent, and inventing one would land in
        // the user's editable brief box as if it were the improved brief. Leave it
        // empty so BriefWriter can detect the miss and keep the current brief.
        if ( 'brief' === $mode ) {
            return '';
        }

        if ( 'analyze' === $mode ) {
            return empty( $operations )
                ? __( 'I reviewed the campaign and found nothing that needs changing.', 'better-payment' )
                : __( 'Here is what I would improve. Apply any suggestion below to make the change.', 'better-payment' );
        }

        return empty( $operations )
            ? __( 'I could not complete that request. Please try rephrasing.', 'better-payment' )
            : __( 'Done — I applied the changes.', 'better-payment' );
    }

    /**
     * Make an edit turn's reply match what was actually applied.
     *
     * - Nothing applied, but the model proposed operations or wrote a completion
     *   claim ("I've updated…") → the reply is replaced by
     *   {@see nothing_applied_notice()}; the model's text is false.
     * - Something applied, but the model also tried to write style settings →
     *   {@see style_notice()} is appended, so the user knows that part was skipped.
     * - The user asked to style one widget ({@see WidgetStyleGuard}) → the refusal
     *   names that widget. A model's "I made it white" never stands beside a colour
     *   the guard dropped. A question keeps its answer, and so does a reply about a
     *   colour the AI can write, unless a page colour was dropped.
     *
     * @param string $message       The reply so far.
     * @param array  $proposed      Raw candidate operations, before validation.
     * @param array  $operations    Validated operations.
     * @param array  $context       Current campaign state.
     * @param array  $style_request From {@see WidgetStyleGuard::detect()}.
     * @param bool   $paint_dropped Whether page-wide colour operations were dropped.
     * @return string
     */
    public static function reconcile_edit_message( string $message, array $proposed, array $operations, array $context = [], array $style_request = [], bool $paint_dropped = false ): string {
        $style_keys = CampaignSchema::style_keys_in_operations( $proposed, $context );

        if ( ! empty( $style_request ) ) {
            $notice = self::widget_style_notice( $style_request );

            if ( empty( $operations ) ) {
                $declined = empty( $style_request['question'] ) && empty( $style_request['writable'] );
                if ( $paint_dropped || $declined || ! empty( $proposed ) || self::claims_change( $message ) ) {
                    return self::nothing_applied_lead() . "\n\n" . $notice;
                }
                return $message;
            }

            if ( $paint_dropped ) {
                return __( 'I applied the other changes you asked for.', 'better-payment' ) . "\n\n" . $notice;
            }

            if ( ! empty( $style_keys ) ) {
                return $message . "\n\n" . $notice;
            }

            return $message;
        }

        if ( empty( $operations ) ) {
            if ( ! empty( $proposed ) || self::claims_change( $message ) ) {
                return self::nothing_applied_notice( ! empty( $style_keys ) );
            }
            return $message;
        }

        if ( ! empty( $style_keys ) ) {
            return $message . "\n\n" . self::style_notice();
        }

        return $message;
    }

    /**
     * Reply for an edit turn that changed nothing. Written in the Markdown subset
     * the assistant bubble renders ({@see ai/reportFormat.js}).
     *
     * @param bool $style_requested Whether the model tried to write style settings.
     * @return string
     */
    public static function nothing_applied_notice( bool $style_requested = false ): string {
        return self::nothing_applied_lead() . "\n\n" . ( $style_requested
            ? self::style_notice()
            : __( 'I can change the wording, the donation amounts, the campaign colours and the title and description typography. For per-widget styling, select the widget and use **Customize → Style**.', 'better-payment' ) );
    }

    /**
     * The bold first line of a reply that changed nothing.
     *
     * @return string
     */
    private static function nothing_applied_lead(): string {
        return __( '**Nothing was changed.** I could not apply that edit to your campaign.', 'better-payment' );
    }

    /**
     * The note that per-widget style controls are not the AI's.
     *
     * @return string
     */
    public static function style_notice(): string {
        return __( 'Widget styles (colours, borders, corner radius, shadows and spacing) cannot be changed by the AI yet. Select the widget and use **Customize → Style** or **Advanced**. I can still set the campaign\'s primary colour and page background, which apply to the whole page.', 'better-payment' );
    }

    /**
     * The refusal for a request to style one widget, naming what was asked.
     *
     * @param array $request From {@see WidgetStyleGuard::detect()}.
     * @return string
     */
    public static function widget_style_notice( array $request ): string {
        $label = (string) ( $request['label'] ?? '' );

        // A colour the AI does hold for this widget: refuse the page colour, not the request.
        if ( ! empty( $request['writable'] ) && '' !== $label ) {
            return sprintf(
                /* translators: %s: widget name (e.g. "Donate Button"). */
                __( 'I did not use the campaign colours, because they would repaint the whole page. Ask again and name the colour of the **%s** widget, and I will change only that widget.', 'better-payment' ),
                $label
            );
        }

        if ( '' === $label ) {
            return self::style_notice();
        }

        $properties = [
            'background' => __( 'background', 'better-payment' ),
            'border'     => __( 'border', 'better-payment' ),
            'radius'     => __( 'corner radius', 'better-payment' ),
            'shadow'     => __( 'shadow', 'better-payment' ),
            'spacing'    => __( 'spacing', 'better-payment' ),
            'hover'      => __( 'hover style', 'better-payment' ),
            'colour'     => __( 'colour', 'better-payment' ),
        ];
        $property   = $properties[ $request['property'] ?? '' ] ?? $properties['colour'];

        return sprintf(
            /* translators: 1: style property (e.g. "background"), 2: widget name (e.g. "Campaign Summary"). */
            __( 'The %1$s of the **%2$s** widget cannot be changed by the AI yet. Select that widget and use **Customize → Style** or **Advanced**.', 'better-payment' ),
            $property,
            $label
        ) . "\n\n" . __( 'I can still set the campaign\'s primary colour and page background, which apply to the whole page. Ask for those by name if that is what you want.', 'better-payment' );
    }

    /**
     * Whether the reply claims a finished edit ("I've updated…", "has been
     * changed", "Done"). Only consulted when no operation survived, to catch a
     * model that announced a change without making one.
     */
    private static function claims_change( string $text ): bool {
        if ( '' === trim( $text ) ) {
            return false;
        }
        // Past tense only: "should I change…?" is a question, not a claim.
        $verbs = '(?:updated|changed|added|removed|deleted|rewr(?:ote|itten)|improved|moved|replaced|adjusted|swapped|revised|applied|made|shortened)\\b';
        return (bool) preg_match( "/\\bi\\s*(?:['\x{2019}]ve|have)?\\s+{$verbs}|\\b(?:has|have)\\s+been\\s+{$verbs}|^\\s*done\\b/iu", $text );
    }

    /**
     * Turn a target descriptor into a validator scope.
     *
     * Kept separate so an empty/malformed target degrades to "no scope" — an
     * unscoped edit — rather than to a scope on the empty string, which would
     * match no element and silently discard every operation the model returned.
     *
     * @param array $target
     * @return array{element_id?: string}
     */
    private static function scope_for( array $target ): array {
        $id = isset( $target['id'] ) ? trim( (string) $target['id'] ) : '';
        return '' === $id ? [] : [ 'element_id' => $id ];
    }

    /**
     * Whether the assistant text reads like it intended to make a change but
     * (apparently) narrated it instead of calling a tool. Used to decide whether
     * to retry with a forced tool call.
     */
    private static function narrates_change( string $text ): bool {
        if ( '' === trim( $text ) ) {
            return false;
        }
        // A refusal is not a narrated edit. "Let me know if you want me to set the
        // page colour" used to match below, and the forced retry then made a tool
        // call out of the offer: a model that declined still repainted the page.
        if ( preg_match( "/\\b(?:can(?:no|')t|can\x{2019}t|unable|not able|let me know)\\b/iu", $text ) ) {
            return false;
        }

        // A first-person "I'm about to do it" phrase — NOT a question like
        // "what would you like to change?" (which must not trigger a forced retry).
        $intent = preg_match( "/\b(i\s*(?:will|'ll|am going to|'m going to)|let'?s|let me|here'?s|here is)\b/i", $text );
        // … paired with a change verb.
        $change = preg_match( '/\b(chang|updat|set|add|remov|delet|rewrit|improv|mov|replac|adjust|make it|swap|revis)/i', $text );
        return (bool) $intent && (bool) $change;
    }

    /**
     * Assemble the messages array from prior history + the new user message.
     *
     * @param array  $history
     * @param string $user_message
     * @return array<int, array{role: string, content: string}>
     */
    private static function build_messages( array $history, string $user_message ): array {
        $messages = ConversationManager::sanitize( $history );
        $messages[] = [ 'role' => 'user', 'content' => $user_message ];
        return $messages;
    }
}
