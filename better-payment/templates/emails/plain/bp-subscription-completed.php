<?php
/**
 * Customer "subscription completed" email (plain text) — the renewal limit
 * was reached and no further payments will be charged.
 *
 * Override by copying it to yourtheme/better-payment/emails/plain/bp-subscription-completed.php.
 *
 * @package Better_Payment\Lite
 * @since 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

/* translators: %s: customer first name */
printf( esc_html__( 'Hi %s,', 'better-payment' ), esc_html( $order->get_billing_first_name() ) );
echo "\n\n";

printf(
    /* translators: 1: order number, 2: site title */
    esc_html__( 'Your subscription (order %1$s) on %2$s is complete — it reached its planned number of renewals, and no further payments will be charged.', 'better-payment' ),
    esc_html( '#' . $order->get_order_number() ),
    esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) )
);
echo "\n\n";

if ( ! empty( $item_names ) ) {
    printf(
        /* translators: %s: comma-separated subscription product names */
        esc_html__( 'Subscription: %s', 'better-payment' ),
        esc_html( implode( ', ', $item_names ) )
    );
    echo "\n";

    if ( '' !== $schedule ) {
        printf(
            /* translators: %s: billing schedule, e.g. "month" or "every 3 months" */
            esc_html__( 'Billing schedule: %s', 'better-payment' ),
            esc_html( $schedule )
        );
        echo "\n";
    }

    echo "\n";
}

if ( '' !== $view_url ) {
    esc_html_e( 'View your subscription:', 'better-payment' );
    echo ' ' . esc_url( $view_url ) . "\n\n";
}

if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );
