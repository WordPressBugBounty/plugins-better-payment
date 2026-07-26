<?php

namespace Better_Payment\Lite\AI\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Campaign analysis / critique.
 *
 * Runs {@see AIService} in 'analyze' mode. The model returns a natural-language
 * report plus optional one-click-fix operations, which the panel holds as
 * *suggestions* — nothing here is applied until the user accepts it.
 *
 * Two guarantees this class adds on top of the raw turn, because the prompt asks
 * for both but a prompt is not an enforcement layer:
 *
 * - **The review stays inside the campaign.** Operations that would introduce a
 *   widget the campaign does not have (`insert_block`, `set_layout`) or that
 *   target an element id not in the current layout are dropped. Reviewing a page
 *   is not an opportunity to upsell widgets the owner never chose, and an Apply
 *   button that adds a Donors Wall to a campaign with no Donors Wall fixes
 *   nothing the user asked about.
 * - **The report reads as a document.** Fenced code blocks and a reply that is
 *   nothing but a JSON dump are stripped from the prose; the model is told not to
 *   emit them, and this catches the turn where it does anyway.
 */
class CampaignAnalyzer {

    /**
     * Operations that can only ever introduce new structure. Analyze critiques
     * what exists, so none of them is ever in scope for this mode.
     *
     * @var array<int, string>
     */
    private static $structural_ops = [ 'insert_block', 'set_layout' ];

    /**
     * @param array $context [ 'layout' => [...], 'meta' => [...] ]
     * @return array|\WP_Error { assistant_message, operations, usage }
     */
    public static function analyze( array $context = [] ) {
        $instruction = __( 'Analyze this campaign and suggest concrete, high-impact improvements.', 'better-payment' );

        $result = AIService::run( 'analyze', $instruction, $context );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $result['operations']        = self::scope_to_campaign( (array) ( $result['operations'] ?? [] ), $context );
        $result['assistant_message'] = self::clean_report( (string) ( $result['assistant_message'] ?? '' ) );

        return $result;
    }

    /**
     * Drop suggestions that are not about a widget this campaign contains.
     *
     * @param array $operations Validated operations from the turn.
     * @param array $context    [ 'layout' => [...], 'meta' => [...] ]
     * @return array<int, array>
     */
    private static function scope_to_campaign( array $operations, array $context ): array {
        $known = self::element_ids( $context );

        $kept = [];
        foreach ( $operations as $operation ) {
            if ( ! is_array( $operation ) ) {
                continue;
            }

            $name = isset( $operation['op'] ) ? (string) $operation['op'] : '';

            if ( in_array( $name, self::$structural_ops, true ) ) {
                continue;
            }

            // Anything naming an element must name one that is actually there.
            if ( isset( $operation['element_id'] ) && ! in_array( (string) $operation['element_id'], $known, true ) ) {
                continue;
            }

            $kept[] = $operation;
        }

        /**
         * Filter the operations an analysis turn is allowed to suggest.
         *
         * @param array $kept       Operations surviving the in-campaign scope check.
         * @param array $operations The full set the model returned.
         * @param array $context    Current campaign state.
         */
        return apply_filters( 'better_payment/ai/analysis_operations', $kept, $operations, $context );
    }

    /**
     * Every element id present in the campaign right now.
     *
     * @param array $context
     * @return array<int, string>
     */
    private static function element_ids( array $context ): array {
        $ids     = [];
        $layout  = is_array( $context['layout'] ?? null ) ? $context['layout'] : [];
        $columns = (array) ( $layout['columns'] ?? [] );

        foreach ( $columns as $column ) {
            if ( ! is_array( $column ) ) {
                continue;
            }
            foreach ( (array) ( $column['elements'] ?? [] ) as $element ) {
                if ( is_array( $element ) && ! empty( $element['id'] ) ) {
                    $ids[] = (string) $element['id'];
                }
            }
        }

        return $ids;
    }

    /**
     * Strip machine output from a report meant for a human.
     *
     * Fenced blocks and a reply that is *entirely* a JSON object are the two
     * shapes that have turned up in place of prose. A brace mid-sentence is left
     * alone — this removes structures, it does not rewrite writing.
     *
     * @param string $text
     * @return string
     */
    private static function clean_report( string $text ): string {
        // Fenced code blocks, including an unterminated trailing one.
        $stripped = preg_replace( '/```[a-z]*\s*\n.*?(?:```|\z)/is', '', $text );
        $trimmed  = trim( is_string( $stripped ) ? $stripped : $text );

        // A reply that is nothing but a JSON/array dump carries no report at all.
        if ( '' !== $trimmed && preg_match( '/^[\{\[].*[\}\]]$/s', $trimmed ) ) {
            return __( 'I reviewed the campaign. Apply any suggestion below to make the change.', 'better-payment' );
        }

        // Collapse the blank lines the stripped fences left behind.
        $collapsed = preg_replace( "/\n{3,}/", "\n\n", $trimmed );

        return is_string( $collapsed ) ? trim( $collapsed ) : $trimmed;
    }
}
