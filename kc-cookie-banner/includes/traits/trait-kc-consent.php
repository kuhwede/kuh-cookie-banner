<?php
if (!defined('ABSPATH')) {
    exit;
}

trait KC_Consent_Trait
{
    public function start_output_buffer()
    {
        if (is_admin() || is_feed() || is_robots() || wp_doing_ajax()) {
            return;
        }
        ob_start(array($this, 'filter_html'));
    }

    private function current_consent()
    {
        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return null;
        }
        $decoded = json_decode(wp_unslash($_COOKIE[self::COOKIE_NAME]), true);
        if (!is_array($decoded) || empty($decoded['decided'])) {
            return null;
        }
        $consent = array('essential' => true);
        foreach ($this->categories as $key => $label) {
            $consent[$key] = !empty($decoded[$key]);
        }
        return $consent;
    }

    private function category_for_url($url)
    {
        $settings = $this->settings();
        $url = strtolower(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));

        if (
            strpos($url, 'googletagmanager.com/gtag/js') !== false ||
            strpos($url, 'google-analytics.com') !== false ||
            strpos($url, 'analytics.google.com') !== false
        ) {
            return 'statistics';
        }

        if (strpos($url, 'googletagmanager.com/gtm.js') !== false) {
            return 'marketing';
        }

        if (
            strpos($url, 'hotjar.com') !== false ||
            strpos($url, 'connect.facebook.net') !== false ||
            strpos($url, 'facebook.com/tr') !== false
        ) {
            return 'marketing';
        }

        foreach ($this->categories as $category => $label) {
            $lines = preg_split('/\r\n|\r|\n/', $settings['rules'][$category]);
            foreach ($lines as $line) {
                $rule = strtolower(trim($line));
                if ($rule !== '' && strpos($url, $rule) !== false) {
                    return $category;
                }
            }
        }
        return false;
    }

    private function statistics_allowed($consent)
    {
        return is_array($consent) && !empty($consent['statistics']);
    }

    private function gtm_allowed($consent)
    {
        return is_array($consent) && !empty($consent['marketing']);
    }

    private function hotjar_allowed($consent)
    {
        return is_array($consent) && !empty($consent['marketing']);
    }

    private function youtube_placeholder_html($settings)
    {
        return '<div class="rkcb-blocked-media rkcb-youtube-placeholder" data-rkcb-category="external_media">' .
            '<div class="rkcb-youtube-brand"><span class="rkcb-youtube-play" aria-hidden="true"></span><strong>' . esc_html($settings['youtube_label']) . '</strong></div>' .
            '<p>' . esc_html($settings['youtube_blocked_text']) . '</p>' .
            '<button type="button" class="rkcb-media-allow rkcb-youtube-allow" data-rkcb-category="external_media">' .
            esc_html($settings['youtube_allow_text']) . '</button></div>';
    }

    private function block_elementor_youtube_widget($html)
    {
        $placeholder = $this->youtube_placeholder_html($this->settings());

        return preg_replace_callback(
            '~(<div(?=[^>]*class="[^"]*elementor-widget-video[^"]*")(?=[^>]*data-widget_type="video[.]default")[^>]*>)((?:(?!data-widget_type=).)*?)<div class="elementor-video"></div>~is',
            function ($matches) use ($placeholder) {
                $tag = $matches[1];
                $inner = $matches[2];

                if (!preg_match('~data-settings="([^"]*)"~is', $tag, $settings_match)) {
                    return $matches[0];
                }

                $settings_json = html_entity_decode($settings_match[1], ENT_QUOTES, 'UTF-8');
                $widget_settings = json_decode($settings_json, true);
                if (!is_array($widget_settings) || (isset($widget_settings['video_type']) && $widget_settings['video_type'] !== 'youtube')) {
                    return $matches[0];
                }

                $youtube_url = isset($widget_settings['youtube_url']) ? (string) $widget_settings['youtube_url'] : '';
                if ($youtube_url === '' || (stripos($youtube_url, 'youtube.com') === false && stripos($youtube_url, 'youtu.be') === false)) {
                    return $matches[0];
                }

                $tag = str_replace(' data-widget_type="video.default"', '', $tag);
                $tag = preg_replace('~ data-settings="[^"]*"~is', '', $tag, 1);

                return $tag . $inner . $placeholder;
            },
            $html
        );
    }

    public function filter_html($html)
    {
        if (!is_string($html) || $html === '') {
            return $html;
        }

        $settings = $this->settings();
        $consent = $this->current_consent();
        if (!is_array($consent) || empty($consent['external_media'])) {
            $html = $this->block_elementor_youtube_widget($html);
        }
        $gtm_id = $settings['gtm_id'];
        $hotjar_id = $settings['hotjar_id'];

        if (!$this->gtm_allowed($consent)) {
            $html = preg_replace_callback(
                '~<script\b[^>]*>.*?</script\s*>~is',
                function ($matches) use ($gtm_id) {
                    $block = $matches[0];
                    $has_gtm_loader = stripos($block, 'googletagmanager.com/gtm.js') !== false;
                    $has_standard_gtm_id = preg_match('/\\bGTM-[A-Z0-9]+\\b/i', $block);
                    $has_configured_id = $gtm_id !== '' && stripos($block, $gtm_id) !== false;
                    if (($has_gtm_loader && $has_standard_gtm_id) || $has_configured_id) {
                        return '<script type="text/plain" data-rkcb-blocked-service="gtm"></script>';
                    }
                    return $block;
                },
                $html
            );

            $html = preg_replace(
                '~<noscript\b[^>]*>.*?' . preg_quote($gtm_id !== '' ? $gtm_id : 'GTM-', '~') . '.*?</noscript\s*>~is',
                '',
                $html
            );
        }

        if (!$this->hotjar_allowed($consent)) {
            $html = preg_replace_callback(
                '~<script\b[^>]*>.*?</script\s*>~is',
                function ($matches) use ($hotjar_id) {
                    $block = $matches[0];
                    $has_hotjar_id = stripos($block, 'hotjar.com') !== false || stripos($block, 'hotjar-' . $hotjar_id) !== false;
                    $has_hjid = preg_match('/\bhjid\s*[:=]\s*[\'\"]?' . preg_quote($hotjar_id, '/') . '\b/i', $block);
                    if ($has_hotjar_id || $has_hjid) {
                        return '<script type="text/plain" data-rkcb-blocked-service="hotjar"></script>';
                    }
                    return $block;
                },
                $html
            );
        }

        if (empty($consent['marketing'])) {
            $html = preg_replace_callback(
                '~<script[^>]*>.*?</script>~is',
                function ($matches) {
                    $block = $matches[0];
                    $has_pixel_loader = stripos($block, 'fbq') !== false || stripos($block, 'connect.facebook.net') !== false;
                    if ($has_pixel_loader) {
                        return '<script type="text/plain" data-rkcb-blocked-service="facebook-pixel"></script>';
                    }
                    return $block;
                },
                $html
            );

            $html = preg_replace_callback(
                '~<noscript[^>]*>.*?</noscript>~is',
                function ($matches) {
                    $block = $matches[0];
                    if (stripos($block, 'facebook.com/tr') !== false) {
                        return '';
                    }
                    return $block;
                },
                $html
            );
        }

        $html = preg_replace_callback(
            '~<script\b(?=[^>]*\bsrc\s*=\s*(["\'])(.*?)\1)[^>]*>\s*</script\s*>~is',
            function ($matches) use ($consent, $settings) {
                $url = $matches[2];
                $category = $this->category_for_url($url);
                if (!$category || !empty($consent[$category])) {
                    return $matches[0];
                }
                return '<script type="text/plain" data-rkcb-blocked="1" data-rkcb-category="' .
                    esc_attr($category) . '" data-rkcb-src="' . esc_attr($url) . '"></script>';
            },
            $html
        );

        if (!$this->statistics_allowed($consent)) {
            $html = preg_replace_callback(
                '~<script[^>]*>.*?</script>~is',
                function ($matches) {
                    $block = $matches[0];
                    $has_gtag_loader = stripos($block, 'googletagmanager.com/gtag/js') !== false;
                    $has_legacy_loader = stripos($block, 'google-analytics.com/analytics.js') !== false;
                    $has_gtag_config = stripos($block, "gtag('config'") !== false || stripos($block, 'gtag("config"') !== false;
                    $has_legacy_config = stripos($block, "ga('create'") !== false || stripos($block, 'ga("create"') !== false;
                    if ($has_gtag_loader || $has_legacy_loader || $has_gtag_config || $has_legacy_config) {
                        return '<script type="text/plain" data-rkcb-blocked-service="google-analytics"></script>';
                    }
                    return $block;
                },
                $html
            );
        }

        $html = preg_replace_callback(
            '~<iframe\b(?=[^>]*\bsrc\s*=\s*(["\'])(.*?)\1)[^>]*>.*?</iframe\s*>~is',
            function ($matches) use ($consent, $settings) {
                $url = $matches[2];
                $category = $this->category_for_url($url);
                if (!$category || !empty($consent[$category])) {
                    return $matches[0];
                }

                $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
                $youtube_hosts = array('youtube.com', 'youtube-nocookie.com', 'youtu.be');
                $is_youtube = false;
                foreach ($youtube_hosts as $youtube_host) {
                    if ($host === $youtube_host || substr($host, -strlen('.' . $youtube_host)) === '.' . $youtube_host) {
                        $is_youtube = true;
                        break;
                    }
                }

                $container_class = $is_youtube ? 'rkcb-blocked-media rkcb-youtube-placeholder' : 'rkcb-blocked-media';
                $blocked_text = $is_youtube ? $settings['youtube_blocked_text'] : $settings['media_blocked_text'];
                $button_text = $is_youtube ? $settings['youtube_allow_text'] : $settings['media_allow_text'];
                $button_class = $is_youtube ? 'rkcb-media-allow rkcb-youtube-allow' : 'rkcb-media-allow';
                $youtube_label = $is_youtube
                    ? '<div class="rkcb-youtube-brand"><span class="rkcb-youtube-play" aria-hidden="true"></span><strong>' . esc_html($settings['youtube_label']) . '</strong></div>'
                    : '';

                return '<div class="' . esc_attr($container_class) . '" data-rkcb-category="' . esc_attr($category) . '">' .
                    $youtube_label .
                    '<p>' . esc_html($blocked_text) . '</p>' .
                    '<button type="button" class="' . esc_attr($button_class) . '" data-rkcb-category="' . esc_attr($category) . '">' .
                    esc_html($button_text) . '</button></div>';
            },
            $html
        );

        return $html;
    }
}
