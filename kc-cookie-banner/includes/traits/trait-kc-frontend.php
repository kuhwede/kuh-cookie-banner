<?php
if (!defined('ABSPATH')) {
    exit;
}

trait KC_Frontend_Trait
{
    public function print_nr_cookie_handler()
    {
        if (is_admin()) {
            return;
        }
        ?>
        <script id="rkcb-nr-cookie-handler">
        (function () {
            "use strict";
            var cookieName = <?php echo wp_json_encode(self::COOKIE_NAME); ?>;
            var legacyCookieName = "rkcb_consent_v1";
            if (legacyCookieName !== cookieName) {
                var secure = location.protocol === "https:" ? "; Secure" : "";
                document.cookie = legacyCookieName + "=; Path=/; Max-Age=0; SameSite=Lax" + secure;
            }

            function readConsent() {
                var prefix = cookieName + "=";
                var parts = document.cookie.split(";");
                for (var i = 0; i < parts.length; i++) {
                    var item = parts[i].trim();
                    if (item.indexOf(prefix) === 0) {
                        try {
                            return JSON.parse(decodeURIComponent(item.substring(prefix.length)));
                        } catch (error) {
                            return null;
                        }
                    }
                }
                return null;
            }

            var consentState = readConsent() || {};
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
            window.gtag("consent", "default", {
                analytics_storage: consentState.statistics === true ? "granted" : "denied",
                ad_storage: consentState.marketing === true ? "granted" : "denied",
                ad_user_data: consentState.marketing === true ? "granted" : "denied",
                ad_personalization: consentState.marketing === true ? "granted" : "denied",
                functionality_storage: "granted",
                security_storage: "granted"
            });

            window.NrCookieHandler = window.NrCookieHandler || {};
            if (typeof window.NrCookieHandler.queue !== "function") {
                window.NrCookieHandler.queue = function (category, callback) {
                    if (typeof callback !== "function") return;
                    var consent = readConsent();
                    if (category === "essential" || (consent && consent[category] === true)) {
                        callback();
                    }
                };
            }
        }());
        </script>
        <?php
    }

