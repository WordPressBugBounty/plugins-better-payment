<?php

namespace Better_Payment\Lite\AI\Operations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Catalog of Builder Operations — the stable contract between the AI layer and
 * the Campaign Builder.
 *
 * The AI never edits HTML, DOM, or React state. It emits a list of typed
 * Operations; each one is validated server-side ({@see OperationValidator}) and
 * then applied client-side by mapping onto an existing reducer action. Because
 * operations map 1:1 onto reducer actions, every future AI feature (block-level
 * buttons, bulk edits, templates) reuses this same layer.
 *
 * Each entry declares a JSON-schema-ish `params` shape so it can be exported as
 * provider tool/function definitions AND validated on return.
 *
 * Extend from another plugin:
 *   add_filter( 'better_payment/ai/operations', function ( $ops ) {
 *       $ops['my_op'] = [ 'summary' => '...', 'params' => [ ... ] ];
 *       return $ops;
 *   } );
 *
 * @see OperationValidator                                     Enforces these shapes.
 * @see \Better_Payment\Lite\AI\Schema\CampaignSchema          Element/meta allowlists.
 */
class OperationRegistry {

    /**
     * Built-in operation definitions.
     *
     * @return array<string, array>
     */
    private static function definitions(): array {
        return [
            'set_layout' => [
                'summary' => 'Replace the entire campaign layout. Use only for initial generation or a full structural rebuild.',
                'params'  => [
                    'layout'  => [ 'type' => 'string', 'required' => true, 'summary' => 'One of the layout presets (e.g. 2-column).' ],
                    'columns' => [ 'type' => 'array', 'required' => true, 'summary' => 'Ordered columns, each with id, label, width and elements.' ],
                ],
            ],
            'insert_block' => [
                'summary' => 'Add a new element to a column.',
                'params'  => [
                    'column_id' => [ 'type' => 'string', 'required' => false, 'summary' => 'Target column id. Defaults to the first column.' ],
                    'type'      => [ 'type' => 'string', 'required' => true, 'summary' => 'A registered element type.' ],
                    'settings'  => [ 'type' => 'object', 'required' => false, 'summary' => 'Element settings (per-type keys).' ],
                    'index'     => [ 'type' => 'integer', 'required' => false, 'summary' => 'Insert position; appended when omitted.' ],
                ],
            ],
            'update_block' => [
                'summary' => 'Change settings on an existing element. Only the provided keys are merged.',
                'params'  => [
                    'element_id' => [ 'type' => 'string', 'required' => true, 'summary' => 'Id of the element to update.' ],
                    'settings'   => [ 'type' => 'object', 'required' => true, 'summary' => 'Partial settings to merge.' ],
                ],
            ],
            'delete_block' => [
                'summary' => 'Remove an element from the campaign.',
                'params'  => [
                    'element_id' => [ 'type' => 'string', 'required' => true ],
                ],
            ],
            'move_block' => [
                'summary' => 'Move an element to another column and/or position.',
                'params'  => [
                    'element_id'   => [ 'type' => 'string', 'required' => true ],
                    'to_column_id' => [ 'type' => 'string', 'required' => true ],
                    'index'        => [ 'type' => 'integer', 'required' => false ],
                ],
            ],
            'update_meta' => [
                'summary' => 'Set a single campaign-level meta value (e.g. bpc_goal_amount).',
                'params'  => [
                    'key'   => [ 'type' => 'string', 'required' => true, 'summary' => 'An allowlisted campaign meta key.' ],
                    'value' => [ 'type' => 'mixed', 'required' => true ],
                ],
            ],
            'set_colors' => [
                'summary' => 'Set the campaign primary and/or background colour (hex).',
                'params'  => [
                    'primary'    => [ 'type' => 'string', 'required' => false, 'summary' => 'Hex colour, e.g. #6b63f6.' ],
                    'background' => [ 'type' => 'string', 'required' => false, 'summary' => 'Hex colour.' ],
                ],
            ],
            'set_donation_amounts' => [
                'summary' => 'Replace the suggested donation amounts.',
                'params'  => [
                    'amounts' => [ 'type' => 'array', 'required' => true, 'summary' => 'List of { amount, description?, is_default? }.' ],
                ],
            ],
            'replace_image' => [
                'summary' => 'Point a photo element at a media-library image.',
                'params'  => [
                    'element_id' => [ 'type' => 'string', 'required' => true ],
                    'src'        => [ 'type' => 'string', 'required' => true, 'summary' => 'Image URL.' ],
                    'src_id'     => [ 'type' => 'integer', 'required' => false, 'summary' => 'Attachment id.' ],
                    'src_sizes'  => [ 'type' => 'object', 'required' => false ],
                    'alt'        => [ 'type' => 'string', 'required' => false ],
                ],
            ],
            'generate_image' => [
                'summary' => 'Generate a new image from a description and place it in the campaign. Target an existing photo element by element_id to replace it, or omit it to insert a new photo (optionally into column_id).',
                'params'  => [
                    'prompt'     => [ 'type' => 'string', 'required' => true, 'summary' => 'A vivid description of the image to generate.' ],
                    'element_id' => [ 'type' => 'string', 'required' => false, 'summary' => 'Existing photo element to replace.' ],
                    'column_id'  => [ 'type' => 'string', 'required' => false, 'summary' => 'Column to insert a new photo into.' ],
                ],
            ],
        ];
    }

    /**
     * All operation definitions after the extension filter.
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        /**
         * Filter the registered AI builder operations.
         *
         * @param array<string, array> $operations
         */
        return apply_filters( 'better_payment/ai/operations', self::definitions() );
    }

    /**
     * A single operation definition, or null.
     *
     * @return array|null
     */
    public static function get( string $name ) {
        $all = self::get_all();
        return $all[ $name ] ?? null;
    }

    public static function exists( string $name ): bool {
        return null !== self::get( $name );
    }

    /**
     * Operation names.
     *
     * @return array<int, string>
     */
    public static function names(): array {
        return array_keys( self::get_all() );
    }

    /**
     * Provider-agnostic tool descriptors, one per operation.
     *
     * Providers translate these into their own tool/function-calling format.
     *
     * @return array<int, array{name: string, description: string, params: array}>
     */
    public static function as_tools(): array {
        $tools = [];
        foreach ( self::get_all() as $name => $def ) {
            $tools[] = [
                'name'        => $name,
                'description' => $def['summary'] ?? '',
                'params'      => $def['params'] ?? [],
            ];
        }
        return $tools;
    }
}
