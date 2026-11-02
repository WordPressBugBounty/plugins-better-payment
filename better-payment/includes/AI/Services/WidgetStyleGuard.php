<?php

namespace Better_Payment\Lite\AI\Services;

use Better_Payment\Lite\Campaign\Elements\ElementRegistry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Keeps a request to style ONE widget from repainting the whole campaign.
 *
 * The AI has no key for most widget styles, but it does hold two page-wide
 * colours (`set_colors`, `bpc_color_*`). Asked to "make the campaign summary
 * background white", a model reaches for the only background it has, the operation
 * is perfectly valid, and the whole template turns white. The validator cannot
 * catch that: only the user's own words say the target was one widget.
 *
 * So the request is read here and the page-wide operations are dropped.
 * {@see AIService::reconcile_edit_message()} then says what happened. The prompt
 * asks the model not to do this, and it covers languages this list does not; this
 * is the part that holds. It runs on unscoped edit turns only — a scoped turn
 * already cannot write campaign colours ({@see OperationValidator::in_scope()}).
 *
 * When AI style editing arrives, this class and the notices are what it replaces:
 * the request is detected here, so it can route to a style operation instead.
 */
class WidgetStyleGuard {

    /**
     * Everyday names for the free widgets, beside their registered labels (which
     * also cover add-on widgets and a translated site).
     *
     * @var array<string, string> phrase => element type
     */
    const WIDGET_PHRASES = [
        'campaign summary'  => 'campaign_summary',
        'summary'           => 'campaign_summary',
        'stats'             => 'campaign_summary',
        'statistics'        => 'campaign_summary',
        'campaign title'    => 'campaign_title',
        'title'             => 'campaign_title',
        'heading'           => 'campaign_title',
        'headline'          => 'campaign_title',
        'description'       => 'campaign_description',
        'story'             => 'campaign_description',
        'photo'             => 'photo',
        'image'             => 'photo',
        'picture'           => 'photo',
        'progress bar'      => 'progress_bar',
        'progress'          => 'progress_bar',
        'donate button'     => 'donation_form',
        'donation button'   => 'donation_form',
        'button'            => 'donation_form',
        'donate amount'     => 'donate_amount',
        'donation amount'   => 'donate_amount',
        'donation amounts'  => 'donate_amount',
        'amount buttons'    => 'donate_amount',
        'amounts'           => 'donate_amount',
        'organizer'         => 'organizer',
        'organiser'         => 'organizer',
        'social sharing'    => 'social_sharing',
        'share buttons'     => 'social_sharing',
        'social links'      => 'social_links',
        'social icons'      => 'social_links',
    ];

    /** Words for part of a page that name no particular widget. */
    const GENERIC_PARTS = [ 'widget', 'element', 'block', 'section', 'card', 'tile', 'box', 'panel', 'sidebar', 'column' ];

    /**
     * Style words, grouped by the property the refusal names.
     *
     * @var array<string, string> property => regex alternation
     */
    const PROPERTIES = [
        'background' => 'backgrounds?|bg',
        'border'     => 'borders?|outlines?',
        'radius'     => 'radius|rounded|corners?',
        'shadow'     => 'shadows?',
        'spacing'    => 'padding|margins?|spacing',
        'hover'      => 'hover',
        'colour'     => 'colou?rs?|tint|shade|gradient|opacity|transparent|white|black|red|green|blue|yellow|orange|pink|purple|grey|gray|brown|teal|navy|#[0-9a-f]{3,8}',
    ];

    /** What a widget's own AI-writable colour keys can do, by property. */
    const WRITABLE_COLOURS = [
        'donation_form'        => [ 'colour', 'background' ],
        'campaign_title'       => [ 'colour' ],
        'campaign_description' => [ 'colour' ],
    ];

    /** A request that really is about the whole page, not one part of it. */
    const PAGE_WIDE = '(?:page|campaign|site|template|theme|whole|entire|overall)\s+(?:\w+\s+)?(?:backgrounds?|bg|colou?rs?)|(?:backgrounds?|bg|colou?rs?)\s+(?:of|for|on)\s+(?:the\s+|my\s+|this\s+)?(?:\w+\s+)?(?:page|campaign|site|template|theme)';

