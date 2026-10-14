<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * This class responsible for database work
 * using wordpress functionality 
 * get_option and update_option.
 */
class Helper extends Controller
{
    use TraitsHelper;

    /**
     * The statuses of a confirmed payment, as campaign totals, donor counts and Pro's Donors Wall
     * count them. See approved_donation_sql() for the one other row that counts.
     *
     * `success` is the status Paystack verification writes. It was missing, so Paystack donations
     * never counted toward a campaign's total or donor count, even though the transactions screen
     * and analytics already treated it as paid. Only a transaction Paystack reported as succeeded
     * is ever stored as `success`.
     *
     * @since 2.4.0 (was Campaign\CampaignStats::approved_statuses())
     */
    const APPROVED_DONATION_STATUSES = array( 'Completed', 'paid', 'complete', 'succeeded', 'success' );

    /**
     * The status rows written before 2.4.0 can carry for a free Stripe checkout (a 100%-off
     * promotion code, a subscription trial).
     *
     * Such a row is one of two things, and the status alone cannot tell them apart:
     *   - a CONFIRMED free checkout. The old confirmation stored Stripe's `payment_status` as the
     *     row status and, because `amount_total` was 0 and `! empty()` treated 0 as missing, kept
     *     the form's full amount. Nothing was charged.
     *   - a checkout Stripe reported as `no_payment_required` when it was CREATED and that nobody
     *     completed. The old confirmation only claimed `unpaid` rows, so it was never touched.
     * 2.4.0 stores neither: a confirmed row is always `paid` at its real amount, and a pending
     * one `unpaid`. Stored rows are left as they are and counted by the rule below.
     *
     * @since 2.4.0
     */
    const LEGACY_FREE_CHECKOUT_STATUS = 'no_payment_required';

    /**
     * What only the old Stripe confirmation wrote: the serialized `exact_paid_amount` key of
     * `form_fields_info`, followed by its float value.
     *
     * Every confirmation from the release that introduced discounts (and with them the only way
     * to reach a free checkout) onwards set `$form_fields_info['exact_paid_amount']` to a float
     * in the same UPDATE that wrote the status. No creation path ever writes that key. The
     * columns the confirmation also wrote are no use: `customer_info` and `transaction_id` are
     * already set at creation (the created session and its PaymentIntent), and `email` comes from
     * the charge's billing details, which a checkout that charged nothing does not have.
     *
     * The trailing `d:` makes it the key: after a string VALUE the next token is a key (`s:` or
     * `i:`) or `}`, so a form field whose value happens to be "exact_paid_amount" cannot match.
     *
     * @since 2.4.0
     */
    const LEGACY_CONFIRMATION_MARKER = 's:17:"exact_paid_amount";d:';

    /**
     * SQL condition: true only for an unconfirmed legacy free checkout. Null-safe: a NULL status or
     * NULL form_fields_info never makes it true.
     *
     * OR it into the `incomplete` bucket's `status IN (…)` clause, so the Incomplete tab lists the
     * rows the Completed tab drops (DB::get_transactions()). Contains no `%`.
     *
     * @since 2.4.0
     */
    const UNCONFIRMED_LEGACY_SQL = "( COALESCE(status, '') = '" . self::LEGACY_FREE_CHECKOUT_STATUS . "' AND INSTR(COALESCE(form_fields_info, ''), '" . self::LEGACY_CONFIRMATION_MARKER . "') = 0 )";

    /**
     * SQL condition: false only for an unconfirmed legacy free checkout, the exact negation of
     * UNCONFIRMED_LEGACY_SQL. Null-safe, so a row with a NULL status (PayPal's pending sentinel) or
     * NULL form_fields_info is never dropped by it.
     *
     * AND it onto any aggregation that selects rows by a status bucket containing
     * `no_payment_required` (`completed`, `all`). Admin\DB and Pro's AnalyticsModel do; Pro keeps
     * the literal value, and AnalyticsModelParityTest compares the two values.
     *
     * @since 2.4.0
     */
    const COUNTED_ROW_SQL = 'NOT ' . self::UNCONFIRMED_LEGACY_SQL;

    /**
     * SQL expression: what a row contributes to a money total. A legacy free checkout adds 0,
     * confirmed or not, because its stored amount (the form's) was never charged.
     *
     * @since 2.4.0
     */
    const COUNTABLE_AMOUNT_SQL = "CASE WHEN status = '" . self::LEGACY_FREE_CHECKOUT_STATUS . "' THEN 0 ELSE amount END";

