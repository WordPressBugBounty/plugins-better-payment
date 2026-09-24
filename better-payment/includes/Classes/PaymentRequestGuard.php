<?php
/**
 * Server-side authority over what a payment request is allowed to charge.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Campaign\MetaBox;
use Better_Payment\Lite\Campaign\Services\RendererService;
use Better_Payment\Lite\Controller;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolves the amount, currency, Stripe price and campaign a payment request may use.
 *
 * Every constraint a merchant configures on a form — a fixed amount, preset amounts,
 * min/max, a product price, a subscription plan, a campaign minimum — used to exist only
 * in the browser. The handlers took `primary_payment_amount`, `campaign_currency`, the
 * Stripe price ids and `campaign_id` straight from a request that any visitor can forge
 * (the only gate is a nonce printed on the public form). This class re-derives every one
 * of them from the stored form settings, under one rule:
 *
 *     the server accepts exactly what the rendered form could have submitted — nothing else.
 *
 * Every payment entry point must call it before talking to a gateway: the widget handlers
 * (`Classes\Actions`), the block handlers (`Blocks\BlockActions`) and the Elementor Pro
 * form actions. `tests/Unit/Classes/PaymentRequestGuardWiringTest.php` fails if one stops.
 *
 * Better Payment Pro's payment forms — Payment Form layouts 4/5/6 and the recurring,
 * split-payment and mixed payment types — are not resolved by the rules in this class.
 * PaymentEntitlement refuses them outright without Pro and otherwise asks Pro (or, for
 * an older Pro, Lite's transitional copy of Pro's code) to resolve them. Those resolvers use
 * the public checks below (resolve_amount(), coupon_code(), stripe_price(),
 * product_price(), …), so a Pro form is held to exactly the same amount, price and coupon
 * rules as a free one.
 *
 * @since 2.3.4
 */
class PaymentRequestGuard {

    /**
     * Two amounts closer than this are the same amount (half a minor unit).
     */
    const TOLERANCE = 0.005;

    /**
     * Stripe price lookups made during this request, keyed by price id.
     *
     * @var array<string, float|null>
     */
    private $stripe_prices = [];

    /**
     * Carrier for the Traits\Helper lookups (Stripe prices, Elementor widget settings).
     *
     * @var Controller|null
     */
    private $helper = null;

    /**
     * @return Controller
     */
    private function helper() {
        if ( null === $this->helper ) {
            $this->helper = new Controller();
        }

        return $this->helper;
    }

    /**
     * Resolve a widget or block submission.
     *
     * A Pro payment form (a Pro layout, PaymentEntitlement::is_pro_form()) is refused on a free
     * install before anything else is looked up, and otherwise resolved through
     * PaymentEntitlement::resolve_form_payment() — which fails closed when nothing resolves it.
     * A request posting a field only a Pro form renders is refused on a free install too, and
     * on every install when its form is not a Pro layout. A free layout is a one-time payment,
     * whatever payment type its saved settings still hold.
     *
     * @param array $settings Stored widget/block settings — never request data.
     * @param array $fields   Submitted fields: the AJAX `fields` array, or `$_POST` for PayPal. Unslashed.
     * @param array $context  `page_id` (int) of the form, `gateway` ('stripe'|'paypal'|'paystack').
     *
     * @return array|WP_Error {
     *     @type float      $amount               Total to charge (unit amount × quantity).
     *     @type float      $unit_amount          Validated amount per unit.
     *     @type int|string $quantity             Submitted quantity, or '' when none was sent.
     *     @type int[]      $product_quantities   Per-product quantities of a product-list form, keyed like its product id setting.
     *     @type string     $currency             Currency to charge in.
     *     @type string     $campaign_id          Verified campaign id, or '' (never an unverified request value).
     *     @type string     $coupon_code          Coupon, only when the form has a visible coupon field.
     *     @type bool       $is_recurring         Whether this is a Stripe subscription checkout (Pro forms only).
     *     @type string     $recurring_price_id   Allowed recurring price id (Pro forms only).
     *     @type string     $installment_price_id Allowed split-payment installment price id (Pro forms only).
     * }
     */
    public function resolve_form_payment( array $settings, array $fields, array $context ) {
        $page_id     = isset( $context['page_id'] ) ? absint( $context['page_id'] ) : 0;
        $gateway     = isset( $context['gateway'] ) ? sanitize_key( $context['gateway'] ) : '';
        $is_pro_form     = PaymentEntitlement::is_pro_form( $settings );
        $posts_pro_field = PaymentEntitlement::posts_pro_fields( $fields );

        // Before anything else is read: a free install runs none of a Pro payment.
        if ( ( $is_pro_form || $posts_pro_field ) && ! ProCompatibility::pro_enabled() ) {
            return PaymentEntitlement::refusal();
        }

        // A free layout never renders a plan, an installment or a mixed choice, whatever payment
        // type its saved settings still hold. Such a request is forged, and never reaches Pro.
        if ( ! $is_pro_form && $posts_pro_field ) {
            return self::invalid_price();
        }

        $campaign = $this->resolve_campaign( self::field( $fields, 'campaign_id' ), $page_id );

        $resolved = [
            'amount'               => 0.0,
            'unit_amount'          => 0.0,
            'quantity'             => '',
            'product_quantities'   => [],
            'currency'             => null !== $campaign && '' !== $campaign['currency'] ? $campaign['currency'] : self::form_currency( $settings ),
            'campaign_id'          => null !== $campaign ? $campaign['id'] : '',
            'coupon_code'          => '',
            'is_recurring'         => false,
            'recurring_price_id'   => '',
            'installment_price_id' => '',
        ];

        if ( $is_pro_form ) {
            return PaymentEntitlement::resolve_form_payment(
                $this,
                $resolved,
                $settings,
                $fields,
                [
                    'page_id'  => $page_id,
                    'gateway'  => $gateway,
                    'campaign' => $campaign,
                ]
            );
        }

        $form_fields             = self::repeater( $settings, 'better_payment_form_fields' );
        $resolved['coupon_code'] = self::coupon_code( $form_fields, $fields );

        $amount = $this->resolve_amount(
            $settings,
            $fields,
            $form_fields,
            self::preset_amounts( $settings, 'better_payment_show_amount_list', 'better_payment_amount' ),
            $campaign
        );

        if ( is_wp_error( $amount ) ) {
            return $amount;
        }

        return array_merge( $resolved, $amount );
    }

