<?php

namespace Better_Payment\Lite\WooCommerce\Emails;

use Better_Payment\Lite\WooCommerce\Emails;
use Better_Payment\Lite\WooCommerce\Subscriptions;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared base for the Better Payment subscription lifecycle emails
 * — template plumbing, the notification-hook trigger, and the template
 * data both emails render.
 *
 * Subclasses set $id / $title / $description and the default
 * subject/heading copy, then call this constructor. Everything else —
 * enable toggle, subject, heading, additional content, HTML/plain type —
 * is the stock WC_Email settings UI under WooCommerce → Settings → Emails.
 *
 * This class extends WC_Email, so it must ONLY be loaded from the
 * `woocommerce_email_classes` filter (see Emails::register_email_classes());
 * the rest of the module never references it.
 *
 * @since 2.4.0
 */
abstract class SubscriptionEmail extends \WC_Email {

    /**
     * Bind template paths and the notification hook. Subclasses have
     * already set $this->id when this runs.
     */
    public function __construct() {
        $this->customer_email = true;
        $this->template_base  = Emails::template_base();
        $this->template_html  = Emails::template_html( $this->id );
        $this->template_plain = Emails::template_plain( $this->id );
        $this->placeholders   = array(
            '{order_number}' => '',
        );

        add_action( $this->notification_hook(), array( $this, 'trigger' ) );

        parent::__construct();
    }

    /**
     * The notification hook Emails::notify() fires for this email.
     *
     * @return string
     */
    abstract protected function notification_hook();

    /**
     * Send the email for a subscription order. The lifecycle guards
     * (delayed send, status re-check) already ran in the Emails registrar —
     * this only checks the WC-level toggles (enabled + recipient).
     *
     * @param mixed $order_id Parent (subscription) order id.
     * @return void
     */
    public function trigger( $order_id ) {
        $this->setup_locale();

        $order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( $order_id ) ) : false;

        if ( $order instanceof \WC_Order ) {
            $this->object                         = $order;
            $this->recipient                      = $order->get_billing_email();
            $this->placeholders['{order_number}'] = $order->get_order_number();
        }

        if ( $this->object instanceof \WC_Order && $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    /**
     * Get the HTML email content.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html( $this->template_html, $this->template_args( false ), 'better-payment', $this->template_base );
    }

    /**
     * Get the plain-text email content.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, $this->template_args( true ), 'better-payment', $this->template_base );
    }

    /**
     * The data both templates receive. Schedule + item names come from the
     * parent order's activation snapshot, so a later product edit never
     * changes what the email reports.
     *
     * @param bool $plain_text Rendering the plain-text variant.
     * @return array
     */
    protected function template_args( $plain_text ) {
        $order = $this->object;

        $item_names = array();
        foreach ( Subscriptions::order_subscription_items( $order ) as $item ) {
            $item_names[] = $item->get_name();
        }

        $schedule = '';
        if ( $order instanceof \WC_Order ) {
            $interval = (int) $order->get_meta( Subscriptions::INTERVAL_META );
            if ( $interval > 0 ) {
                $schedule = Subscriptions::describe_schedule( $interval, $order->get_meta( Subscriptions::PERIOD_META ) );
            }
        }

        return array(
            'order'              => $order,
            'item_names'         => $item_names,
            'schedule'           => $schedule,
            'view_url'           => Emails::subscription_view_url( $order ),
            'email_heading'      => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'sent_to_admin'      => false,
            'plain_text'         => (bool) $plain_text,
            'email'              => $this,
        );
    }
}
