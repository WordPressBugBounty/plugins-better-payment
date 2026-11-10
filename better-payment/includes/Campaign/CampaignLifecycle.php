<?php

namespace Better_Payment\Lite\Campaign;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether a Campaign Builder campaign is taking donations.
 *
 * A campaign stops taking donations for one of three reasons, checked in this order:
 *
 *   1. `closed_manual` — the owner set Status to Closed (`_bpc_status = closed`);
 *   2. `ended_date`    — its end date has passed. The end date is INCLUSIVE: a campaign
 *                        ending on the 31st takes donations through 23:59:59 that day, the
 *                        same rule CampaignStats::days_remaining() and AI\Support\DateGuard use;
 *   3. `ended_goal`    — "Close when the goal is reached" is on, it has a goal, and the
 *                        approved total has reached it.
 *
 * Otherwise it is `accepting`. Publication and password protection are a separate question
 * (who can see the campaign) and are not part of this state.
 *
 * Before 2.5.0 the end date and goal were cosmetic and `_bpc_status` was read by nothing:
 * a campaign that "ended" in March still took money in October, with "0 days left" on the
 * page. Every place that lets a donor give now asks this class:
 *
 *   - RendererService replaces the Donate Button with a closed message (the builder keeps the
 *     button and adds a note, so the element stays editable);
 *   - PaymentRequestGuard refuses the payment (`bp_campaign_closed`), so a page cached
 *     from before the close, a forged request and the Pro popup are refused server-side;
 *   - Pro's popup asks it before printing a form.
 *
 * @since 2.5.0
 */
class CampaignLifecycle {

    const ACCEPTING     = 'accepting';
    const ENDED_DATE    = 'ended_date';
    const ENDED_GOAL    = 'ended_goal';
    const CLOSED_MANUAL = 'closed_manual';

    /** `_bpc_status` values. Anything that is not "closed" (or the legacy "ended") is active. */
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';

    /**
     * A stored or submitted `_bpc_status`, as one of the two values it can mean.
     *
     * `ended` is what the never-wired Settings panel offered before 2.5.0; it means closed.
     *
     * @param mixed $raw Raw value.
     * @return string 'active' | 'closed'.
     */
    public static function normalize_status( $raw ): string {
        $status = is_string( $raw ) ? strtolower( trim( $raw ) ) : '';

        return in_array( $status, [ self::STATUS_CLOSED, 'ended' ], true ) ? self::STATUS_CLOSED : self::STATUS_ACTIVE;
    }

    /**
     * Site-local timestamp of the last second a campaign takes donations, or null with no
     * usable end date. A value carrying its own time of day is used as given.
     *
     * @param string $end_date Stored `_bpc_end_date` (usually Y-m-d).
     * @return int|null
     */
    public static function end_timestamp( string $end_date ): ?int {
        $end_date = trim( $end_date );
        if ( '' === $end_date ) {
            return null;
        }

        $has_time = (bool) preg_match( '/\d{1,2}:\d{2}/', $end_date );
        $end      = strtotime( $has_time ? $end_date : $end_date . ' 23:59:59' );

        return false === $end ? null : (int) $end;
    }

    /**
     * The state of a campaign from its meta and raised total. Pure: no database read.
     *
     * @param array $meta   Campaign meta as MetaBox::get_all() returns it (no leading underscore).
     * @param float $raised Approved total (CampaignStats `total_raised`).
     * @param int   $now    Site-local "timestamp" (current_time( 'timestamp' )); 0 = now.
     * @return string One of the state constants.
     */
    public static function state_from( array $meta, float $raised, int $now = 0 ): string {
        if ( self::STATUS_CLOSED === self::normalize_status( $meta['bpc_status'] ?? '' ) ) {
            return self::CLOSED_MANUAL;
        }

        $end = self::end_timestamp( is_string( $meta['bpc_end_date'] ?? null ) ? $meta['bpc_end_date'] : '' );
        $now = $now > 0 ? $now : (int) current_time( 'timestamp' );
        if ( null !== $end && $now > $end ) {
            return self::ENDED_DATE;
        }

        $goal = (float) ( $meta['bpc_goal_amount'] ?? 0 );
        if ( ! empty( $meta['bpc_close_on_goal'] ) && $goal > 0 && $raised + 0.005 >= $goal ) {
            return self::ENDED_GOAL;
        }

        return self::ACCEPTING;
    }

    /**
     * The state of a stored campaign.
     *
     * @param int  $campaign_id Campaign post id.
     * @param bool $fresh       Read the raised total uncached (the payment guard does: the
     *                          cache can be two minutes old, long enough to overshoot a goal).
     * @return string One of the state constants.
     */
    public static function state( int $campaign_id, bool $fresh = false ): string {
        $meta = MetaBox::get_all( $campaign_id );

        // The goal is the only rule that needs the total; skip the query when it cannot apply.
        $raised = 0.0;
        if ( ! empty( $meta['bpc_close_on_goal'] ) && (float) ( $meta['bpc_goal_amount'] ?? 0 ) > 0 ) {
            $stats  = CampaignStats::get_stats( $campaign_id, ! $fresh );
            $raised = (float) ( $stats['total_raised'] ?? 0 );
        }

        return self::state_from( $meta, $raised );
    }

    /**
     * Whether a stored campaign takes donations.
     *
     * @param int  $campaign_id Campaign post id.
     * @param bool $fresh       See state().
     * @return bool
     */
    public static function is_accepting( int $campaign_id, bool $fresh = false ): bool {
        return self::ACCEPTING === self::state( $campaign_id, $fresh );
    }

    /**
     * What visitors read in place of the Donate Button: the owner's message, or a default
     * that names the reason.
     *
     * @param array  $meta  Campaign meta.
     * @param string $state A state constant other than ACCEPTING.
     * @return string Plain text.
     */
    public static function closed_message( array $meta, string $state ): string {
        $custom = is_string( $meta['bpc_closed_message'] ?? null ) ? trim( $meta['bpc_closed_message'] ) : '';
        if ( '' !== $custom ) {
            return $custom;
        }

        switch ( $state ) {
            case self::ENDED_GOAL:
                return __( 'This campaign has reached its goal and is no longer taking donations. Thank you!', 'better-payment' );
            case self::ENDED_DATE:
                return __( 'This campaign has ended and is no longer taking donations.', 'better-payment' );
            default:
                return __( 'This campaign is closed and is no longer taking donations.', 'better-payment' );
        }
    }

    /**
     * Short admin-facing label for a state.
     *
     * @param string $state A state constant.
     * @return string
     */
    public static function label( string $state ): string {
        switch ( $state ) {
            case self::ENDED_DATE:
                return __( 'Ended (end date passed)', 'better-payment' );
            case self::ENDED_GOAL:
                return __( 'Ended (goal reached)', 'better-payment' );
            case self::CLOSED_MANUAL:
                return __( 'Closed', 'better-payment' );
            default:
                return __( 'Accepting donations', 'better-payment' );
        }
    }

    /**
     * The payment guard's refusal for a campaign that is not taking donations.
     *
     * @param string $state A state constant other than ACCEPTING.
     * @return WP_Error `bp_campaign_closed`, with `state` in its data.
     */
    public static function refusal( string $state ): WP_Error {
        return new WP_Error(
            'bp_campaign_closed',
            self::ENDED_GOAL === $state
                ? __( 'This campaign has reached its goal and is no longer taking donations.', 'better-payment' )
                : __( 'This campaign is no longer taking donations.', 'better-payment' ),
            [ 'state' => $state ]
        );
    }
}