    /**
     * Resolve an Elementor Pro Forms submission (Surface A).
     *
     * @param array $form_settings `$record->get( 'form_settings' )`.
     * @param array $sent_data     `$record->get( 'sent_data' )`.
     *
     * @return array|WP_Error `amount` (total), `unit_amount`, `quantity` (int ≥ 1), `campaign_id`.
     */
    public function resolve_elementor_form_payment( array $form_settings, array $sent_data ) {
        $page_id = isset( $form_settings['form_post_id'] ) ? absint( $form_settings['form_post_id'] ) : 0;

        // Elementor Pro resolves the form from a post id the request names, published or not.
        if ( ! $this->helper()->is_payable_form_page( $page_id ) ) {
            return self::form_unavailable();
        }

        $amount = self::posted_amount( $sent_data, 'payment_amount' );

        if ( $amount <= 0 || ! self::elementor_form_allows( $form_settings, $amount ) ) {
            return self::invalid_amount();
        }

        $raw_quantity = isset( $sent_data['pay_quantity'] ) ? $sent_data['pay_quantity'] : '';
        if ( is_array( $raw_quantity ) ) {
            $raw_quantity = isset( $raw_quantity['value'] ) ? $raw_quantity['value'] : '';
        }

        $quantity = 1;
        if ( ! empty( $raw_quantity ) ) {
            $quantity = intval( $raw_quantity );
            if ( $quantity < 1 ) {
                return self::invalid_quantity();
            }
        }

        $campaign = $this->resolve_campaign( self::field( $sent_data, 'campaign_id' ), $page_id );

        return [
            'amount'      => $amount * $quantity,
            'unit_amount' => $amount,
            'quantity'    => $quantity,
            'campaign_id' => null !== $campaign ? $campaign['id'] : '',
        ];
    }

    /**
     * Resolve a submitted campaign id to a campaign that really links to the form page.
     *
     * A campaign relaxes the form's amount rules (the donor picks the amount on the
     * campaign page), so an unverified id must never be honoured: anyone could post
     * `campaign_id=1` to escape a fixed price. A campaign counts only when it exists, is
     * live, and its donate link points at `$page_id`.
     *
     * @param string $campaign_id Submitted id: a `bp_campaign` post id, or an Elementor
     *                            Fundraising Campaign id `bp_campaign_{page}_{postfix}`.
     * @param int    $page_id     The form's page.
     *
     * @return array|null `id`, `currency` ('' = use the form's), `minimum`, `amounts` (float[]|null = any).
     */
    public function resolve_campaign( $campaign_id, $page_id ) {
        $campaign_id = sanitize_text_field( (string) $campaign_id );
        $page_id     = absint( $page_id );

        if ( '' === $campaign_id || 0 === $page_id ) {
            return null;
        }

        if ( ctype_digit( $campaign_id ) ) {
            return self::resolve_builder_campaign( (int) $campaign_id, $page_id );
        }

        if ( preg_match( '/^bp_campaign_(\d+)_.+$/', $campaign_id, $matches ) ) {
            return $this->resolve_widget_campaign( $campaign_id, (int) $matches[1], $page_id );
        }

        return null;
    }

