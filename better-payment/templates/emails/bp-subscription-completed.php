<?php
/**
 * Customer "subscription completed" email (HTML) — the renewal limit was
 * reached and no further payments will be charged.
 *
 * Override by copying it to yourtheme/better-payment/emails/bp-subscription-completed.php.
 *
 * @var \WC_Order                                                     $order              Parent (subscription) order.
 * @var string[]                                                      $item_names         Subscription line-item names.
 * @var string                                                        $schedule           Human billing schedule ("month", "every 3 months") or ''.
 * @var string                                                        $view_url           Customer URL for the subscription, or ''.
 * @var string                                                        $email_heading      Email heading.
 * @var string                                                        $additional_content Shop-owner closing copy.
 * @var bool                                                          $sent_to_admin
 * @var bool                                                          $plain_text
 * @var \Better_Payment\Lite\WooCommerce\Emails\SubscriptionEmail     $email
 *
 * @package Better_Payment\Lite
 * @since 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
    <?php
    /* translators: %s: customer first name */
    printf( esc_html__( 'Hi %s,', 'better-payment' ), esc_html( $order->get_billing_first_name() ) );
    ?>
</p>

<p>
    <?php
    printf(
        /* translators: 1: order number, 2: site title */
        esc_html__( 'Your subscription (order %1$s) on %2$s is complete — it reached its planned number of renewals, and no further payments will be charged.', 'better-payment' ),
        esc_html( '#' . $order->get_order_number() ),
        esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) )
    );
    ?>
</p>

<?php if ( ! empty( $item_names ) ) : ?>
    <p>
        <?php
        printf(
            /* translators: %s: comma-separated subscription product names */
            esc_html__( 'Subscription: %s', 'better-payment' ),
            esc_html( implode( ', ', $item_names ) )
        );
        ?>
        <?php if ( '' !== $schedule ) : ?>
            <br />
            <?php
            printf(
                /* translators: %s: billing schedule, e.g. "month" or "every 3 months" */
                esc_html__( 'Billing schedule: %s', 'better-payment' ),
                esc_html( $schedule )
            );
            ?>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if ( '' !== $view_url ) : ?>
    <p>
        <a href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View your subscription', 'better-payment' ); ?></a>
    </p>
<?php endif; ?>

<?php
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
