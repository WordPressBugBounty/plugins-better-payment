<?php

namespace Better_Payment\Lite\AI\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Per-campaign AI conversation history.
 *
 * Stored as JSON in the `_bpc_ai_history` post meta on the campaign, capped to
 * the most recent turns so prompts stay small and meta stays bounded. For a
 * brand-new (unsaved) campaign, history lives only in the client until first
 * save; the server simply receives it in the request.
 */
class ConversationManager {

    const META_KEY  = '_bpc_ai_history';
    const MAX_TURNS = 40;

    /**
     * Load stored history for a campaign.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public static function get( int $campaign_id ): array {
        if ( $campaign_id <= 0 ) {
            return [];
        }
        $raw = get_post_meta( $campaign_id, self::META_KEY, true );
        if ( is_string( $raw ) && '' !== $raw ) {
            $decoded = json_decode( $raw, true );
            if ( is_array( $decoded ) ) {
                return self::sanitize( $decoded );
            }
        }
        return [];
    }

    /**
     * Append a user + assistant turn and persist (capped).
     *
     * @param int    $campaign_id
     * @param string $user_message
     * @param string $assistant_message
     * @return array<int, array> The full, capped history after appending.
     */
    public static function append( int $campaign_id, string $user_message, string $assistant_message ): array {
        $history   = self::get( $campaign_id );
        $history[] = [ 'role' => 'user', 'content' => $user_message ];
        $history[] = [ 'role' => 'assistant', 'content' => $assistant_message ];
        $history   = array_slice( $history, - self::MAX_TURNS );

        if ( $campaign_id > 0 ) {
            update_post_meta( $campaign_id, self::META_KEY, wp_slash( wp_json_encode( $history ) ) );
        }
        return $history;
    }

    /**
     * Replace the whole stored history with the given messages (sanitized +
     * capped). The client owns the authoritative message list, so this is how it
     * syncs the conversation to the campaign — in particular the first generate
     * turn, which is produced while the campaign is still unsaved and therefore
     * cannot be appended during the /ai/generate request.
     *
     * @param int   $campaign_id
     * @param array $messages Raw [ [ role, content ], ... ] from the client.
     * @return array<int, array{role: string, content: string}> The stored history.
     */
    public static function replace( int $campaign_id, array $messages ): array {
        $history = self::sanitize( $messages );
        if ( $campaign_id <= 0 ) {
            return $history;
        }
        if ( empty( $history ) ) {
            delete_post_meta( $campaign_id, self::META_KEY );
            return [];
        }
        update_post_meta( $campaign_id, self::META_KEY, wp_slash( wp_json_encode( $history ) ) );
        return $history;
    }

    /**
     * Clear stored history for a campaign.
     */
    public static function clear( int $campaign_id ): bool {
        if ( $campaign_id <= 0 ) {
            return false;
        }
        return delete_post_meta( $campaign_id, self::META_KEY );
    }

    /**
     * Normalise a history array to role/content pairs with safe strings.
     *
     * @param array $history
     * @return array<int, array{role: string, content: string}>
     */
    public static function sanitize( array $history ): array {
        $clean = [];
        foreach ( $history as $turn ) {
            if ( ! is_array( $turn ) ) {
                continue;
            }
            $role = ( 'assistant' === ( $turn['role'] ?? '' ) ) ? 'assistant' : 'user';
            $clean[] = [
                'role'    => $role,
                'content' => sanitize_textarea_field( (string) ( $turn['content'] ?? '' ) ),
            ];
        }
        return array_slice( $clean, - self::MAX_TURNS );
    }
}
