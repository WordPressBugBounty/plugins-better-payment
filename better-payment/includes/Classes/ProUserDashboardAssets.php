<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lite's copy of the User Dashboard script and styles for the cards only Pro renders.
 *
 * Transitional copy for Better Payment Pro <= 1.4.3; delete when
 * ProCompatibility::MIN_PRO_VERSION >= 1.4.4 (with src/js/user-dashboard-pro.js,
 * src/css/user-dashboard-pro.scss and their Assets entries).
 * Pro copy: better-payment-pro/src/js/user-dashboard.js (slug `user_dashboard_pro_scripts`)
 * and better-payment-pro/src/css/user-dashboard.scss (slug `user_dashboard_pro_styles`).
 *
 * Pro's templates and block panels render the analytics report box with its custom
 * date range modal and datepicker, and the recurring and split subscription cards
 * with their "View All" button. Their handlers used to live in Lite's common.js and
 * admin.js, their rules in common.scss and admin.scss, all of which load on free
 * installs too. They are now separate handles, each attached as a registration-time
 * dependency of the handle that carried the code before, and only while
 * ProCompatibility::lite_copy_applies() is true:
 *
 * - the script on `better-payment-common-script`, so it loads wherever Lite's
 *   common.js does (the User Dashboard widget and block);
 * - the stylesheet on `better-payment-admin-style`, and it depends on
 *   `better-payment-common-style` itself, so it prints between common.min.css and
 *   admin.min.css, where its rules used to be.
 *
 * The PHP gate is the only switch: a free install never loads either file, and a Pro
 * that declares the slugs loads its own copies instead. Neither file checks anything
 * at runtime.
 *
 * @since 2.4.0
 */
final class ProUserDashboardAssets {

    /**
     * Feature slug Pro declares once it ships the script.
     */
    const SCRIPTS_FEATURE = 'user_dashboard_pro_scripts';

    /**
     * Feature slug Pro declares once it ships the styles.
     */
    const STYLES_FEATURE = 'user_dashboard_pro_styles';

    /**
     * Lite's script handle (registered in Assets).
     */
    const SCRIPT_HANDLE = 'better-payment-user-dashboard-pro-script';

    /**
     * Lite's style handle (registered in Assets).
     */
    const STYLE_HANDLE = 'better-payment-user-dashboard-pro-style';

    /**
     * Dependencies to add to `better-payment-common-script`.
     *
     * @return string[]
     */
    public static function script_depends(): array {
        return ProCompatibility::lite_copy_applies( self::SCRIPTS_FEATURE ) ? array( self::SCRIPT_HANDLE ) : array();
    }

    /**
     * Dependencies to add to `better-payment-admin-style`.
     *
     * @return string[]
     */
    public static function style_depends(): array {
        return ProCompatibility::lite_copy_applies( self::STYLES_FEATURE ) ? array( self::STYLE_HANDLE ) : array();
    }
}
