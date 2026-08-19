<?php
/**
 * Better Payment feature-usage collection for the opt-in usage tracker.
 *
 * @package Better_Payment\Lite\Classes
 */

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Models\SubscriptionRelationModel;
use Better_Payment\Lite\WooCommerce\Subscriptions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Collects "which Better Payment features does this site actually use" for the
 * `optional_data` key of the opt-in tracker payload (`Plugin_Usage_Tracker`).
 *
 * ## The line this class does not cross
 *
 * This is a PAYMENT plugin, so the boundary is stricter than a generic usage
 * tracker's and is the first thing to check before adding a key here. Everything
 * reported is an aggregate count, a boolean, or a fixed enum value chosen from a
 * closed list in our own code. NOTHING below is ever collected:
 *
 *   - **Money.** No transaction amounts, no revenue, no campaign goal or raised
 *     figures, no subscription prices. `campaigns_with_goal` counts campaigns
 *     that HAVE a goal; the amount never leaves the site.
 *   - **Transaction volume.** Deliberately absent, even as a count. "How many
 *     payments has this site taken" is commercially sensitive information about
 *     the site owner's business, not a compatibility signal, and the site URL
 *     travels in the same payload. `has_*` booleans about payments are the same
 *     class of fact and are equally excluded.
 *   - **People.** No customer names, emails, addresses, order ids, user ids, or
 *     anything derived from the transactions table or from WooCommerce orders
 *     other than a COUNT.
 *   - **Credentials.** No gateway keys, no AI API keys, no webhook secrets. The
 *     gateway/AI keys are reported only as "configured: yes/no".
 *   - **Free text.** No campaign titles, descriptions, custom CSS classes or
 *     prompt history. Every string value here comes from a fixed set we ship
 *     (template keys, element types, gateway slugs, provider slugs, currency
 *     codes), so a value can never carry content the user typed.
 *
 * ## Cost
 *
 * `collect()` runs at most once per 24h, only after the user has opted in
 * (`Plugin_Usage_Tracker::do_tracking()` gates on both). Every query here is
 * either a COUNT/GROUP BY, an indexed lookup, or explicitly row-capped — see
 * `MAX_CAMPAIGNS`. Elementor widget counts are read from Elementor's own
 * maintained aggregate rather than by scanning post meta.
 *
 * @since 2.3.2
 */
class Usage_Data {

    /**
     * Ceiling on how many campaign layouts are parsed for element counts.
     *
     * The per-element breakdown needs each campaign's `_bpc_fields_layout` JSON
     * decoded, which is the only unbounded work in this class. Sites with more
     * campaigns than this report element usage from the most recent slice; the
     * campaign COUNTS themselves are always exact (they come from a COUNT query,
     * not from this loop).
     */
    const MAX_CAMPAIGNS = 500;

    /**
     * Elementor widget names registered by this plugin.
     *
     * Mirrors `Admin\Elementor\EL_Integration::init_widgets()`.
     */
    const WIDGETS = array(
        'better-payment',
        'better-payment-user-dashboard',
        'fundraising-campaign',
    );

    /**
     * Gutenberg block names registered by this plugin.
     *
     * Mirrors `Blocks\BlockManager::$blocks` + `Campaign\CampaignBlock`.
     */
    const BLOCKS = array(
        'better-payment/payment-form',
        'better-payment/user-dashboard',
        'better-payment/campaign-display',
    );

    /**
     * Statuses a Better Payment native WooCommerce subscription can hold.
     *
     * Mirrors `WooCommerce\Subscriptions::customer_status_label()`.
     */
    const SUBSCRIPTION_STATUSES = array( 'active', 'past_due', 'cancelled', 'completed' );

    /**
     * Build the full usage payload.
     *
     * Every section is independently guarded, so a missing table, an absent
     * Elementor install or a WooCommerce-free site degrades to fewer keys rather
     * than to a fatal inside the tracker.
     *
     * @return array<string, int|string> Flat map of metric name => scalar.
     */
    public static function collect() {
        $data = array_merge(
            self::widget_usage(),
            self::block_usage(),
            self::campaign_usage(),
            self::subscription_usage(),
            self::feature_configuration()
        );

        /**
         * Filter the usage payload before it is attached to the tracker request.
         *
         * Return an empty array to send no usage data at all while keeping the
         * environment/compatibility half of the tracker payload.
         *
         * @since 2.3.2
         *
         * @param mixed $data Flat map of metric name => scalar. Typed `mixed`
         *                    because a third-party callback can return anything,
         *                    which is what the guard below is for.
         */
        $data = apply_filters( 'better_payment/usage_data', $data );

        return is_array( $data ) ? $data : array();
    }

