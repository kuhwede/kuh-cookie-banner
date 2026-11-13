<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/traits/trait-kc-settings.php';
require_once __DIR__ . '/traits/trait-kc-consent.php';
require_once __DIR__ . '/traits/trait-kc-frontend.php';

final class RKCB_Plugin
{
    const OPTION = 'rkcb_options';
    const COOKIE_NAME = 'kccb_consent_v1';

    private $categories = array(
        'statistics'     => 'Statistiken',
        'marketing'      => 'Marketing',
        'external_media' => 'Externe Medien',
    );

    public function __construct()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('template_redirect', array($this, 'start_output_buffer'), 0);
        add_action('wp_head', array($this, 'print_nr_cookie_handler'), 0);
        add_action('wp_head', array($this, 'print_styles'), 99);
        add_action('wp_footer', array($this, 'print_frontend'), 99);
        add_shortcode('rkcb_settings_link', array($this, 'settings_link_shortcode'));
    }

    use KC_Settings_Trait;
    use KC_Consent_Trait;
    use KC_Frontend_Trait;
}
