<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_single_template(string $template): string
{
    // Check if we're on a single event_o_event post
    if (!is_singular('event_o_event')) {
        return $template;
    }

    // Check if custom template is enabled in settings
    $enabled = (bool) get_option(EVENT_O_OPTION_ENABLE_SINGLE, true);
    if (!$enabled) {
        return $template;
    }

    $candidate = EVENT_O_PLUGIN_DIR . 'templates/single-event.php';
    if (file_exists($candidate)) {
        return $candidate;
    }

    return $template;
}
add_filter('single_template', 'event_o_single_template', 99);