    /**
     * End a plain form POST (the PayPal admin-post handlers) that the guard refused.
     *
     * The AJAX handlers answer with wp_send_json_error(); a PayPal submission is a full
     * page navigation, so it needs a page the visitor can read and go back from.
     *
     * @param WP_Error $error The refusal.
     * @return void
     */
    public static function reject_form_post( WP_Error $error ) {
        wp_die(
            esc_html( $error->get_error_message() ),
            esc_html__( 'Payment not accepted', 'better-payment' ),
            [
                'response'  => 400,
                'back_link' => true,
            ]
        );
    }

    /**
     * The currency a widget/block form charges in, from its settings alone.
     *
     * @param array $settings Stored form settings.
     * @return string
     */
    public static function form_currency( array $settings ) {
        $currency = self::setting( $settings, 'better_payment_form_currency', '' );

        if ( 'yes' === self::setting( $settings, 'better_payment_form_currency_use_woocommerce', '' ) ) {
            $woocommerce_currency = self::setting( $settings, 'better_payment_form_currency_woocommerce', '' );
            if ( '' !== $woocommerce_currency ) {
                $currency = $woocommerce_currency;
            }
        }

        return $currency;
    }

    // ------------------------------------------------------------------ one-time amounts

    /**
     * A one-time amount: a product-priced form, a verified campaign donation, or the amount field's rules.
     *
     * Every one-time form goes through this, Pro's included — Pro's resolver passes the
     * fields repeater and preset amounts its layout renders.
     *
     * @param array      $settings    Form settings.
     * @param array      $fields      Submitted fields.
     * @param array      $form_fields The fields repeater the form renders.
     * @param float[]    $presets     Preset amounts the form renders as radios (preset_amounts()).
     * @param array|null $campaign    Verified campaign (resolve_campaign()), or null.
     * @return array|WP_Error `amount`, `unit_amount`, `quantity`, `product_quantities`.
     */
    public function resolve_amount( array $settings, array $fields, array $form_fields, array $presets, $campaign = null ) {
        $posted   = self::posted_amount( $fields, 'primary_payment_amount' );
        $quantity = self::posted_quantity( $fields );

        if ( is_wp_error( $quantity ) ) {
            return $quantity;
        }

        // The partial layout-vars.php applies the payment source to every layout, and
        // hides the amount input whenever a source prices the form — so the price is
        // not a default the visitor can edit, it is the amount.
        $source = self::setting( $settings, 'better_payment_form_payment_source', 'manual' );

        if ( in_array( $source, [ 'woocommerce', 'fluentcart', 'stripe' ], true ) ) {
            $price = $this->source_price( $settings, $source );

            if ( null === $price ) {
                return self::price_unavailable();
            }

            if ( $price <= 0 || ! self::same_amount( $posted, $price ) ) {
                return self::invalid_amount();
            }

            $posted = $price;
        } elseif ( null !== $campaign ) {
            if ( ! self::campaign_allows( $campaign, $posted ) ) {
                return self::invalid_amount();
            }
        } elseif ( ! self::amount_field_allows( $form_fields, $presets, $posted ) ) {
            return self::invalid_amount();
        }

        if ( $posted <= 0 ) {
            return self::invalid_amount();
        }

        return [
            'amount'             => '' === $quantity ? $posted : $posted * $quantity,
            'unit_amount'        => $posted,
            'quantity'           => $quantity,
            'product_quantities' => [],
        ];
    }

    /**
     * Whether an amount is one the form's amount field could have submitted.
     *
     * Mirrors partials/layout-repeater-vars.php and the layouts that include it:
     * - a preset amount (radio) is always allowed — clicking one writes it into the input,
     *   even a readonly one;
     * - a dynamic amount comes from the URL, so only min/max apply;
     * - a fixed or hidden field can only submit its configured default;
     * - otherwise the visitor types it, within min (default 1, as rendered) and max.
     *
     * @param array   $form_fields The fields repeater the form renders.
     * @param float[] $presets     Preset amounts the form renders.
     * @param float   $amount      Submitted amount.
     * @return bool
     */
    private static function amount_field_allows( array $form_fields, array $presets, $amount ) {
        if ( self::amount_in( $amount, $presets ) ) {
            return true;
        }

        $field = null;
        foreach ( $form_fields as $item ) {
            if ( is_array( $item ) && 'primary_payment_amount' === ( isset( $item['better_payment_primary_field_type'] ) ? $item['better_payment_primary_field_type'] : '' ) ) {
                $field = $item;
                break;
            }
        }

        if ( null === $field ) {
            return false;
        }

        $is_dynamic = 'yes' === ( isset( $field['better_payment_field_name_default_dynamic_enable'] ) ? $field['better_payment_field_name_default_dynamic_enable'] : '' );
        $is_fixed   = ! empty( $field['better_payment_field_name_default_fixed'] );

        if ( ! $is_dynamic && ( $is_fixed || ! self::is_visible( $field ) ) ) {
            return self::matches_default( $amount, isset( $field['better_payment_field_name_default'] ) ? $field['better_payment_field_name_default'] : '' );
        }

        $min = ! empty( $field['better_payment_field_name_min'] ) ? (float) $field['better_payment_field_name_min'] : 1.0;
        $max = isset( $field['better_payment_field_name_max'] ) ? $field['better_payment_field_name_max'] : '';

        return self::within_bounds( $amount, $min, $max );
    }