    /**
     * Elementor widget usage, keyed `widget_{name}`.
     *
     * Two sources, and they do not count the same thing — which is why the
     * payload says which one produced the numbers:
     *
     *   1. Elementor's own `elementor_controls_usage` aggregate. Free to read
     *     and counts widget INSTANCES.
     *   2. A `_elementor_data` scan, counting POSTS that contain the widget.
     *
     * The aggregate is only populated when Elementor's usage module has seen a
     * post save, so a site that has not re-saved its pages since installing
     * Elementor has an empty option and would otherwise report no widget usage
     * at all — indistinguishable from genuinely using none. That blind spot is
     * what the fallback exists for.
     *
     * `widget_count_basis` names the source (`instances` / `posts`) so the two
     * are never silently averaged together on the receiving end.
     *
     * @return array<string, int|string>
     */
    protected static function widget_usage() {
        $counts = self::widget_usage_from_elementor();

        if ( ! empty( $counts ) ) {
            $counts['widget_count_basis'] = 'instances';

            return $counts;
        }

        $counts = self::widget_usage_from_post_meta();

        if ( ! empty( $counts ) ) {
            $counts['widget_count_basis'] = 'posts';
        }

        return $counts;
    }

    /**
     * Widget INSTANCE counts from Elementor's maintained usage aggregate.
     *
     * @return array<string, int>
     */
    protected static function widget_usage_from_elementor() {
        $usage = get_option( 'elementor_controls_usage', array() );

        if ( empty( $usage ) || ! is_array( $usage ) ) {
            return array();
        }

        $counts = array();

        // Shape: [ doc_type => [ widget_name => [ 'count' => int, ... ] ] ].
        foreach ( $usage as $elements ) {
            if ( ! is_array( $elements ) ) {
                continue;
            }

            foreach ( $elements as $widget_name => $element_data ) {
                if ( ! in_array( $widget_name, self::WIDGETS, true ) ) {
                    continue;
                }

                if ( ! is_array( $element_data ) || ! isset( $element_data['count'] ) ) {
                    continue;
                }

                $key            = 'widget_' . $widget_name;
                $counts[ $key ] = isset( $counts[ $key ] )
                    ? $counts[ $key ] + (int) $element_data['count']
                    : (int) $element_data['count'];
            }
        }

        return array_filter( $counts );
    }

    /**
     * Widget usage as a count of POSTS whose `_elementor_data` contains it.
     *
     * One `SUM(... LIKE ...)` pass restricted by `meta_key` (which is indexed),
     * so this touches only Elementor's own rows rather than all of post meta.
     * Matched on the JSON fragment `"widgetType":"{name}"` — a bare name match
     * would also hit a widget's saved settings that happen to mention it.
     *
     * @return array<string, int>
     */
    protected static function widget_usage_from_post_meta() {
        global $wpdb;

        $selects = array();
        $params  = array();

        foreach ( self::WIDGETS as $widget_name ) {
            $selects[] = 'SUM( pm.meta_value LIKE %s ) AS ' . self::widget_alias( $widget_name );
            // Elementor stores its tree JSON-encoded, unspaced.
            $params[]  = '%"widgetType":"' . $wpdb->esc_like( $widget_name ) . '"%';
        }

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $selects is built from a class constant; every user-facing value is a placeholder.
        $sql = 'SELECT ' . implode( ', ', $selects ) . "
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_elementor_data'
              AND p.post_status NOT IN ( 'trash', 'auto-draft', 'inherit' )";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $row = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        if ( empty( $row ) ) {
            return array();
        }

        $counts = array();

        foreach ( self::WIDGETS as $widget_name ) {
            $alias = self::widget_alias( $widget_name );
            $count = isset( $row[ $alias ] ) ? (int) $row[ $alias ] : 0;

            if ( $count > 0 ) {
                $counts[ 'widget_' . $widget_name ] = $count;
            }
        }

