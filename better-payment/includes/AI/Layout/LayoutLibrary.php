<?php

namespace Better_Payment\Lite\AI\Layout;

use Better_Payment\Lite\AI\Schema\CampaignSchema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reusable layout blueprint library for AI campaign generation.
 *
 * The AI used to default to the same 2-column "story + donation sidebar" every
 * time, so generated campaigns felt cloned. This library gives generation a
 * varied palette of professionally-composed page *structures* — different layout
 * presets, column arrangements, widths, section sets, ordering and donation
 * placement — all expressed with the EXISTING element types and layout presets
 * (no new widgets/blocks). When the user hasn't pre-chosen a layout, the
 * generator picks a blueprint (weighted, category-aware, avoiding immediate
 * repeats) and builds the campaign into it. The result is controlled creativity:
 * every campaign varies structurally, but each one still follows sound design.
 *
 * A blueprint is pure configuration, so new patterns can be added — here or via
 * the `better_payment/ai/layout_blueprints` filter — without touching the
 * generation logic.
 *
 * Blueprint shape:
 *   [
 *     'key'     => string,             // unique
 *     'name'    => string,             // human name, shown to the model
 *     'weight'  => int,                // selection weight
 *     'tags'    => array<string>,      // campaign categories it suits
 *     'preset'  => '1-column'|'2-column'|'3-column'|'split',
 *     'columns' => [ [ 'id','label','width','role',
 *                      'suggested' => [element types],
 *                      'pro'       => [Pro element types] ], ... ],
 *   ]
 *
 * `pro` names the Pro widgets that genuinely belong in that column — appended to
 * `suggested` only when Pro is active ({@see self::suggested_for()}). Without it,
 * a paying install generated pages made entirely of free widgets: the schema
 * listed the Pro elements but nothing ever proposed *where* one should go, and
 * the model builds from the design direction, not from the type list. Placement
 * is per-column on purpose — a Donors Wall belongs beside the donation ask, an
 * FAQ belongs under the story, and a video belongs in the hero.
 *
 * @see \Better_Payment\Lite\AI\Services\CampaignGenerator  Consumer.
 */
class LayoutLibrary {

    /**
     * Keyword → category map used to infer a campaign's category from its brief.
     *
     * @return array<string, array<int, string>>
     */
    private static function category_keywords(): array {
        // Matched as whole words (see infer_categories), so include the inflected
        // forms that matter rather than bare stems.
        return [
            'medical'   => [ 'medical', 'surgery', 'surgeries', 'cancer', 'hospital', 'treatment', 'health', 'illness', 'disease', 'transplant', 'therapy', 'diagnosis', 'diagnosed' ],
            'emergency' => [ 'emergency', 'urgent', 'disaster', 'relief', 'crisis', 'flood', 'fire', 'earthquake', 'hurricane', 'refugee', 'refugees', 'evacuate', 'evacuated' ],
            'memorial'  => [ 'memorial', 'funeral', 'memory', 'tribute', 'grief', 'loss', 'passed away' ],
            'animal'    => [ 'animal', 'animals', 'dog', 'dogs', 'cat', 'cats', 'shelter', 'wildlife', 'pet', 'pets', 'rescue', 'paws', 'kitten', 'kittens', 'puppy', 'puppies' ],
            'education' => [ 'school', 'schools', 'student', 'students', 'education', 'scholarship', 'college', 'tuition', 'learning', 'library', 'classroom', 'books' ],
            'community' => [ 'community', 'neighborhood', 'neighbourhood', 'local', 'together', 'village', 'town' ],
            'event'     => [ 'event', 'marathon', 'gala', 'tournament', 'sports', 'team', 'concert', 'festival', 'challenge' ],
            'nonprofit' => [ 'nonprofit', 'non-profit', 'charity', 'foundation', 'ngo', 'mission', 'humanitarian' ],
            'creative'  => [ 'creative', 'art', 'film', 'music', 'album', 'book', 'startup', 'invention', 'documentary' ],
        ];
    }