    /**
     * Whether an Elementor Pro Forms submission carries an amount its fields allow.
     *
     * @param array $form_settings Form settings.
     * @param float $amount        Submitted amount.
     * @return bool
     */
    private static function elementor_form_allows( array $form_settings, $amount ) {
        $form_fields = isset( $form_settings['form_fields'] ) && is_array( $form_settings['form_fields'] ) ? $form_settings['form_fields'] : [];

        foreach ( $form_fields as $field ) {
            if ( ! is_array( $field ) || 'payment_amount' !== ( isset( $field['field_type'] ) ? $field['field_type'] : '' ) ) {
                continue;
            }

            // Payment_Amount_Field renders nothing at all while the integration is off.
            if ( 'yes' !== ( isset( $form_settings['better_payment_payment_amount_enable'] ) ? $form_settings['better_payment_payment_amount_enable'] : '' ) ) {
                return false;
            }

            $field_type = isset( $field['bp_field_type'] ) ? $field['bp_field_type'] : '';
            $list_on    = 'yes' === ( isset( $form_settings['better_payment_show_amount_list_enable'] ) ? $form_settings['better_payment_show_amount_list_enable'] : '' )
                && in_array( $field_type, [ 'amount-list', 'both' ], true );

            if ( $list_on ) {
                $presets = [];
                foreach ( (array) ( isset( $form_settings['better_payment_show_amount_list_items'] ) ? $form_settings['better_payment_show_amount_list_items'] : [] ) as $item ) {
                    $presets[] = floatval( is_array( $item ) && isset( $item['better_payment_amount_val'] ) ? $item['better_payment_amount_val'] : 0 );
                }

                if ( self::amount_in( $amount, $presets ) ) {
                    return true;
                }
            }

            if ( ! in_array( $field_type, [ 'input-field', 'both' ], true ) ) {
                return false;
            }

            if ( ! empty( $field['bp_field_default_fixed'] ) ) {
                return self::matches_default( $amount, isset( $field['bp_field_default'] ) ? $field['bp_field_default'] : '' );
            }

            $min = ! empty( $field['bp_field_min'] ) ? (float) $field['bp_field_min'] : 0.0;

            return self::within_bounds( $amount, $min, isset( $field['bp_field_max'] ) ? $field['bp_field_max'] : '' );
        }

        // No Better Payment amount field: a plain Elementor field with the ID `payment_amount`.
        foreach ( $form_fields as $field ) {
            if ( ! is_array( $field ) || 'payment_amount' !== ( isset( $field['custom_id'] ) ? $field['custom_id'] : '' ) ) {
                continue;
            }

            $field_type = isset( $field['field_type'] ) ? $field['field_type'] : 'text';

            switch ( $field_type ) {
                case 'number':
                    $min = isset( $field['field_min'] ) && '' !== $field['field_min'] ? (float) $field['field_min'] : 0.0;
                    return self::within_bounds( $amount, $min, isset( $field['field_max'] ) ? $field['field_max'] : '' );

                case 'hidden':
                    return self::matches_default( $amount, isset( $field['field_value'] ) ? $field['field_value'] : '' );

                case 'select':
                case 'radio':
                    return self::amount_in( $amount, self::elementor_option_values( isset( $field['field_options'] ) ? $field['field_options'] : '' ) );

                default:
                    return true;
            }
        }

        return false;
    }

    // ------------------------------------------------------------------ campaigns

