<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_get_frontend_block_names(): array
{
    return [
        'event-o/event-list',
        'event-o/event-carousel',
        'event-o/event-grid',
        'event-o/event-hero',
        'event-o/event-program',
    ];
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

function event_o_register_frontend_assets(): void
{
    $styleHandle = 'event-o-style';
    if (!wp_style_is($styleHandle, 'registered')) {
        wp_register_style(
            $styleHandle,
            EVENT_O_PLUGIN_URL . 'assets/style.css',
            [],
            event_o_asset_version('assets/style.css')
        );
    }

    if (!wp_script_is('event-o-frontend', 'registered')) {
        wp_register_script(
            'event-o-frontend',
            EVENT_O_PLUGIN_URL . 'assets/frontend.js',
            [],
            event_o_asset_version('assets/frontend.js'),
            true
        );
    }
}
add_action('init', 'event_o_register_frontend_assets', 20);

function event_o_enqueue_frontend_assets(): void
{
    static $inlineStyleAttached = false;

    event_o_register_frontend_assets();

    $styleHandle = 'event-o-style';
    if (!$inlineStyleAttached) {
        wp_add_inline_style($styleHandle, event_o_get_css_vars_inline());
        $inlineStyleAttached = true;
    }

    wp_enqueue_style($styleHandle);

    wp_enqueue_script('event-o-frontend');
}

function event_o_block_tree_contains_frontend_blocks(array $blocks, array &$reusableBlockIds = []): bool
{
    $frontendBlocks = event_o_get_frontend_block_names();

    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }

        $blockName = isset($block['blockName']) ? $block['blockName'] : null;
        if (is_string($blockName) && in_array($blockName, $frontendBlocks, true)) {
            return true;
        }

        if (
            $blockName === 'core/block'
            && !empty($block['attrs']['ref'])
        ) {
            $ref = (int) $block['attrs']['ref'];
            if ($ref > 0 && empty($reusableBlockIds[$ref])) {
                $reusableBlockIds[$ref] = true;
                $reusableBlock = get_post($ref);
                if ($reusableBlock instanceof WP_Post && event_o_post_contains_frontend_blocks($reusableBlock, $reusableBlockIds)) {
                    return true;
                }
            }
        }

        if (!empty($block['innerBlocks']) && event_o_block_tree_contains_frontend_blocks($block['innerBlocks'], $reusableBlockIds)) {
            return true;
        }
    }

    return false;
}

function event_o_post_contains_frontend_blocks(WP_Post $post, array &$reusableBlockIds = []): bool
{
    $content = (string) $post->post_content;
    if ($content === '' || !has_blocks($content)) {
        return false;
    }

    return event_o_block_tree_contains_frontend_blocks(parse_blocks($content), $reusableBlockIds);
}

function event_o_current_request_needs_frontend_assets(): bool
{
    static $needsFrontendAssets = null;

    if ($needsFrontendAssets !== null) {
        return $needsFrontendAssets;
    }

    if (is_admin()) {
        $needsFrontendAssets = false;
        return $needsFrontendAssets;
    }

    if (is_singular('event_o_event')) {
        $needsFrontendAssets = true;
        return $needsFrontendAssets;
    }

    global $wp_query;

    $posts = [];
    if (isset($wp_query) && isset($wp_query->posts) && is_array($wp_query->posts)) {
        $posts = $wp_query->posts;
    } else {
        $queriedObject = get_queried_object();
        if ($queriedObject instanceof WP_Post) {
            $posts = [$queriedObject];
        }
    }

    $reusableBlockIds = [];
    foreach ($posts as $post) {
        if ($post instanceof WP_Post && event_o_post_contains_frontend_blocks($post, $reusableBlockIds)) {
            $needsFrontendAssets = true;
            return $needsFrontendAssets;
        }
    }

    $needsFrontendAssets = false;
    return $needsFrontendAssets;
}

function event_o_maybe_enqueue_frontend_assets(): void
{
    if (event_o_current_request_needs_frontend_assets()) {
        event_o_enqueue_frontend_assets();
    }
}
add_action('wp_enqueue_scripts', 'event_o_maybe_enqueue_frontend_assets', 20);

function event_o_print_late_frontend_assets(): void
{
    if (wp_style_is('event-o-style', 'enqueued') && !wp_style_is('event-o-style', 'done')) {
        wp_print_styles(['event-o-style']);
    }

    if (wp_script_is('event-o-frontend', 'enqueued') && !wp_script_is('event-o-frontend', 'done')) {
        wp_print_scripts(['event-o-frontend']);
    }
}

function event_o_ensure_frontend_assets(): void
{
    static $latePrintHookAdded = false;

    event_o_enqueue_frontend_assets();

    if (!$latePrintHookAdded && did_action('wp_head')) {
        add_action('wp_footer', 'event_o_print_late_frontend_assets', 1);
        $latePrintHookAdded = true;
    }
}

function event_o_enqueue_editor_assets(): void
{
    $styleHandle = 'event-o-style';

    event_o_register_frontend_assets();

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