    /**
     * Curated primary-colour palettes with mood tags, for colour variation.
     *
     * @return array<int, array{primary: string, mood: string, tags: array<int, string>}>
     */
    private static function palettes(): array {
        return [
            [ 'primary' => '#6b63f6', 'mood' => 'trustworthy and modern', 'tags' => [ 'general', 'nonprofit', 'creative', 'event' ] ],
            [ 'primary' => '#e0533f', 'mood' => 'urgent and heartfelt', 'tags' => [ 'emergency', 'medical' ] ],
            [ 'primary' => '#2ea56b', 'mood' => 'hopeful and reassuring', 'tags' => [ 'medical', 'community', 'animal', 'education' ] ],
            [ 'primary' => '#3b6ef5', 'mood' => 'calm and credible', 'tags' => [ 'nonprofit', 'education', 'general' ] ],
            [ 'primary' => '#ef8f2b', 'mood' => 'warm and welcoming', 'tags' => [ 'community', 'event', 'animal' ] ],
            [ 'primary' => '#0ea5a5', 'mood' => 'fresh and optimistic', 'tags' => [ 'creative', 'community', 'general' ] ],
            [ 'primary' => '#8b5cf6', 'mood' => 'gentle and dignified', 'tags' => [ 'memorial', 'nonprofit' ] ],
            [ 'primary' => '#d64d76', 'mood' => 'compassionate and personal', 'tags' => [ 'medical', 'memorial', 'community' ] ],
        ];
    }

