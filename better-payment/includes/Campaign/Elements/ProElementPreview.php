<?php

namespace Better_Payment\Lite\Campaign\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mock, editor-only previews of the three Pro elements (Donors Wall, FAQ, Video)
 * for sites without Better Payment Pro.
 *
 * Replaces the old flat "PRO" banner. A banner told a free user nothing about
 * what the element does; this paints a realistic approximation so they can see
 * the widget, watch it react to the (disabled) controls beside it, and judge
 * whether Pro is worth buying.
 *
 * ── The one rule that matters ────────────────────────────────────────────────
 * **This NEVER renders on the public frontend.** Every method here fabricates
 * data — invented donor names, invented amounts. On a live fundraising page that
 * is not a cosmetic bug, it is a lie about who gave money. Two independent gates
 * enforce it:
 *
 *   1. Only `RendererService::build_preview_document()` calls this. The public
 *      paths (`render_campaign()`, the shortcode, the block, the CPT template)
 *      have no reference to it — there a Pro element falls through
 *      `render_element()`'s `default:` case, the render filter has no listener,
 *      returns `''`, and the empty-element handler drops it entirely.
 *   2. {@see self::render()} re-checks `$is_preview` itself and returns `''` if
 *      it is anything but strictly true, so a future caller cannot leak it by
 *      accident.
 *
 * `EmptyElementPlaceholderTest` and `ProElementDowngradeTest` guard both.
 *
 * ── Why the markup lives here and not in Pro ─────────────────────────────────
 * Pro stays fully self-contained: its own views in
 * `includes/Admin/views/campaign-builder/` are untouched and never loaded by
 * Lite. That means this markup is a deliberate, simplified re-creation rather
 * than a shared template — it will not track Pro's styling pixel-for-pixel, and
 * it is not supposed to. It is a demo, and the PRO ribbon says so.
 *
 * Everything is inline-styled on purpose, same reasoning as the empty-element
 * placeholder: the builder's preview iframe ships no `<style>` block of its own
 * and **loads no dashicons**, so class hooks and icon fonts are both unavailable.
 */
class ProElementPreview {

    /**
     * Element types this class can mock.
     *
     * @return array<int, string>
     */
    public static function supported_types(): array {
        return [ 'donors_wall', 'faq', 'video' ];
    }

    /**
     * Render a mock preview for a Pro element.
     *
     * @param string $type       Element type.
     * @param array  $settings   Element settings as saved in the layout. Honoured
     *                           where practical so a downgraded campaign still
     *                           shows the author's real configuration.
     * @param bool   $is_preview Must be strictly true. See the class docblock.
     * @return string HTML, or '' when not a builder preview / unsupported type.
     */
    public static function render( string $type, array $settings, bool $is_preview ): string {
        if ( true !== $is_preview ) {
            return '';
        }

        if ( ! in_array( $type, self::supported_types(), true ) ) {
            return '';
        }

        // Whether the mock needs the "sample data" disclosure under it. Donors
        // Wall invents donors and FAQ echoes the author's own questions back at a
        // sample scale, so both must say so. The Video mock displays no data at
        // all — it is a drawn poster frame with no names, figures or copy that
        // could be mistaken for the user's — so the note has nothing to disclose
        // and only adds a line of grey text under the artwork.
        $show_note = true;

        switch ( $type ) {
            case 'donors_wall':
                $body  = self::donors_wall( $settings );
                $label = __( 'Donors Wall', 'better-payment' );
                break;
            case 'faq':
                $body  = self::faq( $settings );
                $label = __( 'FAQ', 'better-payment' );
                break;
            default:
                $body      = self::video( $settings );
                $label     = __( 'Video', 'better-payment' );
                $show_note = false;
                break;
        }

        return self::wrap( $body, $label, $show_note );
    }

