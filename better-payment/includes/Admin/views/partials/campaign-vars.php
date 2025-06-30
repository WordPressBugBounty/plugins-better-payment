<?php
$better_payment_campaign_currency = ! empty( $settings['better_payment_campaign_currency'] ) 
    ? $settings['better_payment_campaign_currency'] 
    : 'USD';
$better_payment_campaign_currency_symbol = $this->get_currency_symbol( $better_payment_campaign_currency );
$better_payment_selected_layout = sanitize_text_field( $settings['better_payment_campaign_layout'] );

// Header related variables
$header_enable                  = ! empty( $settings['better_payment_campaign_general_header_enable'] ) && 'yes' === $settings['better_payment_campaign_general_header_enable'];
$header_title_enable            = ! empty( $settings['better_payment_campaign_general_title_enable'] ) && 'yes' === $settings['better_payment_campaign_general_title_enable'];
$header_short_desc_enable       = ! empty( $settings['better_payment_campaign_general_short_description_enable'] ) && 'yes' === $settings['better_payment_campaign_general_short_description_enable'];
$header_image_one_enable        = ! empty( $settings['better_payment_campaign_general_image_one_enable'] ) && 'yes' === $settings['better_payment_campaign_general_image_one_enable'];
$header_image_two_enable        = ! empty( $settings['better_payment_campaign_general_image_two_enable'] ) && 'yes' === $settings['better_payment_campaign_general_image_two_enable'];
$header_image_three_enable      = ! empty( $settings['better_payment_campaign_general_image_three_enable'] ) && 'yes' === $settings['better_payment_campaign_general_image_three_enable'];

$layout_header_enable           = false;
if( $better_payment_selected_layout === 'layout-1' ) {
    $layout_header_enable       = $header_enable && ( $header_title_enable || $header_short_desc_enable || $header_image_one_enable || $header_image_two_enable );
} else if( $better_payment_selected_layout === 'layout-2' ) {
    $layout_header_enable       = $header_enable && ( $header_title_enable || $header_short_desc_enable || $header_image_one_enable || $header_image_two_enable || $header_image_three_enable );
} else if( $better_payment_selected_layout === 'layout-3' ) {
    $layout_header_enable       = $header_enable && ( $header_title_enable || $header_short_desc_enable || $header_image_one_enable );
}

if ( $better_payment_selected_layout === 'layout-1' ) {
    $header_title               = ! empty( $settings['better_payment_campaign_header_title_text'] ) 
        ? $settings['better_payment_campaign_header_title_text'] 
        : __( 'Give Hope, Change Lives: Help Child in Need', 'better-payment' );
    $header_short_desc          = ! empty( $settings['better_payment_campaign_header_short_description'] ) 
        ? $settings['better_payment_campaign_header_short_description'] 
        : __( 'Every child deserves a life free from hunger. Your contribution can provide nutritious meals, hope and a brighter future for needy children.', 'better-payment' );
    $header_image_one_url       = ! empty( $settings['better_payment_campaign_header_image_one']['url'] ) 
        ? $settings['better_payment_campaign_header_image_one']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/header-left-image.png';
    $header_image_two_url       = ! empty( $settings['better_payment_campaign_header_image_two']['url'] ) 
        ? $settings['better_payment_campaign_header_image_two']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/header-right-image.png';
} else if ( $better_payment_selected_layout === 'layout-2' ) {
    $header_title               = ! empty( $settings['better_payment_campaign_header_title_text'] ) 
        ? $settings['better_payment_campaign_header_title_text'] 
        : __( 'Give Gaza\'s Children a Chance for Safety and Brighter Future', 'better-payment' );
    $header_short_desc          = ! empty( $settings['better_payment_campaign_header_short_description'] ) 
        ? $settings['better_payment_campaign_header_short_description'] 
        : __( 'Your generosity can transform lives. Your small donation token can provide food, shelter, medical aid and safety to children in urgent need.', 'better-payment' );
    $header_image_one_url       = ! empty( $settings['better_payment_campaign_header_image_one']['url'] ) 
        ? $settings['better_payment_campaign_header_image_one']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-2/slider-img-1.webp';
    $header_image_two_url       = ! empty( $settings['better_payment_campaign_header_image_two']['url'] ) 
        ? $settings['better_payment_campaign_header_image_two']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-2/slider-img-2.webp';
    $header_image_three_url     = ! empty( $settings['better_payment_campaign_header_image_three']['url'] ) 
        ? $settings['better_payment_campaign_header_image_three']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-2/slider-img-3.webp';
} else if ( $better_payment_selected_layout === 'layout-3' ) {
    $header_title               = ! empty( $settings['better_payment_campaign_header_title_text'] ) 
        ? $settings['better_payment_campaign_header_title_text'] 
        : __( 'Building Bridges to Change Lives', 'better-payment' );
    $header_short_desc          = ! empty( $settings['better_payment_campaign_header_short_description'] ) 
        ? $settings['better_payment_campaign_header_short_description'] 
        : __( 'We believe every animal deserves a happy and healthy life. By contributing to our fundraiser, you\'re supporting rescue operations, medical treatments, and rehoming efforts for animals in desperate need.', 'better-payment' );
    $header_image_one_url       = ! empty( $settings['better_payment_campaign_header_image_one']['url'] ) 
        ? $settings['better_payment_campaign_header_image_one']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-3/hero.webp';
}

