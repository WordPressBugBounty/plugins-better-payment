<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Controller;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles saving and reading _bpc_* post meta for bp_campaign posts.
 * The builder save path goes through the REST API (CampaignAPI).
 * This class handles the rare case of direct post saves (e.g., autosave, bulk actions).
 */
class MetaBox extends Controller {

    /**
     * Meta fields and their sanitize callbacks.
     */
    private static function meta_fields(): array {
        return apply_filters( 'better_payment/campaign/meta_fields', [
            '_bpc_goal_amount'       => 'floatval',
            '_bpc_end_date'          => 'sanitize_text_field',
            '_bpc_suggested_amounts' => [ self::class, 'sanitize_amounts_array' ],
            '_bpc_allow_custom_amount' => 'absint',
            '_bpc_minimum_amount'    => 'floatval',
            '_bpc_form_page_id'      => 'absint',
            '_bpc_fields_layout'     => [ self::class, 'sanitize_json' ],
            '_bpc_status'            => 'sanitize_text_field',
            '_bpc_color_primary'     => 'sanitize_hex_color',
            '_bpc_color_button'      => 'sanitize_hex_color',
            '_bpc_css_class'         => 'sanitize_html_class',
            '_bpc_template_key'      => 'sanitize_text_field',
        ] );
    }

    /**
     * Save post meta on save_post_bp_campaign.
     */
    public function save( int $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Only handle REST saves that explicitly post _bpc nonce; direct post saves are rare.
        if ( ! isset( $_POST['bpc_meta_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bpc_meta_nonce'] ) ), 'bpc_save_meta_' . $post_id ) ) {
            return;
        }

        foreach ( self::meta_fields() as $key => $sanitize ) {
            if ( ! isset( $_POST[ $key ] ) ) {
                continue;
            }

            $value = wp_unslash( $_POST[ $key ] );
            $value = is_callable( $sanitize ) ? call_user_func( $sanitize, $value ) : $value;
            update_post_meta( $post_id, $key, $value );
        }
    }

    /**
     * Save meta directly from an array (used by CampaignAPI).
     */
    public static function save_from_array( int $post_id, array $data ) {
        foreach ( self::meta_fields() as $key => $sanitize ) {
            $short_key = ltrim( $key, '_' );

            if ( array_key_exists( $short_key, $data ) ) {
                $raw   = $data[ $short_key ];
                $value = is_callable( $sanitize ) ? call_user_func( $sanitize, $raw ) : sanitize_text_field( $raw );
                // wp_unslash() is applied inside update_metadata, which strips backslashes from strings.
                // wp_slash() pre-escapes so the round-trip leaves the value intact (standard WP REST API pattern).
                if ( is_string( $value ) ) {
                    $value = wp_slash( $value );
                }
                update_post_meta( $post_id, $key, $value );
            }
        }
    }

    /**
     * Read all meta for a campaign post as a plain array.
     */
    public static function get_all( int $post_id ): array {
        $result = [];
        foreach ( array_keys( self::meta_fields() ) as $key ) {
            $short           = ltrim( $key, '_' );
            $result[ $short ] = get_post_meta( $post_id, $key, true );
        }

        // Cast integer/float meta so JS receives 0/1 (not "0"/"1" strings) —
        // get_post_meta always returns strings; "0" is truthy in JS which breaks boolean checks.
        $result['bpc_allow_custom_amount'] = (int) ( $result['bpc_allow_custom_amount'] ?? 1 );
        $result['bpc_goal_amount']         = (float) ( $result['bpc_goal_amount'] ?? 0 );
        $result['bpc_minimum_amount']      = '' !== ( $result['bpc_minimum_amount'] ?? '' )
            ? (float) $result['bpc_minimum_amount']
            : '';
        $result['bpc_form_page_id']        = (int) ( $result['bpc_form_page_id'] ?? 0 );
        $result['bpc_css_class']           = (string) ( $result['bpc_css_class'] ?? '' );

        // Parse JSON fields.
        if ( ! empty( $result['bpc_fields_layout'] ) && is_string( $result['bpc_fields_layout'] ) ) {
            $decoded = json_decode( $result['bpc_fields_layout'], true );
            $result['bpc_fields_layout'] = is_array( $decoded ) ? $decoded : [];
        }

        // Parse suggested amounts — stored as JSON array of {id,amount,description,is_default}.
        $sa = $result['bpc_suggested_amounts'] ?? '';
        if ( is_string( $sa ) && '' !== $sa ) {
            $decoded = json_decode( $sa, true );
            if ( is_array( $decoded ) ) {
                $result['bpc_suggested_amounts'] = $decoded;
            } else {
                // Legacy comma-separated — migrate on read.
                $parts = array_filter( array_map( 'trim', explode( ',', $sa ) ) );
                $result['bpc_suggested_amounts'] = array_values( array_map(
                    function ( $amount, $i ) {
                        return [
                            'id'          => 'sa_' . ( $i + 1 ),
                            'amount'      => (string) floatval( $amount ),
                            'description' => '',
                            'is_default'  => false,
                        ];
                    },
                    $parts,
                    array_keys( $parts )
                ) );
            }
        } else {
            $result['bpc_suggested_amounts'] = [];
        }

        return $result;
    }

    // ------------------------------------------------------------------ helpers

    public static function sanitize_amounts_array( $value ): string {
        if ( is_array( $value ) ) {
            // PHP array from REST API JSON body — sanitize each item and re-encode.
            $clean = array_values( array_map( function ( $item ) {
                return [
                    'id'          => sanitize_text_field( $item['id'] ?? '' ),
                    'amount'      => (string) floatval( $item['amount'] ?? 0 ),
                    'description' => sanitize_text_field( $item['description'] ?? '' ),
                    'is_default'  => ! empty( $item['is_default'] ),
                ];
            }, $value ) );
            return wp_json_encode( $clean );
        }
        if ( is_string( $value ) ) {
            $decoded = json_decode( $value, true );
            if ( is_array( $decoded ) ) {
                return self::sanitize_amounts_array( $decoded );
            }
            // Legacy comma-separated — migrate.
            $parts = array_filter( array_map( 'trim', explode( ',', $value ) ) );
            if ( ! empty( $parts ) ) {
                $items = array_values( array_map( function ( $a, $i ) {
                    return [
                        'id'          => 'sa_' . ( $i + 1 ),
                        'amount'      => (string) floatval( $a ),
                        'description' => '',
                        'is_default'  => false,
                    ];
                }, $parts, array_keys( $parts ) ) );
                return wp_json_encode( $items );
            }
            return '[]';
        }
        return '[]';
    }

    public static function sanitize_json( $value ): string {
        if ( is_array( $value ) ) {
            return wp_json_encode( $value );
        }
        if ( is_string( $value ) ) {
            $decoded = json_decode( $value, true );
            return is_array( $decoded ) ? wp_json_encode( $decoded ) : '[]';
        }
        return '[]';
    }
}
