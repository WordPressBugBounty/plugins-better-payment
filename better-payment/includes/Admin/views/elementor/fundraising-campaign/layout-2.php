<?php 
    include BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/partials/campaign-vars.php";
?>
<main class="better-payment better-payment-campaign">
    <div class="better-payment_campaign-layout_2">
        <section class="bp-donation_hero-section" id="bp-bgimage">
            <div class="bp-hero_overlay"></div>
            <div class="bp-container">
                <div class="bp-row bp-align_items-center">
                    <div class="bp-col_8 bp-col bp-col_100-active_900 ">
                        <div class="bp-text_wrapper">
                            <?php if( $header_title_enable ): ?>
                                <h1 class="bp-text_primary bp-hanken_font">
                                    <?php echo esc_html( $header_title ); ?>
                                </h1>
                            <?php endif; ?>
                            <?php if( $header_short_desc_enable ): ?>
                                <p class="bp-text_xm bp-ibm_font">
                                    <?php echo esc_html( $header_short_desc ); ?>
                                </p>
                            <?php endif; ?>
                            <div class="bp-flex">
                                <a <?php echo $this->print_render_attribute_string( 'better_payment_campaign_form_button_link' ); ?> class="btn-2 bp-donate_btn bp-inter_font">
                                    <span><?php esc_html_e( $button_text, 'better-payment' ); ?></span>
                                    <span>
                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1.5 10.5L10.5 1.5M10.5 1.5H3.75M10.5 1.5V8.25" stroke="#344054"
                                                stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="bp-col_4 bp-col bp-col_100-active_900 ">
                        <div class="bp-progress_bar-card">
                            <?php if( !empty( $form_sub_title_text ) ): ?>
                                <span class="card-tag bp-ibm_font">
                                    <?php echo esc_html( $form_sub_title_text ); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if( !empty( $form_title_text ) ): ?>
                                <h3 class="card-header bp-text_xl bp-ibm_font">
                                    <?php echo esc_html( $form_title_text ); ?>
                                </h3>
                            <?php endif; ?>
                            <form>
                                <?php if ( method_exists( $widgetObj, 'render_campaign_hidden_fields' ) ) {
                                    echo $widgetObj->render_campaign_hidden_fields( $settings );
                                }?>
                                <?php if( $form_goal_bar_line_enable ): ?>
                                    <progress id="bpProgressBar" class="bp-progress_bar" value="<?php echo esc_attr( $bpc_goal_percentage ); ?>" max="100"><?php echo esc_html( $bpc_goal_percentage ); ?>%</progress>
                                <?php endif; ?>
                                
                                <?php if( $form_total_donation_enable ): ?>
                                <label for="bpProgressBar" class="bp-donations bp-flex bp-text_m  bp-ibm_font ">
                                    <span> <?php echo esc_html( $bpc_total_payment_count );?> </span> <span> <?php echo esc_html( $form_total_donation_label );?> </span>
                                </label>
                                <?php endif; ?>
                                <div class="bp-flex bp-justify_content-space_between bp-align_items-center">
                                    <?php if( $form_goal_amount_raised_enable ): ?>
                                        <label for="bpProgressBar" class="bp-flex bp-text_m  bp-ibm_font " style="gap: 3px;">
                                            <span><?php echo esc_html( $better_payment_campaign_currency_symbol ); ?><?php echo esc_html( $bpc_total_amount_raised );?></span> <span><?php echo esc_html( $form_goal_amount_raised_label );?></span>
                                        </label>
                                    <?php endif; ?>

                                    <!-- <a href="#"
                                        class="card-raised_btn bp-flex bp-align_items-center bp-justify_content-center">
                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 11L11 1M11 1H3.5M11 1V8.5" stroke="#1C274C" stroke-width="1.25"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a> -->
                                </div>
                            </form>
                        </div>

                        <div class="bp-image_gallery-wrapper bp-d_none-ctive_900">
                            <div class="image-gallery bp-flex bp-justify_content-center">
                                <?php if( $header_image_one_enable ): ?>
                                    <div class="img-wrapper bp-position_reletive">
                                        <div class="image_overlay"></div>
                                        <img src="<?php echo esc_url( $header_image_one_url ); ?>" alt="Image 1" class="gallery-img" data-bg="<?php echo esc_url( $header_image_one_url ); ?>" />
                                    </div>
                                <?php endif; ?>

                                <?php if( $header_image_two_enable ): ?>
                                    <div class="img-wrapper  bp-position_reletive">
                                        <div class="image_overlay"></div>
                                        <img src="<?php echo esc_url( $header_image_two_url ); ?>" alt="Image 2" class="gallery-img" data-bg="<?php echo esc_url( $header_image_two_url ); ?>" />
                                    </div>
                                <?php endif; ?>

                                <?php if( $header_image_three_enable ): ?>
                                    <div class="img-wrapper  bp-position_reletive">
                                        <div class="image_overlay"></div>
                                        <img src="<?php echo esc_url( $header_image_three_url ); ?>" alt="Image 3" class="gallery-img" data-bg="<?php echo esc_url( $header_image_three_url ); ?>" />
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bp-content_navigation-section">
            <div class="bp-container">
                <!-- Tab navigation -->
                <div class="bp-tab_navigation bp-flex">
                    <?php if( $overview_enable ): ?>
                        <button class="bp-tab_button bp-text_xl bp-text_align-left bp-hanken_font  bp-active" data-tab="overview">
                            <?php echo esc_html( $overview_title ); ?>
                        </button>
                    <?php endif; ?>
                    <?php if( ( ! $this->pro_enabled && $is_edit_mode ) ): ?>
                        <button class="bp-tab_button bp-text_xl bp-hanken_font " data-tab="comment">Comment</button>
                        <button class="bp-tab_button bp-text_xl bp-hanken_font " data-tab="update">Update</button>
                    <?php endif; ?>
                    <?php
                        do_action('better_payment/widget/fundraising-campaign/tab_navigation', $settings);
                    ?>
                </div>

                <!-- Tab content -->
                <div class="bp-tab_content">
                    <div class="bp-tab_pane bp-active " id="overview">
                        <div class="bp-row">
                            <?php if( $layout_overview_enable ): ?>
                                <div class="bp-col bp-col_8 bp-col_100-active_900">
                                    <div class="bp-overviow_text-wrapper">
                                        <p class="bp-text_secondary bp-inter_font bp-overview_text">
                                            <?php echo esc_html( $overview_desc_one ); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
                    <?php
                        do_action('better_payment/widget/fundraising-campaign/update_tab_content', $settings);
                    ?>
                    <?php if( $is_edit_mode && ! $this->pro_enabled ): ?>
                        <div class="bp-tab_pane" id="update">
                            <a href="https://betterpayment.io/pricing" target="_blank" class="bp-upgrade_pro">
                                <img class="bp-pro-banner" src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/update-pro.webp' ); ?>" alt="upgrade-to-pro">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <?php if( $related_campaign_enable && count( $related_campaigns ) > 0 ): ?>
            <section class="bp-donate_section">
                <div class="bp-container">
                    <div class="bp-donate_section-wrapper">
                        <div class="bp-section_top-wrapper">
                            <div class="bp-row">
                                <div class="bp-col bp-col_7 bp-col_100-active_900">
                                    <div class="bp-text_wrapper">
                                        <h2 class="bp-hanken_font bp-donate_section-header">
                                            <?php echo esc_html( $related_campaigns[0]['title'] ); ?>
                                        </h2>
                                        <p class="bp-text_xm bp-ibm_font bp-donate_section-sub_header">
                                            <?php echo esc_html( $related_campaigns[0]['description'] ); ?>
                                        </p>
                                        <div class="bp-flex">
                                            <a  href="<?php echo esc_url( $related_campaigns[0]['page_url'] ); ?>" target="_blank" class="btn-2 bp-donate_btn bp-inter_font">
                                                <span><?php esc_html_e( $button_text, 'better-payment' ); ?></span>
                                                <span>
                                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M1.5 10.5L10.5 1.5M10.5 1.5H3.75M10.5 1.5V8.25"
                                                            stroke="#344054" stroke-width="1.4" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                    </svg>

                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bp-col bp-col_5 bp-col_100-active_900">
                                    <div class="img-wrapper">
                                        <img src="<?php echo esc_url( $related_campaigns[0]['image'] ); ?>" alt="image 246.png" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if( count( $related_campaigns ) > 1 ): ?>
                            <div class="bp-section-footer bp-section_bottom-wrapper">
                                <h3 class="bp-hanken_font bp-text_xxl bottom-wrapper_header"><?php echo esc_html( $related_campaign_sub_title ); ?></h3>
                                <div class="bp-row">
                                    <?php 
                                    $i = 0;
                                    foreach( $related_campaigns as $related_campaign ):
                                        if( $i === 0 ) {
                                            $i++;
                                            continue;
                                        }
                                    ?>
                                        <div class="bp-col bp-col_4 bp-col_100-active_700">
                                            <div class="card-wrapper bp-flex">
                                                <div class="card-img_box bp-line_height-0">
                                                    <img src="<?php echo esc_url( $related_campaign['image'] ); ?>" alt="">
                                                </div>
                                                <div class="card-text_box">
                                                    <h5 class="bp-hanken_font  card-header">
                                                        <?php echo esc_html( $related_campaign['title'] ); ?>
                                                    </h5>

                                                    <div class="bp-flex bp-justify_content-space_between bp-align_items-center">
                                                        <span class="bp-ibm_font bp-text_m"><?php echo esc_html( $related_campaign['currency_symbol'] ); ?><?php echo esc_html( $related_campaign['raised_amount'] );?> raised</span>
                                                        <a href="<?php echo esc_url( $related_campaign['page_url'] ); ?>" target="_blank" class="card-btn">
                                                            <svg width="8" height="8" viewBox="0 0 8 8" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M0.664062 7.33268L7.33073 0.666016M7.33073 0.666016H2.33073M7.33073 0.666016V5.66602"
                                                                    stroke="#48506D" stroke-width="0.833333" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>