// Form related variables
$button_text                = ! empty( $settings['better_payment_campaign_form_button_text'] )
    ? $settings['better_payment_campaign_form_button_text'] 
    : __( 'Donate Now', 'better-payment' );
if ( ! empty( $settings['better_payment_campaign_form_button_link']['url'] ) ) {
    $this->add_link_attributes( 'better_payment_campaign_form_button_link', $settings['better_payment_campaign_form_button_link'] );
}
if( $better_payment_selected_layout === 'layout-1' ) {
    $form_title_text            = $settings['better_payment_campaign_form_title_text_layout_1'];
    $form_image                 = $settings['better_payment_campaign_form_image']['url'];
    $form_goal_label            = $settings['better_payment_campaign_form_goal_amount_label'];
    $form_goal_amount           = $settings['better_payment_campaign_form_goal_amount'];
    $form_goal_percentage_enable= ! empty( $settings['better_payment_campaign_form_goal_percentage_enable'] ) && 'yes' === $settings['better_payment_campaign_form_goal_percentage_enable'];
    $form_goal_bar_line_enable  = ! empty( $settings['better_payment_campaign_form_goal_bar_line_enable'] ) && 'yes' === $settings['better_payment_campaign_form_goal_bar_line_enable'];
    $placeholder_text           = $settings['better_payment_campaign_form_placeholder_text_layout_1'];
    $amount_items               = ! empty( $settings['better_payment_campaign_form_amount_list_layout_1'] ) 
        ? $settings['better_payment_campaign_form_amount_list_layout_1'] 
        : [];
} else if( $better_payment_selected_layout === 'layout-2' ) {
    $form_title_text            = $settings['better_payment_campaign_form_title_text_layout_2'];
    $form_sub_title_text        = $settings['better_payment_campaign_form_sub_title_text'];
    $form_goal_amount           = $settings['better_payment_campaign_form_goal_amount'];
    $form_goal_amount_raised_enable = ! empty( $settings['better_payment_campaign_form_goal_amount_raised_enable'] ) && 'yes' === $settings['better_payment_campaign_form_goal_amount_raised_enable'];
    $form_goal_amount_raised_label = $settings['better_payment_campaign_form_goal_amount_raised_label'];
    $form_goal_bar_line_enable  = ! empty( $settings['better_payment_campaign_form_goal_bar_line_enable'] ) && 'yes' === $settings['better_payment_campaign_form_goal_bar_line_enable'];
    $form_total_donation_enable = ! empty( $settings['better_payment_campaign_form_total_donation_enable'] ) && 'yes' === $settings['better_payment_campaign_form_total_donation_enable'];
    $form_total_donation_label  = $settings['better_payment_campaign_form_total_donation_label'];
} else if( $better_payment_selected_layout === 'layout-3' ) {
    $placeholder_text           = $settings['better_payment_campaign_form_placeholder_text_layout_3'];
    $amount_items               = ! empty( $settings['better_payment_campaign_form_amount_list_layout_3'] ) 
        ? $settings['better_payment_campaign_form_amount_list_layout_3'] 
        : [];
}

