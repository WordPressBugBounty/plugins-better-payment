<?php

namespace Better_Payment\Lite\Campaign\Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The campaign category taxonomy — the single source of truth.
 *
 * These are the categories the template picker lists in its sidebar and the AI
 * Smart Prompt Wizard offers under "What are you raising funds for?". Both used
 * to hardcode their own list (`CATEGORIES` in TemplatesTab.js and `CAUSES` in
 * PromptWizard.js), which is why they drifted: the picker showed five categories
 * while the wizard offered a different ten, overlapping on only two.
 *
 * Each category declares:
 *  - `slug`     The key a template's `tags` entry matches (see TemplateManager).
 *  - `label`    Translated display text — shown in the picker sidebar and wizard.
 *  - `brief`    Untranslated English used in the AI brief ("for a medical cause").
 *               Deliberately NOT `__()`: the brief is prompt text for an
 *               English-instructed model, so a translated label here would
 *               produce a mixed-language sentence.
 *  - `photo`    Hero image, relative to `assets/img/campaign/templates/` — the
 *               same artwork the prebuilt templates in that category already use.
 *  - `keywords` Terms that identify this category in free text (see match_text).
 *
 * **Not memoized, unlike TemplateManager::get_all().** That one is memoized
 * because it runs on every public campaign pageview and carries full layouts;
 * this list is small and only read in wp-admin (the builder localization and AI
 * generation). Skipping the memo means a filter registered late still applies,
 * and tests need no static reset.
 *
 * @see \Better_Payment\Lite\Campaign\Templates\TemplateManager  Templates tagged with these slugs.
 * @see \Better_Payment\Lite\AI\Services\CampaignGenerator       Uses `photo` for AI-generated campaigns.
 */
class CategoryRegistry {

    /**
     * The built-in categories, in the order the picker sidebar lists them.
     *
     * @return array<string, array> Keyed by slug.
     */
    private static function defaults(): array {
        return [
            'charity'            => [
                'slug'     => 'charity',
                'label'    => __( 'Charity', 'better-payment' ),
                'brief'    => 'charity',
                'photo'    => 'charity/Refugee-Relief-Fund.webp',
                'keywords' => [
                    'charity', 'charitable', 'nonprofit', 'non-profit', 'ngo',
                    'humanitarian', 'refugee', 'homeless', 'homelessness',
                    'poverty', 'food bank', 'orphan', 'orphanage',
                    'animal', 'animals', 'animal rescue', 'shelter', 'rescue',
                ],
            ],
            'club-organizations' => [
                'slug'     => 'club-organizations',
                'label'    => __( 'Club / Organizations', 'better-payment' ),
                'brief'    => 'club or organization',
                'photo'    => 'club-organizations/Elite-Golf-Club-Membership.webp',
                'keywords' => [
                    'club', 'membership', 'organization', 'organisation',
                    'association', 'society', 'chapter', 'troop', 'league',
                    'team', 'sports', 'sport', 'community', 'community center',
                ],
            ],
            'environmental'      => [
                'slug'     => 'environmental',
                'label'    => __( 'Environmental', 'better-payment' ),
                'brief'    => 'environmental',
                'photo'    => 'environmental/Tree-Plantation-Campaign.webp',
                'keywords' => [
                    'environment', 'environmental', 'climate', 'conservation',
                    'sustainability', 'recycling', 'tree', 'trees', 'plantation',
                    'reforestation', 'forest', 'wildlife', 'ocean',
                    // Disaster relief lives under Environmental here because that
                    // is where its prebuilt template is tagged (disaster-relief).
                    'disaster', 'disaster relief', 'flood', 'earthquake',
                    'hurricane', 'wildfire', 'cyclone', 'tsunami',
                ],
            ],
            'medical'            => [
                'slug'     => 'medical',
                'label'    => __( 'Medical', 'better-payment' ),
                'brief'    => 'medical',
                'photo'    => 'medical/Medical-Emergency-Fund.webp',
                'keywords' => [
                    'medical', 'medicine', 'health', 'healthcare', 'hospital',
                    'surgery', 'treatment', 'cancer', 'illness', 'disease',
                    'patient', 'clinic', 'therapy', 'transplant', 'diagnosis',
                    'icu', 'emergency',
                ],
            ],
            'education'          => [
                'slug'     => 'education',
                'label'    => __( 'Youth / Education', 'better-payment' ),
                'brief'    => 'education',
                'photo'    => 'education/Student-Success-Fund.webp',
                'keywords' => [
                    'education', 'educational', 'school', 'student', 'students',
                    'college', 'university', 'scholarship', 'tuition',
                    'classroom', 'library', 'teacher', 'learning', 'books',
                    'youth', 'kids', 'children',
                ],
            ],
        ];
    }