    /**
     * The built-in blueprints. Widths in a row total ~100%; `split` renders as a
     * full-width row (first column) then two 50/50 columns.
     *
     * @return array<int, array>
     */
    private static function blueprints(): array {
        return [
            [
                'key'     => 'story-sidebar',
                'name'    => 'Story with donation sidebar',
                'weight'  => 3,
                'tags'    => [ 'general', 'medical', 'community', 'nonprofit' ],
                'preset'  => '2-column',
                'columns' => [
                    [ 'id' => 'main', 'label' => 'Main Content', 'width' => '64%', 'role' => 'Hero title, image and the campaign story, ending with the progress bar', 'suggested' => [ 'campaign_title', 'photo', 'campaign_description', 'progress_bar' ], 'pro' => [ 'video', 'faq' ] ],
                    [ 'id' => 'sidebar', 'label' => 'Sidebar', 'width' => '36%', 'role' => 'Donation actions and quick stats', 'suggested' => [ 'donation_form', 'donate_amount', 'campaign_summary', 'social_sharing' ], 'pro' => [ 'donors_wall' ] ],
                ],
            ],
            [
                'key'     => 'donate-first',
                'name'    => 'Donation-first (action on the left)',
                'weight'  => 2,
                'tags'    => [ 'emergency', 'medical', 'nonprofit' ],
                'preset'  => '2-column',
                'columns' => [
                    [ 'id' => 'action', 'label' => 'Donate', 'width' => '42%', 'role' => 'Prominent donation panel with amounts, progress and summary', 'suggested' => [ 'donate_amount', 'donation_form', 'progress_bar', 'campaign_summary' ], 'pro' => [ 'donors_wall' ] ],
                    [ 'id' => 'story', 'label' => 'Story', 'width' => '58%', 'role' => 'Title, image, story and organizer', 'suggested' => [ 'campaign_title', 'photo', 'campaign_description', 'organizer' ], 'pro' => [ 'video', 'faq' ] ],
                ],
            ],
            [
                'key'     => 'full-story',
                'name'    => 'Full-width storytelling',
                'weight'  => 2,
                'tags'    => [ 'memorial', 'community', 'nonprofit', 'general' ],
                'preset'  => '1-column',
                'columns' => [
                    [ 'id' => 'main', 'label' => 'Main Content', 'width' => '100%', 'role' => 'A single vertical narrative: hero, story, progress, then the donation ask and organizer', 'suggested' => [ 'campaign_title', 'photo', 'campaign_description', 'progress_bar', 'donate_amount', 'donation_form', 'organizer', 'social_sharing' ], 'pro' => [ 'video', 'faq', 'donors_wall' ] ],
                ],
            ],
            [
                'key'     => 'hero-split',
                'name'    => 'Hero on top, story and donation below',
                'weight'  => 3,
                'tags'    => [ 'event', 'community', 'general', 'creative' ],
                'preset'  => 'split',
                'columns' => [
                    [ 'id' => 'hero', 'label' => 'Hero', 'width' => '100%', 'role' => 'Full-width hero: title and a strong image', 'suggested' => [ 'campaign_title', 'photo' ], 'pro' => [ 'video' ] ],
                    [ 'id' => 'story', 'label' => 'Story', 'width' => '50%', 'role' => 'The story, progress and organizer', 'suggested' => [ 'campaign_description', 'progress_bar', 'organizer' ], 'pro' => [ 'faq' ] ],
                    [ 'id' => 'donate', 'label' => 'Donate', 'width' => '50%', 'role' => 'Summary and the donation ask', 'suggested' => [ 'campaign_summary', 'donate_amount', 'donation_form', 'social_sharing' ], 'pro' => [ 'donors_wall' ] ],
                ],
            ],
            [
                'key'     => 'impact-split',
                'name'    => 'Impact overview then story and donation',
                'weight'  => 2,
                'tags'    => [ 'nonprofit', 'medical', 'education', 'community' ],
                'preset'  => 'split',
                'columns' => [
                    [ 'id' => 'intro', 'label' => 'Intro', 'width' => '100%', 'role' => 'Full-width title and a concise mission statement', 'suggested' => [ 'campaign_title', 'campaign_description' ], 'pro' => [ 'video' ] ],
                    [ 'id' => 'impact', 'label' => 'Impact', 'width' => '50%', 'role' => 'Visual impact: image, summary stats and progress', 'suggested' => [ 'photo', 'campaign_summary', 'progress_bar' ], 'pro' => [ 'faq' ] ],
                    [ 'id' => 'donate', 'label' => 'Donate', 'width' => '50%', 'role' => 'Donation amounts, the form and sharing', 'suggested' => [ 'donate_amount', 'donation_form', 'social_sharing' ], 'pro' => [ 'donors_wall' ] ],
                ],
            ],
            [
                'key'     => 'minimal',
                'name'    => 'Minimal landing page',
                'weight'  => 1,
                'tags'    => [ 'event', 'creative', 'general' ],
                'preset'  => '1-column',
                'columns' => [
                    [ 'id' => 'main', 'label' => 'Main Content', 'width' => '100%', 'role' => 'A short, focused page: title, one tight paragraph, progress and a single clear donate action', 'suggested' => [ 'campaign_title', 'campaign_description', 'progress_bar', 'donation_form' ] ],
                ],
            ],
            [
                'key'     => 'modern-trio',
                'name'    => 'Three-column overview',
                'weight'  => 1,
                'tags'    => [ 'creative', 'community', 'nonprofit', 'event' ],
                'preset'  => '3-column',
                'columns' => [
                    [ 'id' => 'story', 'label' => 'Story', 'width' => '38%', 'role' => 'Title and the story', 'suggested' => [ 'campaign_title', 'campaign_description' ], 'pro' => [ 'faq' ] ],
                    [ 'id' => 'visual', 'label' => 'Visual', 'width' => '32%', 'role' => 'Image and progress', 'suggested' => [ 'photo', 'progress_bar' ], 'pro' => [ 'video' ] ],
                    [ 'id' => 'action', 'label' => 'Action', 'width' => '30%', 'role' => 'Amounts and the donation form', 'suggested' => [ 'donate_amount', 'donation_form', 'social_sharing' ], 'pro' => [ 'donors_wall' ] ],
                ],
            ],
            [
                'key'     => 'organizer-led',
                'name'    => 'Organizer-led (profile sidebar on the left)',
                'weight'  => 1,
                'tags'    => [ 'nonprofit', 'community', 'memorial' ],
                'preset'  => '2-column',
                'columns' => [
                    [ 'id' => 'profile', 'label' => 'Organizer', 'width' => '36%', 'role' => 'Who is running this: organizer card, summary and links', 'suggested' => [ 'organizer', 'campaign_summary', 'social_links' ], 'pro' => [ 'donors_wall' ] ],
                    [ 'id' => 'main', 'label' => 'Main Content', 'width' => '64%', 'role' => 'Title, image, story, progress and the donation ask', 'suggested' => [ 'campaign_title', 'photo', 'campaign_description', 'progress_bar', 'donation_form' ], 'pro' => [ 'video', 'faq' ] ],
                ],
            ],
            [
                'key'     => 'gallery-story',
                'name'    => 'Gallery storytelling',
                'weight'  => 1,
                'tags'    => [ 'event', 'creative', 'community', 'animal' ],
                'preset'  => '1-column',
                'columns' => [
                    [ 'id' => 'main', 'label' => 'Main Content', 'width' => '100%', 'role' => 'An image-rich narrative: title, image, story, a second image, progress, then donate and share', 'suggested' => [ 'campaign_title', 'photo', 'campaign_description', 'photo', 'progress_bar', 'donation_form', 'social_sharing' ], 'pro' => [ 'video', 'donors_wall', 'faq' ] ],
                ],
            ],
            [
                'key'     => 'urgent-emergency',
                'name'    => 'Urgent emergency appeal',
                'weight'  => 2,
                'tags'    => [ 'emergency', 'medical', 'animal' ],
                'preset'  => '2-column',
                'columns' => [
                    [ 'id' => 'main', 'label' => 'Main Content', 'width' => '58%', 'role' => 'An urgent title, the situation, live progress and an image', 'suggested' => [ 'campaign_title', 'campaign_description', 'progress_bar', 'photo' ], 'pro' => [ 'video' ] ],
                    [ 'id' => 'sidebar', 'label' => 'Sidebar', 'width' => '42%', 'role' => 'Immediate donation ask with amounts and summary', 'suggested' => [ 'donate_amount', 'donation_form', 'campaign_summary', 'social_sharing' ], 'pro' => [ 'donors_wall' ] ],
                ],
            ],
            [
                'key'     => 'balanced',
                'name'    => 'Balanced two-column',
                'weight'  => 1,
                'tags'    => [ 'general', 'education', 'community', 'creative' ],
                'preset'  => '2-column',
                'columns' => [
                    [ 'id' => 'left', 'label' => 'Left', 'width' => '50%', 'role' => 'Title, image and the story', 'suggested' => [ 'campaign_title', 'photo', 'campaign_description' ], 'pro' => [ 'video', 'faq' ] ],
                    [ 'id' => 'right', 'label' => 'Right', 'width' => '50%', 'role' => 'Progress, amounts, the form and summary', 'suggested' => [ 'progress_bar', 'donate_amount', 'donation_form', 'campaign_summary' ], 'pro' => [ 'donors_wall' ] ],
                ],
            ],
            [
                'key'     => 'progress-hero-split',
                'name'    => 'Progress hero, then story and donation',
                'weight'  => 1,
                'tags'    => [ 'medical', 'emergency', 'education', 'general' ],
                'preset'  => 'split',
                'columns' => [
                    [ 'id' => 'hero', 'label' => 'Hero', 'width' => '100%', 'role' => 'Full-width title with the progress bar right up top', 'suggested' => [ 'campaign_title', 'progress_bar' ], 'pro' => [ 'video' ] ],
                    [ 'id' => 'story', 'label' => 'Story', 'width' => '50%', 'role' => 'Image and the story', 'suggested' => [ 'photo', 'campaign_description' ], 'pro' => [ 'faq' ] ],
                    [ 'id' => 'donate', 'label' => 'Donate', 'width' => '50%', 'role' => 'Amounts, the form and summary', 'suggested' => [ 'donate_amount', 'donation_form', 'campaign_summary', 'social_sharing' ], 'pro' => [ 'donors_wall' ] ],
                ],
            ],
        ];
    }