    /**
     * SQL condition: the row is a confirmed donation.
     *
     * An approved status, or a CONFIRMED legacy free checkout (see LEGACY_CONFIRMATION_MARKER).
     * Pair it with COUNTABLE_AMOUNT_SQL for any sum. Contains no `%`, so it can be interpolated
     * into a string passed to `$wpdb->prepare()`.
     *
     * Pro keeps an identical copy in Models\AnalyticsModel (it supports a Lite older than this
     * method), pinned by Pro's AnalyticsModelParityTest.
     *
     * @since 2.4.0
     *
     * @return string
     */
    public static function approved_donation_sql(): string {
        return "( status IN ('" . implode( "', '", self::APPROVED_DONATION_STATUSES ) . "')"
            . " OR ( status = '" . self::LEGACY_FREE_CHECKOUT_STATUS . "' AND INSTR(COALESCE(form_fields_info, ''), '" . self::LEGACY_CONFIRMATION_MARKER . "') > 0 ) )";
    }

    /**
     * PHP twin of `NOT COUNTED_ROW_SQL`, for rows already loaded: is this a legacy free checkout
     * that was never confirmed?
     *
     * A row without `form_fields_info` loaded is treated as unconfirmed (never counted), the safe
     * side of the rule. An already-unserialized `form_fields_info` array is read as the string it was
     * stored as, so a caller that unserialized the row first gets the same answer.
     *
     * @since 2.4.0
     *
     * @param object|array $row A transaction row.
     * @return bool
     */
    public static function is_unconfirmed_legacy_free_checkout( $row ): bool {
        return self::LEGACY_FREE_CHECKOUT_STATUS === self::row_value( $row, 'status' )
            && false === strpos( self::form_fields_info_string( $row ), self::LEGACY_CONFIRMATION_MARKER );
    }

    /**
     * The status bucket a loaded transaction row is labelled and listed under.
     *
     * The raw status's bucket (get_type_by_transaction_status()), except for an unconfirmed legacy
     * free checkout: no count or total includes it, so it is `incomplete` (v1: `unpaid`) rather than
     * the `completed` its `no_payment_required` status maps to. A confirmed one stays `completed`.
     * The row itself is never changed. Every PHP surface that labels a row reads this: the admin REST
     * rows (`status_type`), the CSV export, UserAPI, the User Dashboard template and the block's
     * editor rows. DB::get_transactions() applies the same rule to the Completed / Incomplete tabs.
     *
     * @since 2.4.0
     *
     * @param object|array $row     A transaction row.
     * @param string       $version 'v2' (completed / incomplete / refunded) or 'v1'.
     * @return string The bucket key; '' for a status no bucket lists, as get_type_by_transaction_status().
     */
    public static function transaction_status_type( $row, string $version = 'v2' ): string {
        if ( self::is_unconfirmed_legacy_free_checkout( $row ) ) {
            return 'v1' === $version ? 'unpaid' : 'incomplete';
        }

        return ( new self() )->get_type_by_transaction_status( self::row_value( $row, 'status' ), $version );
    }

    /**
     * The status label of a loaded row ("Completed", "Incomplete", "Refunded"), v2 buckets.
     *
     * @since 2.4.0
     *
     * @param object|array $row A transaction row.
     * @return string
     */
    public static function transaction_status_label( $row ): string {
        return ucfirst( self::transaction_status_type( $row, 'v2' ) );
    }

    /**
     * The admin page slug that served transactions before the React admin.
     *
     * Nothing registers it any more, so WordPress answers a link to it with "Sorry, you are not
     * allowed to access this page". Admin e-mails sent before 2.4.2 carry it in their View
     * Transaction button, which is why ReactAdmin::handle_old_page_redirect() still forwards it.
     *
     * @since 2.4.2
     */
    const LEGACY_TRANSACTIONS_PAGE = 'better-payment-transactions';

    /**
     * The React admin URL that opens one transaction's details.
     *
     * The single place that spells it: the admin notification e-mail's View Transaction button,
     * the User Dashboard's View button (PHP rows and the rows bp-dashboard-pagination.js loads)
     * and the legacy-page redirect all link through here.
     * Transactions.js opens the details view from `action=view&id=…`.
     *
     * @since 2.4.2
     *
     * @param int $transaction_id A transaction row id.
     * @return string Unescaped URL; escape at output.
     */
    public static function transaction_view_url( int $transaction_id ): string {
        return add_query_arg( 'id', $transaction_id, self::transaction_view_base_url() );
    }

