<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lite's copy of the React admin styles for the screens only Pro renders.
 *
 * The transaction receipt modal, the dashboard Analytics card, the Analytics page
 * content, the refund card and the licence page are Pro components, but their rules
 * used to be compiled into Lite's admin stylesheet. They now live in
 * `src/admin/styles/pro/_pro-features.scss`, built as
 * `assets/admin/pro-features.min.css`. Pro compiles a byte-identical copy into its
 * own admin bundle and declares `react_admin_pro_styles`; this loads Lite's copy only
 * for an active Pro that does not. A free install renders none of those screens.
 * A later Lite release deletes the class and the stylesheet.
 *
 * @since 2.4.0
 */
class ProAdminStyles {

    /**
     * Feature slug Pro declares once it ships these styles itself.
     */
    const FEATURE = 'react_admin_pro_styles';

    /**
     * Style handle.
     */
    const HANDLE = 'better-payment-react-admin-pro-features';

    /**
     * Whether Lite's copy should load.
     *
     * @return bool
     */
    public static function lite_copy_applies(): bool {
        if ( ProCompatibility::provides( self::FEATURE ) ) {
            return false;
        }

        return (bool) apply_filters( 'better_payment/pro_enabled', false );
    }

    /**
     * Enqueue Lite's copy after the React admin stylesheet, when it applies.
     *
     * @param string|int|false $version Asset version.
     * @return void
     */
    public static function enqueue( $version ) {
        if ( ! self::lite_copy_applies() ) {
            return;
        }

        $file = file_exists( BETTER_PAYMENT_PATH . '/assets/admin/pro-features.min.css' ) ? 'pro-features.min.css' : 'pro-features.css';
        if ( ! file_exists( BETTER_PAYMENT_PATH . '/assets/admin/' . $file ) ) {
            return;
        }

        wp_enqueue_style( self::HANDLE, BETTER_PAYMENT_ASSETS . '/admin/' . $file, [ 'better-payment-react-admin' ], $version );
    }
}