    /**
     * All blueprints, after the extension filter.
     *
     * @return array<int, array>
     */
    public static function all(): array {
        /**
         * Filter the AI layout blueprints.
         *
         * @param array<int, array> $blueprints
         */
        return apply_filters( 'better_payment/ai/layout_blueprints', self::blueprints() );
    }

    /**
     * Infer the campaign's category tags from its brief (keyword match).
     *
     * @return array<int, string>
     */
    public static function infer_categories( string $brief ): array {
        $haystack = strtolower( $brief );
        $matched  = [];
        foreach ( self::category_keywords() as $category => $keywords ) {
            foreach ( $keywords as $kw ) {
                // Whole-word match so short words don't hit inside others
                // (e.g. "cat" must not match "category").
                if ( preg_match( '/\b' . preg_quote( $kw, '/' ) . '\b/', $haystack ) ) {
                    $matched[] = $category;
                    break;
                }
            }
        }
        return $matched;
    }

    /**
     * Pick a blueprint for a brief: prefer ones matching the inferred category,
     * weight the choice, and avoid repeating $exclude_key when possible.
     *
     * @param string $brief
     * @param string $exclude_key Blueprint key to avoid (e.g. the last one used).
     * @return array The chosen blueprint.
     */
    public static function pick( string $brief, string $exclude_key = '' ): array {
        $all        = array_values( self::all() );
        $categories = self::infer_categories( $brief );

        // Prefer blueprints tagged with an inferred category; fall back to all.
        $candidates = [];
        if ( ! empty( $categories ) ) {
            foreach ( $all as $bp ) {
                if ( array_intersect( $categories, $bp['tags'] ?? [] ) ) {
                    $candidates[] = $bp;
                }
            }
        }
        if ( empty( $candidates ) ) {
            $candidates = $all;
        }

        // Avoid repeating the previous blueprint when there is an alternative.
        if ( '' !== $exclude_key && count( $candidates ) > 1 ) {
            $filtered = array_values( array_filter(
                $candidates,
                static function ( $bp ) use ( $exclude_key ) {
                    return ( $bp['key'] ?? '' ) !== $exclude_key;
                }
            ) );
            if ( ! empty( $filtered ) ) {
                $candidates = $filtered;
            }
        }

        return self::weighted_pick( $candidates );
    }

