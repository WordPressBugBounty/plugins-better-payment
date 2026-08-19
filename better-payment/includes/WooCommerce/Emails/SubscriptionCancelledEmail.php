<?php

namespace Better_Payment\Lite\WooCommerce\Emails;

use Better_Payment\Lite\WooCommerce\Emails;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Customer email: a Better Payment subscription was cancelled.
 *
 * Triggered by Emails::send_cancelled_email() after the grace delay — a
 * subscription reactivated during the window never reaches this class
 * (see the Emails registrar for the two-layer reactivation guard).
 *
 * @since 2.4.0
 */
class SubscriptionCancelledEmail extends SubscriptionEmail {

    /**
     * Set up the email row shown under WooCommerce → Settings → Emails.
     */
    public function __construct() {
        $this->id          = Emails::CANCELLED_EMAIL_ID;
        $this->title       = __( 'Better Payment Subscription Cancelled', 'better-payment' );
        $this->description = __( 'Sent to the customer when their Better Payment subscription is cancelled and no further renewals will be charged. The send is delayed briefly so a reactivated subscription is never emailed about a cancellation.', 'better-payment' );

        parent::__construct();
    }

    /**
     * The notification hook Emails::notify() fires for this email.
     *
     * @return string
     */
    protected function notification_hook() {
        return Emails::CANCELLED_NOTIFICATION;
    }

    /**
     * Default subject line.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Your {site_title} subscription (order {order_number}) has been cancelled', 'better-payment' );
    }

    /**
     * Default email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Subscription cancelled', 'better-payment' );
    }

    /**
     * Default closing copy (editable per-email in the settings row).
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Changed your mind? You can subscribe again from our store at any time.', 'better-payment' );
    }
}