    /**
     * transaction_view_url() without the `id`, for a script that appends `&id=…` to each row it
     * builds (bp-dashboard-pagination.js).
     *
     * @since 2.4.2
     *
     * @return string Unescaped URL; escape at output.
     */
    public static function transaction_view_base_url(): string {
        return add_query_arg(
            array(
                'page'   => 'better-payment-admin',
                'tab'    => 'transactions',
                'action' => 'view',
            ),
            admin_url( 'admin.php' )
        );
    }

    /**
     * The status colour of a loaded row, v2 buckets: the colour of transaction_status_type()'s bucket.
     *
     * @since 2.4.0
     *
     * @param object|array $row A transaction row.
     * @return string
     */
    public static function transaction_status_color( $row ): string {
        $helper = new self();

        if ( self::is_unconfirmed_legacy_free_checkout( $row ) ) {
            $buckets = $helper->transaction_statuses_with_type_v2();

            return $buckets['incomplete']['color'];
        }

        return $helper->get_color_by_transaction_status( self::row_value( $row, 'status' ), 'v2' );
    }

    /**
     * PHP twin of COUNTABLE_AMOUNT_SQL: what a loaded row contributes to a money total.
     *
     * @since 2.4.0
     *
     * @param object|array $row A transaction row.
     * @return float
     */
    public static function countable_amount( $row ): float {
        if ( self::LEGACY_FREE_CHECKOUT_STATUS === self::row_value( $row, 'status' ) ) {
            return 0.0;
        }

        $amount = self::row_value( $row, 'amount' );

        return '' === $amount ? 0.0 : floatval( $amount );
    }

    /**
     * Raised total and payment count over loaded transaction rows (the Elementor Fundraising
     * Campaign widget's progress).
     *
     * Counts the same rows CampaignStats counts: an approved status (Stripe `paid`, Paystack
     * `success`, `Completed`, …) or a confirmed legacy free checkout, which adds 0. It used to
     * count only `paid`, so Paystack and manually completed donations never moved the bar.
     *
     * @since 2.4.0
     *
     * @param mixed $rows Transaction rows (objects or arrays).
     * @return array{amount: float, count: int}
     */
    public static function campaign_totals_from_rows( $rows ): array {
        $totals = array(
            'amount' => 0.0,
            'count'  => 0,
        );

        foreach ( is_array( $rows ) ? $rows : array() as $row ) {
            $status = self::row_value( $row, 'status' );

            $counted = in_array( $status, self::APPROVED_DONATION_STATUSES, true )
                || ( self::LEGACY_FREE_CHECKOUT_STATUS === $status && ! self::is_unconfirmed_legacy_free_checkout( $row ) );

            if ( ! $counted ) {
                continue;
            }

            $totals['amount'] += self::countable_amount( $row );
            ++$totals['count'];
        }

        return $totals;
    }

    /**
     * A scalar column of a row object or array, as a string ('' when absent or not scalar).
     *
     * @param object|array $row A transaction row.
     * @param string       $key Column name.
     * @return string
     */
    private static function row_value( $row, string $key ): string {
        $value = null;

        if ( is_object( $row ) && isset( $row->$key ) ) {
            $value = $row->$key;
        } elseif ( is_array( $row ) && isset( $row[ $key ] ) ) {
            $value = $row[ $key ];
        }

        return is_scalar( $value ) ? (string) $value : '';
    }

    /**
     * A row's `form_fields_info` as stored: the serialized string, an already-unserialized array
     * serialized back, or '' when absent. Only ever searched for LEGACY_CONFIRMATION_MARKER.
     *
     * @param object|array $row A transaction row.
     * @return string
     */
    private static function form_fields_info_string( $row ): string {
        $value = null;

        if ( is_object( $row ) && isset( $row->form_fields_info ) ) {
            $value = $row->form_fields_info;
        } elseif ( is_array( $row ) && isset( $row['form_fields_info'] ) ) {
            $value = $row['form_fields_info'];
        }

        if ( is_array( $value ) ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- searched for the marker, never unserialized.
            return serialize( $value );
        }

        return is_scalar( $value ) ? (string) $value : '';
    }

