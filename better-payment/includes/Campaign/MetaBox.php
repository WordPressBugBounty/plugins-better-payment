<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Campaign\Elements\ElementRegistry;

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
            '_bpc_color_background'   => 'sanitize_hex_color',
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

            $value = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line by the per-field callback from meta_fields(); nonce verified above.
            $value = is_callable( $sanitize ) ? call_user_func( $sanitize, $value ) : $value;

            if ( '_bpc_fields_layout' === $key ) {
                $value = self::enforce_pro_entitlement( $post_id, $value );
            }

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

                if ( '_bpc_fields_layout' === $key ) {
                    $value = self::enforce_pro_entitlement( $post_id, $value );
                }

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

    /**
     * Refuse changes to Pro-element settings from a site without Pro.
     *
     * The builder shows a locked Pro element's upgrade banner instead of its
     * controls, but that is a courtesy, not a control — nothing stops a crafted
     * `POST` to the REST route or the classic editor (or an AI turn) from carrying
     * arbitrary `donors_wall` settings. This is the actual enforcement.
     *
     * **Existing values are preserved, never stripped.** The tempting fix is to
     * delete Pro elements (or blank their settings) on save, but a user whose
     * licence lapsed for a week would come back to find their configuration
     * destroyed by an autosave. Instead:
     *
     *   - element exists already → its stored settings are restored verbatim, so
     *     the campaign is byte-identical on the Pro elements and reactivating Pro
     *     brings everything back exactly as it was;
     *   - element is new → its settings are reset to the registered defaults, so a
     *     free user can't hand-craft values for an element they can't configure.
     *     A free install registers only a lock stub
     *     ({@see \Better_Payment\Lite\Campaign\Elements\ProElementStubs}), whose
     *     defaults are empty, so a new Pro element saves with `[]` settings.
     *
     * **"Exists already" is decided in two passes, and the second one is the one
     * that protects the user.** An element id is not a stable identity: an AI
     * whole-page edit (`set_layout`), an undo to a snapshot from before a save, or
     * any client that re-mints ids hands back the SAME FAQ under a NEW id. Matching
     * by id alone made that FAQ "new", saved it as `[]`, and destroyed the
     * configuration this guard exists to keep — permanently, since reactivating Pro
     * has nothing left to restore. So:
     *
     *   1. an incoming Pro element whose id AND type match a stored one takes that
     *      element's settings;
     *   2. every other incoming Pro element takes the settings of the next stored
     *      element of the SAME type that step 1 did not claim, in document order;
     *   3. only when no unclaimed stored element of that type remains is it new.
     *
     * Settings never cross types, and each stored element is claimed at most once,
     * so an extra Pro element beyond what was stored still gets the defaults. The
     * values restored always come from storage, never from the request.
     *
     * Structure (order, position, insertion, deletion) is deliberately NOT
     * guarded — moving or removing a leftover Pro element is legitimate, and the
     * canvas element toolbar's delete action is there for exactly that.
     *
     * Structure (order, position, insertion, deletion) is deliberately NOT
     * guarded — moving or removing a leftover Pro element is legitimate, and the
     * canvas element toolbar's delete action is there for exactly that.
     *
     * No-ops entirely when Pro is active. Entitlement is read live from
     * `better_payment/pro_enabled` on every save, so a previously-saved campaign
     * grants nothing.
     *
     * @param int   $post_id     Campaign post ID.
     * @param mixed $layout_json Sanitized layout JSON about to be stored. Typed
     *                           loosely because the meta_fields() sanitizer is
     *                           filterable — a third party can swap in one that
     *                           returns something else.
     * @return string Layout JSON with Pro-element settings enforced.
     */
    public static function enforce_pro_entitlement( int $post_id, $layout_json ): string {
        if ( ! is_string( $layout_json ) ) {
            return '[]';
        }

        if ( apply_filters( 'better_payment/pro_enabled', false ) ) {
            return $layout_json;
        }

        $incoming = json_decode( $layout_json, true );
        if ( ! is_array( $incoming ) ) {
            return $layout_json;
        }

        $locked = [];
        foreach ( ElementRegistry::get_all() as $type => $schema ) {
            if ( ! empty( $schema['pro'] ) ) {
                $locked[ $type ] = isset( $schema['defaultSettings'] ) && is_array( $schema['defaultSettings'] )
                    ? $schema['defaultSettings']
                    : [];
            }
        }

        if ( empty( $locked ) ) {
            return $layout_json;
        }

        $stored   = json_decode( (string) get_post_meta( $post_id, '_bpc_fields_layout', true ), true );
        $pool     = self::collect_locked_elements( is_array( $stored ) ? $stored : [], $locked );
        $claims   = self::collect_locked_elements( $incoming, $locked );
        $resolved = self::resolve_locked_settings( $claims, $pool, $locked );

        $ordinal = 0;
        $guarded = self::restore_locked_settings( $incoming, $locked, $resolved, $ordinal );

        $encoded = wp_json_encode( $guarded );

        return is_string( $encoded ) ? $encoded : $layout_json;
    }

    /**
     * Every locked (Pro) element in a layout, in document order.
     *
     * Walks recursively rather than assuming `columns[].elements[]` so the legacy
     * flat-array layout format is covered too. It stops at a locked element, and
     * {@see self::restore_locked_settings()} walks identically, so the Nth entry
     * here is the Nth element that walk rewrites.
     *
     * @param array                $node
     * @param array<string, array> $locked Type => default settings.
     * @param array                $acc
     * @return array<int, array{id: string, type: string, settings: array}>
     */
    private static function collect_locked_elements( array $node, array $locked, array $acc = [] ): array {
        if ( isset( $node['type'] ) && is_string( $node['type'] ) && isset( $locked[ $node['type'] ] ) ) {
            $acc[] = [
                'id'       => isset( $node['id'] ) && is_scalar( $node['id'] ) ? (string) $node['id'] : '',
                'type'     => $node['type'],
                'settings' => isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : [],
            ];

            return $acc;
        }

        foreach ( $node as $child ) {
            if ( is_array( $child ) ) {
                $acc = self::collect_locked_elements( $child, $locked, $acc );
            }
        }

        return $acc;
    }

    /**
     * Decide the settings each incoming locked element is saved with.
     *
     * Pass 1 claims stored elements by id AND type. Pass 2 gives each still
     * unresolved incoming element the next unclaimed stored element of the same
     * type, in document order — the same element under a re-minted id (an AI
     * `set_layout`, an undo, any client that regenerates ids). Whatever is left
     * over is genuinely new and gets the registered defaults. A stored element is
     * claimed at most once and never by another type.
     *
     * @param array<int, array{id: string, type: string, settings: array}> $incoming Locked elements being saved.
     * @param array<int, array{id: string, type: string, settings: array}> $stored   Locked elements already stored.
     * @param array<string, array>                                         $locked   Type => default settings.
     * @return array<int, array> Incoming ordinal => settings to save.
     */
    private static function resolve_locked_settings( array $incoming, array $stored, array $locked ): array {
        $resolved = [];
        $claimed  = [];

        foreach ( $incoming as $i => $element ) {
            if ( '' === $element['id'] ) {
                continue;
            }
            foreach ( $stored as $j => $candidate ) {
                if ( ! isset( $claimed[ $j ] ) && $candidate['id'] === $element['id'] && $candidate['type'] === $element['type'] ) {
                    $resolved[ $i ] = $candidate['settings'];
                    $claimed[ $j ]  = true;
                    break;
                }
            }
        }

        foreach ( $incoming as $i => $element ) {
            if ( array_key_exists( $i, $resolved ) ) {
                continue;
            }

            $resolved[ $i ] = $locked[ $element['type'] ];

            foreach ( $stored as $j => $candidate ) {
                if ( ! isset( $claimed[ $j ] ) && $candidate['type'] === $element['type'] ) {
                    $resolved[ $i ] = $candidate['settings'];
                    $claimed[ $j ]  = true;
                    break;
                }
            }
        }

        return $resolved;
    }

    /**
     * Replace the settings of every locked element with the ones resolved for it.
     * Walks exactly like {@see self::collect_locked_elements()}; `$ordinal` counts
     * the locked elements seen so far.
     *
     * @param array                $node
     * @param array<string, array> $locked   Type => default settings.
     * @param array<int, array>    $resolved Ordinal => settings.
     * @param int                  $ordinal  Running locked-element counter.
     * @return array
     */
    private static function restore_locked_settings( array $node, array $locked, array $resolved, int &$ordinal ): array {
        if ( isset( $node['type'] ) && is_string( $node['type'] ) && isset( $locked[ $node['type'] ] ) ) {
            $node['settings'] = $resolved[ $ordinal ] ?? $locked[ $node['type'] ];
            ++$ordinal;

            return $node;
        }

        foreach ( $node as $key => $child ) {
            if ( is_array( $child ) ) {
                $node[ $key ] = self::restore_locked_settings( $child, $locked, $resolved, $ordinal );
            }
        }

        return $node;
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