// Overview related variables
$overview_enable                = ! empty( $settings['better_payment_campaign_general_overview_enable'] ) && 'yes' === $settings['better_payment_campaign_general_overview_enable'];
$overview_images_enable         = ! empty( $settings['better_payment_campaign_general_overview_images_enable'] ) && 'yes' === $settings['better_payment_campaign_general_overview_images_enable'];
$overview_desc_one_enable       = ! empty( $settings['better_payment_campaign_general_overview_description_one_enable'] ) && 'yes' === $settings['better_payment_campaign_general_overview_description_one_enable'];
$overview_desc_two_enable       = ! empty( $settings['better_payment_campaign_general_overview_description_two_enable'] ) && 'yes' === $settings['better_payment_campaign_general_overview_description_two_enable'];
$overview_mission_enable        = ! empty( $settings['better_payment_campaign_general_overview_mossion_enable'] ) && 'yes' === $settings['better_payment_campaign_general_overview_mossion_enable'];

$layout_overview_enable           = false;
if( $better_payment_selected_layout === 'layout-1' ) {
    $layout_overview_enable       = $overview_enable && ( $overview_images_enable || $overview_desc_one_enable || $overview_desc_two_enable || $overview_mission_enable );
} else if( $better_payment_selected_layout === 'layout-2' ) {
    $layout_overview_enable       = $overview_enable && $overview_desc_one_enable;
} else if( $better_payment_selected_layout === 'layout-3' ) {
    $layout_overview_enable       = $overview_enable && ( $overview_images_enable || $overview_desc_one_enable || $overview_desc_two_enable );
}

