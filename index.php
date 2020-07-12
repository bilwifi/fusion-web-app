<?php

/**
 * Plugin Name: Fusion Web App
 * Description: Connect your native App built with Expo to Wordpress and send push notifications.
 * Version: 1.0.10
 * Author: Mauro Martínez
 * Author URI: https://inspiredpulse.com/
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constants
 */
define('FUSION_WA_SLUG', 'fusion-web-app');
define('FUSION_WA_DIR', plugin_dir_path(__FILE__));
define('FUSION_WA_URL', admin_url('admin.php?page=') . FUSION_WA_SLUG);
define('FUSION_WA_ASSETS', plugin_dir_url(__FILE__) . 'assets/');
define('FUSION_WA_T_ALL', str_replace('-', '_', FUSION_WA_SLUG) . '_all');
define('FUSION_WA_T_RECIPIENTS', str_replace('-', '_', FUSION_WA_SLUG) . '_recipients');
define('FUSION_WA_T_SCREENS', str_replace('-', '_', FUSION_WA_SLUG) . '_screens');
define('FUSION_WA_VERSION', '1.0.10');
define('FUSION_WA_DIR_LANG', dirname( plugin_basename( __FILE__ ) ) . '/languages');

/**
 * Include main Class
 */
if (!class_exists('FusionWebApp')) {
    include_once(dirname(__FILE__) . '/includes/class-init.php');
}

/**
 * Init Main instance of Fusion Web App
 */
function init_fussion_web_app()
{
    return new FusionWebApp();
}

/**
 * Global for backwards compatibility
 */
$GLOBALS['fussion_web_app'] = init_fussion_web_app();         