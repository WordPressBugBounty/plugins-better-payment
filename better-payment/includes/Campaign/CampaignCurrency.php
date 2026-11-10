<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Helper;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The currency a Campaign Builder campaign takes donations in.
 *
 * A campaign has its own currency, stored in `_bpc_currency`. It is stamped from the
 * site's general currency (Settings → General → Currency) when the campaign is created,
 * and a later change to that setting does not change it. Before 2.5.0 every campaign read
 * the setting live, so changing it re-labelled every existing goal in the new currency and
 * moved every amount already raised into the "+ other currencies" line: a campaign that had
 * raised $350 of a $1,000 goal showed "€0 + $350" and 0%.
 *
 * Three rules:
 *
 *   - **Campaigns created before 2.5.0 are not migrated.** They carry no `_bpc_currency`,
 *     and of() reads the site currency for them, as before. Nothing rewrites their data.
 *   - **The stamp is written once, on insert** (`wp_insert_post` with `$update` false), so
 *     every way a campaign is created gets it: the builder's REST route, AI generation and
 *     the MCP `campaign_create` tool all call wp_insert_post().
 *   - **The currency can be changed only while the campaign has no approved donation**
 *     (is_locked()). After that, the stored totals and the goal are in it, and changing it
 *     would split them. MetaBox enforces this on every save path (enforce_lock()); the
 *     builder's locked control is only the visible half.
 *
 * Every place that charges, sums or prints a campaign amount reads of() or resolve():
 * PaymentRequestGuard (what a donation is charged in), CampaignStats (which currency
 * `total_raised` is), the renderer, the campaign list, the REST API, the builder and the AI
 * prompt. Pro's popup reads it through of() too.
 *
 * @since 2.5.0
 */
class CampaignCurrency {

    /** Post meta key. */
    const META_KEY = '_bpc_currency';

    /**
     * Register the creation stamp.
     *
     * @return void
     */
    public static function register_hooks(): void {
        add_action( 'wp_insert_post', [ self::class, 'stamp_on_insert' ], 10, 3 );
    }

    /**
     * Stamp a new campaign with the site currency.
     *
     * Runs before CampaignAPI / the MCP tool save the request's meta, so a currency the
     * builder sent with the first save (the owner picked one before saving) replaces the
     * stamp through MetaBox::save_from_array() as usual.
     *
     * @param int     $post_id Post id.
     * @param WP_Post $post    Post object (unused: the type is read from the id).
     * @param bool    $update  Whether this is an update of an existing post.
     * @return void
     */
    public static function stamp_on_insert( $post_id, $post, $update ): void {
        if ( $update || 'bp_campaign' !== get_post_type( (int) $post_id ) ) {
            return;
        }

        // Revisions and autosaves are separate posts of another type; this guards anything
        // else that inserts a campaign with its meta already set (an importer).
        if ( '' !== self::stored( (int) $post_id ) ) {
            return;
        }

        update_post_meta( (int) $post_id, self::META_KEY, self::site() );
    }

    /**
     * The site's general currency, upper case; USD when unset.
     *
     * @return string
     */
    public static function site(): string {
        $code = DB::get_settings( 'better_payment_settings_general_general_currency' );

        return self::sanitize( is_string( $code ) ? $code : '' ) ?: 'USD';
    }

    /**
     * The currency stored on a campaign, or '' when it has none (a campaign created before 2.5.0).
     *
     * @param int $campaign_id Campaign id.
     * @return string
     */
    public static function stored( int $campaign_id ): string {
        if ( $campaign_id <= 0 ) {
            return '';
        }

        $code = get_post_meta( $campaign_id, self::META_KEY, true );

        return self::sanitize( is_string( $code ) ? $code : '' );
    }

    /**
     * The currency a campaign's goal, totals and donations are in.
     *
     * @param int $campaign_id Campaign id; 0 (an unsaved campaign) reads the site currency.
     * @return string ISO code.
     */
    public static function of( int $campaign_id ): string {
        return self::resolve( self::stored( $campaign_id ) );
    }

    /**
     * A stored or submitted code, or the site currency when it is empty or not a currency.
     *
     * The renderer and the preview pass the meta they already hold (`bpc_currency`), which
     * for an unsaved builder state may differ from what is stored.
     *
     * @param mixed $code Candidate code.
     * @return string ISO code.
     */
    public static function resolve( $code ): string {
        $code = self::sanitize( is_string( $code ) ? $code : '' );

        return '' !== $code ? $code : self::site();
    }

