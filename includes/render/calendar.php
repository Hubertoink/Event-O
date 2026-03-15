<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_render_event_calendar_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    event_o_ensure_frontend_assets();

    $theme = isset($attrs['theme']) ? (string) $attrs['theme'] : 'auto';
    $desktopPopupMatrix = isset($attrs['desktopPopupMatrix']) ? (string) $attrs['desktopPopupMatrix'] : '3x3';
    if (!in_array($desktopPopupMatrix, ['3x3', '3x2'], true)) {
        $desktopPopupMatrix = '3x3';
    }
    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? (string) $attrs['accentColor'] : '#4f6b3a';
    $calBgLight = isset($attrs['calendarBgLight']) ? (string) $attrs['calendarBgLight'] : '#f3f5f7';
    $calBgDark = isset($attrs['calendarBgDark']) ? (string) $attrs['calendarBgDark'] : '#10141a';
    $dayBgLight = isset($attrs['dayBgLight']) ? (string) $attrs['dayBgLight'] : '#ffffff';
    $dayBgDark = isset($attrs['dayBgDark']) ? (string) $attrs['dayBgDark'] : '#1b2330';
    $weekStart = !empty($attrs['weekStartsMonday']) ? '1' : '0';
    $popupBlur = !isset($attrs['popupBlur']) || !empty($attrs['popupBlur']) ? '1' : '0';
    $showSubscribe = !isset($attrs['showSubscribe']) || !empty($attrs['showSubscribe']);
    $subscribeUrl = $showSubscribe ? event_o_get_ical_feed_url() : '';

    $taxQuery = ['relation' => 'AND'];

    $categories = isset($attrs['categories']) ? event_o_parse_slug_list((string) $attrs['categories']) : [];
    if ($categories) {
        $taxQuery[] = [
            'taxonomy' => 'event_o_category',
            'field' => 'slug',
            'terms' => $categories,
        ];
    }

    $venues = isset($attrs['venues']) ? event_o_parse_slug_list((string) $attrs['venues']) : [];
    if ($venues) {
        $taxQuery[] = [
            'taxonomy' => 'event_o_venue',
            'field' => 'slug',
            'terms' => $venues,
        ];
    }

    $organizers = isset($attrs['organizers']) ? event_o_parse_slug_list((string) $attrs['organizers']) : [];
    if ($organizers) {
        $taxQuery[] = [
            'taxonomy' => 'event_o_organizer',
            'field' => 'slug',
            'terms' => $organizers,
        ];
    }

    $queryArgs = [
        'post_type' => 'event_o_event',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => EVENT_O_META_START_TS,
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    ];

    if (count($taxQuery) > 1) {
        $queryArgs['tax_query'] = $taxQuery;
    }

    $query = new WP_Query($queryArgs);
    $timezone = wp_timezone();
    $eventsData = [];

    while ($query->have_posts()) {
        $query->the_post();
        $postId = get_the_ID();
        $status = event_o_get_event_status($postId);

        $categoryDetails = event_o_get_event_category_details($postId);
        $categoryNames = event_o_get_event_category_names($postId);
        $categoryName = implode(' / ', $categoryNames);
        $categoryColor = event_o_get_first_category_color($postId);
        $venueName = event_o_get_first_term_name($postId, 'event_o_venue');
        $imageUrls = event_o_get_event_image_urls($postId, 'medium');
        $imageUrl = !empty($imageUrls) ? (string) $imageUrls[0] : '';

        $excerpt = get_the_excerpt();
        if ($excerpt === '') {
            $excerpt = wp_trim_words(wp_strip_all_tags(get_the_content()), 20, '...');
        } else {
            $excerpt = wp_trim_words($excerpt, 20, '...');
        }

        foreach (event_o_get_all_date_slots($postId) as $slot) {
            $startTs = isset($slot['start_ts']) ? (int) $slot['start_ts'] : 0;
            $endTs = isset($slot['end_ts']) ? (int) $slot['end_ts'] : 0;
            if ($startTs <= 0) {
                continue;
            }

            $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($timezone);
            $dateStr = $start->format('Y-m-d');
            $time = $start->format('H:i');
            $timeEnd = '';

            if ($endTs > 0) {
                $end = (new DateTimeImmutable('@' . $endTs))->setTimezone($timezone);
                $timeEnd = $end->format('H:i');
            }

            $beginTime = !empty($slot['begin_time']) ? (string) $slot['begin_time'] : '';

            $eventsData[] = [
                'id' => $postId,
                'title' => get_the_title(),
                'date' => $dateStr,
                'time' => $time,
                'timeEnd' => $timeEnd,
                'beginTime' => $beginTime,
                'url' => get_permalink(),
                'image' => $imageUrl,
                'category' => $categoryName,
                'categories' => $categoryDetails,
                'categoryColor' => $categoryColor,
                'venue' => $venueName,
                'status' => $status,
                'statusLabel' => event_o_get_event_status_label($postId, $status),
                'cancelled' => ($status === 'cancelled'),
                'soldOut' => ($status === 'soldout'),
                'excerpt' => $excerpt,
            ];
        }
    }

    wp_reset_postdata();

    $styleAttr = 'style="'
        . '--cal-accent:' . esc_attr($accentColor) . ';'
        . '--cal-bg-light:' . esc_attr($calBgLight) . ';'
        . '--cal-bg-dark:' . esc_attr($calBgDark) . ';'
        . '--cal-cell-bg-light:' . esc_attr($dayBgLight) . ';'
        . '--cal-cell-bg-dark:' . esc_attr($dayBgDark) . ';'
        . '--cal-empty-bg-light:' . esc_attr($dayBgLight) . ';'
        . '--cal-empty-bg-dark:' . esc_attr($dayBgDark) . ';'
        . '"';

    return '<div class="event-o event-o-cal-wrap theme-' . esc_attr($theme) . '" '
        . 'data-events="' . esc_attr(wp_json_encode($eventsData)) . '" '
        . 'data-week-start="' . $weekStart . '" '
        . 'data-popup-blur="' . $popupBlur . '" '
        . 'data-desktop-popup-matrix="' . esc_attr($desktopPopupMatrix) . '" '
        . ($showSubscribe ? 'data-subscribe-url="' . esc_attr($subscribeUrl) . '" ' : '')
        . $styleAttr
        . '></div>';
}
