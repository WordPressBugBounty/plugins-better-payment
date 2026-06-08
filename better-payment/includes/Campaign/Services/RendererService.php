<?php

namespace Better_Payment\Lite\Campaign\Services;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Campaign\CampaignStats;
use Better_Payment\Lite\Campaign\MetaBox;
use Better_Payment\Lite\Campaign\Templates\TemplateManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders campaign HTML from the column-based layout schema.
 *
 * Supports both the current column schema and the legacy flat-array format
 * (auto-migrated to a single 1-column layout on render).
 *
 * Used by:
 *   - Shortcode::render_campaign()
 *   - CampaignAPI preview endpoint
 *   - CampaignBlock server-side render
 */
class RendererService {

    /**
     * Render a full campaign.
     *
     * @param int        $campaign_id   The campaign post ID.
     * @param bool       $is_preview    True when rendering a preview (skips publish check).
     * @param array|null $preview_layout Override layout JSON (used by preview endpoint).
     * @return string HTML output.
     */
    public static function render_campaign(
        int $campaign_id,
        bool $is_preview = false,
        ?array $preview_layout = null
    ): string {
        $post = get_post( $campaign_id );
        if ( ! $post || $post->post_type !== 'bp_campaign' ) {
            return '';
        }

        $meta  = MetaBox::get_all( $campaign_id );
        $stats = CampaignStats::get_stats( $campaign_id );

        $template_key  = $meta['bpc_template_key'] ?? '';
        $all_templates = TemplateManager::get_all();
        $theme_class   = ( $template_key && isset( $all_templates[ $template_key ]['theme_class'] ) )
            ? ' ' . sanitize_html_class( $all_templates[ $template_key ]['theme_class'] )
            : '';

        if ( $is_preview && $preview_layout !== null ) {
            $layout_data = $preview_layout;
        } else {
            $raw         = $meta['bpc_fields_layout'] ?? [];
            $layout_data = self::normalize_layout( $raw );
        }

        $columns = $layout_data['columns'] ?? [];
        $layout  = $layout_data['layout']  ?? '1-column';

        // Only fall back to default when there are literally no columns.
        // Blank templates (1-col, 2-col, 3-col) have columns with empty elements arrays —
        // that is intentional and must render as blank to match the editor and preview.
        if ( empty( $columns ) ) {
            $layout_data = self::default_layout();
            $columns     = $layout_data['columns'];
            $layout      = $layout_data['layout'];
        }

        $color_style = self::generate_color_style( $meta, $campaign_id );

        ob_start();
        ?>
        <div class="bp-campaign bp-campaign--<?php echo esc_attr( $layout ); ?> better-payment<?php echo esc_attr( $theme_class ); ?>"
             data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
            <?php echo wp_kses( $color_style, [ 'style' => [] ] ); ?>
            <input type="hidden" class="better_payment_campaign_id"
                   value="<?php echo esc_attr( $campaign_id ); ?>">
            <input type="hidden" class="better_payment_campaign_currency"
                   value="<?php echo esc_attr( self::global_currency() ); ?>">
            <div class="bp-campaign-columns">
                <?php foreach ( $columns as $column ) :
                    $raw_width = $column['width'] ?? '100%';
                    $col_width = preg_match( '/^\d{1,3}(\.\d+)?%$/', $raw_width ) ? $raw_width : '100%';
                    $col_style = 'width: ' . $col_width . ';';
                ?>
                    <div class="bp-campaign-column"
                         style="<?php echo esc_attr( $col_style ); ?>">
                        <?php
                        foreach ( $column['elements'] as $element ) {
                            echo self::render_element( $element, $campaign_id, $post, $meta, $stats );
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate a scoped <style> block for campaign colour customisation.
     *
     * Only emits rules for colours that are explicitly set, so theme CSS remains
     * the default when a colour is empty. Uses !important to override any theme class.
     *
     * @param array $meta        Campaign meta (bpc_color_primary, secondary, tertiary, button).
     * @param int   $campaign_id Scopes the selectors. 0 = isolated builder preview iframe.
     * @return string <style>…</style> or ''.
     */
    private static function generate_color_style( array $meta, int $campaign_id ): string {
        $button = ! empty( $meta['bpc_color_button'] ) ? sanitize_hex_color( $meta['bpc_color_button'] ) : '';

        if ( ! $button ) {
            return '';
        }

        // $campaign_id is typed int and $button is validated hex — both safe for CSS output.
        $scope = $campaign_id > 0
            ? '.bp-campaign[data-campaign-id="' . $campaign_id . '"]'
            : '.bp-campaign';

        $css = $scope . ' .bp-donate_btn { background-color: ' . $button . ' !important; }';

        return '<style>' . wp_strip_all_tags( $css ) . '</style>';
    }

    /**
     * Render a single element by type.
     *
     * @param array    $element     Element definition with type, settings.
     * @param int      $campaign_id
     * @param \WP_Post $post
     * @param array    $meta        Campaign meta from MetaBox::get_all().
     * @param array    $stats       Campaign stats from CampaignStats::get_stats().
     * @return string HTML output.
     */
    public static function render_element(
        array $element,
        int $campaign_id,
        \WP_Post $post,
        array $meta,
        array $stats
    ): string {
        $type     = $element['type']     ?? '';
        $settings = $element['settings'] ?? [];
        $el_id    = $element['id']        ?? '';

        ob_start();

        switch ( $type ) {

            case 'campaign_title':
                $color      = ! empty( $settings['color'] ) ? $settings['color'] : '#1a1a2e';
                $font_size  = ! empty( $settings['font_size'] ) ? $settings['font_size'] : '32px';
                $title_text = ! empty( $settings['title'] ) ? $settings['title'] : $post->post_title;
                $align      = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true ) ? $settings['align'] : 'left';
                ?>
                <h2 class="bp-campaign-title"
                    style="color: <?php echo esc_attr( $color ); ?>; font-size: <?php echo esc_attr( $font_size ); ?>; text-align: <?php echo esc_attr( $align ); ?>;">
                    <?php echo esc_html( $title_text ); ?>
                </h2>
                <?php
                break;

            case 'campaign_description':
                $desc_headline = ! empty( $settings['headline'] ) ? $settings['headline'] : '';
                $desc_content  = ! empty( $settings['content'] )  ? $settings['content']  : $post->post_content;
                $desc_width    = isset( $settings['width'] ) ? max( 10, min( 100, (int) $settings['width'] ) ) : 100;
                $desc_align    = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true ) ? $settings['align'] : 'left';

                if ( ! $desc_headline && ! $desc_content ) break;

                $desc_wrap = 'width:' . $desc_width . '%;';
                if ( 'center' === $desc_align ) $desc_wrap .= 'margin:0 auto;';
                elseif ( 'right' === $desc_align ) $desc_wrap .= 'margin-left:auto;';
                ?>
                <div class="bp-campaign-description"
                     style="text-align:<?php echo esc_attr( $desc_align ); ?>; <?php echo esc_attr( $desc_wrap ); ?>">
                    <?php if ( $desc_headline ) : ?>
                        <h3 class="bp-campaign-description-headline"><?php echo esc_html( $desc_headline ); ?></h3>
                    <?php endif; ?>
                    <?php if ( $desc_content ) : ?>
                        <div class="bp-campaign-description-content"><?php echo wp_kses_post( self::scale_inline_font_sizes( $desc_content ) ); ?></div>
                    <?php endif; ?>
                </div>
                <?php
                break;

            case 'photo':
                $src_id        = isset( $settings['src_id'] ) ? (int) $settings['src_id'] : 0;
                $size          = ! empty( $settings['size'] ) ? $settings['size'] : 'full';
                $allowed_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large', 'full' ];
                if ( ! in_array( $size, $allowed_sizes, true ) ) {
                    $size = 'full';
                }

                // Fallback: resolve attachment ID from URL for elements without a stored src_id.
                if ( $src_id === 0 && ! empty( $settings['src'] ) ) {
                    $src_id = (int) attachment_url_to_postid( $settings['src'] );
                }

                $src = '';
                if ( $src_id > 0 ) {
                    $img_data = wp_get_attachment_image_src( $src_id, $size );
                    $src      = $img_data ? $img_data[0] : '';
                }
                if ( ! $src ) {
                    $src = ! empty( $settings['src'] ) ? $settings['src'] : get_the_post_thumbnail_url( $campaign_id, $size );
                }
                $alt_text = ! empty( $settings['alt'] ) ? $settings['alt'] : $post->post_title;
                $ph_width = isset( $settings['width'] ) ? max( 10, min( 100, (int) $settings['width'] ) ) : 100;
                $ph_align = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true ) ? $settings['align'] : 'center';

                if ( $src ) :
                    $img_style = 'width:auto;max-width:' . $ph_width . '%;height:auto;display:block;border-radius:4px;';
                    if ( 'center' === $ph_align ) $img_style .= 'margin:0 auto;';
                    elseif ( 'right' === $ph_align ) $img_style .= 'margin-left:auto;';
                    ?>
                    <div class="bp-campaign-photo">
                        <img src="<?php echo esc_url( $src ); ?>"
                             alt="<?php echo esc_attr( $alt_text ); ?>"
                             class="bp-campaign-image"
                             style="<?php echo esc_attr( $img_style ); ?>" />
                    </div>
                    <?php
                else : ?>
                    <div class="bp-campaign-photo-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#bbb">
                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                        </svg>
                    </div>
                <?php endif;
                break;

            case 'progress_bar':
                $raised        = (float) $stats['total_raised'];
                $goal          = (float) ( $meta['bpc_goal_amount'] ?? 0 );
                $currency      = self::global_currency();
                $progress      = $stats['progress'];
                $primary       = $meta['bpc_color_primary'] ?: '#6b63f6';
                $headline      = ! empty( $settings['headline'] ) ? $settings['headline'] : '';
                $show_donated  = isset( $settings['show_donated'] ) ? (bool) $settings['show_donated'] : true;
                $show_goal     = isset( $settings['show_goal'] ) ? (bool) $settings['show_goal'] : true;
                $round_amounts = (bool) ( $settings['round_amounts'] ?? false );
                $donate_label  = ! empty( $settings['donate_label'] ) ? $settings['donate_label'] : __( 'Donated:', 'better-payment' );
                $goal_label    = ! empty( $settings['goal_label'] ) ? $settings['goal_label'] : __( 'Goal:', 'better-payment' );
                $width         = isset( $settings['width'] ) ? absint( $settings['width'] ) : 100;
                $align         = ! empty( $settings['align'] ) ? $settings['align'] : 'left';
                $align_map     = [ 'left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end' ];
                $justify       = $align_map[ $align ] ?? 'flex-start';
                $currency_sym  = self::currency_symbol( $currency );

                // Progress label: ceiling (rounded up integer) when round_amounts, else 1 decimal place.
                if ( $round_amounts ) {
                    $display_progress = (int) ceil( $goal > 0 ? min( 100, ( $raised / $goal ) * 100 ) : 0 );
                    $goal_fmt         = number_format( (int) ceil( $goal ) );
                } else {
                    $display_progress = $progress; // 1 decimal float from CampaignStats
                    $goal_fmt         = number_format( $goal, 2 );
                }

                if ( $goal <= 0 ) {
                    break;
                }
                ?>
                <div class="bp-campaign-progress"
                     style="width:<?php echo esc_attr( $width ); ?>%; justify-content:<?php echo esc_attr( $justify ); ?>">
                    <?php if ( $headline ) : ?>
                        <h3 class="bp-progress-headline"><?php echo esc_html( $headline ); ?></h3>
                    <?php endif; ?>
                    <div class="bp-progress-bar-wrap">
                        <div class="bp-progress-bar"
                             style="width:<?php echo esc_attr( $progress ); ?>%;
                                    background-color:<?php echo esc_attr( $primary ); ?>;"></div>
                    </div>
                    <?php if ( $show_donated || $show_goal ) : ?>
                        <div class="bp-progress-labels">
                            <?php if ( $show_donated ) : ?>
                                <span class="bp-progress-donated">
                                    <?php echo esc_html( $donate_label . ' ' . $display_progress . '%' ); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( $show_goal ) : ?>
                                <span class="bp-progress-goal">
                                    <?php echo esc_html( $goal_label . ' ' . $currency_sym . $goal_fmt ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                break;

            case 'campaign_summary':
                $headline      = ! empty( $settings['headline'] ) ? $settings['headline'] : '';
                $show_raised   = (bool) ( $settings['show_raised']  ?? true );
                $show_donors   = (bool) ( $settings['show_donors']  ?? true );
                $show_percent  = (bool) ( $settings['show_percent'] ?? true );
                $show_days     = (bool) ( $settings['show_days']    ?? true );
                $sm_width      = isset( $settings['width'] ) ? max( 10, min( 100, (int) $settings['width'] ) ) : 100;
                $sm_align      = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true ) ? $settings['align'] : 'left';
                $goal          = (float) ( $meta['bpc_goal_amount'] ?? 0 );
                $percent       = $goal > 0 ? min( 100, round( ( (float) $stats['total_raised'] / $goal ) * 100, 1 ) ) : 0;
                $sm_currency     = self::global_currency();
                $sm_currency_sym = self::currency_symbol( $sm_currency );

                $wrap_style = 'width:' . $sm_width . '%;';
                if ( 'center' === $sm_align ) $wrap_style .= 'margin:0 auto;';
                elseif ( 'right' === $sm_align ) $wrap_style .= 'margin-left:auto;';
                ?>
                <div class="bp-campaign-summary-wrap" style="<?php echo esc_attr( $wrap_style ); ?>">
                    <?php if ( $headline ) : ?>
                        <h3 class="bp-summary-headline"><?php echo esc_html( $headline ); ?></h3>
                    <?php endif; ?>
                    <div class="bp-campaign-summary">
                        <?php if ( $show_raised ) : ?>
                            <div class="bp-summary-item">
                                <strong><?php echo esc_html( $sm_currency_sym . number_format( (float) $stats['total_raised'], 2 ) ); ?></strong>
                                <span><?php esc_html_e( 'Raised', 'better-payment' ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( $show_donors ) : ?>
                            <div class="bp-summary-item">
                                <strong><?php echo esc_html( $stats['donor_count'] ); ?></strong>
                                <span><?php esc_html_e( 'Donors', 'better-payment' ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( $show_percent && $goal > 0 ) : ?>
                            <div class="bp-summary-item">
                                <strong><?php echo esc_html( $percent ); ?>%</strong>
                                <span><?php esc_html_e( 'Raised', 'better-payment' ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( $show_days && ! is_null( $stats['days_remaining'] ) ) : ?>
                            <div class="bp-summary-item">
                                <strong><?php echo esc_html( $stats['days_remaining'] ); ?></strong>
                                <span><?php esc_html_e( 'Days Left', 'better-payment' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                break;

            case 'donation_form':
                $button_label = ! empty( $settings['button_label'] )
                    ? $settings['button_label']
                    : ( ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Donate Now', 'better-payment' ) );
                $primary      = $meta['bpc_color_primary'] ?: '#6b63f6';
                $button_color = sanitize_hex_color( $settings['button_color'] ?? '' ) ?: sanitize_hex_color( $primary ) ?: '#6b63f6';
                $open_new_tab = ! empty( $settings['open_new_tab'] );
                $width        = isset( $settings['width'] ) ? max( 10, min( 100, (int) $settings['width'] ) ) : 100;
                $align        = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true )
                    ? $settings['align'] : 'center';

                // URL: element setting takes priority; fall back to campaign meta page ID, then '#'.
                $donate_url = '';
                if ( ! empty( $settings['url'] ) ) {
                    $donate_url = esc_url( $settings['url'] );
                } else {
                    $page_id = absint( $meta['bpc_form_page_id'] ?? 0 );
                    if ( $page_id ) {
                        $donate_url = esc_url( add_query_arg( 'campaign_id', $campaign_id, get_permalink( $page_id ) ) );
                    }
                }
                $url_missing = false;
                if ( ! $donate_url ) {
                    $donate_url  = '#';
                    $url_missing = true;
                }

                if ( $donate_url ) :
                    $min_amount     = isset( $meta['bpc_minimum_amount'] ) && $meta['bpc_minimum_amount'] !== ''
                        ? (float) $meta['bpc_minimum_amount']
                        : 0;
                    $currency       = self::global_currency();
                    $currency_symbol = self::currency_symbol( $currency );

                    $wrap_style = 'width:' . $width . '%;';
                    if ( 'center' === $align ) {
                        $wrap_style .= 'margin:0 auto;';
                    } elseif ( 'right' === $align ) {
                        $wrap_style .= 'margin-left:auto;';
                    }

                    $btn_class = 'bp-donate_btn' . ( $url_missing ? ' bp-donate_btn--no-url' : '' );
                    ?>
                    <div class="bp-campaign-donate-wrap">
                        <?php if ( $min_amount > 0 ) : ?>
                            <p class="bp-min-donation-notice" data-min="<?php echo esc_attr( $min_amount ); ?>">
                                <?php
                                printf(
                                    /* translators: %s: formatted minimum amount with currency symbol */
                                    esc_html__( 'The minimum donation for this campaign is %s.', 'better-payment' ),
                                    esc_html( $currency_symbol . number_format( $min_amount, 2 ) )
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                        <div class="bp-campaign-donate-btn" style="<?php echo esc_attr( $wrap_style ); ?>">
                            <a href="<?php echo $url_missing ? '#' : esc_url( $donate_url ); ?>"
                               class="<?php echo esc_attr( $btn_class ); ?>"
                               style="background-color:<?php echo esc_attr( $button_color ); ?>;"
                               <?php if ( $url_missing ) : ?>
                               aria-disabled="true"
                               title="<?php esc_attr_e( 'Payment page not configured', 'better-payment' ); ?>"
                               <?php else : ?>
                               <?php echo $open_new_tab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                               <?php endif; ?>
                            >
                                <?php echo esc_html( $button_label ); ?>
                            </a>
                        </div>
                    </div>
                    <?php
                endif;
                break;

            case 'organizer':
                $creator_user_id = ! empty( $settings['creator_user_id'] )
                    ? (int) $settings['creator_user_id']
                    : (int) $post->post_author;
                $role_title  = ! empty( $settings['role_title'] ) ? $settings['role_title'] : __( 'Organizer', 'better-payment' );
                $description = ! empty( $settings['description'] ) ? $settings['description'] : '';
                $width       = isset( $settings['width'] ) ? max( 10, min( 100, (int) $settings['width'] ) ) : 100;
                $align       = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true )
                    ? $settings['align'] : 'left';

                $creator = get_user_by( 'ID', $creator_user_id );
                if ( ! $creator ) break;

                $wrap_style = 'width:' . $width . '%;';
                if ( 'center' === $align ) {
                    $wrap_style .= 'margin:0 auto;';
                } elseif ( 'right' === $align ) {
                    $wrap_style .= 'margin-left:auto;';
                }
                ?>
                <div class="bp-campaign-organizer" style="<?php echo esc_attr( $wrap_style ); ?>">
                    <div class="bp-organizer-avatar">
                        <?php echo get_avatar( $creator->user_email, 48 ); ?>
                    </div>
                    <div class="bp-organizer-info">
                        <span class="bp-organizer-name"><?php echo esc_html( $creator->display_name ); ?></span>
                        <span class="bp-organizer-role"><?php echo esc_html( $role_title ); ?></span>
                        <?php if ( $description ) : ?>
                            <div class="bp-organizer-description"><?php echo wp_kses_post( self::scale_inline_font_sizes( $description ) ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                break;

            case 'donate_amount':
                $amounts_meta    = $meta['bpc_suggested_amounts'] ?? [];
                $allow_custom    = (bool) ( $meta['bpc_allow_custom_amount'] ?? 1 );
                $currency        = self::global_currency();
                $currency_symbol = self::currency_symbol( $currency );

                // Fall back to legacy comma-string or defaults when meta is empty.
                if ( empty( $amounts_meta ) ) {
                    $fallback = ! empty( $settings['preset_amounts'] ) ? $settings['preset_amounts'] : '10,25,50,100';
                    foreach ( array_filter( array_map( 'trim', explode( ',', $fallback ) ) ) as $a ) {
                        $amounts_meta[] = [ 'amount' => $a, 'is_default' => false ];
                    }
                }
                ?>
                <div class="bp-campaign-donate">
                    <div class="bp-donate_amounts">
                        <?php foreach ( $amounts_meta as $i => $item ) :
                            $amt        = floatval( $item['amount'] ?? 0 );
                            $uid        = 'bp_camt_' . $campaign_id . '_' . $i;
                            $is_default = ! empty( $item['is_default'] );
                        ?>
                            <input
                                type="radio"
                                class="bp-option-amount"
                                id="<?php echo esc_attr( $uid ); ?>"
                                name="option_amount_<?php echo esc_attr( $campaign_id ); ?>"
                                value="<?php echo esc_attr( $amt ); ?>"
                                <?php checked( $is_default ); ?>
                                hidden
                            />
                            <label for="<?php echo esc_attr( $uid ); ?>" class="bp-amount-label">
                                <?php echo esc_html( $currency_symbol . $amt ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ( $allow_custom ) : ?>
                    <div class="other_amount_section">
                        <span class="bp-amount-currency"><?php echo esc_html( $currency_symbol ); ?></span>
                        <input
                            type="number"
                            class="campaign-custom-amount"
                            min="0"
                            step="0.01"
                            placeholder="<?php esc_attr_e( 'Enter custom amount', 'better-payment' ); ?>"
                        />
                    </div>
                    <?php endif; ?>
                </div>
                <?php
                break;

            case 'social_sharing':
                $sh_headline     = isset( $settings['headline'] ) ? $settings['headline'] : __( 'Share Now', 'better-payment' );
                $sh_open_new_tab = ! empty( $settings['open_new_tab'] );
                $sh_align        = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true ) ? $settings['align'] : 'left';
                $sh_page_url     = get_permalink( $post );
                $sh_title        = rawurlencode( $post->post_title );
                $sh_target       = $sh_open_new_tab ? 'target="_blank" rel="noopener noreferrer"' : '';
                $sh_justify      = [ 'center' => 'center', 'right' => 'flex-end' ][ $sh_align ] ?? 'flex-start';

                $share_links = [
                    'twitter'   => 'https://twitter.com/intent/tweet?url=' . rawurlencode( $sh_page_url ) . '&text=' . $sh_title,
                    'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $sh_page_url ),
                    'linkedin'  => 'https://www.linkedin.com/shareArticle?mini=true&url=' . rawurlencode( $sh_page_url ) . '&title=' . $sh_title,
                    'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . rawurlencode( $sh_page_url ) . '&description=' . $sh_title,
                    'mastodon'  => 'https://mastodonshare.com/?text=' . $sh_title . '&url=' . rawurlencode( $sh_page_url ),
                    'threads'   => 'https://threads.net/intent/post?text=' . $sh_title . '%20' . rawurlencode( $sh_page_url ),
                    'bluesky'   => 'https://bsky.app/intent/compose?text=' . $sh_title . '%20' . rawurlencode( $sh_page_url ),
                ];

                $active_sharing = array_filter( $share_links, function( $url, $key ) use ( $settings ) {
                    return ( $settings[ $key ] ?? true ) !== false;
                }, ARRAY_FILTER_USE_BOTH );

                if ( $active_sharing || $sh_headline ) :
                ?>
                <div class="bp-social-sharing" style="text-align:<?php echo esc_attr( $sh_align ); ?>;">
                    <?php if ( $sh_headline ) : ?>
                        <p class="bp-social-headline"><?php echo esc_html( $sh_headline ); ?></p>
                    <?php endif; ?>
                    <?php if ( $active_sharing ) : ?>
                        <div class="bp-social-icons" style="justify-content:<?php echo esc_attr( $sh_justify ); ?>;">
                            <?php foreach ( $active_sharing as $key => $share_url ) : ?>
                                <a href="<?php echo esc_url( $share_url ); ?>"
                                   class="bp-social-icon"
                                   <?php echo $sh_target; ?>
                                   title="<?php echo esc_attr( ucfirst( $key ) ); ?>">
                                    <?php echo self::social_icon_svg( $key ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                endif;
                break;

            case 'social_links':
                $sl_headline     = isset( $settings['headline'] ) ? $settings['headline'] : __( 'Follow Now', 'better-payment' );
                $sl_open_new_tab = ! empty( $settings['open_new_tab'] );
                $sl_align        = in_array( $settings['align'] ?? '', [ 'left', 'center', 'right' ], true ) ? $settings['align'] : 'left';
                $sl_target       = $sl_open_new_tab ? 'target="_blank" rel="noopener noreferrer"' : '';
                $sl_justify      = [ 'center' => 'center', 'right' => 'flex-end' ][ $sl_align ] ?? 'flex-start';

                $all_link_keys  = [ 'twitter', 'facebook', 'linkedin', 'instagram', 'tiktok', 'pinterest', 'youtube', 'threads', 'bluesky', 'mastodon' ];
                $active_links   = [];
                foreach ( $all_link_keys as $key ) {
                    if ( ! empty( $settings[ $key ] ) ) {
                        $active_links[ $key ] = $settings[ $key ];
                    }
                }

                if ( $active_links || $sl_headline ) :
                ?>
                <div class="bp-social-links" style="text-align:<?php echo esc_attr( $sl_align ); ?>;">
                    <?php if ( $sl_headline ) : ?>
                        <p class="bp-social-headline"><?php echo esc_html( $sl_headline ); ?></p>
                    <?php endif; ?>
                    <?php if ( $active_links ) : ?>
                        <div class="bp-social-icons" style="justify-content:<?php echo esc_attr( $sl_justify ); ?>;">
                            <?php foreach ( $active_links as $key => $url ) : ?>
                                <a href="<?php echo esc_url( $url ); ?>"
                                   class="bp-social-icon"
                                   <?php echo $sl_target; ?>
                                   title="<?php echo esc_attr( ucfirst( $key ) ); ?>">
                                    <?php echo self::social_icon_svg( $key ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                endif;
                break;
        }

        $html = ob_get_clean();

        if ( ! $html || ! $el_id ) {
            return $html;
        }

        return '<div class="bp-element-wrap" data-bp-element-id="' . esc_attr( $el_id ) . '">' . $html . '</div>';
    }

    /**
     * Normalize raw stored layout to the column schema.
     * Handles both old flat-array format and new column format.
     *
     * @param mixed $raw Value from MetaBox::get_all()['bpc_fields_layout'].
     * @return array Normalized layout with 'layout' and 'columns' keys.
     */
    public static function normalize_layout( $raw ): array {
        if ( is_array( $raw ) && isset( $raw['columns'] ) ) {
            return $raw;
        }

        // Legacy flat format: wrap all elements into a single column.
        $elements = is_array( $raw ) ? $raw : [];
        return [
            'layout'  => '1-column',
            'columns' => [
                [
                    'id'       => 'main',
                    'label'    => 'Main Content',
                    'width'    => '100%',
                    'elements' => $elements,
                ],
            ],
        ];
    }

    /**
     * Default layout used when no layout is saved (campaign has no fields yet).
     *
     * @return array
     */
    private static function social_icon_svg( string $network ): string {
        $paths = [
            'twitter'   => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z',
            'facebook'  => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
            'linkedin'  => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
            'instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162S8.597 18.163 12 18.163s6.162-2.759 6.162-6.162S15.403 5.838 12 5.838zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
            'tiktok'    => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
            'pinterest' => 'M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z',
            'youtube'   => 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
            'threads'   => 'M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.028-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.964-.065-1.19.408-2.285 1.33-3.082.88-.76 2.119-1.207 3.583-1.291a13.853 13.853 0 013.271.165c-.07-.75-.273-1.318-.614-1.716-.434-.507-1.119-.768-2.036-.777h-.045c-.67 0-1.938.181-2.669 1.4l-1.812-.755c.97-1.868 2.836-2.677 4.484-2.677h.06c3.233.03 5.164 2.01 5.34 5.49.208 3.394-1.112 5.49-3.317 6.548-.384.186-.785.34-1.197.461C16.418 23.61 14.397 24 12.186 24z',
            'bluesky'   => 'M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 01-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.204-.659-.299-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8z',
            'mastodon'  => 'M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 00.023-.043v-1.809a.052.052 0 00-.066-.051c-1.517.363-3.072.546-4.632.546-2.685 0-3.463-1.284-3.674-1.818a5.593 5.593 0 01-.319-1.433.053.053 0 01.066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.124-.01 1.554-.043 3.19-.167 4.72-.498.038-.009.075-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.005-.109.033-1.25.033-1.36 0-.43.138-3.032-.019-4.643zm-3.22 9.214h-2.058V9.47c0-1.063-.447-1.601-1.35-1.601-1 0-1.5.647-1.5 1.923v2.786h-2.048V9.792c0-1.276-.5-1.923-1.5-1.923-.903 0-1.35.538-1.35 1.601v5.04H7.884V9.32c0-1.062.27-1.907.81-2.534.558-.627 1.287-.948 2.192-.948 1.047 0 1.84.402 2.363 1.206l.509.855.51-.855c.523-.804 1.316-1.206 2.363-1.206.904 0 1.633.32 2.192.948.54.627.925 1.472.925 2.534v5.19z',
        ];
        if ( ! isset( $paths[ $network ] ) ) {
            return '';
        }
        return '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="' . esc_attr( $paths[ $network ] ) . '"/></svg>';
    }

    /**
     * Scale up inline px font-sizes in HTML content saved by the rich text editor.
     * Adds 2px to every explicit pixel font-size so the text matches the campaign's
     * base 16px scale (editor default is 14px).
     */
    private static function scale_inline_font_sizes( string $html ): string {
        return preg_replace_callback(
            '/\bfont-size\s*:\s*(\d+(?:\.\d+)?)px/i',
            function ( $m ) {
                return 'font-size: ' . ( (float) $m[1] + 2 ) . 'px';
            },
            $html
        );
    }

    /**
     * Returns the global Better Payment currency code from plugin settings.
     */
    private static function global_currency(): string {
        $code = DB::get_settings( 'better_payment_settings_general_general_currency' );
        return ( is_string( $code ) && $code !== '' ) ? $code : 'USD';
    }

    private static function currency_symbol( string $code ): string {
        $map = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥',
            'CAD' => 'CA$', 'AUD' => 'A$', 'INR' => '₹', 'BRL' => 'R$',
            'MXN' => 'MX$', 'SGD' => 'S$', 'CHF' => 'CHF', 'SEK' => 'kr',
            'NOK' => 'kr', 'DKK' => 'kr', 'NZD' => 'NZ$', 'ZAR' => 'R',
            'BDT' => '৳', 'PKR' => '₨', 'NGN' => '₦', 'KES' => 'KSh',
        ];
        return $map[ strtoupper( $code ) ] ?? $code;
    }

    /**
     * Render a template definition as HTML for the picker iframe preview.
     *
     * @param string $key Template key from TemplateManager.
     * @return string HTML fragment, or empty string if key not found.
     */
    public static function render_template_preview( string $key ): string {
        $templates = TemplateManager::get_all();
        if ( ! isset( $templates[ $key ] ) ) {
            return '';
        }
        $template = $templates[ $key ];
        $columns  = $template['columns'] ?? [];
        $layout   = $template['layout']  ?? '1-column';

        $first_users      = get_users( [ 'fields' => [ 'ID' ], 'number' => 1 ] );
        $first_creator_id = ! empty( $first_users ) ? (int) $first_users[0]->ID : get_current_user_id();

        $fake_post = new \WP_Post( (object) [
            'ID'           => 0,
            'post_title'   => $template['default_title'] ?? $template['label'] ?? 'Campaign Preview',
            'post_content' => '',
            'post_author'  => $first_creator_id,
            'post_type'    => 'bp_campaign',
            'post_status'  => 'publish',
            'post_name'    => $key,
        ] );

        $meta = [
            'bpc_goal_amount'         => 10000,
            'bpc_color_primary'       => $template['preview_color'] ?? '#6b63f6',
            'bpc_color_button'        => '',
            'bpc_suggested_amounts'   => [],
            'bpc_allow_custom_amount' => true,
            'bpc_minimum_amount'      => '',
            'bpc_form_page_id'        => 0,
            'bpc_status'              => 'active',
            'bpc_template_key'        => $key,
            'bpc_css_class'           => '',
        ];

        $stats = [
            'total_raised'   => 3750,
            'progress'       => 37.5,
            'donor_count'    => 42,
            'days_remaining' => 18,
        ];

        $theme_class = isset( $template['theme_class'] ) ? ' ' . sanitize_html_class( $template['theme_class'] ) : '';

        ob_start();
        ?>
        <div class="bp-campaign bp-campaign--<?php echo esc_attr( $layout ); ?> better-payment<?php echo esc_attr( $theme_class ); ?>">
            <div class="bp-campaign-columns">
                <?php foreach ( $columns as $column ) :
                    $col_style = 'width: ' . esc_attr( $column['width'] ?? '100%' ) . ';';
                    if ( ! empty( $column['style'] ) ) {
                        $col_style .= ' ' . esc_attr( $column['style'] );
                    }
                ?>
                    <div class="bp-campaign-column"
                         style="<?php echo $col_style; ?>">
                        <?php
                        foreach ( $column['elements'] as $element ) {
                            echo self::render_element( $element, 0, $fake_post, $meta, $stats );
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Build a full HTML preview document for the builder's live-preview iframe.
     *
     * Each element is wrapped in a <div data-bp-element-id> so the JS overlay
     * can measure positions and wire up hover/drag interactions. Works for both
     * new unsaved campaigns (campaign_id = 0) and existing ones.
     *
     * @param array $layout_data  Builder layout: { layout, columns }.
     * @param array $meta_input   Campaign meta from the builder store (includes 'title').
     * @param int   $campaign_id  0 for new campaigns; real ID to pull live stats.
     * @return string Full HTML document string.
     */
    public static function build_preview_document(
        array $layout_data,
        array $meta_input,
        int $campaign_id = 0
    ): string {
        // ── Post + stats ──────────────────────────────────────────────────────
        $post  = null;
        $stats = null;

        if ( $campaign_id > 0 ) {
            $real = get_post( $campaign_id );
            if ( $real && $real->post_type === 'bp_campaign' ) {
                $post  = $real;
                $stats = CampaignStats::get_stats( $campaign_id );
            }
        }

        if ( ! $post ) {
            $post = new \WP_Post( (object) [
                'ID'           => 0,
                'post_title'   => sanitize_text_field( $meta_input['title'] ?? 'Campaign Preview' ),
                'post_content' => '',
                'post_author'  => get_current_user_id(),
                'post_type'    => 'bp_campaign',
                'post_status'  => 'publish',
                'post_name'    => 'preview',
            ] );
            $stats = [ 'total_raised' => 0, 'progress' => 0, 'donor_count' => 0, 'days_remaining' => null ];
        } else {
            // Always reflect the current editor title, even for saved campaigns.
            $override = sanitize_text_field( $meta_input['title'] ?? '' );
            if ( $override !== '' ) {
                $post->post_title = $override;
            }
        }

        // Override stats fields that depend on unsaved builder meta so the preview
        // reflects the current editor values without requiring a save first.
        $preview_end_date = $meta_input['bpc_end_date'] ?? '';
        if ( $preview_end_date !== '' ) {
            $diff = strtotime( $preview_end_date ) - current_time( 'timestamp' );
            $stats['days_remaining'] = max( 0, (int) ceil( $diff / DAY_IN_SECONDS ) );
        } else {
            $stats['days_remaining'] = null;
        }

        $preview_goal = (float) ( $meta_input['bpc_goal_amount'] ?? 0 );
        if ( $preview_goal > 0 ) {
            $stats['progress'] = min( 100.0, round( ( $stats['total_raised'] / $preview_goal ) * 100, 1 ) );
        }

        // ── Meta ─────────────────────────────────────────────────────────────
        $meta_defaults = [
            'bpc_goal_amount'         => 0,
            'bpc_color_primary'       => '#6b63f6',
            'bpc_color_button'        => '',
            'bpc_suggested_amounts'   => [],
            'bpc_allow_custom_amount' => true,
            'bpc_minimum_amount'      => '',
            'bpc_form_page_id'        => 0,
            'bpc_status'              => 'active',
            'bpc_template_key'        => '',
            'bpc_css_class'           => '',
        ];

        if ( $campaign_id > 0 ) {
            $meta = array_merge( MetaBox::get_all( $campaign_id ), $meta_defaults, $meta_input );
        } else {
            $meta = array_merge( $meta_defaults, $meta_input );
        }

        // ── Layout ───────────────────────────────────────────────────────────
        $columns = $layout_data['columns'] ?? [];
        $layout  = $layout_data['layout']  ?? '1-column';

        if ( empty( $columns ) ) {
            $default = self::default_layout();
            $columns = $default['columns'];
            $layout  = $default['layout'];
        }

        // ── Template theme class ──────────────────────────────────────────────
        $template_key  = $meta['bpc_template_key'] ?? '';
        $all_templates = TemplateManager::get_all();
        $theme_class   = ( $template_key && isset( $all_templates[ $template_key ]['theme_class'] ) )
            ? ' ' . sanitize_html_class( $all_templates[ $template_key ]['theme_class'] )
            : '';

        // ── Campaign HTML with element-id wrappers ────────────────────────────
        $color_style = self::generate_color_style( $meta, $campaign_id );

        ob_start();
        ?>
        <div class="bp-campaign bp-campaign--<?php echo esc_attr( $layout ); ?> better-payment<?php echo esc_attr( $theme_class ); ?>"
             data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
            <?php echo wp_kses( $color_style, [ 'style' => [] ] ); ?>
            <input type="hidden" class="better_payment_campaign_id"
                   value="<?php echo esc_attr( $campaign_id ); ?>">
            <input type="hidden" class="better_payment_campaign_currency"
                   value="<?php echo esc_attr( self::global_currency() ); ?>">
            <div class="bp-campaign-columns">
                <?php foreach ( $columns as $column ) :
                    $is_empty  = empty( $column['elements'] );
                    $col_class = 'bp-campaign-column' . ( $is_empty ? ' bp-col-empty' : '' );
                    $raw_width = $column['width'] ?? '100%';
                    $col_width = preg_match( '/^\d{1,3}(\.\d+)?%$/', $raw_width ) ? $raw_width : '100%';
                    $col_style = 'width: ' . $col_width . ';';
                ?>
                    <div class="<?php echo esc_attr( $col_class ); ?>"
                         data-bp-column-id="<?php echo esc_attr( $column['id'] ); ?>"
                         style="<?php echo esc_attr( $col_style ); ?>">
                        <?php foreach ( $column['elements'] as $element ) : ?>
                            <div data-bp-element-id="<?php echo esc_attr( $element['id'] ?? '' ); ?>"
                                 data-bp-column-id="<?php echo esc_attr( $column['id'] ); ?>"
                                 class="bp-builder-el-wrap">
                                <?php echo self::render_element( $element, $campaign_id, $post, $meta, $stats ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        $campaign_html = ob_get_clean();

        // ── Assemble full HTML document ───────────────────────────────────────
        // Mirror exactly what the frontend enqueues (single-bp_campaign.php +
        // Shortcode::enqueue_frontend_styles): both campaign-display CSS and
        // fundraising-campaign CSS are required for pixel-perfect parity.
        $v              = BETTER_PAYMENT_VERSION;
        $display_css    = BETTER_PAYMENT_ASSETS . '/blocks/campaign-display/style.min.css';
        $fundraising_css = BETTER_PAYMENT_ASSETS . '/css/fundraising-campaign.min.css';

        $extra_link = file_exists( BETTER_PAYMENT_ASSETS_PATH . '/css/fundraising-campaign.min.css' )
            ? '<link rel="stylesheet" href="' . esc_url( $fundraising_css ) . '?v=' . $v . '">' . "\n"
            : '';

        return '<!DOCTYPE html>' . "\n"
            . '<html>' . "\n"
            . '<head>' . "\n"
            . '<meta charset="utf-8">' . "\n"
            . '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
            . '<base href="' . esc_url( home_url( '/' ) ) . '">' . "\n"
            . '<link rel="stylesheet" href="' . esc_url( $display_css ) . '?v=' . $v . '">' . "\n"
            . $extra_link
            . '<style>' . "\n"
            . '*, *::before, *::after { box-sizing: border-box; }' . "\n"
            . 'html, body { margin: 0; padding: 0; background: #fff; }' . "\n"
            . ( $theme_class ? 'body { padding: 0 24px 16px; }' . "\n" : '' )
            . '.bp-builder-el-wrap { position: relative; }' . "\n"
            // Empty columns need a minimum height so the overlay ColumnDropZone can
            // measure them and render the dashed drop-zone border at the correct size.
            . '.bp-campaign-column.bp-col-empty { min-height: 160px; }' . "\n"
            . '.bp-campaign-columns[style*="stretch"] .bp-builder-el-wrap {' . "\n"
            . '    height: 100%;' . "\n"
            . '    display: flex;' . "\n"
            . '    flex-direction: column;' . "\n"
            . '}' . "\n"
            . '</style>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $campaign_html ) . "\n"
            . '</body>' . "\n"
            . '</html>';
    }

    private static function default_layout(): array {
        return [
            'layout'  => '2-column',
            'columns' => [
                [
                    'id'       => 'main',
                    'label'    => 'Main Content',
                    'width'    => '65%',
                    'elements' => [
                        [ 'id' => 'def_photo',  'type' => 'photo',                'settings' => [] ],
                        [ 'id' => 'def_title',  'type' => 'campaign_title',       'settings' => [] ],
                        [ 'id' => 'def_desc',   'type' => 'campaign_description', 'settings' => [] ],
                    ],
                ],
                [
                    'id'       => 'sidebar',
                    'label'    => 'Sidebar',
                    'width'    => '35%',
                    'elements' => [
                        [ 'id' => 'def_progress', 'type' => 'progress_bar',    'settings' => [] ],
                        [ 'id' => 'def_summary',  'type' => 'campaign_summary', 'settings' => [] ],
                        [ 'id' => 'def_donate',   'type' => 'donation_form',    'settings' => [] ],
                    ],
                ],
            ],
        ];
    }
}
