<?php
/**
 * Plugin Name: Liva Academy Core
 * Description: Structural LMS functionality for the Liva Academy design prototype.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: liva-academy-core
 */
if (!defined('ABSPATH')) { exit; }

define('LIVA_CORE_VERSION', '0.2.0');
define('LIVA_CORE_PATH', plugin_dir_path(__FILE__));
define('LIVA_CORE_URL', plugin_dir_url(__FILE__));

require_once LIVA_CORE_PATH . 'includes/class-liva-core.php';
require_once LIVA_CORE_PATH . 'includes/class-liva-actions.php';
require_once LIVA_CORE_PATH . 'includes/class-liva-shortcodes.php';

register_activation_hook(__FILE__, ['Liva_Core', 'activate']);
add_action('plugins_loaded', ['Liva_Core', 'boot']);
