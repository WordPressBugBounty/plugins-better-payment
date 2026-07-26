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

        $system   = PromptBuilder::system_prompt( $mode, AIManager::system_prompt(), $target );
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
        $operations = $text_only ? [] : self::extract_operations( $result, $context, $scope );

        // Robustness: sometimes the model *describes* the change ("I will update the
        // button label…") but never calls the tool, so nothing is applied. When we
        // got no operations but the reply reads like an intended edit, retry once
        // forcing a tool call so the change actually happens.
        //
        // Never in 'analyze' mode. A critique that has no one-click fix to offer is
        // a perfectly good critique; forcing a tool call there manufactures an edit
        // the user never asked for out of a sentence like "the story could be
        // stronger", and hands them an Apply button for it. Never in a text-only
        // mode either — there is no operation it could be trying to make.
        if ( ! $text_only && 'analyze' !== $mode && empty( $operations ) && self::narrates_change( (string) ( $result['text'] ?? '' ) ) ) {
            $forced = $provider->chat( $messages, array_merge( $chat_options, [ 'tool_choice' => 'required' ] ) );
            if ( empty( $forced['error'] ) ) {
                $forced_ops = self::extract_operations( $forced, $context, $scope );
                if ( ! empty( $forced_ops ) ) {
                    $result     = $forced;
                    $operations = $forced_ops;
                }
            }
        }

        $assistant_message = trim( (string) ( $result['text'] ?? '' ) );
        if ( '' === $assistant_message ) {
            $assistant_message = self::fallback_message( $mode, $operations );
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
     * Extract + validate operations from a provider chat result.
     *
     * @param array $result
     * @param array $context
     * @param array $scope Optional [ 'element_id' => string ] restriction.
     * @return array<int, array>
     */
    private static function extract_operations( array $result, array $context, array $scope = [] ): array {
        $candidates = ResponseValidator::extract_operations( $result );
        return OperationValidator::validate_batch( $candidates, $context, $scope );
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
