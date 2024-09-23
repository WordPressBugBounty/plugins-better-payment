<?php

$current_user = wp_get_current_user();
$username = '';

?>

<div class="better-payment">
    <div class="better-payment-user-dashboard-container bp--section-wrapper flex gap-6 min-h-full">
        <?php if ( $bp_settings['sidebar_show'] ) : ?>
        <div class="better-payment-user-dashboard-sidebar bp--sidebar-wrapper">
            <div class="bp--author">
                <?php 
                if ( $bp_settings['avatar_show'] ) :
                    if ( ($current_user instanceof WP_User) ) {
                        echo get_avatar( $current_user->user_email, 32 );
                        $username = $current_user->user_login;
                    }
                endif;
                ?>

                <?php if ( $bp_settings['username_show'] ) : ?>
                <h5 class="user-name"><?php echo esc_html( $username ); ?></h5>
                <?php endif; ?>
            </div>
            <div class="bp--sidebar-nav-list">
                <div class="bp--sidebar-nav d-none">
                    <span class="bp--nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_400_549)">
                                <path
                                    d="M4 5C4 4.73478 4.10536 4.48043 4.29289 4.29289C4.48043 4.10536 4.73478 4 5 4H9C9.26522 4 9.51957 4.10536 9.70711 4.29289C9.89464 4.48043 10 4.73478 10 5V9C10 9.26522 9.89464 9.51957 9.70711 9.70711C9.51957 9.89464 9.26522 10 9 10H5C4.73478 10 4.48043 9.89464 4.29289 9.70711C4.10536 9.51957 4 9.26522 4 9V5Z"
                                    stroke="#48506D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M4 15C4 14.7348 4.10536 14.4804 4.29289 14.2929C4.48043 14.1054 4.73478 14 5 14H9C9.26522 14 9.51957 14.1054 9.70711 14.2929C9.89464 14.4804 10 14.7348 10 15V19C10 19.2652 9.89464 19.5196 9.70711 19.7071C9.51957 19.8946 9.26522 20 9 20H5C4.73478 20 4.48043 19.8946 4.29289 19.7071C4.10536 19.5196 4 19.2652 4 19V15Z"
                                    stroke="#48506D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M14 15C14 14.7348 14.1054 14.4804 14.2929 14.2929C14.4804 14.1054 14.7348 14 15 14H19C19.2652 14 19.5196 14.1054 19.7071 14.2929C19.8946 14.4804 20 14.7348 20 15V19C20 19.2652 19.8946 19.5196 19.7071 19.7071C19.5196 19.8946 19.2652 20 19 20H15C14.7348 20 14.4804 19.8946 14.2929 19.7071C14.1054 19.5196 14 19.2652 14 19V15Z"
                                    stroke="#48506D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M14 7H20" stroke="#48506D" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M17 4V10" stroke="#48506D" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </g>
                            <defs>
                                <clipPath id="clip0_400_549">
                                    <rect width="24" height="24" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                    </span>
                    <span class="bp--nav-text">Dashboard</span>
                </div>

                <?php if ( $bp_settings['subscriptions_show'] ) : ?>
                <div class="bp--sidebar-nav subscriptions-tab active">
                    <span class="bp--nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_400_558)">
                                <path
                                    d="M3 8V12.172C3.00011 12.7024 3.2109 13.211 3.586 13.586L9.296 19.296C9.74795 19.7479 10.3609 20.0017 11 20.0017C11.6391 20.0017 12.252 19.7479 12.704 19.296L16.296 15.704C16.7479 15.252 17.0017 14.6391 17.0017 14C17.0017 13.3609 16.7479 12.748 16.296 12.296L10.586 6.586C10.211 6.2109 9.70239 6.00011 9.172 6H5C4.46957 6 3.96086 6.21071 3.58579 6.58579C3.21071 6.96086 3 7.46957 3 8Z"
                                    stroke="#2B2748" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M18 19L19.592 17.408C20.4958 16.5041 21.0035 15.2782 21.0035 14C21.0035 12.7218 20.4958 11.4959 19.592 10.592L15 6"
                                    stroke="#2B2748" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M6.99828 10H6.98828" stroke="#2B2748" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </g>
                            <defs>
                                <clipPath id="clip0_400_558">
                                    <rect width="24" height="24" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                    </span>
                    <span class="bp--nav-text"><?php esc_html_e($bp_settings['subscription_label'], 'better-payment'); ?></span>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>

        <div class="bp--db-main-wrapper">
            <div class="transactions-tab-wrapper d-none">

            </div>    
            
            <?php if ( $bp_settings['subscriptions_show'] ) : ?>
                <?php if ( ( ! $this->pro_enabled ) ) : ?>
                <p>
                    <a class="width-100" target="_blank" href="//wpdeveloper.com/in/upgrade-better-payment-pro">
                        <img width="100%" src="<?php echo esc_url(BETTER_PAYMENT_ASSETS . '/img/' . 'user-dashboard-subscription-pro-banner.png'); ?>" alt="subscription-pro-banner">
                    </a>
                </p>
                <?php endif; ?>
                
                <div class="subscription-tab-wrapper">
                    <?php do_action('better_payment/widget/user-dashboard/subscriptions_tab', $settings, $bp_settings); ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>