<?php

namespace Better_Payment\Lite\AI\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Conversational editing of an existing campaign.
 *
 * Thin wrapper over {@see AIService} in 'edit' mode. Kept as its own service so
 * block-level and bulk-edit features can extend edit behaviour without touching
 * the generic orchestrator.
 *
 * Note: unlike generation, this path deliberately does NOT run
 * {@see CampaignGenerator::fill_default_photos()}. The campaign being edited
 * already holds the user's real, uploaded images, and a `set_layout` that echoes
 * them back would have every one of them overwritten with stock artwork. A
 * hallucinated `src` on an `insert_block` therefore still reaches the canvas
 * here — a narrower guard (fill only photos with no attachment id) is the fix,
 * not this one.
 */
class CampaignEditor {

    /**
     * @param string $message
     * @param array  $context           [ 'layout' => [...], 'meta' => [...] ]
     * @param array  $history
     * @param string $target_element_id Optional. When the turn came from the
     *                                  builder's per-element AI buttons, the id of
     *                                  the selected widget.
     * @return array|\WP_Error { assistant_message, operations, usage }
     */
    public static function edit( string $message, array $context = [], array $history = [], string $target_element_id = '' ) {
        $target = self::resolve_target( $context, $target_element_id );

        return AIService::run( 'edit', $message, $context, $history, [], $target );
    }

    /**
     * Look the target element up in the trusted context layout.
     *
     * The client sends only an **id**. Type and current settings are read back out
     * of the layout the request already carries, so the widget the prompt describes
     * is by construction the widget that is on the page — a client that sent its
     * own `type` could otherwise have the model briefed on a Donors Wall while the
     * id pointed at a headline, and the resulting `update_block` would write
     * donors-wall keys onto it.
     *
     * An id that matches nothing returns `[]`, which degrades to a normal unscoped
     * edit rather than a scope that matches no element and drops every operation.
     *
     * @param array  $context
     * @param string $element_id
     * @return array{id?: string, type?: string, settings?: array}
     */
    private static function resolve_target( array $context, string $element_id ): array {
        $element_id = trim( $element_id );
        if ( '' === $element_id ) {
            return [];
        }

        $layout = isset( $context['layout'] ) && is_array( $context['layout'] ) ? $context['layout'] : [];

        foreach ( (array) ( $layout['columns'] ?? [] ) as $column ) {
            if ( ! is_array( $column ) ) {
                continue;
            }
            foreach ( (array) ( $column['elements'] ?? [] ) as $element ) {
                if ( ! is_array( $element ) ) {
                    continue;
                }
                if ( (string) ( $element['id'] ?? '' ) !== $element_id ) {
                    continue;
                }
                return [
                    'id'       => $element_id,
                    'type'     => (string) ( $element['type'] ?? '' ),
                    'settings' => isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [],
                ];
            }
        }

        return [];
    }
}