    public function print_styles()
    {
        if (is_admin()) {
            return;
        }
        $settings = $this->settings();
        $colors = $settings['colors'];
        ?>
        <style id="rkcb-styles">
            :root {
                --rkcb-bg: <?php echo esc_html($colors['background']); ?>;
                --rkcb-text: <?php echo esc_html($colors['text']); ?>;
                --rkcb-accept-bg: <?php echo esc_html($colors['accept_bg']); ?>;
                --rkcb-accept-text: <?php echo esc_html($colors['accept_text']); ?>;
                --rkcb-secondary-bg: <?php echo esc_html($colors['secondary_bg']); ?>;
                --rkcb-secondary-text: <?php echo esc_html($colors['secondary_text']); ?>;
                --rkcb-youtube-button-bg: <?php echo esc_html($colors['youtube_button_bg']); ?>;
                --rkcb-youtube-button-text: <?php echo esc_html($colors['youtube_button_text']); ?>;
                --rkcb-youtube-button-hover-bg: <?php echo esc_html($colors['youtube_button_hover_bg']); ?>;
                --rkcb-youtube-button-hover-text: <?php echo esc_html($colors['youtube_button_hover_text']); ?>;
                --rkcb-link: <?php echo esc_html($colors['link']); ?>;
                --rkcb-border: <?php echo esc_html($colors['border']); ?>;
            }
            .rkcb-banner {
                position: fixed; z-index: 999999; left: 50%; top: 50%; transform: translate(-50%, -50%);
                width: min(<?php echo (int) $settings['width']; ?>px, calc(100vw - 32px));
                max-height: calc(100vh - 32px); max-height: calc(100dvh - 32px); overflow-y: auto;
                box-sizing: border-box; padding: 34px; color: var(--rkcb-text); background: var(--rkcb-bg);
                border: 1px solid var(--rkcb-border); border-radius: 22px;
                box-shadow: 0 0 0 100vmax rgba(17,24,39,.58), 0 24px 70px rgba(17,24,39,.25); font: 16px/1.5 Arial, Helvetica, sans-serif; text-shadow: none;
            }
            .rkcb-banner[hidden] { display: none !important; }
            .rkcb-banner *, .rkcb-blocked-media * { box-sizing: border-box; font-family: inherit !important; text-shadow: none !important; }
            .rkcb-banner h2 { margin: 0 0 12px; color: var(--rkcb-text); font-size: clamp(24px,3vw,32px); line-height: 1.2; letter-spacing: -.025em; }
            .rkcb-banner h2::before {
                content: "✓"; display: grid; place-items: center; width: 44px; height: 44px; margin-bottom: 18px;
                color: var(--rkcb-accept-bg); background: #f0faf1; border: 1px solid #d9f1dc; border-radius: 14px;
                font-size: 22px; font-weight: 700;
            }
            .rkcb-banner p { max-width: none; width: 100%; margin: 0 0 22px; color: var(--rkcb-text); line-height: 1.6; }
            .rkcb-categories { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; margin: 24px 0; }
            .rkcb-category {
                display: flex; align-items: center; gap: 12px; min-height: 62px; padding: 13px 15px;
                color: var(--rkcb-text); background: #f8f9fb; border: 1px solid var(--rkcb-border);
                border-radius: 13px; cursor: pointer; font-weight: 600; transition: border-color .15s ease, background-color .15s ease;
            }
            .rkcb-category:hover { background: #f2f5f7; border-color: #b9c1ca; }
            .rkcb-category input { flex: 0 0 auto; width: 20px; height: 20px; margin: 0; accent-color: var(--rkcb-accept-bg); cursor: pointer; }
            .rkcb-category input:disabled { cursor: not-allowed; }
            .rkcb-actions { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 11px; }
            .rkcb-actions button, .rkcb-media-allow {
                width: 100%; min-height: 52px; padding: 13px 18px; border: 1px solid transparent; border-radius: 12px;
                font: inherit; font-weight: 650; cursor: pointer; transition: transform .15s ease, filter .15s ease, box-shadow .15s ease, background-color .15s ease, color .15s ease;
            }
            .rkcb-actions .rkcb-accept {
                grid-column: 1 / -1; min-height: 58px; color: var(--rkcb-accept-text); background: var(--rkcb-accept-bg);
                box-shadow: 0 5px 14px rgba(0,0,0,.12); font-size: 16px;
            }
            .rkcb-actions [data-rkcb-action="selection"] { color: var(--rkcb-secondary-text); background: var(--rkcb-secondary-bg); border-color: var(--rkcb-border); }
            .rkcb-actions [data-rkcb-action="reject"] { color: var(--rkcb-secondary-text); background: var(--rkcb-secondary-bg); border-color: var(--rkcb-border); }
            .rkcb-actions button:hover, .rkcb-media-allow:hover { filter: brightness(.96); transform: translateY(-1px); }
            .rkcb-actions .rkcb-accept:hover { box-shadow: 0 8px 18px rgba(0,0,0,.16); }
            .rkcb-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px 28px; margin-top: 22px; }
            .rkcb-links a { color: var(--rkcb-link); font-size: 15px; text-decoration: underline; text-underline-offset: 3px; }
            .rkcb-blocked-media {
                box-sizing: border-box; display: flex; min-height: 220px; flex-direction: column; align-items: center; justify-content: center;
                gap: 12px; padding: 24px; text-align: center; background: #f4f6f8;
                border: 1px solid var(--rkcb-border); border-radius: 14px; font: 16px/1.5 Arial, Helvetica, sans-serif; text-shadow: none;
            }
            .rkcb-youtube-placeholder { aspect-ratio: 16 / 9; min-height: 180px; background: rgba(244,246,248,.85); }
            .elementor-widget-video .elementor-wrapper { position: relative; container-type: inline-size; }
            .elementor-widget-video .elementor-wrapper .rkcb-youtube-placeholder {
                position: absolute; z-index: 1; top: 0; right: 0; left: 0;
                width: 100%; height: auto; min-height: 0; aspect-ratio: 16 / 9;
            }
            .wp-block-embed.wp-embed-aspect-16-9 .wp-block-embed__wrapper { position: relative; }
            .wp-block-embed.wp-embed-aspect-16-9 .wp-block-embed__wrapper > .rkcb-youtube-placeholder {
                position: absolute; z-index: 1; top: 0; right: 0; left: 0;
                width: 100%; height: auto; min-height: 0; aspect-ratio: 16 / 9;
            }
            .rkcb-youtube-brand { display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; }
            .rkcb-youtube-play { position: relative; display: inline-block; width: 42px; height: 30px; background: #ff0033; border-radius: 9px; }
            .rkcb-youtube-play::after {
                content: ""; position: absolute; left: 50%; top: 50%; width: 0; height: 0;
                transform: translate(-33.333%,-50%);
                border-top: 7px solid transparent; border-bottom: 7px solid transparent; border-left: 11px solid #ffffff;
            }
            .rkcb-blocked-media p { margin: 0; }
            .rkcb-media-allow { width: auto; min-height: 44px; color: var(--rkcb-secondary-text); background: var(--rkcb-secondary-bg); }
            .rkcb-media-allow.rkcb-youtube-allow { color: var(--rkcb-youtube-button-text); background: var(--rkcb-youtube-button-bg); }
            .rkcb-media-allow.rkcb-youtube-allow:hover {
                color: var(--rkcb-youtube-button-hover-text); background: var(--rkcb-youtube-button-hover-bg); filter: none;
            }
            @container (max-width: 380px) {
                .rkcb-youtube-placeholder { gap: clamp(5px,2.2cqw,8px); padding: clamp(10px,4cqw,16px); }
                .rkcb-youtube-brand { gap: clamp(5px,2.4cqw,8px); font-size: clamp(12px,4.5cqw,15px); }
                .rkcb-youtube-play { width: clamp(26px,9.5cqw,32px); height: clamp(18px,6.6cqw,22px); }
                .rkcb-youtube-play::after { border-top-width: clamp(4px,1.7cqw,6px); border-bottom-width: clamp(4px,1.7cqw,6px); border-left-width: clamp(7px,2.8cqw,9px); }
                .rkcb-youtube-placeholder p { max-width: 100%; font-size: clamp(11px,3.9cqw,13px); line-height: 1.25; overflow-wrap: anywhere; }
                .rkcb-youtube-placeholder .rkcb-youtube-allow {
                    display: block; width: 100%; max-width: 100%; min-height: 0;
                    padding: clamp(6px,2.2cqw,8px) clamp(8px,2.7cqw,10px);
                    font-size: clamp(10px,3.6cqw,12px); line-height: 1.2; white-space: normal; overflow-wrap: anywhere;
                }
            }
            @container (max-width: 260px) {
                .rkcb-youtube-placeholder { gap: 4px; padding: 4px 6px; }
                .rkcb-youtube-brand { gap: 5px; font-size: 12px; }
                .rkcb-youtube-play { width: 24px; height: 17px; }
                .rkcb-youtube-play::after { border-top-width: 4px; border-bottom-width: 4px; border-left-width: 6px; }
                .rkcb-youtube-placeholder p { font-size: 10px; line-height: 1.1; }
                .rkcb-youtube-placeholder .rkcb-youtube-allow { padding: 5px 6px; font-size: 10px; line-height: 1.1; }
            }
            .rkcb-banner button:focus-visible, .rkcb-category input:focus-visible, .rkcb-links a:focus-visible,
            .rkcb-settings-link:focus-visible, .rkcb-media-allow:focus-visible { outline: 3px solid var(--rkcb-accept-bg); outline-offset: 3px; }
            @media (max-width: 600px) {
                .rkcb-banner { width: calc(100vw - 24px); max-height: calc(100vh - 24px); max-height: calc(100dvh - 24px); padding: 24px 20px; border-radius: 18px; }
                .rkcb-categories { grid-template-columns: 1fr; gap: 9px; margin: 20px 0; }
                .rkcb-category { min-height: 54px; }
                .rkcb-actions { grid-template-columns: 1fr; }
                .rkcb-actions .rkcb-accept { grid-column: auto; }
                .rkcb-links { margin-top: 18px; }
            }
            @media (prefers-reduced-motion: reduce) {
                .rkcb-category, .rkcb-actions button, .rkcb-settings-link, .rkcb-media-allow { transition: none; }
            }
        </style>
        <?php
    }

    private function render_category_checkbox($key, $label, $checked, $disabled = false)
    {
        ?>
        <label class="rkcb-category">
            <input type="checkbox" data-rkcb-consent="<?php echo esc_attr($key); ?>" <?php checked($checked); ?> <?php disabled($disabled); ?>>
            <span><?php echo esc_html($label); ?></span>
        </label>
        <?php
    }

    public function print_frontend()
    {
        if (is_admin()) {
            return;
        }

        $settings = $this->settings();
        $consent = $this->current_consent();
        ?>
        <?php if ($consent === null) : ?>
            <section class="rkcb-banner" id="rkcb-banner" role="dialog" aria-labelledby="rkcb-title" aria-describedby="rkcb-description">
                <h2 id="rkcb-title"><?php echo esc_html($settings['title']); ?></h2>
                <p id="rkcb-description"><?php echo esc_html($settings['description']); ?></p>
                <div class="rkcb-categories" aria-label="<?php echo esc_attr($settings['categories_aria_label']); ?>">
                    <?php
                    $this->render_category_checkbox('essential', $settings['category_labels']['essential'], true, true);
                    foreach ($this->categories as $key => $label) {
                        $display_label = $settings['category_labels'][$key] ?? $label;
                        $this->render_category_checkbox($key, $display_label, false);
                    }
                    ?>
                </div>
                <div class="rkcb-actions">
                    <button type="button" class="rkcb-accept" data-rkcb-action="all"><?php echo esc_html($settings['button_accept']); ?></button>
                    <button type="button" data-rkcb-action="selection"><?php echo esc_html($settings['button_selection']); ?></button>
                    <button type="button" data-rkcb-action="reject"><?php echo esc_html($settings['button_reject']); ?></button>
                </div>
                <nav class="rkcb-links" aria-label="<?php echo esc_attr($settings['links_aria_label']); ?>">
                    <?php if (!empty($settings['privacy_url'])) : ?><a href="<?php echo esc_url($settings['privacy_url']); ?>"><?php echo esc_html($settings['privacy_label']); ?></a><?php endif; ?>
                    <?php if (!empty($settings['imprint_url'])) : ?><a href="<?php echo esc_url($settings['imprint_url']); ?>"><?php echo esc_html($settings['imprint_label']); ?></a><?php endif; ?>
                </nav>
            </section>
        <?php else : ?>
            <section class="rkcb-banner" id="rkcb-banner" role="dialog" aria-labelledby="rkcb-title" aria-describedby="rkcb-description" hidden>
                <h2 id="rkcb-title"><?php echo esc_html($settings['title']); ?></h2>
                <p id="rkcb-description"><?php echo esc_html($settings['description']); ?></p>
                <div class="rkcb-categories" aria-label="<?php echo esc_attr($settings['categories_aria_label']); ?>">
                    <?php
                    $this->render_category_checkbox('essential', $settings['category_labels']['essential'], true, true);
                    foreach ($this->categories as $key => $label) {
                        $display_label = $settings['category_labels'][$key] ?? $label;
                        $this->render_category_checkbox($key, $display_label, !empty($consent[$key]));
                    }
                    ?>
                </div>
                <div class="rkcb-actions">
                    <button type="button" class="rkcb-accept" data-rkcb-action="all"><?php echo esc_html($settings['button_accept']); ?></button>
                    <button type="button" data-rkcb-action="selection"><?php echo esc_html($settings['button_selection']); ?></button>
                    <button type="button" data-rkcb-action="reject"><?php echo esc_html($settings['button_reject']); ?></button>
                </div>
                <nav class="rkcb-links" aria-label="<?php echo esc_attr($settings['links_aria_label']); ?>">
                    <?php if (!empty($settings['privacy_url'])) : ?><a href="<?php echo esc_url($settings['privacy_url']); ?>"><?php echo esc_html($settings['privacy_label']); ?></a><?php endif; ?>
                    <?php if (!empty($settings['imprint_url'])) : ?><a href="<?php echo esc_url($settings['imprint_url']); ?>"><?php echo esc_html($settings['imprint_label']); ?></a><?php endif; ?>
                </nav>
            </section>
        <?php endif; ?>

        <script>
        (function () {
            "use strict";
            var config = {
                cookieName: <?php echo wp_json_encode(self::COOKIE_NAME); ?>,
                categories: ["statistics", "marketing", "external_media"]
            };

            function readConsent() {
                var prefix = config.cookieName + "=";
                var parts = document.cookie.split(";");
                for (var i = 0; i < parts.length; i++) {
                    var item = parts[i].trim();
                    if (item.indexOf(prefix) === 0) {
                        try { return JSON.parse(decodeURIComponent(item.substring(prefix.length))); }
                        catch (error) { return null; }
                    }
                }
                return null;
            }

            function saveConsent(consent) {
                consent.essential = true;
                consent.decided = true;
                var value = encodeURIComponent(JSON.stringify(consent));
                var secure = location.protocol === "https:" ? "; Secure" : "";
                document.cookie = config.cookieName + "=" + value + "; Path=/; Max-Age=15552000; SameSite=Lax" + secure;
                location.reload();
            }

            function selectedConsent() {
                var consent = { essential: true };
                config.categories.forEach(function (category) {
                    var input = document.querySelector('[data-rkcb-consent="' + category + '"]');
                    consent[category] = !!(input && input.checked);
                });
                return consent;
            }

            var banner = document.getElementById("rkcb-banner");

            function applySavedChoices() {
                var saved = readConsent();
                if (!saved) return;
                config.categories.forEach(function (category) {
                    var input = document.querySelector('[data-rkcb-consent="' + category + '"]');
                    if (input && !input.disabled) input.checked = !!saved[category];
                });
            }

            // Falls eine gecachte Seite das Banner ausliefert, obwohl bereits entschieden wurde, bleibt es verborgen.
            if (banner && readConsent() && readConsent().decided) {
                banner.hidden = true;
            }

            document.addEventListener("click", function (event) {
                var trigger = event.target.closest(".rkcb-settings-link, [data-rkcb-open]");
                if (!trigger || !banner) return;
                event.preventDefault();
                applySavedChoices();
                banner.hidden = false;
                var firstAction = banner.querySelector('[data-rkcb-action="all"]');
                if (firstAction) firstAction.focus();
            });
            if (banner) {
                banner.addEventListener("click", function (event) {
                    var action = event.target.getAttribute("data-rkcb-action");
                    if (!action) return;
                    if (action === "all") {
                        saveConsent({ statistics: true, marketing: true, external_media: true });
                    } else if (action === "reject") {
                        saveConsent({ statistics: false, marketing: false, external_media: false });
                    } else {
                        saveConsent(selectedConsent());
                    }
                });
            }
            document.addEventListener("click", function (event) {
                var button = event.target.closest(".rkcb-media-allow");
                if (!button) return;
                var category = button.getAttribute("data-rkcb-category");
                var consent = readConsent() || {
                    essential: true, statistics: false, marketing: false, external_media: false
                };
                if (config.categories.indexOf(category) !== -1) {
                    consent[category] = true;
                    saveConsent(consent);
                }
            });

        }());
        </script>
        <?php
    }
}