        return $counts;
    }

    /**
     * Gutenberg block usage, keyed `block_{name}` — the number of posts that
     * contain each block.
     *
     * One `SUM(... LIKE ...)` query rather than three `COUNT(*)`s, matched on the
     * block comment delimiter (`<!-- wp:better-payment/payment-form`) so a block
     * name that is a prefix of another cannot inflate its neighbour. Revisions
     * and auto-drafts are excluded — a revision is a copy of content already
     * counted through its parent.
     *
     * @return array<string, int>
     */
    protected static function block_usage() {
        global $wpdb;

        $selects = array();
        $params  = array();

        foreach ( self::BLOCKS as $block_name ) {
            $selects[] = 'SUM( post_content LIKE %s ) AS ' . self::block_alias( $block_name );
            // Trailing space/newline is not matched: a block may be self-closing
            // (`/-->`), have attributes (`{"x":1}`), or neither.
            $params[]  = '%<!-- wp:' . $wpdb->esc_like( $block_name ) . '%';
        }

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $selects is built from class constants; every user-facing value is a placeholder.
        $sql = 'SELECT ' . implode( ', ', $selects ) . "
            FROM {$wpdb->posts}
            WHERE post_status NOT IN ( 'trash', 'auto-draft', 'inherit' )
              AND post_type NOT IN ( 'revision' )";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $row = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        if ( empty( $row ) ) {
            return array();
        }

        $counts = array();

        foreach ( self::BLOCKS as $block_name ) {
            $alias = self::block_alias( $block_name );
            $count = isset( $row[ $alias ] ) ? (int) $row[ $alias ] : 0;

            if ( $count > 0 ) {
                $counts[ 'block_' . self::short_block_name( $block_name ) ] = $count;
            }
        }

        return $counts;
    }

    /**
     * Campaign Builder usage.
     *
     * Three signals, in ascending order of usefulness:
     *
     *   - `campaigns_{status}`  — how many campaigns exist, and how many made it
     *     out of draft. The gap between the two is the builder's drop-off rate.
     *   - `campaign_template_*` — which starting templates people actually pick
     *     (including `ai-assistant`, the AI-generated path).
     *   - `campaign_element_*`  — which elements survive on a saved campaign.
     *     This is the campaign equivalent of widget usage and the only way to
     *     tell a shipped element from an ignored one.
     *
     * @return array<string, int>
     */
    protected static function campaign_usage() {
        global $wpdb;

        $counts = array();

        $status_counts = wp_count_posts( 'bp_campaign' );

        foreach ( array( 'publish', 'draft' ) as $status ) {
            if ( isset( $status_counts->$status ) && (int) $status_counts->$status > 0 ) {
                $counts[ 'campaigns_' . $status ] = (int) $status_counts->$status;
            }
        }

        if ( empty( $counts ) ) {
            // No campaigns at all — skip three queries that can only return zero.
            return $counts;
        }

        // Campaigns that carry a fundraising goal / a deadline. The VALUES are
        // never collected, only whether the field is in use.
        foreach ( array( '_bpc_goal_amount' => 'campaigns_with_goal', '_bpc_end_date' => 'campaigns_with_end_date' ) as $meta_key => $metric ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                     WHERE pm.meta_key = %s
                       AND pm.meta_value != ''
                       AND pm.meta_value != '0'
                       AND p.post_type = 'bp_campaign'
                       AND p.post_status NOT IN ( 'trash', 'auto-draft' )",
                    $meta_key
                )
            );

            if ( $count > 0 ) {
                $counts[ $metric ] = $count;
            }
        }

        // Which starting template each campaign was built from.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $templates = $wpdb->get_results(
            "SELECT pm.meta_value AS template_key, COUNT(*) AS total
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_bpc_template_key'
               AND pm.meta_value != ''
               AND p.post_type = 'bp_campaign'
               AND p.post_status NOT IN ( 'trash', 'auto-draft' )
             GROUP BY pm.meta_value",
            ARRAY_A
        );

        if ( ! empty( $templates ) ) {
            foreach ( $templates as $template ) {
                $key = sanitize_key( $template['template_key'] );

                if ( '' !== $key ) {
                    $counts[ 'campaign_template_' . $key ] = (int) $template['total'];
                }
            }
        }

        return array_merge( $counts, self::campaign_element_usage() );
    }

    /**
     * Per-element counts across saved campaign layouts, keyed
     * `campaign_element_{type}`.
     *
     * @return array<string, int>
     */
    protected static function campaign_element_usage() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $layouts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_bpc_fields_layout'
                   AND pm.meta_value != ''
                   AND p.post_type = 'bp_campaign'
                   AND p.post_status NOT IN ( 'trash', 'auto-draft' )
                 ORDER BY p.ID DESC
                 LIMIT %d",
                self::MAX_CAMPAIGNS
            )
        );

        if ( empty( $layouts ) ) {
            return array();
        }

        $counts = array();

        foreach ( $layouts as $layout_json ) {
            $layout = json_decode( (string) $layout_json, true );

            if ( ! is_array( $layout ) ) {
                continue;
            }

            // Legacy campaigns store a flat element list; current ones store
            // { layout, columns: [ { elements: [] } ] }. Normalize to columns.
            $columns = isset( $layout['columns'] ) && is_array( $layout['columns'] )
                ? $layout['columns']
                : array( array( 'elements' => $layout ) );

            foreach ( $columns as $column ) {
                if ( empty( $column['elements'] ) || ! is_array( $column['elements'] ) ) {
                    continue;
                }

                foreach ( $column['elements'] as $element ) {
                    if ( empty( $element['type'] ) || ! is_string( $element['type'] ) ) {
                        continue;
                    }

                    $type = sanitize_key( $element['type'] );

                    if ( '' === $type ) {
                        continue;
                    }

                    $key            = 'campaign_element_' . $type;
                    $counts[ $key ] = isset( $counts[ $key ] ) ? $counts[ $key ] + 1 : 1;
                }
            }
        }

        return $counts;
    }

    /**
     * Subscription usage — both kinds, which are different systems.
     *
     *   - `subscription_relations_{source}` counts rows in the e-commerce
     *     relation table, i.e. subscriptions owned by WooCommerce / FluentCart /
     *     SureCart that Better Payment is integrating with.
     *   - `subscriptions_{status}` counts Better Payment's OWN native
     *     WooCommerce recurring payments, which never write to that table
     *     (they live as parent-order meta).
     *   - `subscription_products` counts products opted into native recurring
     *     billing — configured intent, as opposed to the sales above.
     *
     * Counts only. No order ids, no customer ids, no amounts, no billing dates.
     *
     * @return array<string, int>
     */
    protected static function subscription_usage() {
        global $wpdb;

        $counts = array();

        $table = $wpdb->prefix . 'better_payment_subscription_order';

        // The table arrives via a version migration, so an install that has not
        // migrated yet legitimately does not have it.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

        if ( $table_exists === $table ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
            $rows = $wpdb->get_results(
                "SELECT source, type, COUNT(*) AS total FROM {$table} GROUP BY source, type",
                ARRAY_A
            );

            if ( ! empty( $rows ) ) {
                foreach ( $rows as $row ) {
                    $source = sanitize_key( $row['source'] );

                    if ( '' === $source ) {
                        continue;
                    }

                    $key            = 'subscription_relations_' . $source;
                    $counts[ $key ] = isset( $counts[ $key ] ) ? $counts[ $key ] + (int) $row['total'] : (int) $row['total'];

                    if ( SubscriptionRelationModel::TYPE_RENEW === $row['type'] ) {
                        $renew_key            = 'subscription_renewals_' . $source;
                        $counts[ $renew_key ] = isset( $counts[ $renew_key ] )
                            ? $counts[ $renew_key ] + (int) $row['total']
                            : (int) $row['total'];
                    }
                }
            }
        }

        return array_merge( $counts, self::native_subscription_usage() );
    }

    /**
     * Better Payment's own WooCommerce recurring payments.
     *
     * Subscription state is parent-order meta, and orders may live in either the
     * posts table or HPOS's own tables — so this goes through `wc_get_orders()`
     * with `paginate`, which resolves to a COUNT against whichever store is
     * active and returns `total` without hydrating a single order object.
     *
     * @return array<string, int>
     */
    protected static function native_subscription_usage() {
        if ( ! function_exists( 'wc_get_orders' ) || ! class_exists( Subscriptions::class ) ) {
            return array();
        }

        global $wpdb;

        $counts = array();

        foreach ( self::SUBSCRIPTION_STATUSES as $status ) {
            $result = wc_get_orders(
                array(
                    'type'       => 'shop_order',
                    'limit'      => 1,
                    'return'     => 'ids',
                    'paginate'   => true,
                    'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the status meta only exists on subscription parent orders; this is the intended lookup, run at most once a day behind opt-in.
                        array(
                            'key'     => Subscriptions::STATUS_META,
                            'value'   => $status,
                            'compare' => '=',
                        ),
                    ),
                )
            );

            $total = is_object( $result ) && isset( $result->total ) ? (int) $result->total : 0;

            if ( $total > 0 ) {
                $counts[ 'subscriptions_' . $status ] = $total;
            }
        }

        // Products opted into recurring billing. Products remain a CPT under
        // HPOS, so this stays a plain post-meta count.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $products = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = %s
                   AND pm.meta_value = 'yes'
                   AND p.post_type IN ( 'product', 'product_variation' )
                   AND p.post_status NOT IN ( 'trash', 'auto-draft' )",
                Subscriptions::PRODUCT_ENABLED_META
            )
        );

        if ( $products > 0 ) {
            $counts['subscription_products'] = $products;
        }

        return $counts;
    }

    /**
     * Which features are switched on, and how the plugin is configured.
     *
     * This is the half that explains the counts above — a site with zero
     * campaigns and `feature_fundraising_campaign` off is a very different
     * data point from one that enabled the feature and never used it.
     *
     * Gateways and AI report **configured or not**, never the credential.
     *
     * @return array<string, int|string>
     */
    protected static function feature_configuration() {
        $settings = DB::get_settings();

        if ( ! is_array( $settings ) ) {
            return array();
        }

        $data = array();

        // Gateways: enabled in settings, and whether the site has left test mode.
        // "Enabled but never went live" vs "live" is the single clearest signal
        // of whether an install is actually in production.
        foreach ( array( 'stripe', 'paypal', 'paystack' ) as $gateway ) {
            $data[ 'gateway_' . $gateway ]        = self::flag( $settings, 'better_payment_settings_general_general_' . $gateway );
            $data[ 'gateway_' . $gateway . '_live' ] = self::flag( $settings, 'better_payment_settings_payment_' . $gateway . '_live_mode' );
        }

        $data['feature_user_dashboard']       = self::flag( $settings, 'better_payment_settings_general_general_user_dashboard' );
        $data['feature_fundraising_campaign'] = self::flag( $settings, 'better_payment_settings_general_general_fundraising_campaign' );
        $data['feature_email']                = self::flag( $settings, 'better_payment_settings_general_general_email' );
        $data['feature_ai']                   = self::flag( $settings, 'better_payment_settings_ai_enabled' );

        // Provider slug only, and only when AI is actually on. The API key is
        // never read here.
        if ( 1 === $data['feature_ai'] && ! empty( $settings['better_payment_settings_ai_provider'] ) ) {
            $data['ai_provider'] = sanitize_key( $settings['better_payment_settings_ai_provider'] );
        }

        // Currency drives which gateways/currencies to prioritise. It is a code
        // from a fixed list, not an amount.
        if ( ! empty( $settings['better_payment_settings_general_general_currency'] ) ) {
            $data['currency'] = sanitize_text_field( $settings['better_payment_settings_general_general_currency'] );
        }

        // Subscription behaviour — only meaningful once the module is in use.
        if ( ! empty( $settings['better_payment_settings_ecommerce_subscription_renewal_process'] ) ) {
            $renewal = sanitize_key( $settings['better_payment_settings_ecommerce_subscription_renewal_process'] );
            $data['subscription_renewal_process'] = in_array( $renewal, array( 'auto', 'manual' ), true ) ? $renewal : 'auto';
        }

        $data['subscription_auto_renew_toggle'] = self::flag( $settings, 'better_payment_settings_ecommerce_subscription_auto_renewal_toggle' );
        $data['subscription_myaccount_tab']     = self::flag( $settings, 'better_payment_settings_ecommerce_subscription_myaccount_tab' );

        return $data;
    }

    /**
     * Read a settings value as a 1/0 flag.
     *
     * The settings array stores booleans as the string `'yes'` (on) and `''` or
     * `'no'` (off) — see `Admin\DB::default_settings()`.
     *
     * @param array  $settings Full settings array.
     * @param string $key      Settings key.
     * @return int
     */
    protected static function flag( $settings, $key ) {
        $value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

        return ( 'yes' === $value || '1' === $value || 1 === $value || true === $value ) ? 1 : 0;
    }

    /**
     * Block name without the `better-payment/` vendor prefix.
     *
     * @param string $block_name Full block name.
     * @return string
     */
    protected static function short_block_name( $block_name ) {
        $parts = explode( '/', $block_name );

        return end( $parts );
    }

    /**
     * A SQL-safe column alias for a block name.
     *
     * @param string $block_name Full block name.
     * @return string
     */
    protected static function block_alias( $block_name ) {
        return 'bp_' . str_replace( '-', '_', self::short_block_name( $block_name ) );
    }

    /**
     * A SQL-safe column alias for an Elementor widget name.
     *
     * @param string $widget_name Widget name.
     * @return string
     */
    protected static function widget_alias( $widget_name ) {
        return 'bp_w_' . str_replace( '-', '_', $widget_name );
    }
}
