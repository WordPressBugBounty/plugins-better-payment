<?php 
    include BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/partials/campaign-vars.php";
?>
<main class="better-payment better-payment-campaign">
    <div class="better-payment_campaign-layout_1">
        <?php if( $layout_header_enable ): ?>
            <section class="bp-section-header bp-donation_thoughts-section">
                <div class="bp-hero_container">
                    <div class="bp-row ">
                        <div class="bp-col bp-col_2 bp-col_100-active_900">
                            <div class="bp-image_wrapper-left bp-text_align-center bp-left_right-image_style-active_900">
                                <?php if( $header_image_one_enable ): ?>
                                    <div class="bp-left-img-wrapper">
                                        <img src="<?php echo esc_url( $header_image_one_url ); ?>" alt="Group One" />
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bp-col bp-col_8 bp-col_100-active_900">
                            <div class="bp-text_wrapper">
                                <?php if( $header_title_enable ): ?>
                                    <h1 class="bp-text_primary bp-sora_font bp-text_align-center">
                                        <?php echo esc_html( $header_title ); ?>
                                    </h1>
                                <?php endif; ?>
                                <?php if( $header_short_desc_enable ): ?>
                                    <p class="bp-text_secondary bp-inter_font bp-text_align-center">
                                        <?php echo esc_html( $header_short_desc ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bp-col bp-col_2 bp-col_100-active_900">
                            <div class="bp-image_wrapper-right bp-text_align-center bp-left_right-image_style-active_900">
                                <?php if( $header_image_two_enable ): ?>
                                    <div class="bp-right-img-wrapper">
                                        <img src="<?php echo esc_url( $header_image_two_url ); ?>" alt="Group Two" />
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="bp-section-donate bp-charity_raised-section">
            <div class="bp-container">
                <div class="bp-charity_raised-card">
                    <div class="bp-row">
                        <div class="bp-col bp-col_5 bp-col_100-active_900">
                            <?php if( ! empty( $form_image ) ): ?>
                                <div class="bp-line_height-0 bp-charity_raised-img-wrapper">
                                    <img src="<?php echo esc_url( $form_image ); ?>" alt="Payment form" />
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="bp-col bp-col_7 bp-col_100-active_900">
                            <div class="bp-form_wrapper bp-justify_content-center">
                                <?php if( ! empty( $form_title_text ) ): ?>
                                    <h2 class="bp-form_header bp-text_xxl bp-text_align-left bp-sora_font">
                                        <?php echo esc_html( $form_title_text ); ?>
                                    </h2>
                                <?php endif; ?>

                                <form id="myForm">
                                    <?php if ( method_exists( $widgetObj, 'render_campaign_hidden_fields' ) ) {
                                        echo $widgetObj->render_campaign_hidden_fields( $settings );
                                    }?>

                                    <div class="bp-flex bp-justify_content-space_between bp-align_items_center bp-form_top">
                                        <?php if( ! empty( $form_goal_label ) && ! empty( $form_goal_amount ) ): ?>
                                            <label for="bpProgressBar" class="bp-flex bp-text_m  bp-inter_font">
                                                <span class="bp-amount-label"><?php echo esc_html( $form_goal_label ); ?>:</span> <span class="bp-aamount"><?php echo esc_html( $better_payment_campaign_currency_symbol ); ?><?php echo esc_html( $form_goal_amount ); ?></span>
                                            </label>
                                        <?php endif; ?>
                                        <?php if( $form_goal_percentage_enable ): ?>
                                            <div>
                                                <output id="bpProgressOutput" class="bp-progress_output bp-text_m  bp-inter_font" aria-live="polite"><?php echo esc_html( $bpc_goal_percentage ); ?>%</output>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if( $form_goal_bar_line_enable ): ?>
                                        <progress id="bpProgressBar" class="bp-progress_bar" value="<?php echo esc_attr( $bpc_goal_percentage ); ?>"
                                            max="100"><?php echo esc_html( $bpc_goal_percentage ); ?>%</progress>
                                    <?php endif; ?>

                                    <div class="bp-flex bp-align_items_center bp-donate_amounts" id="bpDonateAmount">
                                        <?php if( ! empty( $amount_items ) ) : ?>
                                            <?php foreach( $amount_items as $amount_item ) : ?>
                                                <?php if( !empty( $amount_item['better_payment_campaign_form_amount_list_val_layout_1'] ) ): ?>
                                                    <label for="amount_<?php echo esc_attr( $amount_item['better_payment_campaign_form_amount_list_val_layout_1'] ); ?>" class="bp-radio_label bp-inter_font">
                                                        <input type="radio" id="amount_<?php echo esc_attr( $amount_item['better_payment_campaign_form_amount_list_val_layout_1'] ); ?>" name="option_amount" value="<?php echo esc_attr( $amount_item['better_payment_campaign_form_amount_list_val_layout_1'] ); ?>" data-value="<?php echo esc_attr( $amount_item['better_payment_campaign_form_amount_list_val_layout_1'] ); ?>" class="bp-option-amount">
                                                        <?php echo esc_html( $better_payment_campaign_currency_symbol ); ?><?php echo esc_html( $amount_item['better_payment_campaign_form_amount_list_val_layout_1'] ); ?>
                                                    </label>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <input type="number" min="1" name="campaign-custom-amount" class="campaign-custom-amount bp-inter_font"
                                        placeholder="<?php echo esc_html__( $placeholder_text, 'better-payment' ); ?>">
                                    <div class="bp-flex">
                                        <a <?php echo $this->print_render_attribute_string( 'better_payment_campaign_form_button_link' ); ?> class="btn-1 bp-donate_btn bp-inter_font">
                                            <?php echo esc_html_e( $button_text, 'better-payment' ); ?>
                                        </a>
                                    </div>
                                </form>
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
                    <?php if( $layout_overview_enable ): ?>
                        <button class="bp-tab_button bp-text_xl bp-text_align-left bp-hanken_font  bp-active" data-tab="overview">
                            <?php echo esc_html( $overview_title ); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if( ( ! $this->pro_enabled && $is_edit_mode ) ): ?>
                        <button class="bp-tab_button bp-text_xl bp-hanken_font" data-tab="comment">Comment</button>
                        <button class="bp-tab_button bp-text_xl bp-hanken_font " data-tab="update">Update</button>
                    <?php endif; ?>
                    <?php
                        do_action('better_payment/widget/fundraising-campaign/tab_navigation', $settings);
                    ?>
                </div>

                <!-- Tab content -->
                <div class="bp-tab_content">
                    <?php if( $layout_overview_enable || $team_enable ): ?>
                        <div class="bp-tab_pane bp-active " id="overview">
                            <?php if( $layout_overview_enable && ($overview_desc_one_enable || $overview_images_enable || $overview_desc_two_enable || $overview_mission_enable ) ): ?>
                                <div class="bp-section-body">
                                    <?php if( $overview_desc_one_enable ): ?>
                                        <p class="bp-text_secondary bp-inter_font bp-overview_text">
                                            <?php echo esc_html( $overview_desc_one ); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if( $overview_images_enable ): ?>
                                        <div class="bp-overview_image-wrapper bp-row">
                                            <div class="bp-col bp-col_4 bp-line_height-0 bp-col_100-active_700 bp-text_align-center">
                                                <img src="<?php echo esc_url( $overview_image_one_url ); ?>" class="bp-overview_img-1 bp-col_50-active_700 ">
                                                <img src="<?php echo esc_url( $overview_image_two_url ); ?>" class="bp-overview_img-2 bp-col_50-active_700 ">
                                            </div>
                                            <div class="bp-col bp-col_8 bp-line_height-0 bp-col_100-active_700">
                                                <img src="<?php echo esc_url( $overview_image_three_url ); ?>" class="bp-overview_img-3">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="bp-overview_text-wrapper">
                                        <?php if( $overview_mission_enable ): ?>
                                            <div class="our-mission-section">
                                                <h3 class="bp-text_l bp-hanken_font">
                                                    <?php echo esc_html( $overview_mission_title ); ?>
                                                </h3>
                                                <?php if( ! empty( $overview_missions ) ): ?>
                                                    <ul class="bp-overview_list">
                                                        <?php foreach( $overview_missions as $overview_mission ): ?>
                                                            <li class="bp-flex bp-align_items-center bp-overview_list-item bp-text_l">
                                                                <span>
                                                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M20.668 2.45352C22.679 3.61466 24.3519 5.28088 25.5211 7.28725C26.6902 9.29362 27.3151 11.5705 27.3339 13.8926C27.3526 16.2147 26.7646 18.5015 25.628 20.5264C24.4914 22.5514 22.8456 24.2445 20.8536 25.4379C18.8616 26.6314 16.5925 27.2839 14.2708 27.3309C11.9491 27.3779 9.65537 26.8177 7.61671 25.7058C5.57805 24.5939 3.86513 22.9689 2.64752 20.9915C1.42992 19.0142 0.749848 16.7531 0.674636 14.4322L0.667969 14.0002L0.674636 13.5682C0.749306 11.2655 1.41937 9.02146 2.61949 7.05484C3.81961 5.08822 5.50884 3.46612 7.52249 2.34669C9.53614 1.22727 11.8055 0.648711 14.1093 0.667433C16.4131 0.686154 18.6728 1.30151 20.668 2.45352ZM18.944 10.3909C18.7144 10.1613 18.4089 10.0234 18.0849 10.003C17.7608 9.98262 17.4405 10.0812 17.184 10.2802L17.0586 10.3909L12.668 14.7802L10.944 13.0575L10.8186 12.9468C10.5621 12.748 10.2418 12.6495 9.91784 12.67C9.59389 12.6904 9.28851 12.8283 9.05898 13.0579C8.82946 13.2874 8.69154 13.5928 8.67111 13.9167C8.65067 14.2407 8.74911 14.561 8.94797 14.8175L9.05863 14.9428L11.7253 17.6095L11.8506 17.7202C12.0845 17.9016 12.372 18.0001 12.668 18.0001C12.9639 18.0001 13.2515 17.9016 13.4853 17.7202L13.6106 17.6095L18.944 12.2762L19.0546 12.1508C19.2536 11.8943 19.3522 11.574 19.3318 11.2499C19.3114 10.9259 19.1735 10.6204 18.944 10.3909Z" fill="#B872FF" />
                                                                    </svg>
                                                                </span>
                                                                <p class="bp-inter_font">
                                                                    <?php echo esc_html( $overview_mission['better_payment_campaign_overview_our_mission_item'] ); ?>
                                                                </p>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if( $overview_desc_two_enable ): ?>
                                            <p class="bp-text_secondary bp-inter_font bp-overview_text">
                                                <?php echo esc_html( $overview_desc_two ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                                            
                            <?php if( $team_enable ): ?>
                                <div class="bp-section-footer bp-overview_team-wrapper">
                                    <?php if( ! empty( $team_title ) ): ?>
                                        <h3 class="bp-text_xxl bp-sora_font bp-tram_section-header">
                                            <?php echo esc_html( $team_title ); ?>
                                        </h3>
                                    <?php endif; ?>
                                    <?php if( ! empty( $team_members ) ): ?>
                                        <div class="bp-row bp-justify_content-center_active-700">
                                            <?php $bp_i = 0; foreach( $team_members as $team_member ): ?>
                                                <?php if( ! empty( $team_member['better_payment_campaign_team_member_image']['url'] ) || ! empty( $team_member['better_payment_campaign_team_member_name'] ) ): ?>
                                                    <div class="bp-col_3 bp-col bp-col_50-active_700">
                                                        <div class="bp-team_wrapper">
                                                            <?php if( ! empty( $team_member['better_payment_campaign_team_member_image']['url'] ) ): ?>
                                                                <div class="img-box">
                                                                    <img src="<?php echo esc_url( $team_member['better_payment_campaign_team_member_image']['url'] ); ?>" alt="Team member" />
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if( ! empty( $team_member['better_payment_campaign_team_member_name'] ) ): ?>
                                                                <h4 class=" bp-inter_font member-name">
                                                                    <?php echo esc_html( $team_member['better_payment_campaign_team_member_name'] ); ?>
                                                                </h4>
                                                            <?php endif; ?>
                                                            <?php if( ! empty( $team_member['better_payment_campaign_team_member_designation'] ) ): ?>
                                                                <p class="bp-inter_font member-designation">
                                                                    <?php echo esc_html( $team_member['better_payment_campaign_team_member_designation'] ); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                            <?php if( ! empty( $team_member['better_payment_campaign_team_member_social_links_enable'] ) && 'yes' === $team_member['better_payment_campaign_team_member_social_links_enable'] && count( $social_links ) > 0 ): ?>
                                                                <div class="bp-flex bp-team-social_links-wrapper">
                                                                    <?php foreach( $social_links as $link => $icon ): ?>
                                                                        <?php if( ! empty( $team_member[$link]['url'] ) ): 
                                                                            $this->add_link_attributes( $link . '_' . $bp_i, $team_member[$link] );
                                                                        ?>
                                                                            
                                                                            <a <?php echo $this->print_render_attribute_string( $link . '_' . $bp_i ); ?>>
                                                                                <?php
                                                                                if( ! empty( $team_member[$icon] ) ) {
                                                                                    \Elementor\Icons_Manager::render_icon( $team_member[$icon], [ 'aria-hidden' => 'true' ] );
                                                                                }
                                                                                ?>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php $bp_i++; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
    </div>
</main>