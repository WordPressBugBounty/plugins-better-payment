<?php

namespace Better_Payment\Lite\AI\Operations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Folds a validated operation batch into the campaign state it describes.
 *
 * Extracted verbatim from {@see \Better_Payment\Lite\AI\Services\CampaignGenerator::attempt()},
 * which is still its only caller inside the AI flow. It moved out so the MCP module can
 * apply the same operation contract without a second implementation: two copies of "what
 * does set_layout mean" would drift, and the drift would be invisible until a campaign
 * saved through one path came out different from the same batch saved through the other.
 *
 * Only whole-document operations are folded here — `set_layout`, `update_meta`,
 * `set_colors`, `set_donation_amounts`. Block-level operations (`insert_block`,
 * `update_block`, `remove_block`) are applied by the builder's reducer against live
 * client state and have no server-side equivalent.
 *
 * @since 2.5.0
 */
class StateDeriver {

    /**
     * Derive `{ operations, layout, meta }` from a validated operation batch.
     *
     * @param array $operations Validated operations.
     * @return array{operations: array, layout: array|null, meta: array}
     */
    public static function derive( array $operations ): array {
        $layout = null;
        $meta   = [];

        foreach ( $operations as $operation ) {
            $op = $operation['op'] ?? '';
            if ( 'set_layout' === $op ) {
                $layout = [ 'layout' => $operation['layout'], 'columns' => $operation['columns'] ];
            } elseif ( 'update_meta' === $op ) {
                $meta[ $operation['key'] ] = $operation['value'];
            } elseif ( 'set_colors' === $op ) {
                if ( isset( $operation['primary'] ) ) {
                    $meta['bpc_color_primary'] = $operation['primary'];
                }
                if ( isset( $operation['background'] ) ) {
                    $meta['bpc_color_background'] = $operation['background'];
                }
            } elseif ( 'set_donation_amounts' === $op ) {
                $meta['bpc_suggested_amounts'] = $operation['amounts'];
            }
        }

        // An all-empty layout must not wipe the canvas — drop the set_layout op.
        if ( null !== $layout ) {
            $total = 0;
            foreach ( $layout['columns'] as $col ) {
                $total += is_array( $col['elements'] ?? null ) ? count( $col['elements'] ) : 0;
            }
            if ( 0 === $total ) {
                $layout     = null;
                $operations = array_values( array_filter(
                    $operations,
                    static function ( $op ) {
                        return is_array( $op ) && 'set_layout' !== ( $op['op'] ?? '' );
                    }
                ) );
            }
        }

        return [
            'operations' => $operations,
            'layout'     => $layout,
            'meta'       => $meta,
        ];
    }
}