    /**
     * Normalise a currency code: one of the codes Better Payment offers, upper case, or ''.
     *
     * The list is Settings → General's (Traits\Helper::get_currency_list()), so a campaign
     * can only hold a currency the site could be set to.
     *
     * @param mixed $code Candidate code.
     * @return string
     */
    public static function sanitize( $code ): string {
        if ( ! is_string( $code ) ) {
            return '';
        }

        $code = strtoupper( trim( $code ) );

        return isset( self::currencies()[ $code ] ) ? $code : '';
    }

    /**
     * Currencies a campaign may use: code => name.
     *
     * @return array<string, string>
     */
    public static function currencies(): array {
        static $list = null;

        if ( null === $list ) {
            $list = ( new Helper() )->get_currency_names_list();
        }

        return $list;
    }

    /**
     * Whether a campaign's currency can no longer change: it has an approved donation.
     *
     * Read uncached: a stale "no donations" here would let a save re-label money that has
     * already been raised.
     *
     * @param int $campaign_id Campaign id.
     * @return bool
     */
    public static function is_locked( int $campaign_id ): bool {
        global $wpdb;

        if ( $campaign_id <= 0 ) {
            return false;
        }

        $table    = $wpdb->prefix . 'better_payment';
        $approved = Helper::approved_donation_sql();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; $approved is Helper::approved_donation_sql(), a constant fragment.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table, no WP API; must be uncached (see above).
        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM `{$table}` WHERE campaign_id = %s AND {$approved} LIMIT 1",
                (string) $campaign_id
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return null !== $found;
    }

    /**
     * The value a save may store for `_bpc_currency`, or null to leave the meta untouched.
     *
     * - An empty or unknown code changes nothing: an old campaign keeps following the site
     *   currency, and the builder sends '' for it on every save.
     * - A locked campaign keeps the currency it has (for an old campaign, none: it goes on
     *   following the site currency), whatever the request says.
     *
     * @param int   $campaign_id Campaign id.
     * @param mixed $value       Submitted value.
     * @return string|null
     */
    public static function enforce_lock( int $campaign_id, $value ): ?string {
        $code = self::sanitize( $value );

        if ( '' === $code || $code === self::stored( $campaign_id ) ) {
            return null;
        }

        return self::is_locked( $campaign_id ) ? null : $code;
    }

    /**
     * Which currencies the gateways with a currency rule take, and whether each is enabled.
     *
     * Stripe takes every currency Better Payment offers; PayPal and Paystack do not
     * (Traits\Helper::bp_unsupported_currencies() / bp_supported_currencies(), the same
     * lists the payment widgets use). The builder reads this for its Settings notice.
     *
     * @param array|null $settings Plugin settings; null reads them.
     * @return array{paypal: array{enabled: bool, unsupported: string[]}, paystack: array{enabled: bool, supported: string[]}}
     */
    public static function gateway_currencies( ?array $settings = null ): array {
        $settings = null === $settings ? (array) DB::get_settings() : $settings;
        $helper   = new Helper();
        $enabled  = static function ( string $key ) use ( $settings ): bool {
            return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
        };

        return [
            'paypal'   => [
                'enabled'     => $enabled( 'better_payment_settings_general_general_paypal' ),
                'unsupported' => array_values( $helper->bp_unsupported_currencies( 'paypal' ) ),
            ],
            'paystack' => [
                'enabled'   => $enabled( 'better_payment_settings_general_general_paystack' ),
                'supported' => array_values( $helper->bp_supported_currencies( 'paystack' ) ),
            ],
        ];
    }

    /**
     * Enabled payment gateways that cannot charge in a currency.
     *
     * @param string     $code     ISO code.
     * @param array|null $settings Plugin settings; null reads them.
     * @return string[] Gateway slugs (`paypal`, `paystack`).
     */
    public static function unsupported_gateways( string $code, ?array $settings = null ): array {
        $code     = strtoupper( trim( $code ) );
        $gateways = self::gateway_currencies( $settings );

        $unsupported = [];

        if ( $gateways['paypal']['enabled'] && in_array( $code, $gateways['paypal']['unsupported'], true ) ) {
            $unsupported[] = 'paypal';
        }

        if ( $gateways['paystack']['enabled'] && ! in_array( $code, $gateways['paystack']['supported'], true ) ) {
            $unsupported[] = 'paystack';
        }

        return $unsupported;
    }

    /**
     * The builder's view of a campaign's currency.
     *
     * @param int $campaign_id Campaign id (0 for a new one).
     * @return array{currency: string, stored: bool, locked: bool}
     */
    public static function for_client( int $campaign_id ): array {
        return [
            'currency' => self::of( $campaign_id ),
            'stored'   => '' !== self::stored( $campaign_id ),
            'locked'   => self::is_locked( $campaign_id ),
        ];
    }
}
