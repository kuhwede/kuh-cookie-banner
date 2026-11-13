<?php
/**
 * Plugin Name: Kuh Cookie
 * Description: Cookie-Banner mit Kategorien und gezielter Einwilligungssteuerung für GTM, Hotjar und Facebook Pixel.
 * Version: 1.4.3
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Ron Kuhwede
 * Text Domain: rk-cookie-banner
 * Update URI: https://github.com/kuhwede/kuh-cookie-banner
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('KC_COOKIE_VERSION')) {
    define('KC_COOKIE_VERSION', '1.4.3');
}

require_once __DIR__ . '/includes/class-kc-updater.php';
KC_GitHub_Updater::register(__FILE__, KC_COOKIE_VERSION);

require_once __DIR__ . '/includes/class-kc-plugin.php';

new RKCB_Plugin();
