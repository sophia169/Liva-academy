<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <header class="liva-site-header">
        <div class="liva-header-inner">
            <div class="liva-brand">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Liva Academy home', 'liva-academy'); ?>">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/liva-logo-horizontal.jpg'); ?>" alt="<?php bloginfo('name'); ?>">
                    </a>
                <?php endif; ?>
            </div>

            <button class="liva-mobile-nav-toggle" type="button" aria-expanded="false" aria-controls="liva-primary-nav">☰</button>

            <nav id="liva-primary-nav" class="liva-primary-nav" aria-label="<?php esc_attr_e('Primary navigation', 'liva-academy'); ?>">
                <!-- My Learning Dropdown -->
                <!-- My Learning Dropdown -->
                <div class="liva-nav-item has-dropdown">
                    <a href="<?php echo esc_url(liva_page_url('my-learning', home_url('/my-learning/'))); ?>">My learning</a>
                    <div class="liva-dropdown-menu">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'courses', liva_page_url('my-learning', home_url('/my-learning/')))); ?>">
                            <?php esc_html_e('Courses', 'liva-academy'); ?>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'listening', liva_page_url('my-learning', home_url('/my-learning/')))); ?>">
                            <?php esc_html_e('Listening practice', 'liva-academy'); ?>
                        </a>
                        <a href="<?php echo esc_url(liva_page_url('placement-test', home_url('/placement-test/'))); ?>">
                            <?php esc_html_e('Placement test', 'liva-academy'); ?>
                        </a>
                    </div>
                </div>
                <!-- Online Classroom Dropdown -->
                <div class="liva-nav-item has-dropdown">
                    <a href="<?php echo esc_url(liva_page_url('online-classroom', home_url('/#classroom'))); ?>">Online classroom</a>
                    <div class="liva-dropdown-menu">
                        <a href="<?php echo esc_url(liva_page_url('classroom-intro', home_url('/#introduction'))); ?>">Introduction</a>
                        <a href="<?php echo esc_url(liva_page_url('classroom-contact', home_url('/#contact'))); ?>">Contact</a>
                        <a href="<?php echo esc_url(liva_page_url('classroom-reservation', home_url('/#reservation'))); ?>">Reservation</a>
                    </div>
                </div>

                <!-- Pricing (No Dropdown) -->
                <div class="liva-nav-item">
                    <a href="<?php echo esc_url(liva_page_url('pricing', home_url('/#pricing'))); ?>">Pricing</a>
                </div>

                <!-- Company Dropdown -->
                <div class="liva-nav-item has-dropdown">
                    <a href="<?php echo esc_url(liva_page_url('company', home_url('/#company'))); ?>">Company</a>
                    <div class="liva-dropdown-menu">
                        <a href="<?php echo esc_url(liva_page_url('about-us', home_url('/#about'))); ?>">About us</a>
                        <a href="<?php echo esc_url(liva_page_url('contact-us', home_url('/#contact'))); ?>">Contact us</a>
                        <a href="<?php echo esc_url(liva_page_url('help-center', home_url('/#help'))); ?>">Help center</a>
                    </div>
                </div>
            </nav>

            <div class="liva-header-actions">
                <?php if (is_user_logged_in()) : ?>
                    <!-- Figma Cart (Trolley with Wheels) -->
                    <a class="liva-icon-link" href="<?php echo esc_url(liva_page_url('order-history', '#')); ?>" aria-label="Cart">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span class="liva-cart-count">0</span>
                    </a>

                    <!-- Figma Notification Bell (Outline) -->
                    <a class="liva-icon-link" href="<?php echo esc_url(liva_page_url('notifications', '#')); ?>" aria-label="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </a>

                    <!-- Figma Profile (Outline) -->
                    <?php
                    $admin_link = (current_user_can('liva_manage_courses') || current_user_can('manage_options'))
                        ? liva_page_url('admin-dashboard', '#')
                        : liva_page_url('student-dashboard', '#');
                    ?>
                    <a class="liva-icon-link" href="<?php echo esc_url($admin_link); ?>" aria-label="Account">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                <?php else : ?>
                    <a class="liva-text-link" href="<?php echo esc_url(liva_page_url('login', wp_login_url())); ?>">Log in</a>
                    <a class="liva-btn liva-btn-primary liva-btn-sm" href="<?php echo esc_url(liva_page_url('register', wp_registration_url())); ?>">Get started</a>
                <?php endif; ?>
            </div>
    </header>

    <main id="main" class="liva-site-main">