    /**
     * A Campaign Builder campaign (`bp_campaign` post).
     *
     * @param int $post_id Campaign post id.
     * @param int $page_id Form page id.
     * @return array|null
     */
    private static function resolve_builder_campaign( $post_id, $page_id ) {
        $post = get_post( $post_id );

        if ( ! $post || 'bp_campaign' !== $post->post_type || 'publish' !== $post->post_status ) {
            return null;
        }

        $meta   = MetaBox::get_all( $post_id );
        $layout = RendererService::normalize_layout( isset( $meta['bpc_fields_layout'] ) ? $meta['bpc_fields_layout'] : [] );

        $targets_page = (int) ( isset( $meta['bpc_form_page_id'] ) ? $meta['bpc_form_page_id'] : 0 ) === $page_id;
        $presets      = [];

        foreach ( (array) ( isset( $layout['columns'] ) ? $layout['columns'] : [] ) as $column ) {
            foreach ( (array) ( is_array( $column ) && isset( $column['elements'] ) ? $column['elements'] : [] ) as $element ) {
                $type     = is_array( $element ) && isset( $element['type'] ) ? $element['type'] : '';
                $settings = is_array( $element ) && isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [];

                if ( 'donation_form' === $type && ! empty( $settings['url'] ) && self::url_points_to_page( $settings['url'], $page_id ) ) {
                    $targets_page = true;
                }

                if ( 'donate_amount' === $type ) {
                    $fallback = ! empty( $settings['preset_amounts'] ) ? $settings['preset_amounts'] : '10,25,50,100';
                    foreach ( array_filter( array_map( 'trim', explode( ',', (string) $fallback ) ) ) as $preset ) {
                        $presets[] = floatval( $preset );
                    }
                }
            }
        }

        if ( ! $targets_page ) {
            return null;
        }

        $amounts = null;

        // Same reading as RendererService's donate_amount element: without a custom
        // amount input, the donor can only pick one of the rendered amounts.
        if ( ! (bool) ( isset( $meta['bpc_allow_custom_amount'] ) ? $meta['bpc_allow_custom_amount'] : 1 ) ) {
            $amounts = [];
            foreach ( (array) ( isset( $meta['bpc_suggested_amounts'] ) ? $meta['bpc_suggested_amounts'] : [] ) as $item ) {
                $amounts[] = floatval( is_array( $item ) && isset( $item['amount'] ) ? $item['amount'] : 0 );
            }

            if ( empty( $amounts ) ) {
                $amounts = $presets;
            }
        }

        $currency = DB::get_settings( 'better_payment_settings_general_general_currency' );

        return [
            'id'       => (string) $post_id,
            'currency' => is_string( $currency ) && '' !== $currency ? sanitize_text_field( $currency ) : 'USD',
            'minimum'  => isset( $meta['bpc_minimum_amount'] ) && '' !== $meta['bpc_minimum_amount'] ? (float) $meta['bpc_minimum_amount'] : 0.0,
            'amounts'  => $amounts,
        ];
    }

    /**
     * An Elementor Fundraising Campaign widget, via the `better_payment_campaigns` registry it writes on render.
     *
     * @param string $campaign_id      Full id `bp_campaign_{page}_{postfix}`.
     * @param int    $campaign_page_id Page the campaign widget lives on.
     * @param int    $page_id          Form page id.
     * @return array|null
     */
    private function resolve_widget_campaign( $campaign_id, $campaign_page_id, $page_id ) {
        $registry = get_option( 'better_payment_campaigns', [] );

        if ( ! is_array( $registry ) ) {
            return null;
        }

        foreach ( $registry as $widget_id => $entry ) {
            $data = is_string( $entry ) ? json_decode( $entry, true ) : $entry;

            if ( ! is_array( $data )
                || (string) ( isset( $data['campaign_id'] ) ? $data['campaign_id'] : '' ) !== $campaign_id
                || (int) ( isset( $data['page_id'] ) ? $data['page_id'] : 0 ) !== $campaign_page_id ) {
                continue;
            }

            // A campaign on a page visitors cannot see is not a live campaign.
            if ( ! $this->helper()->is_payable_form_page( $campaign_page_id ) ) {
                return null;
            }

            $settings = $this->helper()->get_elementor_widget_settings( $campaign_page_id, (string) $widget_id );
            $link     = isset( $settings['better_payment_campaign_form_button_link']['url'] ) ? $settings['better_payment_campaign_form_button_link']['url'] : '';

            if ( ! self::url_points_to_page( $link, $page_id ) ) {
                return null;
            }

            return [
                'id'       => $campaign_id,
                // An empty campaign currency is never posted by fundraising-campaign.js, so the form's applies.
                'currency' => isset( $settings['better_payment_campaign_currency'] ) ? sanitize_text_field( $settings['better_payment_campaign_currency'] ) : '',
                // The widget's custom amount input is rendered with min="1".
                'minimum'  => 1.0,
                'amounts'  => null,
            ];
        }

        return null;
    }

    /**
     * Whether a verified campaign allows the donated amount.
     *
     * @param array $campaign Resolved campaign.
     * @param float $amount   Submitted amount.
     * @return bool
     */
    private static function campaign_allows( array $campaign, $amount ) {
        if ( $amount + self::TOLERANCE < $campaign['minimum'] ) {
            return false;
        }

        return null === $campaign['amounts'] || self::amount_in( $amount, $campaign['amounts'] );
    }

    /**
     * Whether a link resolves to the given page.
     *
     * @param mixed $url     Link.
     * @param int   $page_id Page id.
     * @return bool
     */
    private static function url_points_to_page( $url, $page_id ) {
        $url = is_string( $url ) ? trim( $url ) : '';

        if ( '' === $url || '#' === $url[0] ) {
            return false;
        }

        $resolved = url_to_postid( $url );
        if ( $resolved > 0 ) {
            return $resolved === $page_id;
        }

        // url_to_postid() cannot resolve a static front page.
        if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $page_id ) {
            $path      = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
            $home_path = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );

