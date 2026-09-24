<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Styles for the Fundraising Campaign widget's Pro "Updates" tab.
 *
 * Only Pro renders that tab, so the rules live in their own stylesheet
 * (`src/css/fundraising-campaign-updates.scss`) instead of
 * `fundraising-campaign.scss`. Lite adds its copy to the widget's style depends
 * while Pro is active but does not declare `fundraising_campaign_update_styles`;
 * a Pro that declares it adds its own byte-identical copy through
 * `better_payment/elementor/editor/get_style_depends`. A later Lite release
 * deletes this class and the stylesheet.
 *
 * @since 2.4.0
 */
class ProFundraisingUpdateStyles {

    /**
     * Feature slug Pro declares once it loads the styles itself.
     */
    const FEATURE = 'fundraising_campaign_update_styles';

    /**
     * Lite's style handle (registered in Assets).
     */
    const HANDLE = 'fundraising-campaign-updates-style';

    /**
     * Whether Lite's copy of the styles should load.
     *
     * A free install renders only the upgrade banner in that tab, so nothing needs
     * the styles there.
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
     * The widget's style depends, with Lite's copy appended when it applies.
     *
     * @param array $depends Style handles.
     * @return array
     */
    public static function style_depends( array $depends ): array {
        if ( self::lite_copy_applies() ) {
            $depends[] = self::HANDLE;
        }

        return $depends;
    }
}
