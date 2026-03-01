<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_asset_version(string $relativePath): string
{
    $absolutePath = EVENT_O_PLUGIN_DIR . ltrim($relativePath, '/');
    if (file_exists($absolutePath)) {
        $mtime = filemtime($absolutePath);
        if ($mtime !== false) {
            return (string) $mtime;
        }
    }

    return EVENT_O_VERSION;
}

function event_o_enqueue_frontend_assets(): void
{
    $styleHandle = 'event-o-style';
    wp_register_style(
        $styleHandle,
        EVENT_O_PLUGIN_URL . 'assets/style.css',
        [],
        event_o_asset_version('assets/style.css')
    );
    wp_add_inline_style($styleHandle, event_o_get_css_vars_inline());
    wp_enqueue_style($styleHandle);

    wp_register_script(
        'event-o-frontend',
        EVENT_O_PLUGIN_URL . 'assets/frontend.js',
        [],
        event_o_asset_version('assets/frontend.js'),
        true
    );

    // Only needed for carousel behavior; safe to enqueue always (tiny), but we keep it simple.
    wp_enqueue_script('event-o-frontend');
}
add_action('wp_enqueue_scripts', 'event_o_enqueue_frontend_assets');

function event_o_enqueue_editor_assets(): void
{
    $styleHandle = 'event-o-style';
    wp_register_style(
        $styleHandle,
        EVENT_O_PLUGIN_URL . 'assets/style.css',
        [],
        EVENT_O_VERSION
    );
    wp_add_inline_style($styleHandle, event_o_get_css_vars_inline());
    wp_enqueue_style($styleHandle);

    wp_enqueue_style(
        'event-o-editor',
        EVENT_O_PLUGIN_URL . 'assets/editor.css',
        ['wp-edit-blocks'],
        event_o_asset_version('assets/editor.css')
    );

    wp_enqueue_script(
        'event-o-editor',
        EVENT_O_PLUGIN_URL . 'assets/editor.js',
        [
            'wp-blocks',
            'wp-element',
            'wp-i18n',
            'wp-components',
            'wp-block-editor',
            'wp-server-side-render',
        ],
        event_o_asset_version('assets/editor.js'),
        true
    );

    // Also load frontend.js in the editor so the calendar block can be rendered.
    wp_enqueue_script(
        'event-o-frontend',
        EVENT_O_PLUGIN_URL . 'assets/frontend.js',
        [],
        event_o_asset_version('assets/frontend.js'),
        true
    );
}
add_action('enqueue_block_editor_assets', 'event_o_enqueue_editor_assets');
