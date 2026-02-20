<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_register_post_type(): void
{
    $labels = [
        'name' => __('Event-O Events', 'event-o'),
        'singular_name' => __('Event-O Event', 'event-o'),
        'add_new' => __('Add New', 'event-o'),
        'add_new_item' => __('Add New Event', 'event-o'),
        'edit_item' => __('Edit Event', 'event-o'),
        'new_item' => __('New Event', 'event-o'),
        'view_item' => __('View Event', 'event-o'),
        'search_items' => __('Search Events', 'event-o'),
        'not_found' => __('No events found', 'event-o'),
        'not_found_in_trash' => __('No events found in Trash', 'event-o'),
        'all_items' => __('All Events', 'event-o'),
        'menu_name' => __('Event-O', 'event-o'),
        'name_admin_bar' => __('Event-O Event', 'event-o'),
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'events'],
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'capability_type' => ['event_o_event', 'event_o_events'],
        'map_meta_cap' => true,
    ];

    register_post_type('event_o_event', $args);
}
