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
 * Customer email: a Better Payment subscription completed — its renewal
 * limit was reached and no further payments will be charged.
 *
 * This is Better Payment's "subscription expired" email. Completed is
 * terminal with no reactivation route, so it
 * is sent immediately from Emails::send_completed_email() — the delayed
 * send only exists for the cancelled email, where reactivation is possible.
 *
 * @since 2.4.0
 */
class SubscriptionCompletedEmail extends SubscriptionEmail {

    /**
     * Set up the email row shown under WooCommerce → Settings → Emails.
     */
    public function __construct() {
        $this->id          = Emails::COMPLETED_EMAIL_ID;
        $this->title       = __( 'Better Payment Subscription Completed', 'better-payment' );
        $this->description = __( 'Sent to the customer when their Better Payment subscription completes — no further payments will be charged.', 'better-payment' );

        parent::__construct();
    }

    /**
     * The notification hook Emails::notify() fires for this email.
     *
     * @return string
     */
    protected function notification_hook() {
        return Emails::COMPLETED_NOTIFICATION;
    }

    /**
     * Default subject line.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Your {site_title} subscription (order {order_number}) is complete', 'better-payment' );
    }

    /**
     * Default email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Subscription completed', 'better-payment' );
    }

    /**
     * Default closing copy (editable per-email in the settings row).
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Thank you for subscribing with us. You can start a new subscription from our store at any time.', 'better-payment' );
    }
}
