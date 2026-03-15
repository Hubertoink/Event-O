<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_register_settings(): void
{
    event_o_maybe_migrate_legacy_options();

    register_setting('event_o_settings', EVENT_O_OPTION_PRIMARY, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#4f6b3a',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_ACCENT, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#2d3a22',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_TEXT, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#141414',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_MUTED, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#6a6a6a',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_ENABLE_SINGLE, [
        'type' => 'boolean',
        'sanitize_callback' => static function ($value) {
            return (bool) $value;
        },
        'default' => true,
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_SHARE_OPTIONS, [
        'type' => 'array',
        'sanitize_callback' => static function ($value) {
            if (!is_array($value)) {
                return ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy'];
            }
            $allowed = ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy'];
            return array_values(array_intersect($value, $allowed));
        },
        'default' => ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy'],
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_DARK_MODE, [
        'type' => 'string',
        'sanitize_callback' => static function ($value) {
            $allowed = ['auto', 'light', 'dark'];
            return in_array($value, $allowed, true) ? $value : 'auto';
        },
        'default' => 'auto',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_DARK_SELECTOR, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'html[data-neve-theme="dark"]',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_HIGH_CONTRAST, [
        'type' => 'boolean',
        'sanitize_callback' => static function ($value) {
            return (bool) $value;
        },
        'default' => false,
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_SINGLE_ANIMATION, [
        'type' => 'string',
        'sanitize_callback' => static function ($value) {
            $allowed = ['none', 'fade-up', 'fade-in', 'slide-left', 'scale-up'];
            return in_array($value, $allowed, true) ? $value : 'none';
        },
        'default' => 'none',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_RELATED_CATEGORY_ONLY, [
        'type' => 'boolean',
        'sanitize_callback' => static fn($v) => (bool) $v,
        'default' => false,
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_HERO_PARALLAX, [
        'type' => 'boolean',
        'sanitize_callback' => static fn($v) => (bool) $v,
        'default' => false,
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_SINGLE_CATEGORY_COLOR, [
        'type' => 'boolean',
        'sanitize_callback' => static fn($v) => (bool) $v,
        'default' => true,
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_SINGLE_TITLE_LAYOUT, [
        'type' => 'string',
        'sanitize_callback' => static function ($value) {
            $allowed = ['both', 'hero', 'content'];
            return in_array($value, $allowed, true) ? $value : 'both';
        },
        'default' => 'both',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_SINGLE_SHOW_TAGS, [
        'type' => 'boolean',
        'sanitize_callback' => static fn($v) => (bool) $v,
        'default' => false,
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_PAST_GRACE_DAYS, [
        'type' => 'integer',
        'sanitize_callback' => static function ($v) {
            $v = (int) $v;
            return max(0, min(7, $v));
        },
        'default' => 3,
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_LIGHT_SELECTOR, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'html[data-neve-theme="light"]',
    ]);

    register_setting('event_o_settings', EVENT_O_OPTION_WIZARD_MODE, [
        'type' => 'boolean',
        'sanitize_callback' => static function ($value) {
            return (bool) $value;
        },
        'default' => false,
    ]);

    add_settings_section(
        'event_o_settings_design',
        __('Design', 'event-o'),
        static function () {
            echo '<p>' . esc_html__('Clean, square aesthetic with configurable main colors.', 'event-o') . '</p>';
        },
        'event_o_settings'
    );

    event_o_add_color_field(EVENT_O_OPTION_PRIMARY, __('Primary color', 'event-o'));
    event_o_add_color_field(EVENT_O_OPTION_ACCENT, __('Accent color', 'event-o'));
    event_o_add_color_field(EVENT_O_OPTION_TEXT, __('Text color', 'event-o'));
    event_o_add_color_field(EVENT_O_OPTION_MUTED, __('Muted color', 'event-o'));

    add_settings_section(
        'event_o_settings_behavior',
        __('Behavior', 'event-o'),
        static function () {
            echo '<p>' . esc_html__('Optional features you can enable per site.', 'event-o') . '</p>';
        },
        'event_o_settings'
    );

    add_settings_field(
        EVENT_O_OPTION_ENABLE_SINGLE,
        __('Enable single event template', 'event-o'),
        static function () {
            $value = (bool) get_option(EVENT_O_OPTION_ENABLE_SINGLE, true);
            echo '<label><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_ENABLE_SINGLE) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Use Event-O single template for Event-O events.', 'event-o') . '</label>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_HIGH_CONTRAST,
        __('High Contrast Modus', 'event-o'),
        static function () {
            $value = (bool) get_option(EVENT_O_OPTION_HIGH_CONTRAST, false);
            echo '<label><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_HIGH_CONTRAST) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Alle gedämpften Farben durch volle Textfarbe (Schwarz/Weiß je nach Modus) ersetzen für maximalen Kontrast.', 'event-o') . '</label>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_RELATED_CATEGORY_ONLY,
        __('Weitere Events nach Kategorie', 'event-o'),
        static function () {
            $value = (bool) get_option(EVENT_O_OPTION_RELATED_CATEGORY_ONLY, false);
            echo '<label><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_RELATED_CATEGORY_ONLY) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('"Weitere Veranstaltungen" auf der Einzelseite nur aus der gleichen Kategorie anzeigen.', 'event-o') . '</label>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_HERO_PARALLAX,
        __('Parallax Hero-Bild', 'event-o'),
        static function () {
            $value = (bool) get_option(EVENT_O_OPTION_HERO_PARALLAX, false);
            echo '<label><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_HERO_PARALLAX) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Parallax-Scrolleffekt auf dem Hero-Bild der Event-Einzelseite aktivieren.', 'event-o') . '</label>';
            echo '<p class="description">' . esc_html__('Funktioniert am besten mit hochauflösenden Bildern. Wird bei "prefers-reduced-motion" automatisch deaktiviert.', 'event-o') . '</p>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_SINGLE_ANIMATION,
        __('Seitenanimation (Single Event)', 'event-o'),
        static function () {
            $value = (string) get_option(EVENT_O_OPTION_SINGLE_ANIMATION, 'none');
            $options = [
                'none' => __('Keine', 'event-o'),
                'fade-up' => __('Fade Up – Elemente gleiten von unten ein', 'event-o'),
                'fade-in' => __('Fade In – Sanftes Einblenden', 'event-o'),
                'slide-left' => __('Slide Left – Von rechts hereingleiten', 'event-o'),
                'scale-up' => __('Scale Up – Heranzoomen', 'event-o'),
            ];
            echo '<select name="' . esc_attr(EVENT_O_OPTION_SINGLE_ANIMATION) . '">';
            foreach ($options as $key => $label) {
                $selected = selected($value, $key, false);
                echo '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Einblende-Animation für Inhalte auf der Event-Einzelseite.', 'event-o') . '</p>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_SINGLE_CATEGORY_COLOR,
        __('Kategorie-Farbe auf Einzelseite', 'event-o'),
        static function () {
            $value = (bool) get_option(EVENT_O_OPTION_SINGLE_CATEGORY_COLOR, true);
            echo '<label><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_SINGLE_CATEGORY_COLOR) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Kategorien auf der Event-Einzelseite mit ihrer zugewiesenen Farbe anzeigen.', 'event-o') . '</label>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_SINGLE_TITLE_LAYOUT,
        __('Titel-Position auf Einzelseite', 'event-o'),
        static function () {
            $value = (string) get_option(EVENT_O_OPTION_SINGLE_TITLE_LAYOUT, 'both');
            $options = [
                'both' => __('Oben im Bild und unten im Inhalt', 'event-o'),
                'hero' => __('Nur oben im Bild', 'event-o'),
                'content' => __('Nur unten im Inhalt', 'event-o'),
            ];
            echo '<select name="' . esc_attr(EVENT_O_OPTION_SINGLE_TITLE_LAYOUT) . '">';
            foreach ($options as $key => $label) {
                echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Steuert, ob der Event-Titel auf der Einzelseite im Hero-Bild, im Inhaltsbereich oder an beiden Stellen angezeigt wird.', 'event-o') . '</p>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_SINGLE_SHOW_TAGS,
        __('Schlagwörter auf Einzelseite', 'event-o'),
        static function () {
            $value = (bool) get_option(EVENT_O_OPTION_SINGLE_SHOW_TAGS, false);
            echo '<label><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_SINGLE_SHOW_TAGS) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Schlagwörter auf der Event-Einzelseite anzeigen.', 'event-o') . '</label>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_PAST_GRACE_DAYS,
        __('Vergangene Events anzeigen (Tage)', 'event-o'),
        static function () {
            $value = (int) get_option(EVENT_O_OPTION_PAST_GRACE_DAYS, 3);
            echo '<select name="' . esc_attr(EVENT_O_OPTION_PAST_GRACE_DAYS) . '">';
            for ($i = 0; $i <= 7; $i++) {
                $selected = selected($value, $i, false);
                $label = $i === 0 ? __('0 (nur zukünftige Events)', 'event-o') : sprintf(_n('%d Tag', '%d Tage', $i, 'event-o'), $i);
                echo '<option value="' . esc_attr((string) $i) . '"' . $selected . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Wie viele Tage nach dem Eventbeginn soll ein Event noch in den Blöcken angezeigt werden. Events werden immer mindestens bis Mitternacht des Starttages angezeigt. Mehrtägige oder mehrterminige Events bleiben sichtbar, bis der letzte noch laufende Termin vorbei ist.', 'event-o') . '</p>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_WIZARD_MODE,
        __('Geführte Event-Eingabe', 'event-o'),
        static function () {
            $value = (bool) get_option(EVENT_O_OPTION_WIZARD_MODE, false);
            echo '<label><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_WIZARD_MODE) . '" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Geführten Wizard beim Erstellen/Bearbeiten von Events anzeigen.', 'event-o') . '</label>';
            echo '<p class="description">' . esc_html__('Aktiviert eine schrittweise Eingabemaske (Wizard) für Redakteure. Der klassische Editor bleibt über einen Button jederzeit erreichbar.', 'event-o') . '</p>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    add_settings_field(
        EVENT_O_OPTION_SHARE_OPTIONS,
        __('Share buttons', 'event-o'),
        static function () {
            $options = get_option(EVENT_O_OPTION_SHARE_OPTIONS, ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy']);
            if (!is_array($options)) {
                $options = ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy'];
            }

            $all_options = [
                'facebook' => 'Facebook',
                'twitter' => 'X (Twitter)',
                'whatsapp' => 'WhatsApp',
                'linkedin' => 'LinkedIn',
                'email' => 'E-Mail',
                'instagram' => 'Instagram',
                'calendar' => 'Zum Kalender hinzufügen',
                'copy' => 'URL kopieren',
            ];

            echo '<fieldset>';
            foreach ($all_options as $key => $label) {
                $checked = in_array($key, $options, true) ? 'checked' : '';
                echo '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="' . esc_attr(EVENT_O_OPTION_SHARE_OPTIONS) . '[]" value="' . esc_attr($key) . '" ' . $checked . ' /> ' . esc_html($label) . '</label>';
            }
            echo '</fieldset>';
            echo '<p class="description">' . esc_html__('Wähle aus, welche Teilen-Buttons angezeigt werden sollen.', 'event-o') . '</p>';
        },
        'event_o_settings',
        'event_o_settings_behavior'
    );

    // Theme Compatibility Section
    add_settings_section(
        'event_o_settings_theme',
        __('Theme-Kompatibilität', 'event-o'),
        static function () {
            echo '<p>' . esc_html__('Konfiguriere wie das Plugin mit dem Dark/Light Mode deines Themes zusammenarbeitet.', 'event-o') . '</p>';
        },
        'event_o_settings'
    );

    add_settings_field(
        EVENT_O_OPTION_DARK_MODE,
        __('Farbmodus', 'event-o'),
        static function () {
            $value = (string) get_option(EVENT_O_OPTION_DARK_MODE, 'auto');
            $options = [
                'auto' => __('Automatisch (Theme-Selektor)', 'event-o'),
                'light' => __('Immer Light Mode', 'event-o'),
                'dark' => __('Immer Dark Mode', 'event-o'),
            ];
            echo '<select name="' . esc_attr(EVENT_O_OPTION_DARK_MODE) . '">';
            foreach ($options as $key => $label) {
                $selected = selected($value, $key, false);
                echo '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Bei "Automatisch" wird der Dark/Light Mode anhand der Theme-Selektoren erkannt.', 'event-o') . '</p>';
        },
        'event_o_settings',
        'event_o_settings_theme'
    );

    add_settings_field(
        EVENT_O_OPTION_DARK_SELECTOR,
        __('Dark Mode Selektor', 'event-o'),
        static function () {
            $value = (string) get_option(EVENT_O_OPTION_DARK_SELECTOR, 'html[data-neve-theme="dark"]');
            echo '<input type="text" name="' . esc_attr(EVENT_O_OPTION_DARK_SELECTOR) . '" value="' . esc_attr($value) . '" class="regular-text" />';
            echo '<p class="description">' . esc_html__('CSS-Selektor für Dark Mode. Beispiele:', 'event-o') . '<br>';
            echo '<code>html[data-neve-theme="dark"]</code> (Neve Theme)<br>';
            echo '<code>body.dark-mode</code> (Andere Themes)<br>';
            echo '<code>html.dark</code> (Tailwind-basierte Themes)</p>';
        },
        'event_o_settings',
        'event_o_settings_theme'
    );

    add_settings_field(
        EVENT_O_OPTION_LIGHT_SELECTOR,
        __('Light Mode Selektor', 'event-o'),
        static function () {
            $value = (string) get_option(EVENT_O_OPTION_LIGHT_SELECTOR, 'html[data-neve-theme="light"]');
            echo '<input type="text" name="' . esc_attr(EVENT_O_OPTION_LIGHT_SELECTOR) . '" value="' . esc_attr($value) . '" class="regular-text" />';
            echo '<p class="description">' . esc_html__('CSS-Selektor für Light Mode (wird genutzt um Dark bei auto auszuschließen). Beispiele:', 'event-o') . '<br>';
            echo '<code>html[data-neve-theme="light"]</code> (Neve Theme)<br>';
            echo '<code>body.light-mode</code> (Andere Themes)</p>';
        },
        'event_o_settings',
        'event_o_settings_theme'
    );
}

function event_o_add_color_field(string $optionName, string $label): void
{
    add_settings_field(
        $optionName,
        $label,
        static function () use ($optionName) {
            $value = (string) get_option($optionName, '');
            echo '<input type="text" class="event-o-color-picker" name="' . esc_attr($optionName) . '" value="' . esc_attr($value) . '" data-default-color="#4f6b3a" />';
        },
        'event_o_settings',
        'event_o_settings_design'
    );
}

function event_o_register_settings_page(): void
{
    add_submenu_page(
        'edit.php?post_type=event_o_event',
        __('Einstellungen', 'event-o'),
        __('Einstellungen', 'event-o'),
        'manage_options',
        'event-o-settings',
        'event_o_render_settings_page'
    );
}

function event_o_register_block_guide_page(): void
{
    add_submenu_page(
        'edit.php?post_type=event_o_event',
        __('Block Guide', 'event-o'),
        __('Block Guide', 'event-o'),
        'edit_posts',
        'event-o-block-guide',
        'event_o_render_block_guide_page'
    );
}

function event_o_enqueue_settings_assets(string $hookSuffix): void
{
    $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
    $postType = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';

    $allowedPages = ['event-o-settings', 'event-o-block-guide'];

    if (
        $hookSuffix !== 'event-o_page_event-o-settings'
        && $hookSuffix !== 'event-o_page_event-o-block-guide'
        && (!in_array($page, $allowedPages, true) || $postType !== 'event_o_event')
    ) {
        return;
    }

    wp_enqueue_style('wp-color-picker');

    wp_enqueue_style(
        'event-o-admin-settings',
        EVENT_O_PLUGIN_URL . 'assets/admin-settings.css',
        ['wp-color-picker'],
        function_exists('event_o_asset_version') ? event_o_asset_version('assets/admin-settings.css') : EVENT_O_VERSION
    );

    wp_enqueue_script(
        'event-o-admin-settings',
        EVENT_O_PLUGIN_URL . 'assets/admin-settings.js',
        ['jquery', 'wp-color-picker'],
        function_exists('event_o_asset_version') ? event_o_asset_version('assets/admin-settings.js') : EVENT_O_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'event_o_enqueue_settings_assets');

function event_o_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $tokens = event_o_get_design_tokens();
    $sections = event_o_get_settings_sections();
    $summary = event_o_get_settings_summary();
    $wrapperStyle = sprintf(
        '--event-o-settings-primary:%1$s;--event-o-settings-accent:%2$s;--event-o-settings-text:%3$s;--event-o-settings-muted:%4$s;',
        esc_attr($tokens['primary']),
        esc_attr($tokens['accent']),
        esc_attr($tokens['text']),
        esc_attr($tokens['muted'])
    );

    echo '<div class="wrap event-o-settings-page" style="' . $wrapperStyle . '">';
    echo '<div class="event-o-settings-hero">';
    echo '<div class="event-o-settings-hero-copy">';
    echo '<p class="event-o-settings-eyebrow">' . esc_html__('Event-O Workspace', 'event-o') . '</p>';
    echo '<h1>' . esc_html__('Einstellungen', 'event-o') . '</h1>';
    echo '<p class="event-o-settings-subtitle">' . esc_html__('Konfiguriere Gestaltung, Single-Event-Erlebnis, Redaktionsworkflow und Theme-Verhalten an einem Ort.', 'event-o') . '</p>';
    echo '</div>';
    echo '<div class="event-o-settings-hero-status">';
    foreach ($summary['badges'] as $badge) {
        echo '<span class="event-o-settings-pill ' . esc_attr($badge['class']) . '">' . esc_html($badge['label']) . '</span>';
    }
    echo '</div>';
    echo '</div>';

    echo '<div class="event-o-settings-shell">';
    echo '<aside class="event-o-settings-sidebar">';
    echo '<section class="event-o-settings-panel event-o-settings-panel-nav">';
    echo '<h2>' . esc_html__('Bereiche', 'event-o') . '</h2>';
    echo '<nav class="event-o-settings-nav" aria-label="' . esc_attr__('Settings sections', 'event-o') . '">';
    foreach ($sections as $section) {
        echo '<a href="#' . esc_attr($section['id']) . '">';
        echo '<span class="event-o-settings-nav-title">' . esc_html($section['title']) . '</span>';
        echo '<span class="event-o-settings-nav-copy">' . esc_html($section['short']) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
    echo '</section>';

    echo '<section class="event-o-settings-panel event-o-settings-panel-summary">';
    echo '<h2>' . esc_html__('Aktuelle Konfiguration', 'event-o') . '</h2>';
    echo '<div class="event-o-settings-summary-grid">';
    foreach ($summary['metrics'] as $metric) {
        echo '<div class="event-o-settings-metric">';
        echo '<span class="event-o-settings-metric-label">' . esc_html($metric['label']) . '</span>';
        echo '<strong class="event-o-settings-metric-value">' . esc_html($metric['value']) . '</strong>';
        echo '<span class="event-o-settings-metric-help">' . esc_html($metric['help']) . '</span>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="event-o-settings-preview">';
    echo '<h3>' . esc_html__('Design-Vorschau', 'event-o') . '</h3>';
    echo '<div class="event-o-settings-swatches">';
    foreach ($summary['swatches'] as $swatch) {
        echo '<div class="event-o-settings-swatch">';
        echo '<span class="event-o-settings-swatch-color" style="background:' . esc_attr($swatch['value']) . '"></span>';
        echo '<span class="event-o-settings-swatch-label">' . esc_html($swatch['label']) . '</span>';
        echo '<code>' . esc_html(strtoupper($swatch['value'])) . '</code>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '</section>';

    echo '<section class="event-o-settings-panel event-o-settings-panel-help">';
    echo '<h2>' . esc_html__('Hinweise', 'event-o') . '</h2>';
    echo '<ul class="event-o-settings-help-list">';
    echo '<li>' . esc_html__('Farben und Theme-Selektoren wirken sich direkt auf Frontend-Blöcke und Single-Templates aus.', 'event-o') . '</li>';
    echo '<li>' . esc_html__('Workflow-Optionen betreffen die Event-Erstellung und Sichtbarkeit in Listen.', 'event-o') . '</li>';
    echo '<li>' . esc_html__('Die Einstellungen werden siteweit gespeichert und gelten sofort nach dem Speichern.', 'event-o') . '</li>';
    echo '</ul>';
    echo '</section>';
    echo '</aside>';

    echo '<form method="post" action="options.php" class="event-o-settings-form">';
    settings_fields('event_o_settings');

    foreach ($sections as $section) {
        echo '<section id="' . esc_attr($section['id']) . '" class="event-o-settings-section-card">';
        echo '<div class="event-o-settings-section-head">';
        echo '<div>';
        echo '<p class="event-o-settings-section-kicker">' . esc_html($section['kicker']) . '</p>';
        echo '<h2>' . esc_html($section['title']) . '</h2>';
        echo '</div>';
        echo '<p>' . esc_html($section['description']) . '</p>';
        echo '</div>';
        echo '<div class="event-o-settings-fields">';
        foreach ($section['fields'] as $field) {
            event_o_render_settings_field($field);
        }
        echo '</div>';
        echo '</section>';
    }

    echo '<div class="event-o-settings-submit">';
    submit_button(__('Einstellungen speichern', 'event-o'), 'primary', 'submit', false, ['class' => 'button button-primary button-large']);
    echo '<span class="event-o-settings-submit-note">' . esc_html__('Nach dem Speichern sind die Änderungen sofort aktiv.', 'event-o') . '</span>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}

function event_o_render_block_guide_page(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    $tokens = event_o_get_design_tokens();
    $guide = event_o_get_block_guide_content();
    $wrapperStyle = sprintf(
        '--event-o-settings-primary:%1$s;--event-o-settings-accent:%2$s;--event-o-settings-text:%3$s;--event-o-settings-muted:%4$s;',
        esc_attr($tokens['primary']),
        esc_attr($tokens['accent']),
        esc_attr($tokens['text']),
        esc_attr($tokens['muted'])
    );

    echo '<div class="wrap event-o-settings-page event-o-guide-page" style="' . $wrapperStyle . '">';
    echo '<div class="event-o-settings-hero event-o-guide-hero">';
    echo '<div class="event-o-settings-hero-copy">';
    echo '<p class="event-o-settings-eyebrow">' . esc_html__('Event-O Block Library', 'event-o') . '</p>';
    echo '<h1>' . esc_html__('Gutenberg-Block Guide', 'event-o') . '</h1>';
    echo '<p class="event-o-settings-subtitle">' . esc_html__('Eine kompakte Übersicht für Redaktion und Layout: welcher Block wofür gedacht ist, welche Einstellungen wirklich zählen und wie du mehrere Blöcke sinnvoll kombinierst.', 'event-o') . '</p>';
    echo '</div>';
    echo '<div class="event-o-guide-hero-aside">';
    foreach ($guide['hero_stats'] as $stat) {
        echo '<div class="event-o-guide-stat">';
        echo '<span class="event-o-guide-stat-label">' . esc_html($stat['label']) . '</span>';
        echo '<strong class="event-o-guide-stat-value">' . esc_html($stat['value']) . '</strong>';
        echo '<span class="event-o-guide-stat-help">' . esc_html($stat['help']) . '</span>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';

    echo '<div class="event-o-settings-shell event-o-guide-shell">';
    echo '<aside class="event-o-settings-sidebar event-o-guide-sidebar">';

    echo '<section class="event-o-settings-panel event-o-settings-panel-nav">';
    echo '<h2>' . esc_html__('Blöcke', 'event-o') . '</h2>';
    echo '<nav class="event-o-settings-nav" aria-label="' . esc_attr__('Block guide navigation', 'event-o') . '">';
    foreach ($guide['blocks'] as $block) {
        echo '<a href="#' . esc_attr($block['slug']) . '">';
        echo '<span class="event-o-settings-nav-title">' . esc_html($block['title']) . '</span>';
        echo '<span class="event-o-settings-nav-copy">' . esc_html($block['summary']) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
    echo '</section>';

    echo '<section class="event-o-settings-panel event-o-guide-panel">';
    echo '<h2>' . esc_html__('Schnellstart', 'event-o') . '</h2>';
    echo '<ol class="event-o-guide-ordered-list">';
    foreach ($guide['quick_start'] as $step) {
        echo '<li>' . esc_html($step) . '</li>';
    }
    echo '</ol>';
    echo '</section>';

    echo '<section class="event-o-settings-panel event-o-guide-panel">';
    echo '<h2>' . esc_html__('Redaktions-Tipps', 'event-o') . '</h2>';
    echo '<ul class="event-o-settings-help-list">';
    foreach ($guide['editorial_tips'] as $tip) {
        echo '<li>' . esc_html($tip) . '</li>';
    }
    echo '</ul>';
    echo '</section>';

    echo '<section class="event-o-settings-panel event-o-guide-panel event-o-guide-panel-links">';
    echo '<h2>' . esc_html__('Direkt weiter', 'event-o') . '</h2>';
    echo '<div class="event-o-guide-link-stack">';
    echo '<a class="event-o-guide-link" href="' . esc_url(admin_url('edit.php?post_type=event_o_event&page=event-o-settings')) . '">';
    echo '<span>' . esc_html__('Zu den Event-O Einstellungen', 'event-o') . '</span>';
    echo '<small>' . esc_html__('Farben, Workflow und Theme-Verhalten anpassen', 'event-o') . '</small>';
    echo '</a>';
    echo '<a class="event-o-guide-link" href="' . esc_url(admin_url('post-new.php?post_type=page')) . '">';
    echo '<span>' . esc_html__('Neue Seite mit Blöcken anlegen', 'event-o') . '</span>';
    echo '<small>' . esc_html__('Direkt im Gutenberg-Editor testen', 'event-o') . '</small>';
    echo '</a>';
    echo '</div>';
    echo '</section>';

    echo '</aside>';

    echo '<main class="event-o-guide-content">';

    echo '<section class="event-o-settings-section-card event-o-guide-overview">';
    echo '<div class="event-o-settings-section-head">';
    echo '<div>';
    echo '<p class="event-o-settings-section-kicker">' . esc_html__('Was Event-O liefert', 'event-o') . '</p>';
    echo '<h2>' . esc_html__('Welche Blocktypen es gibt', 'event-o') . '</h2>';
    echo '</div>';
    echo '<p>' . esc_html__('Event-O deckt Listen, visuelle Einstiege, Kalenderansichten und redaktionell kuratierte Highlights ab. Nutze die Übersicht unten, um pro Seitentyp schnell zum passenden Block zu greifen.', 'event-o') . '</p>';
    echo '</div>';
    echo '<div class="event-o-guide-pill-grid">';
    foreach ($guide['use_cases'] as $useCase) {
        echo '<div class="event-o-guide-use-case">';
        echo '<strong>' . esc_html($useCase['title']) . '</strong>';
        echo '<span>' . esc_html($useCase['copy']) . '</span>';
        echo '</div>';
    }
    echo '</div>';
    echo '</section>';

    echo '<section class="event-o-guide-grid">';
    foreach ($guide['blocks'] as $block) {
        echo '<article id="' . esc_attr($block['slug']) . '" class="event-o-settings-section-card event-o-guide-card">';
        echo '<div class="event-o-guide-card-head">';
        echo '<div class="event-o-guide-icon">' . esc_html($block['icon']) . '</div>';
        echo '<div>';
        echo '<p class="event-o-settings-section-kicker">' . esc_html($block['kicker']) . '</p>';
        echo '<h2>' . esc_html($block['title']) . '</h2>';
        echo '</div>';
        echo '</div>';

        echo '<p class="event-o-guide-card-summary">' . esc_html($block['summary']) . '</p>';

        echo '<div class="event-o-guide-badges">';
        foreach ($block['badges'] as $badge) {
            echo '<span class="event-o-settings-pill is-muted">' . esc_html($badge) . '</span>';
        }
        echo '</div>';

        echo '<div class="event-o-guide-card-grid">';
        echo '<div class="event-o-guide-card-section">';
        echo '<h3>' . esc_html__('Ideal für', 'event-o') . '</h3>';
        echo '<ul class="event-o-settings-help-list">';
        foreach ($block['ideal_for'] as $item) {
            echo '<li>' . esc_html($item) . '</li>';
        }
        echo '</ul>';
        echo '</div>';

        echo '<div class="event-o-guide-card-section">';
        echo '<h3>' . esc_html__('Wichtige Einstellungen', 'event-o') . '</h3>';
        echo '<ul class="event-o-settings-help-list">';
        foreach ($block['key_settings'] as $item) {
            echo '<li>' . esc_html($item) . '</li>';
        }
        echo '</ul>';
        echo '</div>';

        echo '<div class="event-o-guide-card-section">';
        echo '<h3>' . esc_html__('Kombinieren mit', 'event-o') . '</h3>';
        echo '<ul class="event-o-settings-help-list">';
        foreach ($block['combine_with'] as $item) {
            echo '<li>' . esc_html($item) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';

        echo '<div class="event-o-guide-card-footer">';
        echo '<span class="event-o-guide-inline-label">' . esc_html__('Im Editor suchen nach', 'event-o') . '</span>';
        echo '<code>' . esc_html($block['search_term']) . '</code>';
        echo '</div>';
        echo '</article>';
    }
    echo '</section>';

    echo '</main>';
    echo '</div>';
    echo '</div>';
}

function event_o_get_block_guide_content(): array
{
    return [
        'hero_stats' => [
            [
                'label' => __('Blöcke', 'event-o'),
                'value' => '6',
                'help' => __('für Listing, Highlights und Kalender', 'event-o'),
            ],
            [
                'label' => __('Ideal für', 'event-o'),
                'value' => __('Startseiten', 'event-o'),
                'help' => __('Landingpages, Programmseiten und Archiv-Ansichten', 'event-o'),
            ],
            [
                'label' => __('Fokus', 'event-o'),
                'value' => __('schnell wählen', 'event-o'),
                'help' => __('welcher Block für welchen redaktionellen Zweck passt', 'event-o'),
            ],
        ],
        'quick_start' => [
            __('Neue Seite oder Beitrag im Block-Editor öffnen.', 'event-o'),
            __('Nach "Event" oder direkt nach dem Blocknamen suchen.', 'event-o'),
            __('Zuerst den passenden Darstellungsblock wählen, danach Filter und Design einstellen.', 'event-o'),
            __('Mit Kategorien, Orten oder Veranstaltern gezielt auf Teilprogramme einschränken.', 'event-o'),
        ],
        'editorial_tips' => [
            __('Für starke Startseiten zuerst Hero oder Carousel einsetzen und darunter Grid oder Liste zur Vertiefung ergänzen.', 'event-o'),
            __('Wenn Besucher schnell vergleichen sollen, ist Grid oder Program meist besser als ein Carousel.', 'event-o'),
            __('Kalender eignet sich für Monatsübersichten, ersetzt aber keine ausführliche Programmseite mit Filtern.', 'event-o'),
            __('Filter nur aktivieren, wenn wirklich mehrere Kategorien, Orte oder Veranstalter auswählbar sein sollen.', 'event-o'),
        ],
        'use_cases' => [
            [
                'title' => __('Startseite', 'event-o'),
                'copy' => __('Hero oder Carousel für Aufmerksamkeit, darunter Grid oder Liste für Details.', 'event-o'),
            ],
            [
                'title' => __('Programmseite', 'event-o'),
                'copy' => __('Program oder Liste für viele Termine mit klarer Leseführung.', 'event-o'),
            ],
            [
                'title' => __('Monatsübersicht', 'event-o'),
                'copy' => __('Calendar für Orientierung, ergänzt durch Grid oder Liste für ausführliche Infos.', 'event-o'),
            ],
            [
                'title' => __('Themen-Landingpage', 'event-o'),
                'copy' => __('Mit Kategorien filtern und pro Seite eine kuratierte Auswahl zeigen.', 'event-o'),
            ],
        ],
        'blocks' => [
            [
                'slug' => 'event-o-guide-list',
                'icon' => '01',
                'kicker' => __('Klassisch und flexibel', 'event-o'),
                'title' => __('Event List', 'event-o'),
                'summary' => __('Die vielseitigste Übersicht für viele Termine mit klarer Lesbarkeit, optionalen Filtern und viel Meta-Information.', 'event-o'),
                'badges' => [__('viele Termine', 'event-o'), __('Filter möglich', 'event-o'), __('editorial sicher', 'event-o')],
                'ideal_for' => [
                    __('Programmseiten mit vielen Events oder Kategorien.', 'event-o'),
                    __('Archiv- oder Unterseiten, auf denen Informationen wichtiger sind als große Bilder.', 'event-o'),
                    __('Redaktionelle Seiten, die schnell gepflegt und zuverlässig lesbar bleiben sollen.', 'event-o'),
                ],
                'key_settings' => [
                    __('perPage bestimmt, wie dicht oder luftig die Seite wirkt.', 'event-o'),
                    __('groupByMonth ordnet lange Listen verständlicher nach Monaten.', 'event-o'),
                    __('showFilters und filterStyle helfen bei größeren Programmen.', 'event-o'),
                ],
                'combine_with' => [
                    __('Mit Hero über der Liste für einen starken Einstieg.', 'event-o'),
                    __('Mit Calendar daneben oder darunter für Monatsnavigation.', 'event-o'),
                ],
                'search_term' => 'Event List',
            ],
            [
                'slug' => 'event-o-guide-carousel',
                'icon' => '02',
                'kicker' => __('Dynamischer Einstieg', 'event-o'),
                'title' => __('Event Carousel', 'event-o'),
                'summary' => __('Ein kompakter Slider für Highlights, Teaser und Startseitenbereiche mit wenig Platz in der Höhe.', 'event-o'),
                'badges' => [__('teasertauglich', 'event-o'), __('visuell', 'event-o'), __('für Highlights', 'event-o')],
                'ideal_for' => [
                    __('Startseiten oder Magazineinstiege mit kuratierter Auswahl.', 'event-o'),
                    __('Abschnitte, die nur wenige ausgewählte Events betonen sollen.', 'event-o'),
                    __('Bereiche, in denen Bildwirkung wichtiger ist als Detailtiefe.', 'event-o'),
                ],
                'key_settings' => [
                    __('slidesToShow steuert, ob der Block eher teaserhaft oder galerieartig wirkt.', 'event-o'),
                    __('showImage sollte fast immer aktiv bleiben, damit der Slider genug visuelle Substanz hat.', 'event-o'),
                    __('Mit Kategorien oder Veranstaltern gezielt nur Highlights eines Formats anzeigen.', 'event-o'),
                ],
                'combine_with' => [
                    __('Mit List oder Grid darunter für alle weiteren Termine.', 'event-o'),
                    __('Mit Hero vermeiden, wenn beide direkt nacheinander zu dominant wirken.', 'event-o'),
                ],
                'search_term' => 'Event Carousel',
            ],
            [
                'slug' => 'event-o-guide-grid',
                'icon' => '03',
                'kicker' => __('Vergleich auf einen Blick', 'event-o'),
                'title' => __('Event Grid', 'event-o'),
                'summary' => __('Kachelansicht für moderne Übersichtsseiten, auf denen mehrere Termine parallel erfassbar sein sollen.', 'event-o'),
                'badges' => [__('kachelansicht', 'event-o'), __('modern', 'event-o'), __('vergleichbar', 'event-o')],
                'ideal_for' => [
                    __('Seiten mit mittlerer Event-Anzahl und starkem Bildanteil.', 'event-o'),
                    __('Landingpages für Genres, Reihen oder Veranstaltungsorte.', 'event-o'),
                    __('Layouts, in denen Besucher schnell mehrere Optionen scannen sollen.', 'event-o'),
                ],
                'key_settings' => [
                    __('columns sollte auf Desktop großzügig, aber nicht zu eng gewählt werden.', 'event-o'),
                    __('showCategory und showOrganizer helfen, Karten kontextreich zu halten.', 'event-o'),
                    __('sortOrder ist relevant, wenn auch vergangene Termine gezeigt werden.', 'event-o'),
                ],
                'combine_with' => [
                    __('Mit Filteroptionen für Themen- und Archivseiten.', 'event-o'),
                    __('Mit Calendar als zweite Perspektive auf dieselben Termine.', 'event-o'),
                ],
                'search_term' => 'Event Grid',
            ],
            [
                'slug' => 'event-o-guide-hero',
                'icon' => '04',
                'kicker' => __('Großer Auftritt', 'event-o'),
                'title' => __('Event Hero', 'event-o'),
                'summary' => __('Der aufmerksamkeitsstärkste Block für Bühnenflächen, Header-Bereiche und kuratierte Highlights.', 'event-o'),
                'badges' => [__('hero', 'event-o'), __('startseite', 'event-o'), __('highlight', 'event-o')],
                'ideal_for' => [
                    __('Erste Sichtfläche auf der Startseite oder Kampagnenseiten.', 'event-o'),
                    __('Besondere Eventreihen, die emotional und bildstark inszeniert werden sollen.', 'event-o'),
                    __('Kuratiertes Maximum statt vollständiger Übersicht.', 'event-o'),
                ],
                'key_settings' => [
                    __('perPage klein halten, damit der Hero fokussiert bleibt.', 'event-o'),
                    __('preferHighlights und onePerCategory helfen bei kuratierter Auswahl.', 'event-o'),
                    __('autoPlay nur verwenden, wenn die Seite visuell ruhig genug bleibt.', 'event-o'),
                ],
                'combine_with' => [
                    __('Fast immer mit List oder Grid darunter kombinieren.', 'event-o'),
                    __('Mit Program ergänzen, wenn nach dem Hero sofort konkrete Termine folgen sollen.', 'event-o'),
                ],
                'search_term' => 'Event Hero',
            ],
            [
                'slug' => 'event-o-guide-program',
                'icon' => '05',
                'kicker' => __('Für redaktionelle Programmseiten', 'event-o'),
                'title' => __('Event Program', 'event-o'),
                'summary' => __('Eine detailreiche Programmansicht mit Fokus auf Ablauf, Metadaten und redaktionell gut strukturierte Terminlisten.', 'event-o'),
                'badges' => [__('detailreich', 'event-o'), __('programm', 'event-o'), __('stark für Redaktion', 'event-o')],
                'ideal_for' => [
                    __('Festival-, Saison- oder Reihenübersichten.', 'event-o'),
                    __('Seiten, auf denen Beschreibungen, Spielorte und Zusatzinfos wichtig sind.', 'event-o'),
                    __('Programme, die weniger magazinartig und mehr serviceorientiert wirken sollen.', 'event-o'),
                ],
                'key_settings' => [
                    __('showDescription, showCalendar und showShare nur aktiv lassen, wenn die Zusatzinfos wirklich helfen.', 'event-o'),
                    __('animation sparsam einsetzen, damit das Programm ruhig bleibt.', 'event-o'),
                    __('Filter sind hier besonders nützlich bei vielen Reihen oder Spielorten.', 'event-o'),
                ],
                'combine_with' => [
                    __('Mit Hero als emotionalen Einstieg oberhalb des Programms.', 'event-o'),
                    __('Mit Calendar als alternative Monatsansicht derselben Inhalte.', 'event-o'),
                ],
                'search_term' => 'Event Program',
            ],
            [
                'slug' => 'event-o-guide-calendar',
                'icon' => '06',
                'kicker' => __('Monatlich denken', 'event-o'),
                'title' => __('Event Calendar', 'event-o'),
                'summary' => __('Die beste Wahl für Monatsüberblicke, Terminfindung und Besucher, die zuerst nach Datum statt nach Thema navigieren.', 'event-o'),
                'badges' => [__('kalender', 'event-o'), __('monatsblick', 'event-o'), __('interaktiv', 'event-o')],
                'ideal_for' => [
                    __('Monatsseiten, Servicebereiche und klassische Veranstaltungskalender.', 'event-o'),
                    __('Besucher, die nach Datum browsen statt über Kategorien.', 'event-o'),
                    __('Kombinationen mit einer zweiten, textreicheren Darstellung darunter.', 'event-o'),
                ],
                'key_settings' => [
                    __('theme und Farben an das aktive Website-Design angleichen.', 'event-o'),
                    __('weekStartsMonday für deutschsprachige Sites in der Regel aktiv lassen.', 'event-o'),
                    __('desktopPopupMatrix bestimmt, wie viel Raum die Event-Popups auf Desktop bekommen.', 'event-o'),
                ],
                'combine_with' => [
                    __('Mit List oder Program unterhalb des Kalenders für ausführliche Inhalte.', 'event-o'),
                    __('Mit Grid nur dann kombinieren, wenn die Seite bewusst visuell aufgebaut ist.', 'event-o'),
                ],
                'search_term' => 'Event Calendar',
            ],
        ],
    ];
}

function event_o_get_settings_sections(): array
{
    return [
        [
            'id' => 'event-o-settings-design',
            'kicker' => __('Look & Feel', 'event-o'),
            'title' => __('Design', 'event-o'),
            'short' => __('Farben und Lesbarkeit', 'event-o'),
            'description' => __('Definiere die Grundfarben des Plugins und optimiere die Lesbarkeit für helle und dunkle Themes.', 'event-o'),
            'fields' => [
                [
                    'name' => EVENT_O_OPTION_PRIMARY,
                    'label' => __('Primary color', 'event-o'),
                    'type' => 'color',
                    'description' => __('Prägt Akzente, Buttons und markante Hervorhebungen.', 'event-o'),
                    'default' => '#4f6b3a',
                ],
                [
                    'name' => EVENT_O_OPTION_ACCENT,
                    'label' => __('Accent color', 'event-o'),
                    'type' => 'color',
                    'description' => __('Zweite Markenfarbe für Kontraste und besondere UI-Elemente.', 'event-o'),
                    'default' => '#2d3a22',
                ],
                [
                    'name' => EVENT_O_OPTION_TEXT,
                    'label' => __('Text color', 'event-o'),
                    'type' => 'color',
                    'description' => __('Standardfarbe für Text auf hellen Flächen.', 'event-o'),
                    'default' => '#141414',
                ],
                [
                    'name' => EVENT_O_OPTION_MUTED,
                    'label' => __('Muted color', 'event-o'),
                    'type' => 'color',
                    'description' => __('Wird für Meta-Infos, Hilfetexte und sekundäre Inhalte genutzt.', 'event-o'),
                    'default' => '#6a6a6a',
                ],
                [
                    'name' => EVENT_O_OPTION_HIGH_CONTRAST,
                    'label' => __('Hoher Kontrast', 'event-o'),
                    'type' => 'toggle',
                    'description' => __('Ersetzt gedämpfte Farben durch volle Textfarbe für maximale Lesbarkeit.', 'event-o'),
                    'default' => false,
                ],
            ],
        ],
        [
            'id' => 'event-o-settings-single',
            'kicker' => __('Frontend Experience', 'event-o'),
            'title' => __('Single Event', 'event-o'),
            'short' => __('Template, Inhalte und Sharing', 'event-o'),
            'description' => __('Steuere das Verhalten der Event-Einzelseite, Animationen, verwandte Inhalte und Sharing-Optionen.', 'event-o'),
            'fields' => [
                [
                    'name' => EVENT_O_OPTION_ENABLE_SINGLE,
                    'label' => __('Single-Template aktivieren', 'event-o'),
                    'type' => 'toggle',
                    'description' => __('Verwendet das Event-O-Template für Einzelseiten von Event-O-Events.', 'event-o'),
                    'default' => true,
                ],
                [
                    'name' => EVENT_O_OPTION_SINGLE_ANIMATION,
                    'label' => __('Seitenanimation', 'event-o'),
                    'type' => 'select',
                    'description' => __('Sanfte Einblendung für Inhalte auf der Event-Einzelseite.', 'event-o'),
                    'default' => 'none',
                    'options' => [
                        'none' => __('Keine', 'event-o'),
                        'fade-up' => __('Fade Up', 'event-o'),
                        'fade-in' => __('Fade In', 'event-o'),
                        'slide-left' => __('Slide Left', 'event-o'),
                        'scale-up' => __('Scale Up', 'event-o'),
                    ],
                ],
                [
                    'name' => EVENT_O_OPTION_HERO_PARALLAX,
                    'label' => __('Parallax Hero-Bild', 'event-o'),
                    'type' => 'toggle',
                    'description' => __('Aktiviert einen Parallax-Effekt auf dem Hero-Bild. Wird bei reduzierter Bewegung automatisch deaktiviert.', 'event-o'),
                    'default' => false,
                ],
                [
                    'name' => EVENT_O_OPTION_SINGLE_TITLE_LAYOUT,
                    'label' => __('Titel-Position', 'event-o'),
                    'type' => 'select',
                    'description' => __('Bestimmt, wo der Event-Titel innerhalb der Einzelseite erscheint.', 'event-o'),
                    'default' => 'both',
                    'options' => [
                        'both' => __('Im Hero und im Inhalt', 'event-o'),
                        'hero' => __('Nur im Hero', 'event-o'),
                        'content' => __('Nur im Inhalt', 'event-o'),
                    ],
                ],
                [
                    'name' => EVENT_O_OPTION_SINGLE_CATEGORY_COLOR,
                    'label' => __('Kategorie-Farben nutzen', 'event-o'),
                    'type' => 'toggle',
                    'description' => __('Zeigt Kategorien auf der Einzelseite in ihrer zugewiesenen Farbe an.', 'event-o'),
                    'default' => true,
                ],
                [
                    'name' => EVENT_O_OPTION_SINGLE_SHOW_TAGS,
                    'label' => __('Schlagwörter anzeigen', 'event-o'),
                    'type' => 'toggle',
                    'description' => __('Blendet Schlagwörter auf der Event-Einzelseite ein.', 'event-o'),
                    'default' => false,
                ],
                [
                    'name' => EVENT_O_OPTION_RELATED_CATEGORY_ONLY,
                    'label' => __('Weitere Events nur aus gleicher Kategorie', 'event-o'),
                    'type' => 'toggle',
                    'description' => __('Filtert den Bereich mit weiteren Veranstaltungen auf passende Kategorien.', 'event-o'),
                    'default' => false,
                ],
                [
                    'name' => EVENT_O_OPTION_SHARE_OPTIONS,
                    'label' => __('Share-Buttons', 'event-o'),
                    'type' => 'checkbox-group',
                    'description' => __('Wähle, welche Aktionen Besucherinnen und Besuchern auf Einzelseiten sehen.', 'event-o'),
                    'default' => ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy'],
                    'options' => [
                        'facebook' => 'Facebook',
                        'twitter' => 'X (Twitter)',
                        'whatsapp' => 'WhatsApp',
                        'linkedin' => 'LinkedIn',
                        'email' => __('E-Mail', 'event-o'),
                        'instagram' => 'Instagram',
                        'calendar' => __('Zum Kalender hinzufügen', 'event-o'),
                        'copy' => __('URL kopieren', 'event-o'),
                    ],
                ],
            ],
        ],
        [
            'id' => 'event-o-settings-workflow',
            'kicker' => __('Editorial Flow', 'event-o'),
            'title' => __('Redaktionsworkflow', 'event-o'),
            'short' => __('Erstellung und Sichtbarkeit', 'event-o'),
            'description' => __('Lege fest, wie Redakteure Events anlegen und wie lange vergangene Termine in Blöcken sichtbar bleiben.', 'event-o'),
            'fields' => [
                [
                    'name' => EVENT_O_OPTION_WIZARD_MODE,
                    'label' => __('Geführte Event-Eingabe', 'event-o'),
                    'type' => 'toggle',
                    'description' => __('Aktiviert eine schrittweise Eingabemaske. Der klassische Editor bleibt erreichbar.', 'event-o'),
                    'default' => false,
                ],
                [
                    'name' => EVENT_O_OPTION_PAST_GRACE_DAYS,
                    'label' => __('Vergangene Events in Blöcken zeigen', 'event-o'),
                    'type' => 'select',
                    'description' => __('Mehrtägige oder mehrterminige Events bleiben sichtbar, bis der letzte laufende Termin vorbei ist.', 'event-o'),
                    'default' => 3,
                    'options' => [
                        '0' => __('0 Tage, nur zukünftige Events', 'event-o'),
                        '1' => __('1 Tag', 'event-o'),
                        '2' => __('2 Tage', 'event-o'),
                        '3' => __('3 Tage', 'event-o'),
                        '4' => __('4 Tage', 'event-o'),
                        '5' => __('5 Tage', 'event-o'),
                        '6' => __('6 Tage', 'event-o'),
                        '7' => __('7 Tage', 'event-o'),
                    ],
                ],
            ],
        ],
        [
            'id' => 'event-o-settings-theme',
            'kicker' => __('Theme Integration', 'event-o'),
            'title' => __('Theme-Kompatibilität', 'event-o'),
            'short' => __('Dark und Light Mode', 'event-o'),
            'description' => __('Bestimme, wie Event-O den Farbmodus deines Themes erkennt und welche Selektoren dafür genutzt werden.', 'event-o'),
            'fields' => [
                [
                    'name' => EVENT_O_OPTION_DARK_MODE,
                    'label' => __('Farbmodus', 'event-o'),
                    'type' => 'select',
                    'description' => __('Automatisch nutzt Theme-Selektoren. Light oder Dark überschreibt die Theme-Erkennung.', 'event-o'),
                    'default' => 'auto',
                    'options' => [
                        'auto' => __('Automatisch', 'event-o'),
                        'light' => __('Immer Light Mode', 'event-o'),
                        'dark' => __('Immer Dark Mode', 'event-o'),
                    ],
                ],
                [
                    'name' => EVENT_O_OPTION_DARK_SELECTOR,
                    'label' => __('Dark-Mode-Selektor', 'event-o'),
                    'type' => 'text',
                    'description' => __('Beispiel: html[data-neve-theme="dark"], body.dark-mode oder html.dark', 'event-o'),
                    'default' => 'html[data-neve-theme="dark"]',
                    'placeholder' => 'html[data-neve-theme="dark"]',
                    'input_class' => 'event-o-settings-code-input',
                    'conditions' => [
                        'field' => EVENT_O_OPTION_DARK_MODE,
                        'value' => 'auto',
                    ],
                ],
                [
                    'name' => EVENT_O_OPTION_LIGHT_SELECTOR,
                    'label' => __('Light-Mode-Selektor', 'event-o'),
                    'type' => 'text',
                    'description' => __('Hilft im Auto-Modus dabei, Dark Mode sauber von Light Mode zu trennen.', 'event-o'),
                    'default' => 'html[data-neve-theme="light"]',
                    'placeholder' => 'html[data-neve-theme="light"]',
                    'input_class' => 'event-o-settings-code-input',
                    'conditions' => [
                        'field' => EVENT_O_OPTION_DARK_MODE,
                        'value' => 'auto',
                    ],
                ],
            ],
        ],
    ];
}

function event_o_get_settings_summary(): array
{
    $tokens = event_o_get_design_tokens();
    $darkMode = (string) get_option(EVENT_O_OPTION_DARK_MODE, 'auto');
    $shareOptions = get_option(EVENT_O_OPTION_SHARE_OPTIONS, ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy']);
    if (!is_array($shareOptions)) {
        $shareOptions = [];
    }

    $darkModeLabels = [
        'auto' => __('Auto', 'event-o'),
        'light' => __('Light', 'event-o'),
        'dark' => __('Dark', 'event-o'),
    ];

    return [
        'badges' => [
            [
                'label' => (bool) get_option(EVENT_O_OPTION_ENABLE_SINGLE, true) ? __('Single aktiv', 'event-o') : __('Single aus', 'event-o'),
                'class' => (bool) get_option(EVENT_O_OPTION_ENABLE_SINGLE, true) ? 'is-good' : 'is-muted',
            ],
            [
                'label' => (bool) get_option(EVENT_O_OPTION_WIZARD_MODE, false) ? __('Wizard aktiv', 'event-o') : __('Wizard aus', 'event-o'),
                'class' => (bool) get_option(EVENT_O_OPTION_WIZARD_MODE, false) ? 'is-good' : 'is-muted',
            ],
            [
                'label' => sprintf(__('Farbmodus: %s', 'event-o'), $darkModeLabels[$darkMode] ?? $darkMode),
                'class' => $darkMode === 'dark' ? 'is-warning' : 'is-muted',
            ],
        ],
        'metrics' => [
            [
                'label' => __('Share-Buttons', 'event-o'),
                'value' => (string) count($shareOptions),
                'help' => __('Aktiv auf der Einzelseite', 'event-o'),
            ],
            [
                'label' => __('Vergangene Events', 'event-o'),
                'value' => sprintf(__('%s Tage', 'event-o'), (string) get_option(EVENT_O_OPTION_PAST_GRACE_DAYS, 3)),
                'help' => __('Sichtbarkeit in Blöcken', 'event-o'),
            ],
            [
                'label' => __('Animation', 'event-o'),
                'value' => (string) get_option(EVENT_O_OPTION_SINGLE_ANIMATION, 'none'),
                'help' => __('Single Event Übergang', 'event-o'),
            ],
            [
                'label' => __('Kontrast', 'event-o'),
                'value' => (bool) get_option(EVENT_O_OPTION_HIGH_CONTRAST, false) ? __('Hoch', 'event-o') : __('Standard', 'event-o'),
                'help' => __('Lesbarkeit der UI', 'event-o'),
            ],
        ],
        'swatches' => [
            ['label' => __('Primary', 'event-o'), 'value' => $tokens['primary']],
            ['label' => __('Accent', 'event-o'), 'value' => $tokens['accent']],
            ['label' => __('Text', 'event-o'), 'value' => $tokens['text']],
            ['label' => __('Muted', 'event-o'), 'value' => $tokens['muted']],
        ],
    ];
}

function event_o_render_settings_field(array $field): void
{
    $name = (string) $field['name'];
    $type = (string) $field['type'];
    $label = (string) $field['label'];
    $description = (string) ($field['description'] ?? '');
    $default = $field['default'] ?? '';
    $value = get_option($name, $default);
    $classes = ['event-o-settings-field', 'is-' . sanitize_html_class($type)];
    $attributes = '';

    if (!empty($field['conditions']) && is_array($field['conditions'])) {
        $conditionField = isset($field['conditions']['field']) ? (string) $field['conditions']['field'] : '';
        $conditionValue = isset($field['conditions']['value']) ? (string) $field['conditions']['value'] : '';
        if ($conditionField !== '') {
            $attributes .= ' data-conditional-field="' . esc_attr($conditionField) . '"';
            $attributes .= ' data-conditional-value="' . esc_attr($conditionValue) . '"';
        }
    }

    echo '<article class="' . esc_attr(implode(' ', $classes)) . '"' . $attributes . '>';
    echo '<div class="event-o-settings-field-head">';
    echo '<div>';
    echo '<h3>' . esc_html($label) . '</h3>';
    if ($description !== '') {
        echo '<p>' . esc_html($description) . '</p>';
    }
    echo '</div>';

    if ($type === 'toggle') {
        echo '<div class="event-o-settings-field-control event-o-settings-field-control-toggle">';
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="0" />';
        echo '<label class="event-o-settings-switch">';
        echo '<input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked((bool) $value, true, false) . ' />';
        echo '<span class="event-o-settings-switch-ui" aria-hidden="true"></span>';
        echo '<span class="screen-reader-text">' . esc_html($label) . '</span>';
        echo '</label>';
        echo '</div>';
        echo '</div>';
        echo '</article>';
        return;
    }

    echo '</div>';
    echo '<div class="event-o-settings-field-control">';

    if ($type === 'color') {
        $defaultColor = isset($field['default']) ? (string) $field['default'] : '#4f6b3a';
        echo '<input type="text" class="event-o-color-picker" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" data-default-color="' . esc_attr($defaultColor) . '" />';
    } elseif ($type === 'select') {
        echo '<select name="' . esc_attr($name) . '" class="event-o-settings-select">';
        foreach ((array) ($field['options'] ?? []) as $optionValue => $optionLabel) {
            echo '<option value="' . esc_attr((string) $optionValue) . '"' . selected((string) $value, (string) $optionValue, false) . '>' . esc_html((string) $optionLabel) . '</option>';
        }
        echo '</select>';
    } elseif ($type === 'text') {
        $inputClass = isset($field['input_class']) ? (string) $field['input_class'] : '';
        $placeholder = isset($field['placeholder']) ? (string) $field['placeholder'] : '';
        echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" placeholder="' . esc_attr($placeholder) . '" class="event-o-settings-text ' . esc_attr($inputClass) . '" />';
    } elseif ($type === 'checkbox-group') {
        $currentValues = is_array($value) ? array_values(array_filter(array_map('strval', $value))) : [];
        echo '<input type="hidden" name="' . esc_attr($name) . '[]" value="" />';
        echo '<div class="event-o-settings-checkbox-grid">';
        foreach ((array) ($field['options'] ?? []) as $optionValue => $optionLabel) {
            $isChecked = in_array((string) $optionValue, $currentValues, true);
            echo '<label class="event-o-settings-checkbox-card">';
            echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr((string) $optionValue) . '" ' . checked($isChecked, true, false) . ' />';
            echo '<span>' . esc_html((string) $optionLabel) . '</span>';
            echo '</label>';
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</article>';
}

function event_o_maybe_migrate_legacy_options(): void
{
    if (get_option(EVENT_O_OPTION_PRIMARY, null) !== null) {
        return;
    }

    $map = [
        'evento_primary_color' => EVENT_O_OPTION_PRIMARY,
        'evento_accent_color' => EVENT_O_OPTION_ACCENT,
        'evento_text_color' => EVENT_O_OPTION_TEXT,
        'evento_muted_color' => EVENT_O_OPTION_MUTED,
        'evento_enable_single_template' => EVENT_O_OPTION_ENABLE_SINGLE,
    ];

    foreach ($map as $legacy => $current) {
        $legacyValue = get_option($legacy, null);
        if ($legacyValue !== null && get_option($current, null) === null) {
            add_option($current, $legacyValue);
        }
    }
}

function event_o_get_design_tokens(): array
{
    $primary = (string) get_option(EVENT_O_OPTION_PRIMARY, '#4f6b3a');
    $accent = (string) get_option(EVENT_O_OPTION_ACCENT, '#2d3a22');
    $text = (string) get_option(EVENT_O_OPTION_TEXT, '#141414');
    $muted = (string) get_option(EVENT_O_OPTION_MUTED, '#6a6a6a');

    return [
        'primary' => sanitize_hex_color($primary) ?: '#4f6b3a',
        'accent' => sanitize_hex_color($accent) ?: '#2d3a22',
        'text' => sanitize_hex_color($text) ?: '#141414',
        'muted' => sanitize_hex_color($muted) ?: '#6a6a6a',
    ];
}

function event_o_get_css_vars_inline(): string
{
    $t = event_o_get_design_tokens();
    $darkMode = (string) get_option(EVENT_O_OPTION_DARK_MODE, 'auto');
    $darkSelector = (string) get_option(EVENT_O_OPTION_DARK_SELECTOR, 'html[data-neve-theme="dark"]');
    $lightSelector = (string) get_option(EVENT_O_OPTION_LIGHT_SELECTOR, 'html[data-neve-theme="light"]');

    // Light mode base variables
    $lightVars = "--event-o-primary:{$t['primary']};--event-o-accent:{$t['accent']};--event-o-text:{$t['text']};--event-o-muted:{$t['muted']};--event-o-border:rgba(0,0,0,.14);--event-o-bg:#fff;--event-o-sidebar-bg:#f8f8f8;--event-o-font:inherit;";
    
    // High contrast: override muted with text color
    $highContrast = (bool) get_option(EVENT_O_OPTION_HIGH_CONTRAST, false);
    if ($highContrast) {
        $lightVars = "--event-o-primary:{$t['primary']};--event-o-accent:{$t['accent']};--event-o-text:{$t['text']};--event-o-muted:{$t['text']};--event-o-border:rgba(0,0,0,.3);--event-o-bg:#fff;--event-o-sidebar-bg:#f8f8f8;--event-o-font:inherit;";
    }
    
    // Dark mode variables
    $darkVars = "--event-o-text:#f3f4f6;--event-o-muted:rgba(243,244,246,.72);--event-o-border:rgba(243,244,246,.16);--event-o-bg:#14161a;--event-o-sidebar-bg:#101216;--event-o-status-bg:rgba(243,244,246,.18);--event-o-status-text:#fff;";
    if ($highContrast) {
        $darkVars = "--event-o-text:#f3f4f6;--event-o-muted:#f3f4f6;--event-o-border:rgba(243,244,246,.3);--event-o-bg:#14161a;--event-o-sidebar-bg:#101216;--event-o-status-bg:rgba(243,244,246,.18);--event-o-status-text:#fff;";
    }

    $css = ":root{{$lightVars}}";

    // Build dark-mode specific selectors
    $darkModeRules = '';

    if ($darkMode === 'dark') {
        // Always dark mode - apply to :root directly
        $css = ":root{{$lightVars}{$darkVars}}";
        // Dark-specific overrides for root scope
        $darkModeRules .= ".event-o-share-twitter:hover{border-color:var(--event-o-text);color:var(--event-o-text)}";
        $darkModeRules .= ".event-o-grid-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.4)}";
        $darkModeRules .= ".event-o-accordion-summary:hover{background:rgba(255,255,255,0.05)}";
        $darkModeRules .= ".event-o-hero-bg::before{background:linear-gradient(to top,var(--event-o-bg) 0,rgba(20,22,26,0.6) 60%,transparent 100%)}";
        // Program block: invert today/normal in dark mode
        $progDarkMuted = $highContrast ? '--eo-prog-muted:#f3f4f6' : '--eo-prog-muted:rgba(243,244,246,.6)';
        $progTodayMuted = $highContrast ? '--eo-prog-today-muted:#1a1a1a' : '--eo-prog-today-muted:rgba(0,0,0,0.55)';
        $darkModeRules .= ".event-o-program{--eo-prog-bg:#14161a;--eo-prog-text:#f3f4f6;{$progDarkMuted};--eo-prog-border:rgba(243,244,246,.16);--eo-prog-today-bg:#fff;--eo-prog-today-text:#1a1a1a;{$progTodayMuted};--eo-prog-today-border:rgba(0,0,0,.14)}";
    } elseif ($darkMode === 'auto' && !empty($darkSelector)) {
        // Auto mode: use theme selector for dark
        $css .= "{$darkSelector}{{$darkVars}}";
        // Dark-specific overrides scoped to selector
        $darkModeRules .= "{$darkSelector} .event-o-share-twitter:hover{border-color:var(--event-o-text);color:var(--event-o-text)}";
        $darkModeRules .= "{$darkSelector} .event-o-grid-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.4)}";
        $darkModeRules .= "{$darkSelector} .event-o-accordion-summary:hover{background:rgba(255,255,255,0.05)}";
        $darkModeRules .= "{$darkSelector} .event-o-hero-bg::before{background:linear-gradient(to top,var(--event-o-bg) 0,rgba(20,22,26,0.6) 60%,transparent 100%)}";
        // Program block: invert today/normal in dark mode
        $progDarkMuted = $highContrast ? '--eo-prog-muted:#f3f4f6' : '--eo-prog-muted:rgba(243,244,246,.6)';
        $progTodayMuted = $highContrast ? '--eo-prog-today-muted:#1a1a1a' : '--eo-prog-today-muted:rgba(0,0,0,0.55)';
        $darkModeRules .= "{$darkSelector} .event-o-program{--eo-prog-bg:#14161a;--eo-prog-text:#f3f4f6;{$progDarkMuted};--eo-prog-border:rgba(243,244,246,.16);--eo-prog-today-bg:#fff;--eo-prog-today-text:#1a1a1a;{$progTodayMuted};--eo-prog-today-border:rgba(0,0,0,.14)}";
    }
    // 'light' mode: just use the base :root vars (already set), no dark rules needed

    return $css . $darkModeRules;
}
