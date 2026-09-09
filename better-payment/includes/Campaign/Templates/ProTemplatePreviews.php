<?php

namespace Better_Payment\Lite\Campaign\Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Preview-only layouts for Better Payment Pro's templates.
 *
 * `ProTemplateCatalog` gives a free install the four Pro templates as locked
 * CARDS — labels, artwork, copy, no layout. This class is what lets the picker's
 * Preview lightbox render those cards live, exactly like a free template, instead
 * of showing a cropped screenshot of a design nobody can scroll.
 *
 * ── The enforcement is unchanged, and that is the point ──────────────────────
 * `TemplateManager::get_all()` still reports `columns => []` for these keys.
 * Nothing here is ever merged into the registry, localized to the builder, or
 * returned by `GET /campaigns/templates`. The browser never receives a Pro
 * layout — only rendered HTML from
 * `RendererService::build_template_preview_document()`, which cannot be applied.
 * So a client that ignores the crown, the CTA and the click handler and
 * dispatches `APPLY_TEMPLATE` on a locked card still builds an empty campaign,
 * never a free copy of a Pro design. **Do not "simplify" this by folding these
 * layouts into `ProTemplateCatalog::catalog()`** — that is the whole gate.
 *
 * ── Why the data file is generated ───────────────────────────────────────────
 * `data/pro-preview-layouts.php` is exported from Pro by
 * `scripts/export-pro-preview-layouts.php`. Four large layouts maintained by hand
 * in two plugins is the drift `ProElementSchemas` exists to prevent: the free
 * preview would misrepresent the product the first time Pro redesigned a
 * template, and nothing would report it. Regenerating is one command, and Pro's
 * `BuilderTemplatesRegistrationTest` fails when the two disagree.
 *
 * With Pro ACTIVE none of this runs: Pro registers the real templates, they carry
 * their own `columns`, and `build_template_preview_document()` never asks.
 */
class ProTemplatePreviews {

    /**
     * Per-request memo of the resolved layouts.
     *
     * @var array<string, array>|null
     */
    private static $cache = null;

    /**
     * Layout for one Pro template, ready to render.
     *
     * @param string $key Template key.
     * @return array `[ 'layout' => string, 'columns' => array ]`, or `[]` when the
     *               key has no mirrored layout.
     */
    public static function layout( string $key ): array {
        $all = self::all();

        return isset( $all[ $key ] ) ? $all[ $key ] : [];
    }

    /**
     * Every mirrored layout, keyed by template key.
     *
     * @return array<string, array>
     */
    public static function all(): array {
        if ( null !== self::$cache ) {
            return self::$cache;
        }

        $file = __DIR__ . '/data/pro-preview-layouts.php';

        if ( ! file_exists( $file ) ) {
            self::$cache = [];
            return self::$cache;
        }

        $raw = include $file;

        self::$cache = is_array( $raw ) ? self::resolve( $raw ) : [];

        return self::$cache;
    }

    /**
     * Reset the memo. Tests only.
     */
    public static function flush(): void {
        self::$cache = null;
    }

    /**
     * Fill in the two values the generated file deliberately does not carry.
     *
     * Both are install-specific, so baking either into a shipped file would make
     * every site render the exporting developer's own: a photo `src` pointing at
     * the Pro plugin on the machine it was exported from, and an Organizer
     * pointing at a user id that means someone else here (or nobody).
     *
     * @param array $layouts Raw layouts from the data file.
     * @return array
     */
    private static function resolve( array $layouts ): array {
        $assets = defined( 'BETTER_PAYMENT_ASSETS' ) ? BETTER_PAYMENT_ASSETS : '';

        // Same rule as TemplateManager: the site's first user, falling back to
        // whoever is looking. One query for all four layouts, not one per photo.
        $users     = get_users( [ 'fields' => [ 'ID' ], 'number' => 1 ] );
        $organizer = ! empty( $users ) ? (int) $users[0]->ID : get_current_user_id();

        return self::walk( $layouts, '', $assets, $organizer );
    }

    /**
     * @param mixed  $value     Node.
     * @param string $key       Key the node sits under.
     * @param string $assets    Lite's assets URL.
     * @param int    $organizer Default organizer user id.
     * @return mixed
     */
    private static function walk( $value, string $key, string $assets, int $organizer ) {
        if ( is_array( $value ) ) {
            $out = [];
            foreach ( $value as $k => $v ) {
                $out[ $k ] = self::walk( $v, (string) $k, $assets, $organizer );
            }
            return $out;
        }

        if ( 'creator_user_id' === $key ) {
            return $organizer;
        }

        if ( is_string( $value ) && 0 === strpos( $value, '@bp-assets/' ) ) {
            return $assets . '/' . substr( $value, strlen( '@bp-assets/' ) );
        }

        return $value;
    }
}