    /**
     * Campaign meta that paints the page, or could be used to.
     *
     * @var array<int, string>
     */
    const PAGE_META_KEYS = [ 'bpc_color_primary', 'bpc_color_background', 'bpc_css_class' ];

    /**
     * Read the request. Words in quotes are copy, not instructions.
     *
     * @param string $message The user's message.
     * @return array{type: string, label: string, property: string, page_wide: bool, writable: bool, question: bool}|array{} Empty when the message does not ask to style a widget.
     */
    public static function detect( string $message ): array {
        $text = (string) preg_replace( '/"[^"]*"|\x{201C}[^\x{201D}]*\x{201D}|(?<![\p{L}\p{N}])\'[^\']*\'(?![\p{L}\p{N}])/u', ' ', $message );

        $property = self::property_in( $text );
        if ( '' === $property ) {
            return [];
        }

        // Longest first, and each match is cut out, so "campaign summary" cannot
        // leave a "campaign" behind for the page-wide test.
        $widgets = self::widget_names();
        $types   = [];
        foreach ( $widgets as $phrase => $type ) {
            $text = (string) preg_replace_callback(
                self::word_regex( preg_quote( (string) $phrase, '/' ) ),
                static function () use ( &$types, $type ) {
                    $types[] = $type;
                    return ' ';
                },
                $text
            );
        }

        $generic = (bool) preg_match( self::word_regex( implode( '|', self::GENERIC_PARTS ) ), $text );
        if ( empty( $types ) && ! $generic ) {
            return [];
        }

        $type = $types[0] ?? '';

        return [
            'type'      => $type,
            'label'     => self::label_for( $type ),
            'property'  => $property,
            'page_wide' => (bool) preg_match( self::word_regex( self::PAGE_WIDE ), $text ),
            'writable'  => in_array( $property, self::WRITABLE_COLOURS[ $type ] ?? [], true ),
            'question'  => (bool) preg_match( '/^\s*(?:what|why|how|where|which|who|when|does|do|is|are)\b/i', $message ),
        ];
    }

    /**
     * Drop the operations that would paint the whole page for a one-widget request.
     *
     * @param array $operations Validated operations.
     * @param array $request    From {@see self::detect()}.
     * @return array<int, array> Operations to apply, in order.
     */
    public static function without_page_paint( array $operations, array $request ): array {
        if ( empty( $request ) || ! empty( $request['page_wide'] ) ) {
            return $operations;
        }

        return array_values(
            array_filter(
                $operations,
                static function ( $operation ) {
                    $name = is_array( $operation ) ? ( $operation['op'] ?? '' ) : '';
                    if ( 'set_colors' === $name ) {
                        return false;
                    }
                    return ! ( 'update_meta' === $name && in_array( $operation['key'] ?? '', self::PAGE_META_KEYS, true ) );
                }
            )
        );
    }

    /**
     * The style property the text names, or ''. A colour word only qualifies the
     * others ("a red border" is a border), so `colour` is read last.
     */
    private static function property_in( string $text ): string {
        foreach ( self::PROPERTIES as $property => $words ) {
            if ( preg_match( self::word_regex( $words ), $text ) ) {
                return $property;
            }
        }

        return '';
    }

    /**
     * Every name a widget goes by, longest first.
     *
     * @return array<string, string> phrase => element type
     */
    private static function widget_names(): array {
        $names = self::WIDGET_PHRASES;
        foreach ( ElementRegistry::get_all() as $type => $schema ) {
            $label = (string) ( $schema['label'] ?? '' );
            if ( '' !== $label ) {
                $names[ $label ] = (string) $type;
            }
        }

        uksort(
            $names,
            static function ( $a, $b ) {
                return strlen( (string) $b ) - strlen( (string) $a );
            }
        );

        return $names;
    }

    /**
     * The widget's name as the builder shows it, '' when unknown.
     */
    private static function label_for( string $type ): string {
        $schema = '' !== $type ? ElementRegistry::get( $type ) : null;

        return is_array( $schema ) ? (string) ( $schema['label'] ?? '' ) : '';
    }

    /**
     * A case-insensitive, Unicode-aware whole-word pattern.
     */
    private static function word_regex( string $alternation ): string {
        return '/(?<![\p{L}\p{N}_#])(?:' . $alternation . ')(?![\p{L}\p{N}_])/iu';
    }
}
