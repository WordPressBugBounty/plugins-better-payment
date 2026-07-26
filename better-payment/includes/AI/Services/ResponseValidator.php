<?php

namespace Better_Payment\Lite\AI\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Turns a raw provider chat result into a clean list of candidate operations.
 *
 * Primary path: the provider returned structured tool calls. Fallback path: some
 * models describe operations as a JSON block inside the text reply — we recover
 * those too. Everything returned here is still *unvalidated* and must pass
 * {@see \Better_Payment\Lite\AI\Operations\OperationValidator} before use.
 */
class ResponseValidator {

    /**
     * Extract candidate operations (wire shape: [ 'op' => name, ...args ]).
     *
     * @param array $chat_result Normalised provider result (text, tool_calls, ...).
     * @return array<int, array>
     */
    public static function extract_operations( array $chat_result ): array {
        $operations = [];

        foreach ( (array) ( $chat_result['tool_calls'] ?? [] ) as $call ) {
            $name = $call['name'] ?? '';
            if ( '' === $name ) {
                continue;
            }
            $args = is_array( $call['arguments'] ?? null ) ? $call['arguments'] : [];
            $operations[] = array_merge( [ 'op' => $name ], $args );
        }

        // Fallback: recover a JSON operations block from the text reply.
        if ( empty( $operations ) ) {
            $operations = self::recover_from_text( (string) ( $chat_result['text'] ?? '' ) );
        }

        return $operations;
    }

    /**
     * Best-effort recovery of an operations array embedded in free text.
     *
     * Looks for a fenced ```json block or a bare JSON object/array containing an
     * `operations` key or a top-level array of operation objects.
     *
     * @return array<int, array>
     */
    public static function recover_from_text( string $text ): array {
        if ( '' === trim( $text ) ) {
            return [];
        }

        $candidates = [];

        // Fenced code block(s).
        if ( preg_match_all( '/```(?:json)?\s*(.+?)```/s', $text, $matches ) ) {
            foreach ( $matches[1] as $block ) {
                $candidates[] = $block;
            }
        }
        // The whole text as a last resort.
        $candidates[] = $text;

        foreach ( $candidates as $candidate ) {
            $decoded = json_decode( trim( $candidate ), true );
            if ( ! is_array( $decoded ) ) {
                continue;
            }
            if ( isset( $decoded['operations'] ) && is_array( $decoded['operations'] ) ) {
                return array_values( array_filter( $decoded['operations'], 'is_array' ) );
            }
            // A bare list of operation objects.
            if ( self::looks_like_op_list( $decoded ) ) {
                return array_values( array_filter( $decoded, 'is_array' ) );
            }
        }

        return [];
    }

    /**
     * @param array $value
     */
    private static function looks_like_op_list( array $value ): bool {
        if ( empty( $value ) || ! isset( $value[0] ) || ! is_array( $value[0] ) ) {
            return false;
        }
        return isset( $value[0]['op'] ) || isset( $value[0]['operation'] ) || isset( $value[0]['type'] );
    }
}
