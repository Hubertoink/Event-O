<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_register_taxonomies(): void
{
    register_taxonomy(
        'event_o_category',
        ['event_o_event'],
        [
            'labels' => [
                'name' => __('Event-O Categories', 'event-o'),
                'singular_name' => __('Event-O Category', 'event-o'),
                'add_new_item' => __('Kategorie hinzufügen', 'event-o'),
                'new_item_name' => __('Neue Kategorie', 'event-o'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'event-o-category'],
        ]
    );

    register_taxonomy(
        'event_o_venue',
        ['event_o_event'],
        [
            'labels' => [
                'name' => __('Event-O Venues', 'event-o'),
                'singular_name' => __('Event-O Venue', 'event-o'),
                'add_new_item' => __('Ort hinzufügen', 'event-o'),
                'new_item_name' => __('Neuer Ort', 'event-o'),
                'search_items' => __('Orte suchen', 'event-o'),
                'all_items' => __('Alle Orte', 'event-o'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'event-o-venue'],
        ]
    );

    register_taxonomy(
        'event_o_organizer',
        ['event_o_event'],
        [
            'labels' => [
                'name' => __('Event-O Organizers', 'event-o'),
                'singular_name' => __('Event-O Organizer', 'event-o'),
                'add_new_item' => __('Organisation hinzufügen', 'event-o'),
                'new_item_name' => __('Neue Organisation', 'event-o'),
                'search_items' => __('Organisationen suchen', 'event-o'),
                'all_items' => __('Alle Organisationen', 'event-o'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'event-o-organizer'],
        ]
    );
}
