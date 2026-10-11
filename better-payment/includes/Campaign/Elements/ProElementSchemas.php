<?php

namespace Better_Payment\Lite\Campaign\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fatal guard for Better Payment Pro 1.4.3 and older. Holds no schema.
 *
 * The Donors Wall, FAQ and Video schemas and default settings ship with Pro
 * (better-payment-pro/includes/Campaign/ProElementSchemas.php); a free install gets
 * {@see ProElementStubs}. Lite's transitional copy of the schemas was deleted when
 * ProCompatibility::MIN_PRO_VERSION reached 1.4.4.
 *
 * What stays is only what Pro 1.4.3 reads directly: its `BuilderElements` aliases
 * these constants at class-constant time and calls {@see self::default_video_url()},
 * so removing them would fatal every campaign render on a site that updated Lite
 * before Pro. Pro 1.4.4+ reads its own class. Pinned by
 * tests/Unit/ProCouplingContractTest.php; delete this file once no supported site
 * can run Pro 1.4.3.
 *
 * @deprecated Kept only so Better Payment Pro <= 1.4.3 does not fatal.
 */
class ProElementSchemas {

    /**
     * The video a newly added Video element starts with.
     *
     * Pro 1.4.3's `BuilderElements::DEFAULT_VIDEO_URL` aliases this constant.
     */
    const DEFAULT_VIDEO_URL = 'https://www.youtube.com/watch?v=jeXG-4SOlZE';

    /**
     * Seconds into {@see self::DEFAULT_VIDEO_URL} that playback starts.
     */
    const DEFAULT_VIDEO_START = 2;

    /**
     * Filterable default video URL.
     *
     * @return string
     */
    public static function default_video_url(): string {
        return (string) apply_filters( 'better_payment/campaign/default_video_url', self::DEFAULT_VIDEO_URL );
    }
}
