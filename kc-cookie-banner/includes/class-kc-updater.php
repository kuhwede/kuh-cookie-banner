<?php
if (!defined('ABSPATH')) {
    exit;
}

final class KC_GitHub_Updater
{
    const OWNER = 'kuhwede';
    const REPOSITORY = 'kuh-cookie-banner';
    const PLUGIN_SLUG = 'kc-cookie-banner';
    const ASSET_PREFIX = 'kc-cookie-banner-';
    const CACHE_KEY = 'kccb_github_release_v1';
    const CACHE_TTL = 21600;

    private $plugin_file;
    private $plugin_basename;
    private $current_version;

    private function __construct($plugin_file, $current_version)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->current_version = $current_version;

        add_filter('pre_set_site_transient_update_plugins', array($this, 'filter_update_transient'));
        add_filter('plugins_api', array($this, 'filter_plugin_information'), 20, 3);
    }

    public static function register($plugin_file, $current_version)
    {
        new self($plugin_file, $current_version);
    }

    public function filter_update_transient($transient)
    {
        if (!is_object($transient) || empty($transient->checked) || !is_array($transient->checked)) {
            return $transient;
        }

        if (!array_key_exists($this->plugin_basename, $transient->checked)) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if (!$release || !version_compare($release['version'], $this->current_version, '>')) {
            return $transient;
        }

        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = array();
        }

        $transient->response[$this->plugin_basename] = (object) array(
            'slug'        => self::PLUGIN_SLUG,
            'plugin'      => $this->plugin_basename,
            'new_version' => $release['version'],
            'url'         => $release['html_url'],
            'package'     => $release['download_url'],
            'requires'    => '6.0',
            'requires_php'=> '7.4',
        );

        return $transient;
    }

    public function filter_plugin_information($result, $action, $args)
    {
        if ($action !== 'plugin_information' || !is_object($args) || empty($args->slug) || $args->slug !== self::PLUGIN_SLUG) {
            return $result;
        }

        $release = $this->get_latest_release();
        if (!$release) {
            return $result;
        }

        $info = new stdClass();
        $info->name = 'Kuh Cookie';
        $info->slug = self::PLUGIN_SLUG;
        $info->version = $release['version'];
        $info->author = 'Ron Kuhwede';
        $info->homepage = $release['html_url'];
        $info->download_link = $release['download_url'];
        $info->requires = '6.0';
        $info->requires_php = '7.4';
        $info->sections = array(
            'description' => 'Cookie-Banner mit Kategorien und Einwilligungssteuerung.',
            'changelog'   => $release['body'] !== '' ? wpautop(esc_html($release['body'])) : 'Keine Release-Notizen vorhanden.',
        );

        return $info;
    }

    private function get_latest_release()
    {
        $cached = get_site_transient(self::CACHE_KEY);
        if (is_array($cached) && !empty($cached['version']) && !empty($cached['download_url'])) {
            return $cached;
        }

        $api_url = 'https://api.github.com/repos/' . self::OWNER . '/' . self::REPOSITORY . '/releases/latest';
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'Kuh-Cookie-WordPress-Updater',
            ),
        ));

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $release = json_decode(wp_remote_retrieve_body($response));
        if (!is_object($release) || !empty($release->draft) || !empty($release->prerelease) || empty($release->tag_name) || empty($release->assets) || !is_array($release->assets)) {
            return null;
        }

        $version = preg_replace('/^[vV]/', '', (string) $release->tag_name);
        if ($version === '' || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
            return null;
        }

        $expected_asset = self::ASSET_PREFIX . $version . '.zip';
        $download_url = '';
        foreach ($release->assets as $asset) {
            if (!is_object($asset) || empty($asset->name) || $asset->name !== $expected_asset || empty($asset->browser_download_url)) {
                continue;
            }
            $parts = wp_parse_url($asset->browser_download_url);
            $path_prefix = '/kuhwede/kuh-cookie-banner/releases/download/';
            if (!is_array($parts) || empty($parts['scheme']) || strtolower($parts['scheme']) !== 'https' || empty($parts['host']) || strtolower($parts['host']) !== 'github.com' || empty($parts['path']) || strpos($parts['path'], $path_prefix) !== 0) {
                continue;
            }
            $download_url = esc_url_raw($asset->browser_download_url);
            break;
        }

        if ($download_url === '') {
            return null;
        }

        $result = array(
            'version'      => $version,
            'download_url' => $download_url,
            'html_url'     => !empty($release->html_url) ? esc_url_raw($release->html_url) : 'https://github.com/' . self::OWNER . '/' . self::REPOSITORY,
            'body'         => isset($release->body) ? (string) $release->body : '',
        );

        set_site_transient(self::CACHE_KEY, $result, self::CACHE_TTL);
        return $result;
    }
}
