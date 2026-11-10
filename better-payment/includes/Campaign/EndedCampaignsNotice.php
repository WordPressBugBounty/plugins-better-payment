<?php

namespace Better_Payment\Lite\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Tells owners which published campaigns stopped taking donations when the end date
 * started to count, and lets them reopen each one in a click.
 *
 * Before 2.5.0 a campaign's end date was cosmetic: a campaign that "ended" in March still
 * took money. CampaignLifecycle now enforces it, so on the first request after the update
 * every published campaign whose end date has already passed closes at once — taking money
 * after a stated deadline is the larger harm. An owner who meant the date as decoration
 * would otherwise find out from a donor, so the check runs once per site (not per version,
 * so a site already on the release still runs it) and records which campaigns it closed.
 *
 * The notice lists only those campaigns, and only while they are still closed by their date:
 * one that was extended, given no end date, unpublished or deleted drops off, and the notice
 * goes away by itself once none is left. "Extend 30 days" moves the end date to 30 days from
 * today (site time). A campaign that ends later, under the new rule, is never listed.
 *
 * @since 2.5.0
 */
class EndedCampaignsNotice {

    /**
     * `[ 'status' => show|dismissed|none, 'ids' => int[] ]`. Missing until the one-time check runs.
     */
    const OPTION = 'better_payment_ended_campaigns_notice';

    const DISMISS_ACTION = 'better_payment_dismiss_ended_campaigns';
    const EXTEND_ACTION  = 'better_payment_extend_campaign';

    /** Days "Extend" adds, counted from today. */
    const EXTEND_DAYS = 30;

    /** WordPress screens that show the notice (Better Payment's own page shows it on every tab). */
    const SCREENS = [ 'dashboard', 'dashboard-network', 'plugins', 'plugins-network', 'edit-bp_campaign' ];

    const ADMIN_PAGE = 'better-payment-admin';

    /**
     * Record, once per site, the published campaigns the end date closes.
     *
     * @return bool True when this run turned the notice on.
     */
    public static function maybe_flag(): bool {
        if ( false !== get_option( self::OPTION, false ) ) {
            return false;
        }

        $ids = self::ended_campaign_ids();

        update_option(
            self::OPTION,
            [
                'status' => empty( $ids ) ? 'none' : 'show',
                'ids'    => $ids,
            ],
            false
        );

        return ! empty( $ids );
    }

    /**
     * Published campaigns whose end date has passed (CampaignLifecycle::ENDED_DATE).
     *
     * @param int[]|null $among Only these ids; null = every published campaign with an end date.
     * @return int[]
     */
    public static function ended_campaign_ids( ?array $among = null ): array {
        if ( null === $among ) {
            $among = get_posts(
                [
                    'post_type'   => 'bp_campaign',
                    'post_status' => 'publish',
                    'fields'      => 'ids',
                    'numberposts' => -1,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- once per site, admin only.
                    'meta_query'  => [
                        [
                            'key'     => '_bpc_end_date',
                            'value'   => '',
                            'compare' => '!=',
                        ],
                    ],
                ]
            );
        }

        $ended = [];
        foreach ( $among as $id ) {
            $id   = (int) $id;
            $post = get_post( $id );
            if ( ! $post || 'bp_campaign' !== $post->post_type || 'publish' !== $post->post_status ) {
                continue;
            }
            if ( CampaignLifecycle::ENDED_DATE === CampaignLifecycle::state( $id ) ) {
                $ended[] = $id;
            }
        }

        return $ended;
    }

    /**
     * The campaigns the notice still lists.
     *
     * @return int[]
     */
    public static function pending_ids(): array {
        $stored = get_option( self::OPTION );
        if ( ! is_array( $stored ) || 'show' !== ( $stored['status'] ?? '' ) ) {
            return [];
        }

        return self::ended_campaign_ids( array_map( 'intval', (array) ( $stored['ids'] ?? [] ) ) );
    }

    /**
     * Hook the notice and its actions. Admin requests only.
     *
     * @return void
     */
    public static function register(): void {
        add_action( 'admin_notices', [ __CLASS__, 'render' ] );
        add_action( 'better_payment_admin_notices', [ __CLASS__, 'render' ] );
        add_action( 'admin_post_' . self::DISMISS_ACTION, [ __CLASS__, 'dismiss' ] );
        add_action( 'admin_post_' . self::EXTEND_ACTION, [ __CLASS__, 'extend' ] );
    }

