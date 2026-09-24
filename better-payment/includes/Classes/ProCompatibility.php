<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The Lite ↔ Pro compatibility contract.
 *
 * Pro-feature code is being moved out of Lite in stages — expand, migrate,
 * contract: a Lite release adds the hook, a Pro release takes the code over and
 * announces it, and only a later Lite release deletes Lite's copy. Two things
 * make that safe, and both live here.
 *
 * - provides(): Pro lists what it now ships itself on `better_payment/pro_provides`,
 *   and Lite skips its own copy of that feature. Without it a transition release
 *   registers the same Elementor control, stylesheet or schema twice — and a
 *   duplicate Elementor control id is an editor error, not a cosmetic one.
 * - The outdated-Pro notice: neither plugin used to check the other's version,
 *   so a site could run a new Lite beside a Pro that still expects code Lite has
 *   removed. MIN_PRO_VERSION is raised in the Lite release that removes such code.
 *
 * Nothing an installed Pro calls directly (classes, methods, constants, partials)
 * may be removed from Lite until MIN_PRO_VERSION is past the Pro release that
 * stopped calling it. tests/Unit/ProCouplingContractTest.php lists those symbols
 * and fails if one disappears.
 *
 * @since 2.4.0
 */
final class ProCompatibility {

    /**
     * The oldest Better Payment Pro this Lite is known to work with.
     */
    const MIN_PRO_VERSION = '1.4.3';

    /**
     * Wire the admin notice.
     *
     * Hooked on both notice actions: Admin::hide_admin_notices() strips
     * `admin_notices` on Better Payment screens and replays only
     * `better_payment_admin_notices` there, and that action never fires anywhere
     * else — so the notice prints exactly once on every screen.
     *
     * @return void
     */
    public static function register(): void {
        add_action( 'admin_notices', array( __CLASS__, 'render_outdated_pro_notice' ) );
        add_action( 'better_payment_admin_notices', array( __CLASS__, 'render_outdated_pro_notice' ) );
    }

    /**
     * Whether the active Pro ships its own copy of a feature.
     *
     * Entitlement is read live on every call, like every other Pro gate, so a
     * deactivated Pro immediately hands the feature back to Lite.
     *
     * @param string $feature Feature slug, e.g. 'campaign_theme_styles'.
     * @return bool
     */
    public static function provides( string $feature ): bool {
        $features = apply_filters( 'better_payment/pro_provides', array() );

        return is_array( $features ) && in_array( $feature, $features, true );
    }

    /**
     * Whether Pro is active on this request.
     *
     * @return bool
     */
    public static function pro_enabled(): bool {
        return (bool) apply_filters( 'better_payment/pro_enabled', false );
    }

    /**
     * Whether Lite's transitional copy of a Pro feature should run.
     *
     * True only for an active Pro that does not ship the feature itself — an
     * older Pro during a hand-off. A free install never runs a Lite copy of Pro
     * code: without Pro there is nothing to serve, and with a current Pro, Pro
     * serves it. Every Lite copy of Pro code gates on this, never on provides()
     * alone (`! provides()` is true on every free install).
     *
     * @param string $feature Feature slug.
     * @return bool
     */
    public static function lite_copy_applies( string $feature ): bool {
        return self::pro_enabled() && ! self::provides( $feature );
    }

    /**
     * The installed Pro version, or '' when Pro is not loaded.
     *
     * @return string
     */
    public static function pro_version(): string {
        return defined( 'BETTER_PAYMENT_PRO_VERSION' ) ? (string) BETTER_PAYMENT_PRO_VERSION : '';
    }

    /**
     * Whether a Pro version is older than this Lite supports.
     *
     * An empty version means Pro is not installed, which is not "outdated".
     *
     * @param string $pro_version Installed Pro version.
     * @param string $minimum     Oldest supported version.
     * @return bool
     */
    public static function is_pro_outdated( string $pro_version, string $minimum = self::MIN_PRO_VERSION ): bool {
        if ( '' === $pro_version ) {
            return false;
        }

        return version_compare( $pro_version, $minimum, '<' );
    }

    /**
     * Print the "update Better Payment Pro" notice when it applies.
     *
     * @return void
     */
    public static function render_outdated_pro_notice(): void {
        $pro_version = self::pro_version();

        if ( ! self::is_pro_outdated( $pro_version ) || ! current_user_can( 'update_plugins' ) ) {
            return;
        }

        printf(
            '<div class="notice notice-warning better-payment-pro-outdated-notice"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: 1: installed Better Payment Pro version, 2: minimum supported version. */
                    __( 'Better Payment Pro %1$s is older than this version of Better Payment supports. Please update Better Payment Pro to %2$s or newer so every Pro feature keeps working.', 'better-payment' ),
                    $pro_version,
                    self::MIN_PRO_VERSION
                )
            )
        );
    }
}
