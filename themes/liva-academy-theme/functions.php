<?php
if (!defined('ABSPATH')) { exit; }

function liva_theme_setup(): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', [
        'height'      => 90,
        'width'       => 320,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    register_nav_menus([
        'primary' => __('Primary Navigation', 'liva-academy'),
        'footer_learning' => __('Footer Learning', 'liva-academy'),
        'footer_account' => __('Footer Account', 'liva-academy'),
        'footer_company' => __('Footer Company', 'liva-academy'),
    ]);
}
add_action('after_setup_theme', 'liva_theme_setup');

function liva_theme_assets(): void {
    $theme   = wp_get_theme();
    $version = $theme->get('Version');
    $css_uri = get_template_directory_uri() . '/assets/css/';

    // Keep stylesheet dependencies explicit so the original cascade order is preserved.
    wp_enqueue_style('liva-academy-tokens', $css_uri . 'tokens.css', [], $version);
    wp_enqueue_style('liva-academy', $css_uri . 'liva.css', ['liva-academy-tokens'], $version);
    wp_enqueue_style('liva-academy-public', $css_uri . 'liva-public.css', ['liva-academy'], $version);
    wp_enqueue_style('liva-academy-placement', $css_uri . 'liva-placement.css', ['liva-academy-public'], $version);
    wp_enqueue_style('liva-academy-course', $css_uri . 'liva-course.css', ['liva-academy-placement'], $version);

    wp_enqueue_script('liva-academy', get_template_directory_uri() . '/assets/js/liva.js', [], $version, true);
}
add_action('wp_enqueue_scripts', 'liva_theme_assets');

function liva_page_url(string $slug, string $fallback = '#'): string {
    $page = get_page_by_path($slug);
    return $page ? get_permalink($page) : $fallback;
}

function liva_is_app_page(): bool {
    if (!is_page()) { return false; }
    $slug = get_post_field('post_name', get_queried_object_id());
    return in_array($slug, [
        'student-dashboard', 'my-courses', 'listening-practice', 'admin-dashboard',
        'liva-users', 'liva-courses', 'liva-listening-videos', 'liva-placement-tests', 'liva-orders',
        'liva-analytics', 'liva-settings'
    ], true);
}

function liva_body_classes(array $classes): array {
    if (liva_is_app_page()) { $classes[] = 'liva-app-page'; }
    return $classes;
}
add_filter('body_class', 'liva_body_classes');
/**
 * Retrieve and render an inline SVG icon from assets/icons/ with fallback support.
 */
function liva_icon(string $name): string {
    $dir  = trailingslashit(get_template_directory()) . 'assets/icons/';
    $name = sanitize_file_name(trim($name));

    $aliases = [
        'cart'      => ['cart.svg', 'order.svg', 'orders.svg'],
        'bell'      => ['bell.svg', 'notification.svg', 'notifications.svg'],
        'user'      => ['user.svg', 'account.svg', 'users.svg', 'profile.svg'],
        'dashboard' => ['home.svg', 'dashboard.svg'],
        'courses'   => ['courses.svg', 'course.svg'],
        'video'     => ['video.svg', 'listening.svg', 'videos.svg'],
        'video_2'   => ['video_2.svg', 'video2.svg', 'video.svg'],
        'video2'    => ['video_2.svg', 'video2.svg', 'video.svg'],
        'orders'    => ['order.svg', 'orders.svg', 'cart.svg'],
        'tests'     => ['tests.svg', 'test.svg', 'placement.svg'],
        'analytics' => ['analytics.svg'],
        'settings'  => ['setting.svg', 'settings.svg', 'gear.svg'],
        'search'    => ['search.svg'],
        'edit'      => ['edit.svg', 'pencil.svg'],
        'trash'     => ['trash.svg', 'delete.svg'],
        'logout'    => ['logout.svg', 'exit.svg'],
        'plus'      => ['plus.svg'],
        'play'      => ['play.svg'],
        'import'    => ['import.svg'],
        'order_history' => ['order_history.svg'],
        'arrangement'   => ['arrangement.svg'],
        'preview'       => ['preview.svg'],
        'chevron_up'    => ['chevron_up.svg'],
        'download'      => ['download.svg'],
        'recycle_bin'   => ['recycle_bin.svg', 'trash.svg', 'delete.svg'],
        'check_radio'   => ['check_radio.svg'],
        'uncheck_radio' => ['uncheck_radio.svg'],
    ];

    $files_to_check = $aliases[$name] ?? [$name, $name . '.svg'];

    foreach ($files_to_check as $file) {
        $path = $dir . ltrim($file, '/');
        if (file_exists($path)) {
            $svg = file_get_contents($path);
            $svg = preg_replace('/<\?xml.*?\?>/i', '', $svg);

            // Replace visible stroke and fill colors with currentColor without touching fill="none"
            $svg = preg_replace('/\bstroke="(?!none|transparent)[^"]*"/i', 'stroke="currentColor"', $svg);
            $svg = preg_replace('/\bfill="(?!none|transparent)[^"]*"/i', 'fill="currentColor"', $svg);

            // Add liva-raw-icon class to bypass global stroke styling
            return preg_replace('/<svg\b([^>]*)>/i', '<svg class="liva-raw-icon" $1>', $svg, 1);
        }
    }

    return '';
}