    /**
     * Whether a screen shows the notice.
     *
     * @param string $screen_id Current screen id.
     * @param string $page      The `page` query arg.
     * @return bool
     */
    public static function shows_on( string $screen_id, string $page ): bool {
        return in_array( $screen_id, self::SCREENS, true ) || self::ADMIN_PAGE === $page;
    }

    /**
     * Print the notice.
     *
     * @return void
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing arg.
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( ! self::shows_on( $screen ? (string) $screen->id : '', $page ) ) {
            return;
        }

        $ids = self::pending_ids();
        if ( empty( $ids ) ) {
            return;
        }

        $dismiss_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ), self::DISMISS_ACTION );
        ?>
        <div class="notice notice-warning better-payment-ended-campaigns-notice">
            <p><strong><?php esc_html_e( 'Better Payment: campaigns past their end date no longer take donations', 'better-payment' ); ?></strong></p>
            <p><?php esc_html_e( 'A campaign’s end date now closes it: visitors see a closed message instead of the Donate Button, and payments are refused. These published campaigns were already past their end date. Extend any that should keep taking donations.', 'better-payment' ); ?></p>
            <ul style="list-style:disc;margin-left:20px;">
                <?php foreach ( $ids as $id ) : ?>
                    <?php
                    $extend_url = wp_nonce_url(
                        admin_url( 'admin-post.php?action=' . self::EXTEND_ACTION . '&campaign_id=' . $id ),
                        self::EXTEND_ACTION . '_' . $id
                    );
                    $title      = get_the_title( $id );
                    $end_date   = (string) get_post_meta( $id, '_bpc_end_date', true );
                    ?>
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bp-campaign-builder&campaign_id=' . $id ) ); ?>"><?php echo esc_html( '' !== $title ? $title : '#' . $id ); ?></a>
                        <?php
                        /* translators: %s: the campaign's end date as stored (Y-m-d). */
                        echo esc_html( sprintf( __( '(ended %s)', 'better-payment' ), $end_date ) );
                        ?>
                        — <a href="<?php echo esc_url( $extend_url ); ?>">
                            <?php
                            /* translators: %d: number of days. */
                            echo esc_html( sprintf( __( 'Extend %d days', 'better-payment' ), self::EXTEND_DAYS ) );
                            ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Keep them closed, dismiss', 'better-payment' ); ?></a></p>
        </div>
        <?php
    }

    /**
     * admin-post: extend one campaign, then go back.
     *
     * @return void
     */
    public static function extend(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified in process_extend().
        self::process_extend( isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0 );

        $redirect = wp_get_referer();
        wp_safe_redirect( $redirect ? $redirect : admin_url() );
        exit;
    }

    /**
     * Check capability and nonce, then move the end date to EXTEND_DAYS from today.
     *
     * Dies on a missing capability, a bad nonce or a post that is not a campaign.
     *
     * @param int $campaign_id Campaign id.
     * @return string The new end date (Y-m-d).
     */
    public static function process_extend( int $campaign_id ): string {
        $post = get_post( $campaign_id );
        if ( ! $post || 'bp_campaign' !== $post->post_type || ! current_user_can( 'edit_post', $campaign_id ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'better-payment' ), '', [ 'response' => 403 ] );
        }

        check_admin_referer( self::EXTEND_ACTION . '_' . $campaign_id );

        $end_date = gmdate( 'Y-m-d', (int) current_time( 'timestamp' ) + self::EXTEND_DAYS * DAY_IN_SECONDS );
        update_post_meta( $campaign_id, '_bpc_end_date', $end_date );
        CampaignStats::bust_cache( $campaign_id );

        return $end_date;
    }

    /**
     * admin-post: dismiss the notice for the site, then go back.
     *
     * @return void
     */
    public static function dismiss(): void {
        self::process_dismiss();

        $redirect = wp_get_referer();
        wp_safe_redirect( $redirect ? $redirect : admin_url() );
        exit;
    }

    /**
     * Check capability and nonce, then record the dismissal.
     *
     * @return void
     */
    public static function process_dismiss(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'better-payment' ), '', [ 'response' => 403 ] );
        }

        check_admin_referer( self::DISMISS_ACTION );

        $stored           = get_option( self::OPTION );
        $stored           = is_array( $stored ) ? $stored : [ 'ids' => [] ];
        $stored['status'] = 'dismissed';
        update_option( self::OPTION, $stored, false );
    }
}
