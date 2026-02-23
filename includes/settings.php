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
            echo '<p class="description">' . esc_html__('Wie viele Tage nach dem Eventbeginn soll ein Event noch in den Blöcken angezeigt werden. Events werden immer mindestens bis Mitternacht des Starttages angezeigt.', 'event-o') . '</p>';
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

function event_o_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Enqueue WordPress color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Event_O Settings', 'event-o') . '</h1>';
    echo '<form method="post" action="options.php">';

    settings_fields('event_o_settings');
    do_settings_sections('event_o_settings');
    submit_button();

    echo '</form>';
    echo '</div>';

    // Initialize color pickers
    echo '<script>jQuery(document).ready(function($){ $(".event-o-color-picker").wpColorPicker(); });</script>';
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
