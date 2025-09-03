<?php 
    include BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/partials/campaign-vars.php";
?>
<main class="better-payment better-payment-campaign">
    <div class="better-payment_campaign-layout_3">
        <section class="bp-donation_hero-section">
            <div class="bp-container">
                <div class="bp-hero_wrapper">
                    <div class="bp-row bp-align_items-center">
                        <div class="bp-col bp-col_6 bp-col_100-active_900">
                            <div class="bp-text_wrapper">
                                <?php if( $header_title_enable ): ?>
                                    <h1 class="bp-ibm_font bp-text_primary margin-bottom_32 bp-hero_header">
                                        <?php echo esc_html( $header_title ); ?>
                                    </h1>
                                <?php endif; ?>
                                <?php if( $header_short_desc_enable ): ?>
                                    <p class="bp-ibm_font bp-text_secondary margin-bottom_32 bp-hero_secondary-text">
                                        <?php echo esc_html( $header_short_desc ); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <form class="bp-payment_form">
                                    <?php if ( method_exists( $widgetObj, 'render_campaign_hidden_fields' ) ) {
                                        echo $widgetObj->render_campaign_hidden_fields( $settings );
                                    }?>
                                    <div
                                        class="bp-flex bp-justify_content-space_between bp-align_items-center margin-bottom_32 input-fild">
                                        <input type="number" min="1" name="campaign-custom-amount" placeholder="<?php echo esc_html__( $placeholder_text, 'better-payment' ); ?>"
                                            class="bp-inter_font bp-payment_form-input other_amount campaign-custom-amount">
                                        <span class="bp-ibm_font bp-text_xm layout-3-currency"><?php echo esc_html( $better_payment_campaign_currency ); ?></span>
                                    </div>

                                    <div class="bp-flex bp-flex_wrap  bp-align_items-center bp-payment_item">
                                        <!-- Payment Amounts -->
                                        <?php if( ! empty( $amount_items ) ) : ?>
                                            <?php foreach( $amount_items as $amount_item ) : ?>
                                                <?php if( !empty( $amount_item['better_payment_campaign_form_amount_list_val_layout_3'] ) ): ?>
                                                    <input type="radio" id="amount_<?php echo esc_attr( $amount_item['better_payment_campaign_form_amount_list_val_layout_3'] ); ?>" name="option_amount" value="<?php echo esc_attr( $amount_item['better_payment_campaign_form_amount_list_val_layout_3'] ); ?>" class="bp-display_none bp-option-amount" />
                                                    <label for="amount_<?php echo esc_attr( $amount_item['better_payment_campaign_form_amount_list_val_layout_3'] ); ?>" class="bp-inter_font bp-text_xm bp-payment_item_bg">
                                                        <?php echo esc_html( $better_payment_campaign_currency_symbol ); ?><?php echo esc_html( $amount_item['better_payment_campaign_form_amount_list_val_layout_3'] ); ?>
                                                    </label>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="bp-flex">
                                        <a <?php echo $this->print_render_attribute_string( 'better_payment_campaign_form_button_link' ); ?> class="btn-3 bp-inter_font bp-donation_btn bp-flex bp-align_items-center bp-donate_btn">
                                            <span><?php esc_html_e( $button_text, 'better-payment' ); ?></span>
                                            <span>
                                                <svg width="16" height="12" viewBox="0 0 16 12" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M1.33203 6H14.6654M14.6654 6L9.66536 1M14.6654 6L9.66536 11"
                                                        stroke="white" stroke-width="1.41667" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php if( $header_image_one_enable ): ?>
                            <div class="bp-col bp-col_6 bp-col_100-active_900">
                                <div class="bp-img_wrapper">
                                    <img src="<?php echo esc_url( $header_image_one_url ); ?>" alt="Hero image" />
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="bp-help_section">
            <div class="bp-container">
                <div class="bp-row bp-flex_direction-reverse_active-700">
                    <div class="bp-col bp-col_8 bp-col_100-active_700">
                        <?php if( $layout_overview_enable ): ?>
                            <div class="bp-help_wrapper">
                                <h2 class="bp-ibm_font bp-text_xxl margin-bottom_32 bp-help_section-header">
                                    <?php echo esc_html( $overview_title ); ?>
                                </h2>
                                <?php if( $overview_desc_one_enable ): ?>
                                    <p class="bp-ibm_font bp-text_secondary margin-bottom_32">
                                        <?php echo wp_kses_post( $overview_desc_one ); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if( $overview_images_enable ): ?>
                                    <div class="bp-img_gallery">
                                        <div class="bp-img_wrapper bp-line_height-0">
                                            <img src="<?php echo esc_url( $overview_image_one_url ); ?>" alt="img-1">
                                        </div>
                                        <div class="bp-img_wrapper bp-line_height-0">
                                            <img src="<?php echo esc_url( $overview_image_two_url ); ?>" alt="img-2">
                                        </div>
                                        <div class="bp-img_wrapper bp-line_height-0">
                                            <img src="<?php echo esc_url( $overview_image_three_url ); ?>" alt="img-3">
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if( $overview_desc_two_enable ): ?>
                                    <p class="bp-ibm_font bp-text_secondary margin-bottom_32">
                                        <?php echo esc_html( $overview_desc_two ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php 
                            do_action('better_payment/widget/fundraising-campaign/comment_tab_content', $settings);
                        ?>
                        <?php if( $is_edit_mode && ! $this->pro_enabled ): ?>
                            <div class="bp-tab_pane" id="comment">
                                <a href="https://betterpayment.io/pricing" target="_blank" class="bp-upgrade_pro">
                                    <img class="bp-pro-banner" src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/comingsoon.webp' ); ?>" alt="coming-soon">
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                        do_action('better_payment/widget/fundraising-campaign/update_tab_content', $settings);
                    ?>
                    <?php if( $is_edit_mode && ! $this->pro_enabled ): ?>
                        <div class="bp-col bp-col_4 bp-col_100-active_700">
                            <a href="https://betterpayment.io/pricing" target="_blank" class="bp-upgrade_pro">
                                <img class="bp-pro-banner" src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/campaign/layout-3/update-pro.webp' ); ?>" alt="upgrade-to-pro">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </section>
    </div>
</main>