    /**
     * Every campaign category, keyed by slug.
     *
     * Mirrors `better_payment/campaign_templates`: an add-on registering
     * templates with a new tag should register the matching category here, so it
     * gets a picker sidebar entry and a wizard tile instead of being reachable
     * only via search. (The `photo` feeds the prebuilt-template artwork and the
     * client tiles; AI-generated campaigns no longer use it — every generated
     * photo uses `ai-default-hero.svg`. See `CampaignGenerator::default_photo_url`.)
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        $defaults = self::defaults();

        /**
         * Filter the campaign category taxonomy.
         *
         * @param array<string, array> $categories Keyed by slug.
         */
        // @var is deliberate: the @param above describes what we pass in, but a
        // third-party listener can return anything at all.
        /** @var mixed $filtered */
        $filtered = apply_filters( 'better_payment/campaign_categories', $defaults );

        // An add-on returning a non-array must not take the built-in categories
        // down with it (which would empty the picker sidebar AND the wizard's
        // tiles), nor fatal the `: array` return — keep the unfiltered set.
        return is_array( $filtered ) ? $filtered : $defaults;
    }

    /**
     * The categories as a plain list, shaped for localization to the builder.
     *
     * `keywords` and the relative `photo` path are dropped — the client only
     * needs to render tiles and echo a slug back. `photo` is resolved to an
     * absolute URL so a caller never has to know the assets layout.
     *
     * @return array<int, array{slug: string, label: string, brief: string, photo: string}>
     */
    public static function for_client(): array {
        $out = [];
        foreach ( self::get_all() as $slug => $category ) {
            $out[] = [
                'slug'  => (string) $slug,
                'label' => (string) ( $category['label'] ?? $slug ),
                'brief' => (string) ( $category['brief'] ?? $slug ),
                'photo' => self::photo_url( (string) $slug ),
            ];
        }
        return $out;
    }

    /**
     * Whether a slug is a known category.
     */
    public static function exists( string $slug ): bool {
        return '' !== $slug && isset( self::get_all()[ $slug ] );
    }

    /**
     * Absolute URL of a category's hero image, or '' when the category is
     * unknown, declares no photo, or the assets constant is missing.
     *
     * A `photo` that already looks absolute (an add-on pointing at its own
     * plugin) is returned untouched; a relative one resolves against Lite's
     * campaign-template assets, the same base TemplateManager uses.
     */
    public static function photo_url( string $slug ): string {
        $categories = self::get_all();
        $photo      = isset( $categories[ $slug ]['photo'] ) ? (string) $categories[ $slug ]['photo'] : '';
        if ( '' === $photo ) {
            return '';
        }

        if ( 0 === strpos( $photo, 'http://' ) || 0 === strpos( $photo, 'https://' ) || 0 === strpos( $photo, '//' ) ) {
            return $photo;
        }

        if ( ! defined( 'BETTER_PAYMENT_ASSETS' ) ) {
            return '';
        }

        return BETTER_PAYMENT_ASSETS . '/img/campaign/templates/' . ltrim( $photo, '/' );
    }

    /**
     * Infer a category from free text (an AI brief, a prompt, a title).
     *
     * This is the "or the AI picked it" half of category detection: the wizard
     * sends an explicit slug only when the user clicks a category tile, so every
     * other route in — the wizard's non-category causes, a hand-written prompt,
     * a chat message — arrives as prose and is matched here.
     *
     * Keywords match on word boundaries, so "ngo" does not fire inside "among"
     * and "tree" does not fire inside "street". The category with the most
     * distinct keyword hits wins; ties break toward declaration order, keeping
     * the result deterministic for a given text.
     *
     * @param string $text Free text to classify.
     * @return string A category slug, or '' when nothing matches (a "new"
     *                category — the caller should fall back to the default).
     */
    public static function match_text( string $text ): string {
        $text = trim( $text );
        if ( '' === $text ) {
            return '';
        }

        $best  = '';
        $score = 0;

        foreach ( self::get_all() as $slug => $category ) {
            $keywords = isset( $category['keywords'] ) && is_array( $category['keywords'] )
                ? $category['keywords']
                : [];

            $hits = 0;
            foreach ( $keywords as $keyword ) {
                $keyword = trim( (string) $keyword );
                if ( '' === $keyword ) {
                    continue;
                }
                if ( preg_match( '/\b' . preg_quote( $keyword, '/' ) . '\b/i', $text ) ) {
                    $hits++;
                }
            }

            // Strictly greater, so the first-declared category wins a tie.
            if ( $hits > $score ) {
                $best  = (string) $slug;
                $score = $hits;
            }
        }

        return $best;
    }
}
