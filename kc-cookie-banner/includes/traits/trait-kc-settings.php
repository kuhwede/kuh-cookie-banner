<?php
if (!defined('ABSPATH')) {
    exit;
}

trait KC_Settings_Trait
{
    public function settings_link_shortcode($atts = array(), $content = null, $tag = '')
    {
        $settings = $this->settings();
        $label = is_string($content) && trim($content) !== '' ? $content : $settings['settings_link_label'];
        return '<a href="#" class="rkcb-settings-link" data-rkcb-open="1">' . esc_html($label) . '</a>';
    }

    private function defaults()
    {
        return array(
            'title'            => 'Datenschutzeinstellungen',
            'description'      => 'Wir nutzen Cookies auf unserer Website. Einige von ihnen sind essenziell, während andere uns helfen, diese Website und Ihre Erfahrung zu verbessern.',
            'button_accept'    => 'Alle akzeptieren',
            'button_selection' => 'Auswahl akzeptieren',
            'button_reject'    => 'Ablehnen',
            'category_labels'  => array(
                'essential'     => 'Essenziell',
                'statistics'    => 'Statistiken',
                'marketing'     => 'Marketing',
                'external_media' => 'Externe Medien',
            ),
            'privacy_label'       => 'Datenschutzerklärung',
            'imprint_label'       => 'Impressum',
            'settings_link_label' => 'Cookie-Einstellungen',
            'categories_aria_label' => 'Cookie-Kategorien',
            'links_aria_label'      => 'Weitere Informationen',
            'media_blocked_text' => 'Dieses externe Medium ist bis zu Ihrer Einwilligung blockiert.',
            'media_allow_text'   => 'Einwilligen und Medium laden',
            'youtube_label'      => 'YouTube-Video',
            'youtube_blocked_text' => 'Dieses YouTube-Video ist blockiert. Wenn Sie es laden, stimmen Sie der Kategorie „Externe Medien“ zu. Weitere externe Medien werden dann ebenfalls geladen.',
            'youtube_allow_text' => 'Externe Medien erlauben und Video laden',
            'privacy_url'      => '',
            'imprint_url'      => '',
            'width'            => 960,
            'gtm_id'           => '',
            'hotjar_id'        => '',
            'facebook_pixel_id' => '',
            'colors'           => array(
                'background'     => '#ffffff',
                'text'           => '#111111',
                'accept_bg'      => '#333333',
                'accept_text'    => '#ffffff',
                'secondary_bg'          => '#f7f7f7',
                'secondary_text'        => '#a6a6a6',
                'youtube_button_bg'     => '#333333',
                'youtube_button_text'   => '#ffffff',
                'youtube_button_hover_bg'   => '#313131',
                'youtube_button_hover_text' => '#ffffff',
                'link'                  => '#888888',
                'border'         => '#dddddd',
            ),
            'rules'            => array(
                'statistics'     => "google-analytics.com\nanalytics.google.com\ngoogletagmanager.com/gtag/js\nmatomo",
                'marketing'      => "connect.facebook.net\nfacebook.com/tr\ndoubleclick.net\nsnap.licdn.com\ntiktok.com\nstatic.hotjar.com\nscript.hotjar.com",
                'external_media' => "youtube.com/embed\nyoutube-nocookie.com/embed\nplayer.vimeo.com/video\ngoogle.com/maps\nmaps.google.com",
            ),
        );
    }

    private function settings()
    {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        $defaults = $this->defaults();
        $settings = array_replace_recursive($defaults, $saved);
        if (trim((string) $settings['title']) === '') {
            $settings['title'] = $defaults['title'];
        }
        return $settings;
    }

    public function admin_menu()
    {
        add_options_page(
            'RK Cookie Banner',
            'Cookie Banner',
            'manage_options',
            'rk-cookie-banner',
            array($this, 'render_admin_page')
        );
    }