            return $path === $home_path;
        }

        return false;
    }

    // ------------------------------------------------------------------ prices

    /**
     * The price a payment source charges.
     *
     * @param array  $settings Form settings.
     * @param string $source   'woocommerce' | 'fluentcart' | 'stripe'.
     * @return float|null Null when it cannot be determined.
     */
    private function source_price( array $settings, $source ) {
        if ( 'stripe' === $source ) {
            return $this->stripe_price_amount(
                self::setting( $settings, 'better_payment_form_payment_source_stripe_price_id', '' ),
                self::stripe_secret_key( $settings )
            );
        }

        $product_id = absint( isset( $settings[ "better_payment_form_{$source}_product_id" ] ) ? $settings[ "better_payment_form_{$source}_product_id" ] : 0 );

        return $product_id ? $this->product_price( $source, $product_id ) : null;
    }

    /**
     * Unit amount (major units) of a Stripe price, read with the form's own Stripe secret key.
     *
     * Looked up once per request. Null when it cannot be read — refuse the payment then; it
     * is never charged at the visitor's amount.
     *
     * @param mixed $price_id Stripe price id; anything but a scalar reads as none.
     * @param array $settings Form settings (they select the live or test secret key).
     * @return float|null
     */
    public function stripe_price( $price_id, array $settings ) {
        return $this->stripe_price_amount( is_scalar( $price_id ) ? (string) $price_id : '', self::stripe_secret_key( $settings ) );
    }

    /**
     * Current price of a WooCommerce or FluentCart product, read the way the form renders it.
     *
     * @param string $platform   'woocommerce' | 'fluentcart'.
     * @param int    $product_id Product id.
     * @return float|null
     */
    public function product_price( $platform, $product_id ) {
        $price = null;

        if ( 'woocommerce' === $platform && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $price = floatval( $product->get_price() );
            }
        } elseif ( 'fluentcart' === $platform && class_exists( '\FluentCart\App\Models\Product' ) ) {
            try {
                $product = \FluentCart\App\Models\Product::query()->with( [ 'detail', 'variants' ] )->find( $product_id );
                if ( $product ) {
                    $minor = 0;
                    if ( ! empty( $product->variants ) && count( $product->variants ) > 0 ) {
                        $minor = $product->variants[0]->item_price;
                    } elseif ( ! empty( $product->detail ) ) {
                        $minor = $product->detail->min_price;
                    }
                    $price = floatval( $minor ) / 100;
                }
            } catch ( \Exception $e ) {
                $price = null;
            }
        }

        /**
         * Filters the server-side price of a product a Better Payment form sells.
         *
         * Return null when the price cannot be determined; the payment is then refused.
         *
         * @since 2.3.4
         *
         * @param float|null $price      Price in major units, or null.
         * @param string     $platform   'woocommerce' | 'fluentcart'.
         * @param int        $product_id Product id.
         */
        $price = apply_filters( 'better_payment/payment_guard/product_price', $price, $platform, $product_id );

        return is_numeric( $price ) ? (float) $price : null;
    }

    /**
     * Unit amount (major units) of a Stripe price.
     *
     * @param string $price_id   Stripe price id.
     * @param string $secret_key Stripe secret key.
     * @return float|null
     */
    private function stripe_price_amount( $price_id, $secret_key ) {
        if ( '' === $price_id || '' === $secret_key ) {
            return null;
        }

        if ( ! array_key_exists( $price_id, $this->stripe_prices ) ) {
            $details = $this->helper()->get_stripe_price_details( $price_id, $secret_key );

            $this->stripe_prices[ $price_id ] = isset( $details['unit_amount'] ) && is_numeric( $details['unit_amount'] )
                ? floatval( $details['unit_amount'] ) / 100
                : null;
        }

        return $this->stripe_prices[ $price_id ];
    }

    /**
     * The Stripe secret key a form uses.
     *
     * @param array $settings Form settings.
     * @return string
     */
    private static function stripe_secret_key( array $settings ) {
        $is_live = 'yes' === self::setting( $settings, 'better_payment_stripe_live_mode', '' );

        return self::setting( $settings, $is_live ? 'better_payment_stripe_secret_key_live' : 'better_payment_stripe_secret_key', '' );
    }

    // ------------------------------------------------------------------ form configuration

    /**
     * A repeater setting (a list of items), or [] when it is unset or not a list.
     *
     * @param array  $settings Form settings.
     * @param string $key      Setting key, e.g. `better_payment_form_fields`.
     * @return array
     */
    public static function repeater( array $settings, $key ) {
        return isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ? $settings[ $key ] : [];
    }

    /**
     * Preset amounts a form renders as radios: the items of `$items_key`, while `$toggle_key` is on.
     *
     * @param array  $settings   Form settings.
     * @param string $toggle_key Show-amount-list switch, e.g. `better_payment_show_amount_list`.
     * @param string $items_key  Amount list repeater, e.g. `better_payment_amount`.
     * @return float[]
     */
    public static function preset_amounts( array $settings, $toggle_key, $items_key ) {
        if ( 'yes' !== self::setting( $settings, $toggle_key, '' ) || empty( $settings[ $items_key ] ) || ! is_array( $settings[ $items_key ] ) ) {
            return [];
        }

        $amounts = [];
        foreach ( $settings[ $items_key ] as $item ) {
            if ( is_array( $item ) && isset( $item['better_payment_amount_val'] ) ) {
                $amounts[] = floatval( $item['better_payment_amount_val'] );
            }
        }

        return $amounts;
    }

    /**
     * A coupon code, only when the form actually offers a visible coupon field.
     *
     * @param array $form_fields The fields repeater the form renders.
     * @param array $fields      Submitted fields.
     * @return string
     */
    public static function coupon_code( array $form_fields, array $fields ) {
        $code = self::field( $fields, 'primary_coupon_code' );

        if ( '' === $code ) {
            return '';
        }

        foreach ( $form_fields as $item ) {
            if ( is_array( $item )
                && 'primary_coupon_code' === ( isset( $item['better_payment_primary_field_type'] ) ? $item['better_payment_primary_field_type'] : '' )
                && self::is_visible( $item ) ) {
                return $code;
            }
        }

        return '';
    }

    /**
     * The Stripe discount a typed coupon code may apply.
     *
     * Checkout used to attach whatever was typed as `discounts[coupon]`, and a Stripe coupon
     * id is a shared secret at best — any coupon in the merchant's account applied, internal
     * 100%-off ones included. Two things are accepted now:
     * - an active Stripe **promotion code** matching the text (Stripe's customer-facing layer,
     *   which carries its own expiry, redemption limits and restrictions);
     * - a raw **coupon id**, only when the merchant tagged that coupon in Stripe with the
     *   metadata `better_payment_public = yes`.
     * Anything else refuses the checkout rather than silently charging full price for a code
     * the donor believes applied.
     *
     * Call this only with a code coupon_code() has let through.
     *
     * @since 2.3.4
     *
     * @param string $code       Coupon code from resolve_form_payment().
     * @param string $secret_key Stripe secret key of the form.
     * @return array|WP_Error `[ 'promotion_code' => id ]`, `[ 'coupon' => id ]`, `[]` when no code was typed.
     */
    public function resolve_stripe_discount( $code, $secret_key ) {
        $code       = trim( (string) $code );
        $secret_key = trim( (string) $secret_key );

        if ( '' === $code ) {
            return [];
        }

        if ( '' === $secret_key ) {
            return self::invalid_coupon();
        }

        $promotion = $this->stripe_get(
            'promotion_codes',
            [
                'code'   => $code,
                'active' => 'true',
                'limit'  => 1,
            ],
            $secret_key
        );

        if ( isset( $promotion['data'][0]['id'] ) && is_string( $promotion['data'][0]['id'] ) ) {
            return [ 'promotion_code' => sanitize_text_field( $promotion['data'][0]['id'] ) ];
        }

        $coupon    = $this->stripe_get( 'coupons/' . rawurlencode( $code ), [], $secret_key );
        $is_public = isset( $coupon['metadata']['better_payment_public'] )
            && is_string( $coupon['metadata']['better_payment_public'] )
            && 'yes' === strtolower( trim( $coupon['metadata']['better_payment_public'] ) );

        if ( $is_public && isset( $coupon['id'] ) && is_string( $coupon['id'] ) && ! empty( $coupon['valid'] ) ) {
            return [ 'coupon' => sanitize_text_field( $coupon['id'] ) ];
        }

        return self::invalid_coupon();
    }

    /**
     * GET a Stripe API resource; [] on any failure.
     *
     * @param string $path       Path under /v1/.
     * @param array  $query      Query arguments.
     * @param string $secret_key Secret key.
     * @return array
     */
    private function stripe_get( $path, array $query, $secret_key ) {
        $response = wp_safe_remote_get(
            'https://api.stripe.com/v1/' . $path . ( $query ? '?' . http_build_query( $query ) : '' ),
            [
                'headers' => [ 'Authorization' => 'Bearer ' . $secret_key ],
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return [];
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return is_array( $data ) ? $data : [];
    }

    /**
     * Whether a fields-repeater item is shown (the Elementor control defaults to 'yes').
     *
     * @param array $item Repeater item.
     * @return bool
     */
    private static function is_visible( array $item ) {
        return ! isset( $item['better_payment_field_name_show'] ) || 'yes' === $item['better_payment_field_name_show'];
    }

    /**
     * Values of an Elementor select/radio field's options (`label|value` per line).
     *
     * @param string $options Raw options.
     * @return float[]
     */
    private static function elementor_option_values( $options ) {
        $values = [];

        foreach ( preg_split( '/\r\n|\r|\n/', (string) $options ) as $line ) {
            $line = trim( $line );
            if ( '' === $line ) {
                continue;
            }
            $parts    = explode( '|', $line );
            $values[] = floatval( trim( end( $parts ) ) );
        }

        return $values;
    }

    // ------------------------------------------------------------------ request + comparison helpers

    /**
     * The submitted amount: the amount input, falling back to the preset radio (as every handler reads it).
     *
     * @param array  $fields Submitted fields.
     * @param string $key    Amount input name.
     * @return float
     */
    public static function posted_amount( array $fields, $key ) {
        $amount = self::field( $fields, $key );

        if ( '' === $amount || 0.0 === floatval( $amount ) ) {
            $amount = self::field( $fields, 'primary_payment_amount_radio' );
        }

        $amount = floatval( $amount );

        return is_finite( $amount ) ? $amount : 0.0;
    }

    /**
     * The submitted quantity: '' when absent, otherwise a whole number of at least 1.
     *
     * @param array $fields Submitted fields.
     * @return int|string|WP_Error
     */
    private static function posted_quantity( array $fields ) {
        $raw = self::field( $fields, 'payment_amount_quantity' );

        if ( empty( $raw ) ) {
            return '';
        }

        $quantity = intval( $raw );

        return $quantity < 1 ? self::invalid_quantity() : $quantity;
    }

    /**
     * A submitted scalar field (arrays yield their first value), sanitized.
     *
     * @param array  $fields Submitted fields.
     * @param string $key    Field name.
     * @return string
     */
    public static function field( array $fields, $key ) {
        if ( ! isset( $fields[ $key ] ) ) {
            return '';
        }

        $value = $fields[ $key ];
        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return is_scalar( $value ) ? trim( sanitize_text_field( (string) $value ) ) : '';
    }

    /**
     * A stored setting as a sanitized string.
     *
     * @param array  $settings Settings.
     * @param string $key      Key.
     * @param string $fallback Value when unset or empty.
     * @return string
     */
    public static function setting( array $settings, $key, $fallback ) {
        return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) && '' !== (string) $settings[ $key ]
            ? sanitize_text_field( (string) $settings[ $key ] )
            : $fallback;
    }

    /**
     * Whether an amount equals a configured default.
     *
     * The layouts render the default through intval() while Payment_Amount_Field renders it
     * raw, so both readings of the same setting are accepted.
     *
     * @param float $amount  Submitted amount.
     * @param mixed $default Configured default.
     * @return bool
     */
    private static function matches_default( $amount, $default ) {
        if ( ! is_scalar( $default ) || '' === trim( (string) $default ) ) {
            return false;
        }

        return self::amount_in( $amount, [ floatval( $default ), (float) intval( $default ) ] );
    }

    /**
     * Whether an amount lies within [min, max] (an empty max is unbounded).
     *
     * @param float $amount Amount.
     * @param float $min    Minimum.
     * @param mixed $max    Maximum, '' for none.
     * @return bool
     */
    private static function within_bounds( $amount, $min, $max ) {
        if ( $amount <= 0 || $amount + self::TOLERANCE < $min ) {
            return false;
        }

        return ! is_numeric( $max ) || 0.0 === (float) $max || $amount <= (float) $max + self::TOLERANCE;
    }

    /**
     * Whether an amount is one of a list.
     *
     * @param float   $amount  Amount.
     * @param float[] $amounts Allowed amounts.
     * @return bool
     */
    private static function amount_in( $amount, array $amounts ) {
        foreach ( $amounts as $allowed ) {
            if ( $allowed > 0 && self::same_amount( $amount, $allowed ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether two amounts are equal to within half a minor unit.
     *
     * @param float $a Amount.
     * @param float $b Amount.
     * @return bool
     */
    public static function same_amount( $a, $b ) {
        return abs( (float) $a - (float) $b ) < self::TOLERANCE;
    }

    /**
     * @return WP_Error
     */
    public static function invalid_amount() {
        return new WP_Error( 'bp_invalid_amount', __( 'The payment amount is not allowed for this form.', 'better-payment' ) );
    }

    /**
     * @return WP_Error
     */
    public static function invalid_quantity() {
        return new WP_Error( 'bp_invalid_quantity', __( 'The quantity must be a whole number of at least 1.', 'better-payment' ) );
    }

    /**
     * @return WP_Error
     */
    public static function invalid_price() {
        return new WP_Error( 'bp_invalid_price', __( 'The selected payment plan is not available for this form.', 'better-payment' ) );
    }

    /**
     * @return WP_Error
     */
    private static function invalid_coupon() {
        return new WP_Error( 'bp_invalid_coupon', __( 'This coupon code is not valid.', 'better-payment' ) );
    }

    /**
     * @return WP_Error
     */
    private static function form_unavailable() {
        return new WP_Error( 'bp_form_unavailable', __( 'This payment form is not available.', 'better-payment' ) );
    }

    /**
     * @return WP_Error
     */
    public static function price_unavailable() {
        return new WP_Error( 'bp_price_unavailable', __( 'The price for this payment could not be verified. Please try again later.', 'better-payment' ) );
    }
}
