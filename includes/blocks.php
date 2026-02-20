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
            'singleOpen' => ['type' => 'boolean', 'default' => false],
            'categories' => ['type' => 'string', 'default' => ''],
            'venues' => ['type' => 'string', 'default' => ''],
            'organizers' => ['type' => 'string', 'default' => ''],
            'showImage' => ['type' => 'boolean', 'default' => true],
            'showVenue' => ['type' => 'boolean', 'default' => true],
            'showOrganizer' => ['type' => 'boolean', 'default' => true],
            'showPrice' => ['type' => 'boolean', 'default' => true],
            'showMoreLink' => ['type' => 'boolean', 'default' => true],
            'accentColor' => ['type' => 'string', 'default' => ''],
            'showFilters' => ['type' => 'boolean', 'default' => false],
            'filterByCategory' => ['type' => 'boolean', 'default' => true],
            'filterByVenue' => ['type' => 'boolean', 'default' => true],
            'filterByOrganizer' => ['type' => 'boolean', 'default' => true],
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
            'showFilters' => ['type' => 'boolean', 'default' => false],
            'filterByCategory' => ['type' => 'boolean', 'default' => true],
            'filterByVenue' => ['type' => 'boolean', 'default' => true],
            'filterByOrganizer' => ['type' => 'boolean', 'default' => true],
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
            'showPrice' => ['type' => 'boolean', 'default' => true],
            'accentColor' => ['type' => 'string', 'default' => ''],
            'showFilters' => ['type' => 'boolean', 'default' => false],
            'filterByCategory' => ['type' => 'boolean', 'default' => true],
            'filterByVenue' => ['type' => 'boolean', 'default' => true],
            'filterByOrganizer' => ['type' => 'boolean', 'default' => true],
        ],
    ]);

    register_block_type('event-o/event-hero', [
        'api_version' => 2,
        'render_callback' => 'event_o_render_event_hero_block',
        'supports' => [
            'align' => ['wide', 'full'],
        ],
        'attributes' => [
            'perPage' => ['type' => 'number', 'default' => 5],
            'showPast' => ['type' => 'boolean', 'default' => false],
            'categories' => ['type' => 'string', 'default' => ''],
            'venues' => ['type' => 'string', 'default' => ''],
            'organizers' => ['type' => 'string', 'default' => ''],
            'showDate' => ['type' => 'boolean', 'default' => true],
            'dateVariant' => ['type' => 'string', 'default' => 'date'],
            'showDesc' => ['type' => 'boolean', 'default' => true],
            'showButton' => ['type' => 'boolean', 'default' => true],
            'buttonStyle' => ['type' => 'string', 'default' => 'rounded'],
            'accentColor' => ['type' => 'string', 'default' => ''],
            'contentIndent' => ['type' => 'number', 'default' => 60],
            'heroHeight' => ['type' => 'number', 'default' => 520],
            'overlayColor' => ['type' => 'string', 'default' => 'black'],
            'showFilters' => ['type' => 'boolean', 'default' => false],
            'filterByCategory' => ['type' => 'boolean', 'default' => true],
            'filterByVenue' => ['type' => 'boolean', 'default' => true],
            'filterByOrganizer' => ['type' => 'boolean', 'default' => true],
        ],
    ]);

    register_block_type('event-o/event-program', [
        'api_version' => 2,
        'render_callback' => 'event_o_render_event_program_block',
        'attributes' => [
            'perPage' => ['type' => 'number', 'default' => 8],
            'showPast' => ['type' => 'boolean', 'default' => false],
            'categories' => ['type' => 'string', 'default' => ''],
            'venues' => ['type' => 'string', 'default' => ''],
            'organizers' => ['type' => 'string', 'default' => ''],
            'showImage' => ['type' => 'boolean', 'default' => true],
            'showVenue' => ['type' => 'boolean', 'default' => true],
            'showCategory' => ['type' => 'boolean', 'default' => true],
            'showDescription' => ['type' => 'boolean', 'default' => true],
            'showCalendar' => ['type' => 'boolean', 'default' => true],
            'showShare' => ['type' => 'boolean', 'default' => true],
            'showBands' => ['type' => 'boolean', 'default' => true],
            'showPrice' => ['type' => 'boolean', 'default' => true],
            'accentColor' => ['type' => 'string', 'default' => ''],
        ],
    ]);
}