    /**
     * Pick a colour palette for a brief, biased toward the inferred category.
     *
     * @return array{primary: string, mood: string}
     */
    public static function pick_palette( string $brief ): array {
        $categories = self::infer_categories( $brief );
        $all        = self::palettes();

        $candidates = [];
        if ( ! empty( $categories ) ) {
            foreach ( $all as $palette ) {
                if ( array_intersect( $categories, $palette['tags'] ) ) {
                    $candidates[] = $palette;
                }
            }
        }
        if ( empty( $candidates ) ) {
            $candidates = $all;
        }

        $chosen = $candidates[ self::rand_index( count( $candidates ) ) ];
        return [ 'primary' => $chosen['primary'], 'mood' => $chosen['mood'] ];
    }

    /**
     * The element types to suggest for one blueprint column.
     *
     * The column's free `suggested` list, plus its `pro` list when Pro is active.
     * Pro types are appended rather than interleaved so the free composition stays
     * the backbone of the page and the Pro widgets read as additions to it — a
     * campaign whose donation column is all Donors Wall and no donate button is a
     * worse page, not a more premium one.
     *
     * `$pro_enabled` is the authority on entitlement — this method does not
     * re-read the filter, so the caller's answer and this one can never disagree.
     * The only extra check is registration: a `pro` entry naming a type no longer
     * in the registry (a blueprint added through the filter, say) must not be
     * named to the model as something it may use.
     *
     * @param array $column      A blueprint column.
     * @param bool  $pro_enabled Whether Pro is active.
     * @return array<int, string>
     */
    public static function suggested_for( array $column, bool $pro_enabled ): array {
        $suggested = array_values( (array) ( $column['suggested'] ?? [] ) );

        if ( ! $pro_enabled ) {
            return $suggested;
        }

        foreach ( (array) ( $column['pro'] ?? [] ) as $type ) {
            $type = (string) $type;
            if ( '' !== $type && CampaignSchema::is_element_type( $type ) && ! in_array( $type, $suggested, true ) ) {
                $suggested[] = $type;
            }
        }

        return $suggested;
    }

    /**
     * The trusted column structure for enforcement — only id/label/width (the
     * role/suggested/pro hints are for the prompt, not the stored layout).
     *
     * @param array $blueprint
     * @return array{preset: string, columns: array}
     */
    public static function structure( array $blueprint ): array {
        $columns = [];
        foreach ( $blueprint['columns'] ?? [] as $col ) {
            $columns[] = [
                'id'    => $col['id'] ?? '',
                'label' => $col['label'] ?? '',
                'width' => $col['width'] ?? '100%',
            ];
        }
        return [
            'preset'  => $blueprint['preset'] ?? '1-column',
            'columns' => $columns,
        ];
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Weighted random pick from a list of blueprints.
     *
     * @param array<int, array> $candidates
     * @return array
     */
    private static function weighted_pick( array $candidates ): array {
        $total = 0;
        foreach ( $candidates as $bp ) {
            $total += max( 1, (int) ( $bp['weight'] ?? 1 ) );
        }
        $roll = self::rand_int( 1, $total );
        $acc  = 0;
        foreach ( $candidates as $bp ) {
            $acc += max( 1, (int) ( $bp['weight'] ?? 1 ) );
            if ( $roll <= $acc ) {
                return $bp;
            }
        }
        return $candidates[0];
    }

    private static function rand_int( int $min, int $max ): int {
        if ( $max <= $min ) {
            return $min;
        }
        return function_exists( 'wp_rand' ) ? wp_rand( $min, $max ) : mt_rand( $min, $max ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
    }

    private static function rand_index( int $count ): int {
        return $count > 0 ? self::rand_int( 0, $count - 1 ) : 0;
    }
}