if ( $better_payment_selected_layout === 'layout-1' ) {
    $overview_title               = ! empty( $settings['better_payment_campaign_overview_title_text'] ) 
        ? $settings['better_payment_campaign_overview_title_text'] 
        : __( 'Overview', 'better-payment' );
    $overview_desc_one            = ! empty( $settings['better_payment_campaign_overview_description_one'] ) 
        ? $settings['better_payment_campaign_overview_description_one'] 
        : __( 'We believe every animal deserves a happy and healthy life. By contributing to our fundraiser, you\'re supporting rescue operations, medical treatments, and rehoming efforts for animals in desperate need. and rehoming efforts for animals in desperate need.', 'better-payment' );
    $overview_desc_two            = ! empty( $settings['better_payment_campaign_overview_description_two'] ) 
        ? $settings['better_payment_campaign_overview_description_two'] 
        : __( 'Your generosity fuels our vision of a world where every child has the chance to grow, learn, and thrive without the shadow of hunger.', 'better-payment' );
    $overview_image_one_url       = ! empty( $settings['better_payment_campaign_overview_image_one']['url'] ) 
        ? $settings['better_payment_campaign_overview_image_one']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/overview-1.webp';
    $overview_image_two_url       = ! empty( $settings['better_payment_campaign_overview_image_two']['url'] ) 
        ? $settings['better_payment_campaign_overview_image_two']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/overview-2.webp';
    $overview_image_three_url     = ! empty( $settings['better_payment_campaign_overview_image_three']['url'] ) 
        ? $settings['better_payment_campaign_overview_image_three']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/overview-3.webp';
    $overview_mission_title       = ! empty( $settings['better_payment_campaign_overview_our_mission_title_text'] ) 
        ? $settings['better_payment_campaign_overview_our_mission_title_text'] 
        : __( 'Our Mission', 'better-payment' );
    $overview_missions            = ! empty( $settings['better_payment_campaign_overview_our_mission_items'] ) 
        ? $settings['better_payment_campaign_overview_our_mission_items'] 
        : [];
} else if ( $better_payment_selected_layout === 'layout-2' ) {
    $overview_title               = ! empty( $settings['better_payment_campaign_overview_title_text'] ) 
        ? $settings['better_payment_campaign_overview_title_text'] 
        : __( 'Overview', 'better-payment' );
    $overview_desc_one            = ! empty( $settings['better_payment_campaign_overview_description_one'] ) 
        ? $settings['better_payment_campaign_overview_description_one'] 
        : __( 'Children in Gaza are facing unimaginable hardships, but your support can provide a lifeline. Every donation, big or small, helps provide essentials like food, shelter, and medical care. Your generosity can transform lives, offering hope and safety to those in need.', 'better-payment' );
} else if ( $better_payment_selected_layout === 'layout-3' ) {
    $overview_title               = ! empty( $settings['better_payment_campaign_overview_title_text'] ) 
        ? $settings['better_payment_campaign_overview_title_text'] 
        : __( 'Lending Hands, Healing Hearts', 'better-payment' );
    $overview_desc_one            = ! empty( $settings['better_payment_campaign_overview_description_one'] ) 
        ? $settings['better_payment_campaign_overview_description_one'] 
        : __( 'Every individual deserves hope, compassion and a chance at a better tomorrow. Your support helps provide essential resources such as food, clothing and shelter to those whose are in need. Together, we can create opportunities for education, skill-building and empowerment.
        <br /> <br />
        Extend a spiritual and humanitarian helping hand to those in desperate need and ensure no one is left behind. Join us in building a world where every soul can thrive. Your kindness can make all the difference. Through your generosity, we can fund critical programs to help underprivileged and senior citizens.', 'better-payment' );
    $overview_desc_two            = ! empty( $settings['better_payment_campaign_overview_description_two'] ) 
        ? $settings['better_payment_campaign_overview_description_two'] 
        : __( 'From providing warm meals and safe places to stay, to educational initiatives and healthcare services, every humanitarian contribution brings us closer to ending the cycle of poverty and inequality. By empowering underprivileged and senior citizens, we uplift entire communities around the world.', 'better-payment' );
    $overview_image_one_url       = ! empty( $settings['better_payment_campaign_overview_image_one']['url'] ) 
        ? $settings['better_payment_campaign_overview_image_one']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-3/overview-1.webp';
    $overview_image_two_url       = ! empty( $settings['better_payment_campaign_overview_image_two']['url'] ) 
        ? $settings['better_payment_campaign_overview_image_two']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-3/overview-2.webp';
    $overview_image_three_url     = ! empty( $settings['better_payment_campaign_overview_image_three']['url'] ) 
        ? $settings['better_payment_campaign_overview_image_three']['url'] 
        : BETTER_PAYMENT_ASSETS . '/img/campaign/layout-3/overview-3.webp';
}


// Team related variables
$team_enable = ! empty( $settings['better_payment_campaign_general_footer_team_enable_layout_1'] ) && 'yes' === $settings['better_payment_campaign_general_footer_team_enable_layout_1'];
$team_title = ! empty( $settings['better_payment_campaign_footer_our_team_title_text'] ) 
    ? $settings['better_payment_campaign_footer_our_team_title_text'] 
    : '';
$team_members = ! empty( $settings['better_payment_campaign_team_members'] ) 
    ? $settings['better_payment_campaign_team_members'] 
    : [];
$social_links = [
    'better_payment_campaign_team_member_social_links_facebook' => 'better_payment_campaign_team_member_social_links_facebook_icon',
    'better_payment_campaign_team_member_social_links_twitter' => 'better_payment_campaign_team_member_social_links_twitter_icon',
    'better_payment_campaign_team_member_social_links_linkedin' => 'better_payment_campaign_team_member_social_links_linkedin_icon',
    'better_payment_campaign_team_member_social_links_instagram' => 'better_payment_campaign_team_member_social_links_instagram_icon',
];

// Related Campaigns related variables
$related_campaign_sub_title = ! empty( $settings['better_payment_campaign_related_campaign_sub_title_text'] ) 
    ? $settings['better_payment_campaign_related_campaign_sub_title_text'] 
    : __( 'We Helped More Than 3,400 Children With Your Generosity', 'better-payment' );
$related_campaign_enable = ! empty( $settings['better_payment_campaign_general_footer_related_campaign_enable_layout_2'] ) && 'yes' === $settings['better_payment_campaign_general_footer_related_campaign_enable_layout_2'];
$related_campaigns_ids = ! empty( $settings['better_payment_campaign_related_campaigns'] ) 
    ? $settings['better_payment_campaign_related_campaigns'] 
    : [];

$better_payment_campaigns = get_option( 'better_payment_campaigns', [] );

$better_payment_campaigns_data = [];
if( ! empty( $better_payment_campaigns ) ) {
    foreach ( $better_payment_campaigns as $widget_id => $campaign_data ) {
        $campaign_data = json_decode( $campaign_data, true );
        $campaign_id = $campaign_data['campaign_id_postfix']; 
        $better_payment_campaigns_data[ $campaign_id ] = $campaign_data;
    }
}

$related_campaigns = [];
$default_settings_data = [
    'layout-1' => [
        'title' => __( 'Give Hope, Change Lives: Help Child in Need', 'better-payment' ),
        'image' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/header-left-image.png',
        'description' => __( 'Every child deserves a life free from hunger. Your contribution can provide nutritious meals, hope and a brighter future for needy children.', 'better-payment' ),
    ],
    'layout-2' => [
        'title' => __( 'Give Gaza\'s Children a Chance for Safety and Brighter Future', 'better-payment' ),
        'image' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-2/slider-img-1.webp',
        'description' => __( 'Your generosity can transform lives. Your small donation token can provide food, shelter, medical aid and safety to children in urgent need.', 'better-payment' ),
    ],
    'layout-3' => [
        'title' => __( 'Building Bridges to Change Lives', 'better-payment' ),
        'image' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-3/hero.webp',
        'description' => __( 'We believe every animal deserves a happy and healthy life. By contributing to our fundraiser, you\'re supporting rescue operations, medical treatments, and rehoming efforts for animals in desperate need.', 'better-payment' ),
    ],
];

if( ! empty( $related_campaigns_ids ) ) {
    foreach ( $related_campaigns_ids as $related_campaign_id ) {
        $related_campaign_id = $related_campaign_id['better_payment_campaign_related_campaign_id'];
        
        if ( ! empty( $related_campaign_id ) && array_key_exists( $related_campaign_id, $better_payment_campaigns_data ) ) {
            $related_campaign_data = $better_payment_campaigns_data[ $related_campaign_id ];

            $related_campaign_page_id = $related_campaign_data['page_id'];
            $related_campaign_widget_id = $related_campaign_data['widget_id'];
            $related_campaign_raised_amount = $related_campaign_data['raised_amount'];
            $related_campaign_settings = $this->get_elementor_widget_settings( $related_campaign_page_id, $related_campaign_widget_id );
            $page_url = get_permalink( $related_campaign_page_id );
            
            if( ! empty( $related_campaign_settings ) && $page_url ) {
                $related_campaign_layout = $related_campaign_settings['better_payment_campaign_layout'];
                $related_campaign_currency = $related_campaign_settings['better_payment_campaign_currency'];
                $related_campaign_currency_symbol = $this->get_currency_symbol( $related_campaign_currency );

                $related_campaign_title = $related_campaign_settings['better_payment_campaign_header_title_text'];
                $related_campaign_image = $related_campaign_settings['better_payment_campaign_header_image_one']['url'];
                $related_campaign_description = $related_campaign_settings['better_payment_campaign_header_short_description'];

                $related_campaigns[] = [
                    'title' => !empty( $related_campaign_title ) ? $related_campaign_title : $default_settings_data[ $related_campaign_layout ]['title'],
                    'image' => !empty( $related_campaign_image ) ? $related_campaign_image : $default_settings_data[ $related_campaign_layout ]['image'],
                    'description' => !empty( $related_campaign_description ) ? $related_campaign_description : $default_settings_data[ $related_campaign_layout ]['description'],
                    'page_id' => $related_campaign_page_id,
                    'widget_id' => $related_campaign_widget_id,
                    'campaign_id' => $related_campaign_id,
                    'raised_amount' => $related_campaign_raised_amount,
                    'currency' => $related_campaign_currency,
                    'currency_symbol' => $related_campaign_currency_symbol,
                    'page_url' => $page_url,
                ];
            }
        }
    }
}