    // find_element_recursive() comes from Traits\Helper; this class used to carry
    // a byte-identical copy of it.

    public function titleToSnake($text, $divider = '_') {
        // Handle null or empty values to prevent deprecated warnings.
        if ( null === $text || '' === $text ) {
            return 'n_a';
        }

        $text = (string) $text;
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $divider);
        $text = preg_replace('~-+~', $divider, $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n_a';
        }

        return $text;
    }

    public function transaction_statuses_with_type(){
        $statuses = [
            'completed' => [
                'statuses' => [
                    'paid',
                    'Completed',
                    'completed',
                    // Stripe's own status for a fully-discounted / $0 Checkout
                    // Session (100% coupon or sale, free trial). The payment
                    // IS settled - there was simply nothing to charge - so it
                    // must classify as completed, not fall through to the
                    // unknown-status default. See Helper::get_type_by_transaction_status().
                    'no_payment_required',
                ],
                'color' => '#0ECA86',
            ], 
            'processing' => [
                'statuses' => [
                    'pending',
                    'Pending',
                    NULL,
                ],
                'color' => '#FFDA15',
            ],
            'refunded' => [
                'statuses' => [
                    'refunded',
                ],
                'color' => '#FF0202',
            ],
            'unpaid' => [
                'statuses' => [
                    'unpaid',
                    // A payment that failed.
                    'failed',
                ],
                'color' => '#999',
            ],
            'all' => [
                'statuses' => [
                    'paid',
                    'Completed',
                    'completed',
                    'no_payment_required',
                    'unpaid',
                    'refunded',
                    'pending',
                    'Pending',
                    'failed',
                    NULL,
                ],
                'color' => '#999',
            ],
        ];

        return $statuses;
    }

    public function transaction_statuses_with_type_v2(){
        $statuses = [
            'completed' => [
                'statuses' => [
                    'paid',
                    'Completed',
                    'completed',
                    'success',
                    // See the v1 note: a $0 Stripe session settles as
                    // 'no_payment_required'. Every status bucket is also a
                    // `status IN (...)` filter in DB::get_transactions(), so a
                    // status missing from BOTH 'completed' and 'incomplete'
                    // is invisible under either tab while still showing on
                    // the unfiltered list - which is exactly how a paid $0
                    // order looked like a stray "Incomplete" row.
                    'no_payment_required',
                ],
                'color' => '#0ECA86',
            ], 
            'incomplete' => [
                'statuses' => [
                    'pending',
                    'Pending',
                    'unpaid',
                    'incomplete',
                    'Incomplete',
                    // A payment that failed.
                    'failed',
                    NULL,
                ],
                'color' => '#FFDA15',
            ],
            'refunded' => [
                'statuses' => [
                    'refunded',
                ],
                'color' => '#FF0202',
            ],
            'all' => [
                'statuses' => [
                    'paid',
                    'Completed',
                    'completed',
                    'no_payment_required',
                    'unpaid',
                    'refunded',
                    'pending',
                    'Pending',
                    'success',
                    'incomplete',
                    'Incomplete',
                    'failed',
                    NULL,
                ],
                'color' => '#999',
            ],
        ];

        return $statuses;
    }

    public function get_type_by_transaction_status($status = '', $version = 'v1'){
        if('v1' === $version){
            $statuses = $this->transaction_statuses_with_type();
        } else {
            $statuses = $this->transaction_statuses_with_type_v2();
        }

        $status_type = '';
        if(!empty($status)){
            foreach($statuses as $type => $statuses_and_color){
                if(in_array($status, $statuses_and_color['statuses'])){
                    $status_type = $type;
                    break;
                }
            }
        }

        if($status === '' || $status === NULL){
            $status_type = 'incomplete';
        }

        return $status_type;
    }

    public function get_color_by_transaction_status($status = '', $version = 'v1'){
        if('v1' === $version){
            $statuses = $this->transaction_statuses_with_type();
        } else {
            $statuses = $this->transaction_statuses_with_type_v2();
        }

        $color = '#999';
        if(!empty($status)){
            foreach($statuses as $type => $statuses_and_color){
                if(in_array($status, $statuses_and_color['statuses'])){
                    $color = $statuses_and_color['color'];
                    break;
                }
            }
        }

        if($status === '' || $status === NULL){
            $color = '#FFDA15'; //incomplete
        }

        return $color;
        
    }

    public function get_statuses_by_transaction_type($status_type = 'all', $version = 'v1'){
        if('v1' === $version){
            $statuses = $this->transaction_statuses_with_type();
        } else {
            $statuses = $this->transaction_statuses_with_type_v2();
        }

        $transaction_statuses = [];
        if(!empty($status_type)){
            foreach($statuses as $type => $statuses_and_color){
                if($type == $status_type){
                    $transaction_statuses = $statuses_and_color['statuses'];
                    break;
                }
            }
        }

        return $transaction_statuses;
    }
    
    public function get_transaction_types($version = 'v1'){
        if('v1' === $version){
            $statuses = $this->transaction_statuses_with_type();
        } else {
            $statuses = $this->transaction_statuses_with_type_v2();
        }

        $statuses_types = array_keys($statuses);
        
        return $statuses_types;
    }

    /**
     * Get interval text for recurring and split payment
     *
     * Lite never calls this. Better Payment Pro <= 1.4.3's layout partials did, from
     * templates that no longer load (Pro 1.4.3 fills no `layout_template` filter), and
     * tests/Unit/ProCouplingContractTest.php still pins it. Pro 1.4.4+ uses its own
     * Admin\Elementor\LayoutRenderer::interval_text().
     *
     * @since 1.3.0
     * @deprecated 2.4.0 Kept as belt-and-braces for Pro 1.4.3. Delete once no supported
     *             site can run it.
     */
    public function get_interval_text( $interval = '' ){
        $interval_text = '';

        switch( $interval ) {
            case 'day':
                $interval_text = 'Daily';
                break;

            case 'month': 
            case 'year':
                $interval_text = $interval . 'ly';
                break;

            default :
                $interval_text = ! empty( $interval ) ? "Per " . esc_html( $interval ) : "";
                break;
        }

        return esc_html( ucfirst( $interval_text ) );
    }

    /**
     * The payment type label of a transaction, as the User Dashboard REST rows and
     * the CSV export print it.
     *
     * Lite's own checkouts only take one-time payments, so Lite says 'One Time'.
     * `better_payment/transaction/payment_type` ($label, $form_fields_info) relabels
     * a row: Pro returns 'Subscription' for its recurring and split payments. The CSV
     * import reads the label back, so keep these two strings.
     *
     * @since 2.4.0
     *
     * @param mixed $form_fields_info The transaction's unserialized form_fields_info.
     * @return string
     */
    public static function payment_type_label( $form_fields_info ): string {
        $form_fields_info = is_array( $form_fields_info ) ? $form_fields_info : [];
        $label            = 'One Time';

        $filtered = apply_filters( 'better_payment/transaction/payment_type', $label, $form_fields_info );

        return is_string( $filtered ) ? $filtered : $label;
    }

    /**
     * Output the page header, compatible with both classic and block themes.
     *
     * Block themes don't use get_header() — they rely on block template parts.
     * We detect this with wp_is_block_theme() and render the header template
     * part via do_blocks() so it appears correctly on CPT single pages that
     * bypass the theme's block templates.
     *
     * @param string $block_header Pre-rendered header markup from do_blocks() (block themes only).
     * @return void
     */
    public static function render_header( string $block_header = '' ): void {
        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            ?>
            <!doctype html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo( 'charset' ); ?>">
                <?php wp_head(); ?>
            </head>
            <body <?php body_class(); ?>>
            <?php
            wp_body_open();
            echo $block_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            get_header();
        }
    }

    /**
     * Output the page footer, compatible with both classic and block themes.
     *
     * @param string $block_footer Pre-rendered footer markup from do_blocks() (block themes only).
     * @return void
     */
    public static function render_footer( string $block_footer = '' ): void {
        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            echo $block_footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            wp_footer();
            ?>
            </body>
            </html>
            <?php
        } else {
            get_footer();
        }
    }
}


// Helper Functions
if ( ! function_exists( 'better_payment_dd' ) ) {
    function better_payment_dd( $data, $show_query = 0, $print_only = 0 ) {
        global $wpdb;

        if ( 1 === $show_query ) {
            echo wp_kses_post( $wpdb->last_query );
            echo wp_kses_post( $wpdb->last_result );
            echo wp_kses_post( $wpdb->last_error );
        }

        echo '<pre>';
        print_r( $data ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
        if ( ! $print_only ) {
            wp_die( 'Printed!' );
        }
    }
}