    /**
     * Chrome around every mock: a dashed frame plus a PRO ribbon, so a demo is
     * never mistaken for the real thing.
     *
     * @param string $body      Already-escaped inner HTML.
     * @param string $label     Element label.
     * @param bool   $show_note Whether to print the "sample data" disclosure.
     *                          Defaults true: a mock that shows fabricated data
     *                          must say so, and that is the common case. Only the
     *                          Video frame opts out, because it displays no data
     *                          to disclose. The PRO ribbon is NOT optional — it is
     *                          what stops any mock being read as the real element.
     * @return string
     */
    private static function wrap( string $body, string $label, bool $show_note = true ): string {
        $frame = 'position:relative;border:1px dashed #c3c4c7;border-radius:8px;'
            . 'padding:28px 16px 16px;margin:0 0 4px;background:#fff;';

        // Pro-orange gradient — the exact fill of the "Get PRO to Unlock" button
        // (`.bp-lc-hotspot__pro-lock-btn`), so the ribbon reads as the same Pro
        // upsell affordance rather than a second, unrelated brand colour.
        $ribbon = 'position:absolute;top:0;left:0;display:inline-flex;align-items:center;gap:6px;'
            . 'background:linear-gradient(135deg,#f6a821,#ec6a2b);color:#fff;font-size:10px;font-weight:700;letter-spacing:.06em;'
            . 'text-transform:uppercase;padding:3px 10px;border-radius:8px 0 8px 0;';

        $note = 'margin:12px 0 0;font-size:11px;line-height:1.5;color:#787c82;text-align:center;';

        ob_start();
        ?>
        <div style="<?php echo esc_attr( $frame ); ?>" data-bp-pro-preview="1">
            <span style="<?php echo esc_attr( $ribbon ); ?>">
                <?php
                printf(
                    /* translators: %s: element name, e.g. "Donors Wall". */
                    esc_html__( 'Pro preview — %s', 'better-payment' ),
                    esc_html( $label )
                );
                ?>
            </span>
            <?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts below. ?>
            <?php if ( $show_note ) : ?>
            <p style="<?php echo esc_attr( $note ); ?>">
                <?php esc_html_e( 'Sample data shown in the editor only. Activate Better Payment Pro to use this element on your live campaign.', 'better-payment' ); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /* --------------------------------------------------------------------- */
    /* Per-element mocks                                                      */
    /* --------------------------------------------------------------------- */

    /**
     * @param array $settings
     * @return string
     */
    private static function donors_wall( array $settings ): string {
        // A completely unset key takes the schema default; an explicit 0 means
        // "show none" and is honoured — same contract as Pro's real renderer, so
        // the preview doesn't disagree with what Pro would do.
        $limit  = isset( $settings['number_to_show'] ) ? (int) $settings['number_to_show'] : 10;
        $limit  = max( 0, min( 6, $limit ) );
        $layout = isset( $settings['layout'] ) && in_array( $settings['layout'], [ 'list', 'grid', 'ticker' ], true )
            ? $settings['layout']
            : 'list';
        $columns = isset( $settings['columns'] ) ? max( 2, min( 4, (int) $settings['columns'] ) ) : 2;

        $show_summary = ! isset( $settings['show_summary'] ) || $settings['show_summary'];
        $show_name    = ! isset( $settings['show_name'] ) || $settings['show_name'];
        $show_amount  = ! isset( $settings['show_amount'] ) || $settings['show_amount'];
        $show_avatar  = ! isset( $settings['show_avatar'] ) || $settings['show_avatar'];
        $show_date    = ! isset( $settings['show_date'] ) || $settings['show_date'];

        $headline = isset( $settings['headline'] ) ? (string) $settings['headline'] : '';
        $accent   = self::safe_color( isset( $settings['accent_color'] ) ? $settings['accent_color'] : '' );

        $donors = array_slice( self::sample_donors(), 0, $limit );

        $row_style = 'grid' === $layout
            ? 'display:grid;grid-template-columns:repeat(' . (int) $columns . ',minmax(0,1fr));gap:10px;'
            : 'display:flex;flex-direction:column;gap:8px;';

        ob_start();
        ?>
        <div>
            <?php if ( '' !== $headline ) : ?>
                <h4 style="margin:0 0 12px;font-size:16px;font-weight:600;color:#1a1a2e;">
                    <?php echo esc_html( $headline ); ?>
                </h4>
            <?php endif; ?>

            <?php if ( $show_summary ) : ?>
                <div style="display:flex;gap:20px;padding:10px 12px;margin:0 0 12px;border-radius:6px;background:#f6f7f7;">
                    <span style="font-size:12px;color:#50575e;">
                        <strong style="display:block;font-size:16px;color:<?php echo esc_attr( $accent ); ?>;">$4,820</strong>
                        <?php esc_html_e( 'raised', 'better-payment' ); ?>
                    </span>
                    <span style="font-size:12px;color:#50575e;">
                        <strong style="display:block;font-size:16px;color:#1a1a2e;">37</strong>
                        <?php esc_html_e( 'donors', 'better-payment' ); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ( 'ticker' === $layout && ! empty( $donors ) ) : ?>
                <p style="margin:0 0 8px;font-size:11px;color:#787c82;font-style:italic;">
                    <?php esc_html_e( 'Ticker layout scrolls automatically on the live campaign.', 'better-payment' ); ?>
                </p>
            <?php endif; ?>

            <?php if ( empty( $donors ) ) : ?>
                <p style="margin:0;font-size:12px;color:#787c82;">
                    <?php esc_html_e( 'Donor list hidden — “Number of Donors To Show” is set to 0.', 'better-payment' ); ?>
                </p>
            <?php else : ?>
                <div style="<?php echo esc_attr( $row_style ); ?>">
                    <?php foreach ( $donors as $donor ) : ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid #f0f0f1;border-radius:6px;">
                            <?php if ( $show_avatar ) : ?>
                                <span style="flex:0 0 auto;width:28px;height:28px;border-radius:50%;background:<?php echo esc_attr( $accent ); ?>;color:#fff;font-size:12px;font-weight:600;display:flex;align-items:center;justify-content:center;">
                                    <?php echo esc_html( mb_substr( $donor['name'], 0, 1 ) ); ?>
                                </span>
                            <?php endif; ?>
                            <span style="flex:1 1 auto;min-width:0;">
                                <?php if ( $show_name ) : ?>
                                    <span style="display:block;font-size:13px;font-weight:600;color:#1a1a2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?php echo esc_html( $donor['name'] ); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ( $show_date ) : ?>
                                    <span style="display:block;font-size:11px;color:#787c82;">
                                        <?php echo esc_html( $donor['when'] ); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <?php if ( $show_amount ) : ?>
                                <span style="flex:0 0 auto;font-size:13px;font-weight:600;color:<?php echo esc_attr( $accent ); ?>;">
                                    <?php echo esc_html( $donor['amount'] ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * The FAQ mock renders the author's OWN items, not invented ones — the
     * questions are already in the saved settings and are not Pro data. Only the
     * accordion's interactivity is missing, so every item is shown open.
     *
     * @param array $settings
     * @return string
     */
    private static function faq( array $settings ): string {
        $heading = isset( $settings['heading'] ) ? (string) $settings['heading'] : '';
        $accent  = self::safe_color( isset( $settings['accent_color'] ) ? $settings['accent_color'] : '' );

        $items = ( isset( $settings['items'] ) && is_array( $settings['items'] ) ) ? $settings['items'] : [];
        $items = array_slice( $items, 0, 30 );

        ob_start();
        ?>
        <div>
            <?php if ( '' !== $heading ) : ?>
                <h4 style="margin:0 0 12px;font-size:16px;font-weight:600;color:#1a1a2e;">
                    <?php echo esc_html( $heading ); ?>
                </h4>
            <?php endif; ?>

            <?php if ( empty( $items ) ) : ?>
                <p style="margin:0;font-size:12px;color:#787c82;">
                    <?php esc_html_e( 'No questions added yet.', 'better-payment' ); ?>
                </p>
            <?php else : ?>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ( $items as $item ) :
                        $question = isset( $item['question'] ) ? (string) $item['question'] : '';
                        $answer   = isset( $item['answer'] ) ? (string) $item['answer'] : '';

                        if ( '' === $question && '' === $answer ) {
                            continue;
                        }
                        ?>
                        <div style="border:1px solid #f0f0f1;border-radius:6px;padding:10px 12px;">
                            <div style="display:flex;align-items:flex-start;gap:8px;">
                                <span style="flex:0 0 auto;color:<?php echo esc_attr( $accent ); ?>;font-weight:700;line-height:1.4;">+</span>
                                <span style="flex:1 1 auto;font-size:13px;font-weight:600;color:#1a1a2e;line-height:1.4;">
                                    <?php echo esc_html( $question ); ?>
                                </span>
                            </div>
                            <?php if ( '' !== $answer ) : ?>
                                <p style="margin:6px 0 0 18px;font-size:12px;line-height:1.6;color:#50575e;">
                                    <?php echo esc_html( $answer ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * A poster frame, never a real embed.
     *
     * Rendering the actual `<iframe>` would mean Lite reproducing Pro's URL
     * parsing and, for self-hosted files, its oEmbed HTTP call — exactly the
     * business logic that must stay in Pro. It would also let a free install
     * ship a working Video element, which is the feature being sold.
     *
     * The frame is **drawn**, not fetched. It deliberately does not pull the real
     * thumbnail from `img.youtube.com`, which would have meant:
     *   - parsing a video ID out of the URL — the Pro-owned logic above;
     *   - an outbound request to Google on every builder preview render, carrying
     *     the admin's IP and referrer, which no one opted into;
     *   - a YouTube-only result, since Vimeo thumbnails need an oEmbed API call.
     *     Real artwork for one provider and a grey box for the others is a worse,
     *     less predictable preview than one honest frame for all three.
     *
     * What it must NOT do is invent facts. The play button, scrubber and duration
     * pill are chrome — they say "this is a video player" and cannot be read as a
     * claim about the user's campaign. A plausible-looking video title or a
     * "12K views" counter would be, and is the same mistake the fictional donor
     * names in {@see self::sample_donors()} are careful to avoid.
     *
     * That is also why nothing is printed *under* the frame. A channel row (avatar,
     * title, source host) lived here briefly and was cut: it added a second block
     * of grey text below the artwork for no information the user cannot see in the
     * settings panel, and the title line was placeholder copy dressed as content.
     * The poster is the whole element. Because it displays no data, `render()`
     * also passes `$show_note = false` to {@see self::wrap()} — there is no sample
     * data here to disclose, unlike Donors Wall and FAQ.
     *
     * With the source line gone, `$settings['url']` is no longer read here at all
     * — `aspect_ratio` is the only setting that changes what is drawn. That is the
     * strongest form of the boundary rule above: the mock cannot leak anything
     * about the URL because it never looks at it.
     *
     * @param array $settings
     * @return string
     */
    private static function video( array $settings ): string {
        $ratios = [
            '16-9' => '56.25%',
            '4-3'  => '75%',
            '1-1'  => '100%',
            '21-9' => '42.86%',
        ];
        $ratio_key = isset( $settings['aspect_ratio'] ) ? (string) $settings['aspect_ratio'] : '16-9';
        $padding   = isset( $ratios[ $ratio_key ] ) ? $ratios[ $ratio_key ] : $ratios['16-9'];

        // Branded artwork over a CSS gradient, in that order.
        //
        // The gradient is not decoration for the artwork — it is the fallback that
        // renders if the SVG 404s (a partial deploy, a CDN rewrite, an install
        // serving assets/ from somewhere unexpected). Declared underneath rather
        // than instead of, so a missing file degrades to the plain dark poster
        // this element had before instead of to a white rectangle with a red play
        // button floating on it.
        //
        // The SVG carries the product's own palette — Better Payment indigo, Pro
        // orange — plus the heart-in-hands mark shared with ai-default-hero.svg,
        // so a placeholder video reads as the same family as AI-generated campaign
        // artwork rather than as generic stock.
        $poster_layers = 'linear-gradient(135deg,#252c52 0%,#151b31 55%,#0a0e18 100%)';

        if ( defined( 'BETTER_PAYMENT_ASSETS' ) ) {
            $poster_url    = BETTER_PAYMENT_ASSETS . '/img/campaign/video-poster.svg';
            $poster_layers = "url('" . esc_url( $poster_url ) . "') center/cover no-repeat," . $poster_layers;
        }

        ob_start();
        ?>
        <div>
            <div style="position:relative;width:100%;padding-bottom:<?php echo esc_attr( $padding ); ?>;background:<?php echo esc_attr( $poster_layers ); ?>;border-radius:10px;overflow:hidden;">

                <?php /* Play button — YouTube's rounded pill, not a circle. */ ?>
                <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:68px;height:48px;border-radius:14px;background:rgba(255,0,0,.92);box-shadow:0 2px 10px rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center;">
                    <span style="display:block;width:0;height:0;margin-left:4px;border-style:solid;border-width:11px 0 11px 19px;border-color:transparent transparent transparent #fff;"></span>
                </span>

                <?php /* Duration pill. Chrome, not a claim — see the docblock. */ ?>
                <span style="position:absolute;right:10px;bottom:12px;padding:2px 5px;border-radius:3px;background:rgba(0,0,0,.8);color:#fff;font-size:11px;font-weight:600;line-height:1.4;">2:14</span>

                <?php
                /*
                 * Scrubber — the red played portion only, deliberately with no
                 * track behind it. A light track (this was rgba(255,255,255,.28))
                 * paints a pale grey band across the full width of an otherwise
                 * dark poster, and at 3px tall against the rounded bottom corners
                 * it reads as a rendering seam rather than as part of the player.
                 * The red segment alone carries the same meaning.
                 */
                ?>
                <span style="position:absolute;left:0;bottom:0;width:38%;height:3px;background:#f00;"></span>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /* --------------------------------------------------------------------- */
    /* Fixtures                                                               */
    /* --------------------------------------------------------------------- */

    /**
     * Obviously-fictional donors. Deliberately generic placeholder names — a
     * demo that looked like real supporter data would be worse, not better.
     *
     * @return array<int, array<string, string>>
     */
    private static function sample_donors(): array {
        return [
            [
                'name'   => __( 'Jordan A.', 'better-payment' ),
                'amount' => '$250.00',
                'when'   => __( '2 hours ago', 'better-payment' ),
            ],
            [
                'name'   => __( 'Priya S.', 'better-payment' ),
                'amount' => '$100.00',
                'when'   => __( '5 hours ago', 'better-payment' ),
            ],
            [
                'name'   => __( 'Marco B.', 'better-payment' ),
                'amount' => '$75.00',
                'when'   => __( 'Yesterday', 'better-payment' ),
            ],
            [
                'name'   => __( 'Anonymous', 'better-payment' ),
                'amount' => '$50.00',
                'when'   => __( 'Yesterday', 'better-payment' ),
            ],
            [
                'name'   => __( 'Lena K.', 'better-payment' ),
                'amount' => '$40.00',
                'when'   => __( '2 days ago', 'better-payment' ),
            ],
            [
                'name'   => __( 'Sam O.', 'better-payment' ),
                'amount' => '$25.00',
                'when'   => __( '3 days ago', 'better-payment' ),
            ],
        ];
    }

    /**
     * Accept only a literal hex colour; anything else falls back to the campaign
     * purple. The value reaches a `style` attribute, so it must not be trusted
     * even though it comes from an admin-authored layout.
     *
     * @param mixed $value
     * @return string
     */
    private static function safe_color( $value ): string {
        if ( is_string( $value ) && preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value ) ) {
            return $value;
        }

        return '#6a4bff';
    }
}
