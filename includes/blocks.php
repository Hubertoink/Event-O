<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_register_blocks(): void
{
    register_block_type('event-o/event-list', [
        'api_version' => 2,
        'render_callback' => 'event_o_render_event_list_block',
        'attributes' => [
            'perPage' => ['type' => 'number', 'default' => 10],
            'showPast' => ['type' => 'boolean', 'default' => false],
            'groupByMonth' => ['type' => 'boolean', 'default' => true],
            'openFirst' => ['type' => 'boolean', 'default' => false],
            'categories' => ['type' => 'string', 'default' => ''],
            'venues' => ['type' => 'string', 'default' => ''],
            'organizers' => ['type' => 'string', 'default' => ''],
            'showImage' => ['type' => 'boolean', 'default' => true],
            'showVenue' => ['type' => 'boolean', 'default' => true],
            'showOrganizer' => ['type' => 'boolean', 'default' => true],
            'showPrice' => ['type' => 'boolean', 'default' => true],
            'showMoreLink' => ['type' => 'boolean', 'default' => true],
            'accentColor' => ['type' => 'string', 'default' => ''],
        ],
    ]);

    register_block_type('event-o/event-carousel', [
        'api_version' => 2,
        'render_callback' => 'event_o_render_event_carousel_block',
        'attributes' => [
            'perPage' => ['type' => 'number', 'default' => 8],
            'showPast' => ['type' => 'boolean', 'default' => false],
            'categories' => ['type' => 'string', 'default' => ''],
            'venues' => ['type' => 'string', 'default' => ''],
            'organizers' => ['type' => 'string', 'default' => ''],
            'slidesToShow' => ['type' => 'number', 'default' => 3],
            'showImage' => ['type' => 'boolean', 'default' => true],
            'showVenue' => ['type' => 'boolean', 'default' => true],
            'showPrice' => ['type' => 'boolean', 'default' => true],
            'accentColor' => ['type' => 'string', 'default' => ''],
        ],
    ]);

    register_block_type('event-o/event-grid', [
        'api_version' => 2,
        'render_callback' => 'event_o_render_event_grid_block',
        'attributes' => [
            'perPage' => ['type' => 'number', 'default' => 4],
            'columns' => ['type' => 'number', 'default' => 4],
            'showPast' => ['type' => 'boolean', 'default' => false],
            'categories' => ['type' => 'string', 'default' => ''],
            'venues' => ['type' => 'string', 'default' => ''],
            'organizers' => ['type' => 'string', 'default' => ''],
            'showImage' => ['type' => 'boolean', 'default' => true],
            'showOrganizer' => ['type' => 'boolean', 'default' => true],
            'showCategory' => ['type' => 'boolean', 'default' => true],
            'showVenue' => ['type' => 'boolean', 'default' => false],
            'accentColor' => ['type' => 'string', 'default' => ''],
        ],
    ]);
}