    public function register_settings()
    {
        register_setting(
            'rkcb_settings_group',
            self::OPTION,
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default'           => $this->defaults(),
            )
        );
    }

    public function sanitize_settings($input)
    {
        $defaults = $this->defaults();
        $input = is_array($input) ? $input : array();
        $clean = $defaults;

        foreach (array('title', 'button_accept', 'button_selection', 'button_reject', 'privacy_label', 'imprint_label', 'settings_link_label', 'categories_aria_label', 'links_aria_label', 'media_allow_text', 'youtube_label', 'youtube_allow_text') as $key) {
            if (isset($input[$key])) {
                $clean[$key] = sanitize_text_field($input[$key]);
            }
        }

        if (isset($input['description'])) {
            $clean['description'] = sanitize_textarea_field($input['description']);
        }
        if (isset($input['media_blocked_text'])) {
            $clean['media_blocked_text'] = sanitize_textarea_field($input['media_blocked_text']);
        }
        if (isset($input['youtube_blocked_text'])) {
            $clean['youtube_blocked_text'] = sanitize_textarea_field($input['youtube_blocked_text']);
        }
        if (isset($input['category_labels']) && is_array($input['category_labels'])) {
            foreach ($defaults['category_labels'] as $key => $default_label) {
                if (isset($input['category_labels'][$key])) {
                    $clean['category_labels'][$key] = sanitize_text_field($input['category_labels'][$key]);
                }
            }
        }

        if (isset($input['privacy_url'])) {
            $clean['privacy_url'] = esc_url_raw($input['privacy_url']);
        }
        if (isset($input['imprint_url'])) {
            $clean['imprint_url'] = esc_url_raw($input['imprint_url']);
        }
        if (isset($input['width'])) {
            $clean['width'] = min(1200, max(320, absint($input['width'])));
        }

        if (isset($input['gtm_id'])) {
            $id = strtoupper(preg_replace('/[^A-Z0-9-]/', '', strtoupper(sanitize_text_field($input['gtm_id']))));
            $clean['gtm_id'] = preg_match('/^GTM-[A-Z0-9]+$/', $id) ? $id : '';
        }
        if (isset($input['hotjar_id'])) {
            $id = preg_replace('/[^0-9]/', '', sanitize_text_field($input['hotjar_id']));
            $clean['hotjar_id'] = $id;
        }
        if (isset($input['facebook_pixel_id'])) {
            $id = preg_replace('/[^0-9]/', '', sanitize_text_field($input['facebook_pixel_id']));
            $clean['facebook_pixel_id'] = $id;
        }

        $color_keys = array('background', 'text', 'accept_bg', 'accept_text', 'secondary_bg', 'secondary_text', 'youtube_button_bg', 'youtube_button_text', 'youtube_button_hover_bg', 'youtube_button_hover_text', 'link', 'border');
        if (isset($input['colors']) && is_array($input['colors'])) {
            foreach ($color_keys as $key) {
                if (isset($input['colors'][$key])) {
                    $color = sanitize_hex_color($input['colors'][$key]);
                    if ($color) {
                        $clean['colors'][$key] = $color;
                    }
                }
            }
        }

        foreach ($this->categories as $key => $label) {
            if (isset($input['rules'][$key])) {
                $clean['rules'][$key] = sanitize_textarea_field($input['rules'][$key]);
            }
        }

        return $clean;
    }

    public function render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->settings();
        ?>
        <div class="wrap">
            <h1>RK Cookie Banner</h1>
            <p>Passen Sie hier die Bannertexte an und konfigurieren Sie die externen Dienste.</p>
            <div class="notice notice-warning inline">
                <p><strong>GTM:</strong> Der Container wird nach Zustimmung zu Marketing freigegeben. Das Plugin setzt <code>analytics_storage</code> ohne Statistik-Zustimmung auf <code>denied</code> und blockiert direkte GA-Loader bis zur Statistik-Einwilligung. Analytics-Tags im GTM müssen zusätzlich eine Statistik-Einwilligungsprüfung erhalten. Consent Mode im Advanced-Modus kann trotz <code>denied</code> cookielose Anfragen senden; wenn vor Statistik-Zustimmung gar keine Analytics-Anfragen erfolgen sollen, müssen diese Tags im GTM strikt bis zur Statistik-Zustimmung zurückgehalten werden. <strong>Hotjar und Facebook Pixel:</strong> Beide Wrapper müssen die Kategorie <code>marketing</code> verwenden. Das Plugin stellt auf dieser Subdomain eine kompatible <code>NrCookieHandler.queue()</code>-Funktion bereit. Die IDs sind optional; gängige GTM-, Hotjar- und Facebook-Snippets werden automatisch erkannt. Tragen Sie eine ID nur ein, wenn ein individuelles Snippet nicht erkannt wird.</p>
            </div>
            <p>Die Filterung erfolgt im HTML der WordPress-Seite. Leeren Sie nach Änderungen den Seiten-, Plugin- und CDN-Cache. Vollständige Seiten-Caches müssen die Einwilligung berücksichtigen oder für diese Seiten deaktiviert werden.</p>

            <form method="post" action="options.php">
                <?php settings_fields('rkcb_settings_group'); ?>

                <h2>Bannertexte und Links</h2>
                <div class="notice notice-info inline">
                    <p><strong>Cookie-Einstellungen-Link einbauen:</strong></p>
                    <p><strong>Elementor-Shortcode-Widget:</strong> Fügen Sie dieses Snippet ein: <code>[rkcb_settings_link]Cookie-Einstellungen[/rkcb_settings_link]</code>. Den sichtbaren Linktext zwischen den beiden Shortcode-Tags können Sie ändern. Ohne eigenen Text verwendet der Shortcode den oben eingestellten Footer-Linktext.</p>
                    <p><strong>Normaler Elementor-Link oder WordPress-Menü:</strong> Verwenden Sie als URL <code>#cookie-settings</code> und tragen Sie als CSS-Klasse <code>rkcb-settings-link</code> ein — ohne Punkt davor. Im WordPress-Menü muss das Feld „CSS-Klassen“ gegebenenfalls über „Ansicht anpassen“ aktiviert werden.</p>
                    <p><strong>HTML-Alternative:</strong> <code>&lt;a href=&quot;#cookie-settings&quot; class=&quot;rkcb-settings-link&quot;&gt;Cookie-Einstellungen&lt;/a&gt;</code></p>
                </div>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="rkcb-title">Überschrift</label></th>
                        <td><input class="regular-text" id="rkcb-title" name="<?php echo esc_attr(self::OPTION); ?>[title]" value="<?php echo esc_attr($settings['title']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rkcb-description">Beschreibung</label></th>
                        <td><textarea class="large-text" rows="3" id="rkcb-description" name="<?php echo esc_attr(self::OPTION); ?>[description]"><?php echo esc_textarea($settings['description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Button-Beschriftungen</th>
                        <td>
                            <p><label for="rkcb-button-accept">Alle akzeptieren</label><br><input class="regular-text" id="rkcb-button-accept" name="<?php echo esc_attr(self::OPTION); ?>[button_accept]" value="<?php echo esc_attr($settings['button_accept']); ?>"></p>
                            <p><label for="rkcb-button-selection">Auswahl akzeptieren</label><br><input class="regular-text" id="rkcb-button-selection" name="<?php echo esc_attr(self::OPTION); ?>[button_selection]" value="<?php echo esc_attr($settings['button_selection']); ?>"></p>
                            <p><label for="rkcb-button-reject">Ablehnen</label><br><input class="regular-text" id="rkcb-button-reject" name="<?php echo esc_attr(self::OPTION); ?>[button_reject]" value="<?php echo esc_attr($settings['button_reject']); ?>"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Kategorietexte</th>
                        <td>
                            <p><label for="rkcb-label-essential">Essenziell</label><br><input class="regular-text" id="rkcb-label-essential" name="<?php echo esc_attr(self::OPTION); ?>[category_labels][essential]" value="<?php echo esc_attr($settings['category_labels']['essential']); ?>"></p>
                            <p><label for="rkcb-label-statistics">Statistiken</label><br><input class="regular-text" id="rkcb-label-statistics" name="<?php echo esc_attr(self::OPTION); ?>[category_labels][statistics]" value="<?php echo esc_attr($settings['category_labels']['statistics']); ?>"></p>
                            <p><label for="rkcb-label-marketing">Marketing</label><br><input class="regular-text" id="rkcb-label-marketing" name="<?php echo esc_attr(self::OPTION); ?>[category_labels][marketing]" value="<?php echo esc_attr($settings['category_labels']['marketing']); ?>"></p>
                            <p><label for="rkcb-label-external-media">Externe Medien</label><br><input class="regular-text" id="rkcb-label-external-media" name="<?php echo esc_attr(self::OPTION); ?>[category_labels][external_media]" value="<?php echo esc_attr($settings['category_labels']['external_media']); ?>"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Texte der Links</th>
                        <td>
                            <p><label for="rkcb-privacy-label">Datenschutzerklärung</label><br><input class="regular-text" id="rkcb-privacy-label" name="<?php echo esc_attr(self::OPTION); ?>[privacy_label]" value="<?php echo esc_attr($settings['privacy_label']); ?>"></p>
                            <p><label for="rkcb-imprint-label">Impressum</label><br><input class="regular-text" id="rkcb-imprint-label" name="<?php echo esc_attr(self::OPTION); ?>[imprint_label]" value="<?php echo esc_attr($settings['imprint_label']); ?>"></p>
                            <p><label for="rkcb-settings-link-label">Footer-Link zum Öffnen der Einstellungen</label><br><input class="regular-text" id="rkcb-settings-link-label" name="<?php echo esc_attr(self::OPTION); ?>[settings_link_label]" value="<?php echo esc_attr($settings['settings_link_label']); ?>"><span class="description">Standardtext für den Footer-Shortcode, wenn dort kein eigener Text eingetragen ist. Einen Menü-Linktext ändern Sie direkt im WordPress-Menü.</span></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Externe Medien</th>
                        <td>
                            <p><label for="rkcb-media-blocked-text">Hinweistext</label><br><textarea class="large-text" rows="2" id="rkcb-media-blocked-text" name="<?php echo esc_attr(self::OPTION); ?>[media_blocked_text]"><?php echo esc_textarea($settings['media_blocked_text']); ?></textarea></p>
                            <p><label for="rkcb-media-allow-text">Button „Einwilligen und Medium laden“</label><br><input class="regular-text" id="rkcb-media-allow-text" name="<?php echo esc_attr(self::OPTION); ?>[media_allow_text]" value="<?php echo esc_attr($settings['media_allow_text']); ?>"></p>
                            <hr>
                            <strong>YouTube-Platzhalter</strong>
                            <p><label for="rkcb-youtube-label">Bezeichnung</label><br><input class="regular-text" id="rkcb-youtube-label" name="<?php echo esc_attr(self::OPTION); ?>[youtube_label]" value="<?php echo esc_attr($settings['youtube_label']); ?>"></p>
                            <p><label for="rkcb-youtube-blocked-text">Hinweistext</label><br><textarea class="large-text" rows="3" id="rkcb-youtube-blocked-text" name="<?php echo esc_attr(self::OPTION); ?>[youtube_blocked_text]"><?php echo esc_textarea($settings['youtube_blocked_text']); ?></textarea></p>
                            <p><label for="rkcb-youtube-allow-text">YouTube-Button: Beschriftung</label><br><input class="regular-text" id="rkcb-youtube-allow-text" name="<?php echo esc_attr(self::OPTION); ?>[youtube_allow_text]" value="<?php echo esc_attr($settings['youtube_allow_text']); ?>"></p>
                            <p class="description">Wird im Seitenbereich des blockierten YouTube-Videos angezeigt. Es wird kein YouTube-Vorschaubild vor der Zustimmung geladen.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Zusatztexte für Screenreader</th>
                        <td>
                            <p><label for="rkcb-categories-aria">Gruppenname der Kategorien</label><br><input class="regular-text" id="rkcb-categories-aria" name="<?php echo esc_attr(self::OPTION); ?>[categories_aria_label]" value="<?php echo esc_attr($settings['categories_aria_label']); ?>"></p>
                            <p><label for="rkcb-links-aria">Gruppenname der Links</label><br><input class="regular-text" id="rkcb-links-aria" name="<?php echo esc_attr(self::OPTION); ?>[links_aria_label]" value="<?php echo esc_attr($settings['links_aria_label']); ?>"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rkcb-privacy">URL Datenschutzerklärung</label></th>
                        <td><input class="regular-text" type="url" id="rkcb-privacy" name="<?php echo esc_attr(self::OPTION); ?>[privacy_url]" value="<?php echo esc_attr($settings['privacy_url']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rkcb-imprint">Impressum</label></th>
                        <td><input class="regular-text" type="url" id="rkcb-imprint" name="<?php echo esc_attr(self::OPTION); ?>[imprint_url]" value="<?php echo esc_attr($settings['imprint_url']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rkcb-width">Maximale Bannerbreite in Pixeln</label></th>
                        <td><input type="number" min="320" max="1200" id="rkcb-width" name="<?php echo esc_attr(self::OPTION); ?>[width]" value="<?php echo esc_attr($settings['width']); ?>"></td>
                    </tr>
                </table>

                <h2>Einwilligungsgesteuerte Code-Snippets</h2>
                <p>Die vorhandenen Code-Snippets können aktiviert bleiben. Das Plugin erkennt gängige GTM-, Hotjar- und Facebook-Snippets anhand typischer Script-Adressen und Code-Merkmale und blockiert sie vor der passenden Einwilligung. Die IDs sind optional; individuelle Snippets können bei Bedarf über die ID-Felder zugeordnet werden.</p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="rkcb-gtm-id">Google Tag Manager Container-ID (optional)</label></th>
                        <td><input class="regular-text" id="rkcb-gtm-id" name="<?php echo esc_attr(self::OPTION); ?>[gtm_id]" value="<?php echo esc_attr($settings['gtm_id']); ?>"><p class="description">Optional. Übliche GTM-Snippets erkennt das Plugin auch ohne eingetragene ID.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rkcb-hotjar-id">Hotjar Site-ID (optional)</label></th>
                        <td><input class="regular-text" id="rkcb-hotjar-id" name="<?php echo esc_attr(self::OPTION); ?>[hotjar_id]" value="<?php echo esc_attr($settings['hotjar_id']); ?>"><p class="description">Optional. Übliche Hotjar-Snippets werden auch ohne eingetragene Site-ID erkannt.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rkcb-facebook-id">Facebook Pixel-ID (optional)</label></th>
                        <td><input class="regular-text" id="rkcb-facebook-id" name="<?php echo esc_attr(self::OPTION); ?>[facebook_pixel_id]" value="<?php echo esc_attr($settings['facebook_pixel_id']); ?>"><p class="description">Optional. Übliche Facebook-Pixel-Snippets werden auch ohne eingetragene Pixel-ID erkannt.</p></td>
                    </tr>
                </table>

                <h2>Farben</h2>
                <table class="form-table" role="presentation">
                    <?php
                    $color_labels = array(
                        'background'     => 'Hintergrund',
                        'text'           => 'Text',
                        'accept_bg'      => '„Alle akzeptieren“: Hintergrund',
                        'accept_text'    => '„Alle akzeptieren“: Text',
                        'secondary_bg'             => 'Weitere Buttons: Hintergrund',
                        'secondary_text'           => 'Weitere Buttons: Text',
                        'youtube_button_bg'        => 'YouTube-Button: Hintergrund',
                        'youtube_button_text'      => 'YouTube-Button: Textfarbe',
                        'youtube_button_hover_bg'  => 'YouTube-Button bei Hover: Hintergrund',
                        'youtube_button_hover_text'=> 'YouTube-Button bei Hover: Textfarbe',
                        'link'                     => 'Links',
                        'border'         => 'Rahmen',
                    );
                    foreach ($color_labels as $key => $label) :
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($label); ?></th>
                            <td>
                                <input type="text" class="small-text" maxlength="7" pattern="#[0-9a-fA-F]{6}" style="width:100px;font-family:monospace;" aria-label="<?php echo esc_attr($label); ?> Hex-Wert" name="<?php echo esc_attr(self::OPTION); ?>[colors][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings['colors'][$key]); ?>" oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) { this.nextElementSibling.value = this.value; }">
                                <input type="color" class="rkcb-swatch" style="vertical-align:middle;" aria-label="<?php echo esc_attr($label); ?> Farbfläche" value="<?php echo esc_attr($settings['colors'][$key]); ?>" oninput="this.previousElementSibling.value = this.value;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <h2>Weitere Blockierregeln</h2>
                <p>Tragen Sie pro Zeile einen URL-Teil ein. Passende externe Skripte und iFrames werden bis zur Einwilligung blockiert.</p>
                <table class="form-table" role="presentation">
                    <?php foreach ($this->categories as $key => $label) : ?>
                        <tr>
                            <th scope="row"><label for="rkcb-rule-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                            <td><textarea class="large-text code" rows="5" id="rkcb-rule-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr(self::OPTION); ?>[rules][<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($settings['rules'][$key]); ?></textarea></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php submit_button('Einstellungen speichern'); ?>
            </form>

        </div>
        <?php
    }
}
