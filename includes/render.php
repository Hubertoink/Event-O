<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_parse_slug_list(string $raw): array
{
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $parts = array_values(array_unique(array_filter($parts, static fn($p) => $p !== '')));
    return $parts;
}

/**
 * Collect taxonomy terms from queried posts for filter dropdowns.
 */
function event_o_collect_filter_terms(WP_Query $q, array $attrs): array
{
    $filterByCategory = !empty($attrs['filterByCategory']);
    $filterByVenue = !empty($attrs['filterByVenue']);
    $filterByOrganizer = !empty($attrs['filterByOrganizer']);

    $categories = [];
    $venues = [];
    $organizers = [];

    $posts = $q->posts;
    foreach ($posts as $post) {
        $pid = $post->ID;

        if ($filterByCategory) {
            $terms = get_the_terms($pid, 'event_o_category');
            if (is_array($terms)) {
                foreach ($terms as $t) {
                    $categories[$t->slug] = $t->name;
                }
            }
        }
        if ($filterByVenue) {
            $terms = get_the_terms($pid, 'event_o_venue');
            if (is_array($terms)) {
                foreach ($terms as $t) {
                    $venues[$t->slug] = $t->name;
                }
            }
        }
        if ($filterByOrganizer) {
            $terms = get_the_terms($pid, 'event_o_organizer');
            if (is_array($terms)) {
                foreach ($terms as $t) {
                    $organizers[$t->slug] = $t->name;
                }
            }
        }
    }

    asort($categories);
    asort($venues);
    asort($organizers);

    return [
        'categories' => $categories,
        'venues' => $venues,
        'organizers' => $organizers,
    ];
}

/**
 * Render the filter bar HTML.
 */
function event_o_render_filter_bar(array $filterTerms, array $attrs): string
{
    $filterByCategory = !empty($attrs['filterByCategory']);
    $filterByVenue = !empty($attrs['filterByVenue']);
    $filterByOrganizer = !empty($attrs['filterByOrganizer']);

    $out = '<div class="event-o-filter-bar">';

    if ($filterByCategory && !empty($filterTerms['categories'])) {
        $out .= '<div class="event-o-filter-group">';
        $out .= '<select class="event-o-filter-select" data-filter="category">';
        $out .= '<option value="">' . esc_html__('Alle Kategorien', 'event-o') . '</option>';
        foreach ($filterTerms['categories'] as $slug => $name) {
            $out .= '<option value="' . esc_attr($slug) . '">' . esc_html($name) . '</option>';
        }
        $out .= '</select>';
        $out .= '</div>';
    }

    if ($filterByVenue && !empty($filterTerms['venues'])) {
        $out .= '<div class="event-o-filter-group">';
        $out .= '<select class="event-o-filter-select" data-filter="venue">';
        $out .= '<option value="">' . esc_html__('Alle Veranstaltungsorte', 'event-o') . '</option>';
        foreach ($filterTerms['venues'] as $slug => $name) {
            $out .= '<option value="' . esc_attr($slug) . '">' . esc_html($name) . '</option>';
        }
        $out .= '</select>';
        $out .= '</div>';
    }

    if ($filterByOrganizer && !empty($filterTerms['organizers'])) {
        $out .= '<div class="event-o-filter-group">';
        $out .= '<select class="event-o-filter-select" data-filter="organizer">';
        $out .= '<option value="">' . esc_html__('Alle Veranstalter', 'event-o') . '</option>';
        foreach ($filterTerms['organizers'] as $slug => $name) {
            $out .= '<option value="' . esc_attr($slug) . '">' . esc_html($name) . '</option>';
        }
        $out .= '</select>';
        $out .= '</div>';
    }

    $out .= '</div>';
    return $out;
}

/**
 * Get data-filter attributes string for a post.
 */
function event_o_get_filter_data_attrs(int $postId): string
{
    $catSlugs = [];
    $venueSlugs = [];
    $orgSlugs = [];

    $cats = get_the_terms($postId, 'event_o_category');
    if (is_array($cats)) {
        foreach ($cats as $t) {
            $catSlugs[] = $t->slug;
        }
    }

    $venues = get_the_terms($postId, 'event_o_venue');
    if (is_array($venues)) {
        foreach ($venues as $t) {
            $venueSlugs[] = $t->slug;
        }
    }

    $orgs = get_the_terms($postId, 'event_o_organizer');
    if (is_array($orgs)) {
        foreach ($orgs as $t) {
            $orgSlugs[] = $t->slug;
        }
    }

    return ' data-categories="' . esc_attr(implode(',', $catSlugs)) . '"'
         . ' data-venues="' . esc_attr(implode(',', $venueSlugs)) . '"'
         . ' data-organizers="' . esc_attr(implode(',', $orgSlugs)) . '"';
}

function event_o_event_query(array $attrs): WP_Query
{
    $perPage = isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 10;
    $showPast = !empty($attrs['showPast']);

    $metaQuery = [];
    if (!$showPast) {
        $metaQuery[] = [
            'key' => EVENT_O_META_START_TS,
            'value' => time(),
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
    }

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

    $args = [
        'post_type' => 'event_o_event',
        'post_status' => 'publish',
        'posts_per_page' => $perPage,
        'meta_key' => EVENT_O_META_START_TS,
        'orderby' => 'meta_value_num',
        'order' => $showPast ? 'DESC' : 'ASC',
    ];

    if ($metaQuery) {
        $args['meta_query'] = $metaQuery;
    }

    if (count($taxQuery) > 1) {
        $args['tax_query'] = $taxQuery;
    }

    return new WP_Query($args);
}

function event_o_format_event_datetime(int $startTs, int $endTs = 0): string
{
    if ($startTs <= 0) {
        return '';
    }

    $tz = wp_timezone();
    $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);

    $out = $start->format(get_option('date_format')) . ' ' . $start->format(get_option('time_format'));

    if ($endTs > 0) {
        $end = (new DateTimeImmutable('@' . $endTs))->setTimezone($tz);
        $out .= ' – ' . $end->format(get_option('time_format'));
    }

    return $out;
}

/**
 * German month names for proper localization.
 */
function event_o_get_german_month(int $monthNum): string
{
    $months = [
        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
    ];
    return $months[$monthNum] ?? '';
}

function event_o_format_datetime_german(int $startTs, int $endTs = 0): array
{
    if ($startTs <= 0) {
        return ['date' => '', 'time' => ''];
    }

    $tz = wp_timezone();
    $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);

    $day = $start->format('j');
    $month = event_o_get_german_month((int) $start->format('n'));
    $year = $start->format('Y');
    $date = $day . '. ' . $month . ' ' . $year;

    $time = $start->format('H:i');
    if ($endTs > 0) {
        $end = (new DateTimeImmutable('@' . $endTs))->setTimezone($tz);
        $time .= ' – ' . $end->format('H:i') . ' Uhr';
    } else {
        $time .= ' Uhr';
    }

    return ['date' => $date, 'time' => $time];
}

function event_o_get_first_term_name(int $postId, string $taxonomy): string
{
    $terms = get_the_terms($postId, $taxonomy);
    if (!is_array($terms) || !$terms) {
        return '';
    }

    $first = array_shift($terms);
    if (!$first || empty($first->name)) {
        return '';
    }

    return (string) $first->name;
}

/**
 * Generate Google Calendar URL.
 */
function event_o_get_google_calendar_url(string $title, int $startTs, int $endTs, string $description = '', string $location = ''): string
{
    $tz = wp_timezone();
    $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);
    
    // If no end time, default to 2 hours after start
    if ($endTs <= 0) {
        $endTs = $startTs + 7200;
    }
    $end = (new DateTimeImmutable('@' . $endTs))->setTimezone($tz);

    $params = [
        'action' => 'TEMPLATE',
        'text' => $title,
        'dates' => $start->format('Ymd\THis') . '/' . $end->format('Ymd\THis'),
        'ctz' => $tz->getName(),
    ];

    if ($description) {
        $params['details'] = $description;
    }
    if ($location) {
        $params['location'] = $location;
    }

    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

/**
 * Generate Outlook Calendar URL.
 */
function event_o_get_outlook_calendar_url(string $title, int $startTs, int $endTs, string $description = '', string $location = ''): string
{
    // If no end time, default to 2 hours after start
    if ($endTs <= 0) {
        $endTs = $startTs + 7200;
    }
    
    // Outlook uses ISO 8601 format
    $start = gmdate('Y-m-d\TH:i:s\Z', $startTs);
    $end = gmdate('Y-m-d\TH:i:s\Z', $endTs);

    $params = [
        'path' => '/calendar/action/compose',
        'rru' => 'addevent',
        'subject' => $title,
        'startdt' => $start,
        'enddt' => $end,
    ];

    if ($description) {
        $params['body'] = $description;
    }
    if ($location) {
        $params['location'] = $location;
    }

    return 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query($params);
}

/**
 * Generate iCal (.ics) data URL.
 */
function event_o_get_ical_data(string $title, int $startTs, int $endTs, string $description = '', string $location = '', string $url = ''): string
{
    $start = gmdate('Ymd\THis\Z', $startTs);
    
    // If no end time, default to 2 hours after start
    if ($endTs <= 0) {
        $endTs = $startTs + 7200;
    }
    $end = gmdate('Ymd\THis\Z', $endTs);
    $now = gmdate('Ymd\THis\Z');
    $uid = md5($startTs . $title) . '@event-o';

    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Event_O//Event_O Plugin//DE\r\n";
    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:" . $uid . "\r\n";
    $ical .= "DTSTAMP:" . $now . "\r\n";
    $ical .= "DTSTART:" . $start . "\r\n";
    $ical .= "DTEND:" . $end . "\r\n";
    $ical .= "SUMMARY:" . event_o_ical_escape($title) . "\r\n";
    
    if ($description) {
        $ical .= "DESCRIPTION:" . event_o_ical_escape($description) . "\r\n";
    }
    if ($location) {
        $ical .= "LOCATION:" . event_o_ical_escape($location) . "\r\n";
    }
    if ($url) {
        $ical .= "URL:" . $url . "\r\n";
    }
    
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";

    return 'data:text/calendar;charset=utf-8,' . rawurlencode($ical);
}

/**
 * Escape string for iCal format.
 */
function event_o_ical_escape(string $str): string
{
    $str = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $str);
    return $str;
}

/**
 * Generate share buttons HTML with optional calendar support.
 */
function event_o_render_share_buttons(string $url, string $title, array $calendarData = []): string
{
    $encodedUrl = rawurlencode($url);
    $encodedTitle = rawurlencode($title);

    // Get enabled share options from settings
    $enabledOptions = get_option(EVENT_O_OPTION_SHARE_OPTIONS, ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy']);
    if (!is_array($enabledOptions)) {
        $enabledOptions = ['facebook', 'twitter', 'whatsapp', 'linkedin', 'email', 'instagram', 'calendar', 'copy'];
    }

    $out = '<div class="event-o-share-buttons">';

    // Facebook
    if (in_array('facebook', $enabledOptions, true)) {
        $out .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl . '" target="_blank" rel="noopener noreferrer" class="event-o-share-btn event-o-share-facebook" aria-label="Auf Facebook teilen" title="Facebook">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
        $out .= '</a>';
    }

    // X (Twitter)
    if (in_array('twitter', $enabledOptions, true)) {
        $out .= '<a href="https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedTitle . '" target="_blank" rel="noopener noreferrer" class="event-o-share-btn event-o-share-twitter" aria-label="Auf X teilen" title="X">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
        $out .= '</a>';
    }

    // WhatsApp
    if (in_array('whatsapp', $enabledOptions, true)) {
        $out .= '<a href="https://api.whatsapp.com/send?text=' . $encodedTitle . '%20' . $encodedUrl . '" target="_blank" rel="noopener noreferrer" class="event-o-share-btn event-o-share-whatsapp" aria-label="Per WhatsApp teilen" title="WhatsApp">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
        $out .= '</a>';
    }

    // LinkedIn
    if (in_array('linkedin', $enabledOptions, true)) {
        $out .= '<a href="https://www.linkedin.com/shareArticle?mini=true&url=' . $encodedUrl . '&title=' . $encodedTitle . '" target="_blank" rel="noopener noreferrer" class="event-o-share-btn event-o-share-linkedin" aria-label="Auf LinkedIn teilen" title="LinkedIn">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
        $out .= '</a>';
    }

    // Email
    if (in_array('email', $enabledOptions, true)) {
        $out .= '<a href="mailto:?subject=' . $encodedTitle . '&body=' . $encodedTitle . '%20' . $encodedUrl . '" class="event-o-share-btn event-o-share-email" aria-label="Per E-Mail teilen" title="E-Mail">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
        $out .= '</a>';
    }

    // Instagram (copies URL to clipboard, user pastes in Instagram)
    if (in_array('instagram', $enabledOptions, true)) {
        $out .= '<button type="button" class="event-o-share-btn event-o-share-instagram" data-url="' . esc_attr($url) . '" aria-label="Für Instagram kopieren" title="Für Instagram kopieren">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>';
        $out .= '</button>';
    }

    // Calendar button (dropdown with Google Calendar, Outlook, and iCal)
    if (in_array('calendar', $enabledOptions, true) && !empty($calendarData) && !empty($calendarData['postId'])) {
        $googleUrl = event_o_get_google_calendar_url(
            $calendarData['title'] ?? $title,
            $calendarData['startTs'] ?? 0,
            $calendarData['endTs'] ?? 0,
            $calendarData['description'] ?? '',
            $calendarData['location'] ?? ''
        );
        $outlookUrl = event_o_get_outlook_calendar_url(
            $calendarData['title'] ?? $title,
            $calendarData['startTs'] ?? 0,
            $calendarData['endTs'] ?? 0,
            $calendarData['description'] ?? '',
            $calendarData['location'] ?? ''
        );
        $icalUrl = event_o_get_ical_url($calendarData['postId']);
        
        $out .= '<div class="event-o-calendar-dropdown">';
        $out .= '<button type="button" class="event-o-share-btn event-o-share-calendar" aria-label="Zum Kalender hinzufügen" title="Zum Kalender hinzufügen">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>';
        $out .= '</button>';
        $out .= '<div class="event-o-calendar-menu">';
        $out .= '<a href="' . esc_url($googleUrl) . '" target="_blank" rel="noopener noreferrer" class="event-o-calendar-option">Google Kalender</a>';
        $out .= '<a href="' . esc_url($outlookUrl) . '" target="_blank" rel="noopener noreferrer" class="event-o-calendar-option">Outlook Kalender</a>';
        $out .= '<a href="' . esc_url($icalUrl) . '" class="event-o-calendar-option">iCal / Apple</a>';
        $out .= '</div>';
        $out .= '</div>';
    }

    // Copy link button with URL text
    if (in_array('copy', $enabledOptions, true)) {
        $out .= '<button type="button" class="event-o-share-btn event-o-share-copy" data-url="' . esc_attr($url) . '" aria-label="Link kopieren" title="Link kopieren">';
        $out .= '<span class="event-o-share-copy-text">URL</span>';
        $out .= '</button>';
    }

    $out .= '</div>';

    return $out;
}

function event_o_render_event_list_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    $q = event_o_event_query($attrs);
    if (!$q->have_posts()) {
        return '<div class="event-o event-o-event-list"><p class="event-o-empty">' . esc_html__('No events found.', 'event-o') . '</p></div>';
    }

    $groupByMonth = !empty($attrs['groupByMonth']);
    $openFirst = !empty($attrs['openFirst']);
    $singleOpen = !empty($attrs['singleOpen']);

    $showImage = !empty($attrs['showImage']);
    $showVenue = !empty($attrs['showVenue']);
    $showOrganizer = !empty($attrs['showOrganizer']);
    $showPrice = !empty($attrs['showPrice']);
    $showMoreLink = isset($attrs['showMoreLink']) ? $attrs['showMoreLink'] : true;
    $showFilters = !empty($attrs['showFilters']);

    // Accent color override from block.
    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = $accentColor !== '' ? ' style="--event-o-block-accent:' . esc_attr($accentColor) . ';"' : '';
    $singleOpenAttr = ' data-single-open="' . ($singleOpen ? '1' : '0') . '"';

    $out = '<div class="event-o event-o-event-list' . ($showFilters ? ' has-filters' : '') . '"' . $styleAttr . $singleOpenAttr . '>';

    // Render filter bar if enabled.
    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $out .= event_o_render_filter_bar($filterTerms, $attrs);
    }

    $tz = wp_timezone();
    $currentMonthKey = null;
    $index = 0;

    while ($q->have_posts()) {
        $q->the_post();
        $postId = get_the_ID();

        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        $endTs = (int) get_post_meta($postId, EVENT_O_META_END_TS, true);
        $price = (string) get_post_meta($postId, EVENT_O_META_PRICE, true);
        $status = (string) get_post_meta($postId, EVENT_O_META_STATUS, true);

        // Backward compat for early builds.
        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }
        if ($endTs <= 0) {
            $endTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_END_TS, true);
        }
        if ($price === '') {
            $price = (string) get_post_meta($postId, EVENT_O_LEGACY_META_PRICE, true);
        }
        if ($status === '') {
            $status = (string) get_post_meta($postId, EVENT_O_LEGACY_META_STATUS, true);
        }

        $monthKey = '';
        $monthLabel = '';
        if ($startTs > 0) {
            $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);
            $monthKey = $start->format('Y-m');
            $monthLabel = event_o_get_german_month((int) $start->format('n')) . ' ' . $start->format('Y');
        }

        if ($groupByMonth && $monthKey && $monthKey !== $currentMonthKey) {
            $currentMonthKey = $monthKey;
            $out .= '<h3 class="event-o-month">' . esc_html(strtoupper($monthLabel)) . '</h3>';
        }

        $title = get_the_title();
        $permalink = get_permalink();
        $dtParts = event_o_format_datetime_german($startTs, $endTs);

        // Get category for display after title.
        $categoryName = event_o_get_first_term_name($postId, 'event_o_category');

        $venueData = $showVenue ? event_o_get_venue_data($postId) : null;
        $organizerData = $showOrganizer ? event_o_get_organizer_data($postId) : null;

        $openAttr = ($openFirst && $index === 0) ? ' open' : '';

        // Filter data attributes for client-side filtering.
        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';

        // Featured image URL.
        $imageUrl = '';
        if ($showImage && has_post_thumbnail($postId)) {
            $imageUrl = get_the_post_thumbnail_url($postId, 'large');
        }

        $out .= '<details class="event-o-accordion-item"' . $openAttr . $filterDataAttrs . '>';

        // Summary: Date on top, time below, then title with category.
        $out .= '<summary class="event-o-accordion-summary">';
        $out .= '<div class="event-o-when">';
        $out .= '<span class="event-o-date">' . esc_html($dtParts['date']) . '</span>';
        $out .= '<span class="event-o-time">' . esc_html($dtParts['time']) . '</span>';
        $out .= '</div>';
        $out .= '<div class="event-o-title-wrap">';
        $out .= '<span class="event-o-title">' . esc_html($title) . '</span>';
        if ($categoryName !== '') {
            $out .= ' <span class="event-o-category-hint">(' . esc_html($categoryName) . ')</span>';
        }
        $out .= '</div>';
        $out .= '<div class="event-o-chevron" aria-hidden="true"></div>';
        $out .= '</summary>';

        // Expanded panel.
        $out .= '<div class="event-o-accordion-panel">';

        // Content wrapper with grid layout.
        $out .= '<div class="event-o-panel-content">';

        // Left sidebar: organizer info + venue.
        $out .= '<aside class="event-o-sidebar">';

        if ($organizerData) {
            $out .= '<div class="event-o-organizer-card">';
            if (!empty($organizerData['logo'])) {
                $out .= '<div class="event-o-org-logo"><img src="' . esc_url($organizerData['logo']) . '" alt="' . esc_attr($organizerData['name']) . '"></div>';
            }
            $out .= '<div class="event-o-org-name">' . esc_html($organizerData['name']) . '</div>';

            $out .= '<h4 class="event-o-sidebar-title">' . esc_html__('VERANSTALTER', 'event-o') . '</h4>';

            if (!empty($organizerData['phone'])) {
                $out .= '<div class="event-o-org-row">';
                $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>';
                $out .= '<span class="event-o-label">TEL</span>';
                $out .= '<a href="tel:' . esc_attr($organizerData['phone']) . '">' . esc_html($organizerData['phone']) . '</a>';
                $out .= '</div>';
            }
            if (!empty($organizerData['email'])) {
                $out .= '<div class="event-o-org-row">';
                $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
                $out .= '<span class="event-o-label">E-MAIL</span>';
                $out .= '<a href="mailto:' . esc_attr($organizerData['email']) . '">' . esc_html($organizerData['email']) . '</a>';
                $out .= '</div>';
            }
            if (!empty($organizerData['website'])) {
                $out .= '<div class="event-o-org-row">';
                $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>';
                $out .= '<span class="event-o-label">WEBSITE</span>';
                $out .= '<a href="' . esc_url($organizerData['website']) . '" target="_blank" rel="noopener">' . esc_html(preg_replace('#^https?://#', '', $organizerData['website'])) . '</a>';
                $out .= '</div>';
            }

            // Social links with monochromatic icons + Instagram gradient text.
            if (!empty($organizerData['instagram']) || !empty($organizerData['facebook'])) {
                $out .= '<div class="event-o-org-social">';
                if (!empty($organizerData['instagram'])) {
                    $out .= '<a href="' . esc_url($organizerData['instagram']) . '" target="_blank" rel="noopener" class="event-o-social-link event-o-instagram-link">';
                    $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>';
                    $out .= '<span class="event-o-instagram-text">INSTAGRAM</span>';
                    $out .= '</a>';
                }
                if (!empty($organizerData['facebook'])) {
                    $out .= '<a href="' . esc_url($organizerData['facebook']) . '" target="_blank" rel="noopener" class="event-o-social-link event-o-facebook-link">';
                    $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
                    $out .= '<span>FACEBOOK</span>';
                    $out .= '</a>';
                }
                $out .= '</div>';
            }
            $out .= '</div>';
        }

        if ($venueData) {
            $out .= '<div class="event-o-venue-card">';
            $out .= '<h4 class="event-o-sidebar-title">' . esc_html__('ORT', 'event-o') . '</h4>';
            $out .= '<div class="event-o-venue-name">' . esc_html($venueData['name']) . '</div>';
            if (!empty($venueData['address'])) {
                $out .= '<div class="event-o-venue-address">' . nl2br(esc_html($venueData['address'])) . '</div>';
            }
            $out .= '</div>';
        }

        // Price card in sidebar
        if ($showPrice && $price !== '') {
            $out .= '<div class="event-o-price-card">';
            $out .= '<h4 class="event-o-sidebar-title">' . esc_html__('EINTRITT', 'event-o') . '</h4>';
            $out .= '<div class="event-o-price-value">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-2-1.46c-1.19.69-2 1.99-2 3.46s.81 2.77 2 3.46V18H4v-2.54c1.19-.69 2-1.99 2-3.46 0-1.48-.8-2.77-1.99-3.46L4 6h16v2.54zM11 15h2v2h-2zm0-4h2v2h-2zm0-4h2v2h-2z"/></svg>';
            $out .= '<span>' . esc_html($price) . '</span>';
            $out .= '</div>';
            $out .= '</div>';
        }

        $out .= '</aside>';

        // Right main content area with event image prominently displayed.
        $out .= '<div class="event-o-main">';

        // Prominent event image at top of content area.
        if ($imageUrl !== '') {
            $out .= '<div class="event-o-featured-image">';
            $out .= '<img src="' . esc_url($imageUrl) . '" alt="' . esc_attr($title) . '" loading="lazy">';
            $out .= '</div>';
        }

        // Content/excerpt.
        $excerpt = get_the_excerpt();
        $content_text = apply_filters('the_content', get_the_content());
        if ($content_text !== '' && trim(strip_tags($content_text)) !== '') {
            $out .= '<div class="event-o-content">' . wp_kses_post($content_text) . '</div>';
        } elseif ($excerpt !== '') {
            $out .= '<div class="event-o-excerpt">' . wp_kses_post(wpautop($excerpt)) . '</div>';
        }

        // Actions (MORE button is optional).
        if ($showMoreLink) {
            $out .= '<div class="event-o-actions">';
            $out .= '<a class="event-o-link" href="' . esc_url($permalink) . '">' . esc_html__('MORE', 'event-o') . '</a>';
            $out .= '</div>';
        }

        // Share this event section with actual buttons.
        $out .= '<div class="event-o-share-section">';
        $out .= '<span class="event-o-share-label">' . esc_html__('TEILE DIESE VERANSTALTUNG', 'event-o') . '</span>';
        $calendarData = [
            'postId' => $postId,
            'title' => $title,
            'startTs' => $startTs,
            'endTs' => $endTs,
            'description' => wp_strip_all_tags(get_the_excerpt()),
            'location' => $venueData ? $venueData['name'] . ($venueData['address'] ? ', ' . $venueData['address'] : '') : '',
        ];
        $out .= event_o_render_share_buttons($permalink, $title, $calendarData);
        $out .= '</div>';

        $out .= '</div>'; // .event-o-main

        $out .= '</div>'; // .event-o-panel-content
        $out .= '</div>'; // .event-o-accordion-panel
        $out .= '</details>';

        $index++;
    }

    wp_reset_postdata();

    $out .= '</div>';
    return $out;
}

function event_o_render_event_carousel_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    $q = event_o_event_query($attrs);
    if (!$q->have_posts()) {
        return '<div class="event-o event-o-carousel"><p class="event-o-empty">' . esc_html__('No events found.', 'event-o') . '</p></div>';
    }

    $slidesToShow = isset($attrs['slidesToShow']) ? max(1, min(6, (int) $attrs['slidesToShow'])) : 3;
    $showImage = !empty($attrs['showImage']);
    $showVenue = !empty($attrs['showVenue']);
    $showPrice = !empty($attrs['showPrice']);
    $showFilters = !empty($attrs['showFilters']);

    // Accent color override.
    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = '--event-o-slides:' . esc_attr((string) $slidesToShow) . ';';
    if ($accentColor !== '') {
        $styleAttr .= '--event-o-block-accent:' . esc_attr($accentColor) . ';';
    }

    $uid = 'event-o-carousel-' . wp_generate_uuid4();

    $out = '<div class="event-o event-o-carousel' . ($showFilters ? ' has-filters' : '') . '" id="' . esc_attr($uid) . '" data-slides="' . esc_attr((string) $slidesToShow) . '" style="' . $styleAttr . '">';

    // Render filter bar if enabled.
    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $out .= event_o_render_filter_bar($filterTerms, $attrs);
    }

    $out .= '<div class="event-o-carousel-header">';
    $out .= '<button type="button" class="event-o-carousel-nav" data-dir="prev" aria-label="' . esc_attr__('Previous', 'event-o') . '">←</button>';
    $out .= '<button type="button" class="event-o-carousel-nav" data-dir="next" aria-label="' . esc_attr__('Next', 'event-o') . '">→</button>';
    $out .= '</div>';

    $out .= '<div class="event-o-carousel-viewport" tabindex="0">';
    $out .= '<div class="event-o-carousel-track">';

    while ($q->have_posts()) {
        $q->the_post();
        $postId = get_the_ID();

        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        $endTs = (int) get_post_meta($postId, EVENT_O_META_END_TS, true);
        $price = (string) get_post_meta($postId, EVENT_O_META_PRICE, true);

        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }
        if ($endTs <= 0) {
            $endTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_END_TS, true);
        }
        if ($price === '') {
            $price = (string) get_post_meta($postId, EVENT_O_LEGACY_META_PRICE, true);
        }

        $title = get_the_title();
        $permalink = get_permalink();
        $dtParts = event_o_format_datetime_german($startTs, $endTs);
        $venueName = $showVenue ? event_o_get_first_term_name($postId, 'event_o_venue') : '';

        // Filter data attributes for client-side filtering.
        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';

        $out .= '<article class="event-o-card"' . $filterDataAttrs . '>';

        if ($showImage && has_post_thumbnail($postId)) {
            $out .= '<a class="event-o-card-media" href="' . esc_url($permalink) . '">' . get_the_post_thumbnail($postId, 'large', ['loading' => 'lazy']) . '</a>';
        }

        $out .= '<div class="event-o-card-body">';
        $out .= '<div class="event-o-card-when">' . esc_html($dtParts['date']) . ' · ' . esc_html($dtParts['time']) . '</div>';
        $out .= '<h3 class="event-o-card-title"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';

        $metaBits = [];
        if ($venueName !== '') {
            $metaBits[] = esc_html($venueName);
        }
        if ($showPrice && $price !== '') {
            $metaBits[] = esc_html($price);
        }
        if ($metaBits) {
            $out .= '<div class="event-o-card-meta">' . implode(' · ', $metaBits) . '</div>';
        }

        $out .= '</div>';
        $out .= '</article>';
    }

    wp_reset_postdata();

    $out .= '</div>';
    $out .= '</div>';
    $out .= '</div>';

    return $out;
}

function event_o_render_event_grid_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    $q = event_o_event_query($attrs);
    if (!$q->have_posts()) {
        return '<div class="event-o event-o-grid"><p class="event-o-empty">' . esc_html__('No events found.', 'event-o') . '</p></div>';
    }

    $columns = isset($attrs['columns']) ? max(1, min(4, (int) $attrs['columns'])) : 4;
    $showImage = !empty($attrs['showImage']);
    $showOrganizer = !empty($attrs['showOrganizer']);
    $showCategory = isset($attrs['showCategory']) ? $attrs['showCategory'] : true;
    $showVenue = !empty($attrs['showVenue']);
    $showPrice = !empty($attrs['showPrice']);
    $showFilters = !empty($attrs['showFilters']);

    // Accent color override.
    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = '--event-o-grid-cols:' . esc_attr((string) $columns) . ';';
    if ($accentColor !== '') {
        $styleAttr .= '--event-o-block-accent:' . esc_attr($accentColor) . ';';
    }

    $out = '<div class="event-o event-o-grid' . ($showFilters ? ' has-filters' : '') . '" style="' . $styleAttr . '">';

    // Render filter bar if enabled.
    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $out .= event_o_render_filter_bar($filterTerms, $attrs);
    }

    $out .= '<div class="event-o-grid-track">';

    $tz = wp_timezone();
    $eventCount = 0;

    while ($q->have_posts()) {
        $q->the_post();
        $postId = get_the_ID();
        $eventCount++;

        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }

        $price = '';
        if ($showPrice) {
            $price = (string) get_post_meta($postId, EVENT_O_META_PRICE, true);
            if ($price === '') {
                $price = (string) get_post_meta($postId, EVENT_O_LEGACY_META_PRICE, true);
            }
        }

        $title = get_the_title();
        $permalink = get_permalink();
        $organizerName = $showOrganizer ? event_o_get_first_term_name($postId, 'event_o_organizer') : '';
        $categoryName = $showCategory ? event_o_get_first_term_name($postId, 'event_o_category') : '';
        $venueName = $showVenue ? event_o_get_first_term_name($postId, 'event_o_venue') : '';

        // Filter data attributes for client-side filtering.
        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';

        // Date badge parts
        $dayNum = '';
        $monthName = '';
        $year = '';
        if ($startTs > 0) {
            $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);
            $dayNum = $start->format('j');
            $monthName = event_o_get_german_month((int) $start->format('n'));
            $year = $start->format('Y');
        }

        $out .= '<a href="' . esc_url($permalink) . '" class="event-o-grid-card"' . $filterDataAttrs . '>';

        // Image with date badge overlay
        $out .= '<div class="event-o-grid-media">';
        if ($showImage && has_post_thumbnail($postId)) {
            $out .= get_the_post_thumbnail($postId, 'large', ['loading' => 'lazy', 'class' => 'event-o-grid-img']);
        } else {
            $out .= '<div class="event-o-grid-placeholder"></div>';
        }
        // Date badge
        if ($dayNum !== '') {
            $out .= '<div class="event-o-grid-badge">';
            $out .= '<span class="event-o-grid-badge-day">' . esc_html($dayNum) . '.</span>';
            $out .= '<span class="event-o-grid-badge-month">' . esc_html($monthName) . '</span>';
            $out .= '<span class="event-o-grid-badge-year">' . esc_html($year) . '</span>';
            $out .= '</div>';
        }
        $out .= '</div>';

        // Card body
        $out .= '<div class="event-o-grid-body">';
        $out .= '<h3 class="event-o-grid-title">' . esc_html($title) . '</h3>';

        if ($organizerName !== '') {
            $out .= '<div class="event-o-grid-organizer">' . esc_html($organizerName) . '</div>';
        }
        if ($categoryName !== '') {
            $out .= '<div class="event-o-grid-category">' . esc_html($categoryName) . '</div>';
        }
        if ($venueName !== '') {
            $out .= '<div class="event-o-grid-venue">' . esc_html($venueName) . '</div>';
        }
        if ($showPrice && $price !== '') {
            $out .= '<div class="event-o-grid-price">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-2-1.46c-1.19.69-2 1.99-2 3.46s.81 2.77 2 3.46V18H4v-2.54c1.19-.69 2-1.99 2-3.46 0-1.48-.8-2.77-1.99-3.46L4 6h16v2.54zM11 15h2v2h-2zm0-4h2v2h-2zm0-4h2v2h-2z"/></svg>';
            $out .= '<span>' . esc_html($price) . '</span>';
            $out .= '</div>';
        }

        $out .= '</div>';
        $out .= '</a>';
    }

    wp_reset_postdata();

    $out .= '</div>'; // .event-o-grid-track

    // Dots navigation for mobile
    if ($eventCount > 1) {
        $out .= '<div class="event-o-grid-dots">';
        for ($i = 0; $i < $eventCount; $i++) {
            $activeClass = $i === 0 ? ' is-active' : '';
            $out .= '<button type="button" class="event-o-grid-dot' . $activeClass . '" data-index="' . $i . '" aria-label="' . esc_attr(sprintf(__('Go to event %d', 'event-o'), $i + 1)) . '"></button>';
        }
        $out .= '</div>';
    }

    $out .= '</div>'; // .event-o-grid

    return $out;
}

function event_o_render_event_program_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    // Load all events (not just perPage), we hide the rest client-side
    $programAttrs = $attrs;
    $perPage = isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 8;
    $programAttrs['perPage'] = 200; // Load all
    $q = event_o_event_query($programAttrs);
    if (!$q->have_posts()) {
        return '<div class="event-o event-o-program"><p class="event-o-empty">' . esc_html__('No events found.', 'event-o') . '</p></div>';
    }

    $perPage = isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 8;
    $showImage = !array_key_exists('showImage', $attrs) || !empty($attrs['showImage']);
    $showVenue = !array_key_exists('showVenue', $attrs) || !empty($attrs['showVenue']);
    $showCategory = !array_key_exists('showCategory', $attrs) || !empty($attrs['showCategory']);
    $showDescription = !array_key_exists('showDescription', $attrs) || !empty($attrs['showDescription']);
    $showCalendar = !array_key_exists('showCalendar', $attrs) || !empty($attrs['showCalendar']);
    $showShare = !array_key_exists('showShare', $attrs) || !empty($attrs['showShare']);
    $showBands = !array_key_exists('showBands', $attrs) || !empty($attrs['showBands']);
    $showPrice = !array_key_exists('showPrice', $attrs) || !empty($attrs['showPrice']);

    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = $accentColor !== '' ? ' style="--event-o-block-accent:' . esc_attr($accentColor) . ';"' : '';

    $tz = wp_timezone();
    $todayStart = (new DateTimeImmutable('now', $tz))->setTime(0, 0, 0)->getTimestamp();
    $todayEnd = (new DateTimeImmutable('now', $tz))->setTime(23, 59, 59)->getTimestamp();

    $out = '<div class="event-o event-o-program"' . $styleAttr . '>';

    $eventIndex = 0;

    while ($q->have_posts()) {
        $q->the_post();
        $postId = get_the_ID();
        $eventIndex++;

        $title = get_the_title();
        $permalink = get_permalink();

        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        $endTs = (int) get_post_meta($postId, EVENT_O_META_END_TS, true);
        $price = (string) get_post_meta($postId, EVENT_O_META_PRICE, true);
        $status = (string) get_post_meta($postId, EVENT_O_META_STATUS, true);
        $bandsRaw = (string) get_post_meta($postId, EVENT_O_META_BANDS, true);

        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }
        if ($endTs <= 0) {
            $endTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_END_TS, true);
        }
        if ($price === '') {
            $price = (string) get_post_meta($postId, EVENT_O_LEGACY_META_PRICE, true);
        }
        if ($status === '') {
            $status = (string) get_post_meta($postId, EVENT_O_LEGACY_META_STATUS, true);
        }

        $isToday = ($startTs >= $todayStart && $startTs <= $todayEnd);
        $dtParts = event_o_format_datetime_german($startTs, $endTs);

        $categoryName = $showCategory ? event_o_get_first_term_name($postId, 'event_o_category') : '';
        $venueData = $showVenue ? event_o_get_venue_data($postId) : null;

        // Parse bands: "Name | spotify | bandcamp" per line
        $bands = [];
        if ($showBands && $bandsRaw !== '') {
            $lines = array_filter(array_map('trim', explode("\n", $bandsRaw)));
            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));
                $bands[] = [
                    'name' => $parts[0] ?? '',
                    'spotify' => isset($parts[1]) && $parts[1] !== '' ? $parts[1] : '',
                    'bandcamp' => isset($parts[2]) && $parts[2] !== '' ? $parts[2] : '',
                ];
            }
        }

        $excerpt = '';
        if ($showDescription) {
            $excerpt = get_the_excerpt();
            if (empty($excerpt)) {
                $excerpt = wp_trim_words(get_the_content(), 35, '…');
            } else {
                $excerpt = wp_trim_words($excerpt, 35, '…');
            }
        }

        $hiddenClass = $eventIndex > $perPage ? ' is-hidden' : '';
        $todayClass = $isToday ? ' is-today' : '';

        $out .= '<article class="event-o-program-item' . $todayClass . $hiddenClass . '">';

        // === LEFT COLUMN ===
        $out .= '<div class="event-o-program-left">';

        // Date + Time
        $out .= '<div class="event-o-program-when">';
        if ($dtParts['date'] !== '') {
            $out .= '<span class="event-o-program-date">' . esc_html($dtParts['date']) . '</span>';
        }
        if ($dtParts['time'] !== '') {
            $out .= '<span class="event-o-program-time">' . esc_html($dtParts['time']) . '</span>';
        }
        $out .= '</div>';

        // Title
        $out .= '<h3 class="event-o-program-title"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';

        // Status badge
        if ($status !== '' && $status !== 'normal') {
            $statusLabels = [
                'cancelled' => __('ABGESAGT', 'event-o'),
                'postponed' => __('VERSCHOBEN', 'event-o'),
                'soldout' => __('AUSVERKAUFT', 'event-o'),
            ];
            $statusLabel = $statusLabels[$status] ?? mb_strtoupper($status);
            $out .= '<span class="event-o-program-status event-o-status-' . esc_attr($status) . '">' . esc_html($statusLabel) . '</span>';
        }

        // Image
        if ($showImage && has_post_thumbnail($postId)) {
            $out .= '<div class="event-o-program-image">';
            $out .= '<a href="' . esc_url($permalink) . '">' . get_the_post_thumbnail($postId, 'medium_large', ['loading' => 'lazy']) . '</a>';
            $out .= '</div>';
        }

        // Band links
        if (!empty($bands)) {
            $out .= '<div class="event-o-program-bands">';
            foreach ($bands as $band) {
                $out .= '<div class="event-o-program-band">';
                if ($band['name'] !== '') {
                    $out .= '<span class="event-o-band-name">' . esc_html($band['name']) . '</span>';
                }
                if ($band['spotify'] !== '') {
                    $out .= '<a href="' . esc_url($band['spotify']) . '" target="_blank" rel="noopener noreferrer" class="event-o-band-link event-o-band-spotify" title="Spotify">';
                    $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>';
                    $out .= '</a>';
                }
                if ($band['bandcamp'] !== '') {
                    $out .= '<a href="' . esc_url($band['bandcamp']) . '" target="_blank" rel="noopener noreferrer" class="event-o-band-link event-o-band-bandcamp" title="Bandcamp">';
                    $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M0 18.75l7.437-13.5H24l-7.438 13.5H0z"/></svg>';
                    $out .= '</a>';
                }
                $out .= '</div>';
            }
            $out .= '</div>';
        }

        // Share buttons
        if ($showShare) {
            $out .= '<div class="event-o-program-share">';
            $calendarDataForShare = [];
            if ($showCalendar) {
                $calendarDataForShare = [
                    'postId' => $postId,
                    'title' => $title,
                    'startTs' => $startTs,
                    'endTs' => $endTs,
                    'description' => wp_strip_all_tags($excerpt),
                    'location' => $venueData ? $venueData['name'] . ($venueData['address'] ? ', ' . $venueData['address'] : '') : '',
                ];
            }
            $out .= event_o_render_share_buttons($permalink, $title, $calendarDataForShare);
            $out .= '</div>';
        }

        $out .= '</div>'; // .event-o-program-left

        // === RIGHT COLUMN ===
        $out .= '<div class="event-o-program-right">';

        if ($categoryName !== '') {
            $out .= '<div class="event-o-program-category">' . esc_html(mb_strtoupper($categoryName)) . '</div>';
        }

        if ($venueData) {
            $out .= '<div class="event-o-program-venue">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
            $out .= '<span>' . esc_html($venueData['name']) . '</span>';
            $out .= '</div>';
        }

        if ($showPrice && $price !== '') {
            $out .= '<div class="event-o-program-price">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-2-1.46c-1.19.69-2 1.99-2 3.46s.81 2.77 2 3.46V18H4v-2.54c1.19-.69 2-1.99 2-3.46 0-1.48-.8-2.77-1.99-3.46L4 6h16v2.54zM11 15h2v2h-2zm0-4h2v2h-2zm0-4h2v2h-2z"/></svg>';
            $out .= '<span>' . esc_html($price) . '</span>';
            $out .= '</div>';
        }

        if ($showDescription && $excerpt !== '') {
            $out .= '<div class="event-o-program-desc">' . wp_kses_post($excerpt) . '</div>';
        }

        // Calendar save (standalone, without share buttons)
        if ($showCalendar && !$showShare) {
            $calendarData = [
                'postId' => $postId,
                'title' => $title,
                'startTs' => $startTs,
                'endTs' => $endTs,
                'description' => wp_strip_all_tags($excerpt),
                'location' => $venueData ? $venueData['name'] . ($venueData['address'] ? ', ' . $venueData['address'] : '') : '',
            ];

            $googleUrl = event_o_get_google_calendar_url($title, $startTs, $endTs, wp_strip_all_tags($excerpt), $venueData ? $venueData['name'] : '');
            $outlookUrl = event_o_get_outlook_calendar_url($title, $startTs, $endTs, wp_strip_all_tags($excerpt), $venueData ? $venueData['name'] : '');
            $icalUrl = event_o_get_ical_url($postId);

            $out .= '<div class="event-o-program-calendar">';
            $out .= '<div class="event-o-calendar-dropdown">';
            $out .= '<button type="button" class="event-o-share-btn event-o-share-calendar" aria-label="Zum Kalender hinzufügen" title="Zum Kalender hinzufügen">';
            $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>';
            $out .= '<span>In Kalender speichern</span>';
            $out .= '</button>';
            $out .= '<div class="event-o-calendar-menu">';
            $out .= '<a href="' . esc_url($googleUrl) . '" target="_blank" rel="noopener noreferrer" class="event-o-calendar-option">Google Kalender</a>';
            $out .= '<a href="' . esc_url($outlookUrl) . '" target="_blank" rel="noopener noreferrer" class="event-o-calendar-option">Outlook Kalender</a>';
            $out .= '<a href="' . esc_url($icalUrl) . '" class="event-o-calendar-option">iCal / Apple</a>';
            $out .= '</div>';
            $out .= '</div>';
            $out .= '</div>';
        }

        $out .= '</div>'; // .event-o-program-right

        $out .= '</article>';
    }

    wp_reset_postdata();

    // Load more button
    if ($q->found_posts > $perPage) {
        $out .= '<div class="event-o-program-loadmore-wrap">';
        $out .= '<button type="button" class="event-o-program-loadmore">' . esc_html__('Mehr laden', 'event-o') . '</button>';
        $out .= '</div>';
    }

    $out .= '</div>'; // .event-o-program

    return $out;
}

function event_o_render_event_hero_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    $q = event_o_event_query($attrs);
    if (!$q->have_posts()) {
        return '<div class="event-o event-o-hero"><p class="event-o-empty">' . esc_html__('No events found.', 'event-o') . '</p></div>';
    }

    $showFilters = !empty($attrs['showFilters']);
    $showDate = !array_key_exists('showDate', $attrs) || !empty($attrs['showDate']);
    $dateVariant = isset($attrs['dateVariant']) && $attrs['dateVariant'] === 'date-time' ? 'date-time' : 'date';
    $showDesc = !array_key_exists('showDesc', $attrs) || !empty($attrs['showDesc']);
    $showButton = !array_key_exists('showButton', $attrs) || !empty($attrs['showButton']);
    $buttonStyle = isset($attrs['buttonStyle']) ? $attrs['buttonStyle'] : 'rounded';
    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $contentIndent = isset($attrs['contentIndent']) ? max(0, (int) $attrs['contentIndent']) : 60;
    $heroHeight = isset($attrs['heroHeight']) ? max(520, min(720, (int) $attrs['heroHeight'])) : 520;
    $overlayColor = isset($attrs['overlayColor']) && $attrs['overlayColor'] === 'white' ? 'white' : 'black';
    $align = isset($attrs['align']) ? $attrs['align'] : '';
    $styleAttr = '';
    if ($accentColor !== '') {
        $styleAttr .= '--event-o-block-accent:' . esc_attr($accentColor) . ';';
    }
    $styleAttr .= '--event-o-hero-indent:' . esc_attr((string) $contentIndent) . 'px;';
    $styleAttr .= '--event-o-hero-height:' . esc_attr((string) $heroHeight) . 'px;';

    $uid = 'event-o-hero-' . wp_generate_uuid4();
    $alignClass = $align !== '' ? ' align' . esc_attr($align) : '';
    $overlayClass = $overlayColor === 'white' ? ' event-o-hero-overlay-white' : '';

    $out = '<div class="event-o event-o-hero' . $alignClass . $overlayClass . ($showFilters ? ' has-filters' : '') . '" id="' . esc_attr($uid) . '" style="' . $styleAttr . '">';

    // Render filter bar if enabled.
    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $out .= event_o_render_filter_bar($filterTerms, $attrs);
    }

    $out .= '<div class="event-o-hero-viewport">';
    $out .= '<div class="event-o-hero-track">';

    $eventCount = 0;
    while ($q->have_posts()) {
        $q->the_post();
        $postId = get_the_ID();
        $eventCount++;

        $title = get_the_title();
        $permalink = get_permalink();
        $categoryName = event_o_get_first_term_name($postId, 'event_o_category');
        if (empty($categoryName)) {
            $categoryName = __('VERANSTALTUNGEN', 'event-o');
        }

        $excerpt = get_the_excerpt();
        if (empty($excerpt)) {
            $excerpt = wp_trim_words(get_the_content(), 20, '...');
        } else {
            $excerpt = wp_trim_words($excerpt, 20, '...');
        }

        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }
        $heroDate = '';
        $heroTime = '';
        if ($showDate && $startTs > 0) {
            $endTs = (int) get_post_meta($postId, EVENT_O_META_END_TS, true);
            if ($endTs <= 0) {
                $endTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_END_TS, true);
            }
            $heroDateParts = event_o_format_datetime_german($startTs, $dateVariant === 'date-time' ? $endTs : 0);
            $heroDate = $heroDateParts['date'] ?? '';
            $heroTime = $dateVariant === 'date-time' ? ($heroDateParts['time'] ?? '') : '';
        }

        $imageUrl = '';
        if (has_post_thumbnail($postId)) {
            $imageUrl = get_the_post_thumbnail_url($postId, 'full');
        }

        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';

        $out .= '<div class="event-o-hero-slide"' . $filterDataAttrs . '>';
        if ($imageUrl !== '') {
            $out .= '<div class="event-o-hero-bg" style="background-image: url(\'' . esc_url($imageUrl) . '\');"></div>';
        } else {
            $out .= '<div class="event-o-hero-bg event-o-hero-bg-placeholder"></div>';
        }
        $out .= '<div class="event-o-hero-overlay"></div>';
        
        $out .= '<div class="event-o-hero-content">';
        $out .= '<div class="event-o-hero-category">' . esc_html(mb_strtoupper($categoryName)) . '</div>';
        if ($heroDate !== '') {
            $out .= '<div class="event-o-hero-date' . ($heroTime !== '' ? ' has-time' : '') . '">';
            $out .= '<span class="event-o-hero-date-main">' . esc_html($heroDate) . '</span>';
            if ($heroTime !== '') {
                $out .= '<span class="event-o-hero-date-time">' . esc_html($heroTime) . '</span>';
            }
            $out .= '</div>';
        }
        $out .= '<h2 class="event-o-hero-title">' . esc_html($title) . '</h2>';
        if ($showDesc) {
            $out .= '<div class="event-o-hero-desc">' . wp_kses_post($excerpt) . '</div>';
        }
        if ($showButton) {
            $btnClass = 'event-o-hero-btn';
            if ($buttonStyle === 'square') {
                $btnClass .= ' is-square';
            } elseif ($buttonStyle === 'outline') {
                $btnClass .= ' is-outline';
            }
            $out .= '<a href="' . esc_url($permalink) . '" class="' . esc_attr($btnClass) . '">' . esc_html__('Zu den Events', 'event-o') . '</a>';
        }
        $out .= '</div>'; // .event-o-hero-content
        $out .= '</div>'; // .event-o-hero-slide
    }

    wp_reset_postdata();

    $out .= '</div>'; // .event-o-hero-track
    $out .= '</div>'; // .event-o-hero-viewport

    // Dots navigation
    if ($eventCount > 1) {
        $out .= '<div class="event-o-hero-dots">';
        for ($i = 0; $i < $eventCount; $i++) {
            $activeClass = $i === 0 ? ' is-active' : '';
            $out .= '<button type="button" class="event-o-hero-dot' . $activeClass . '" data-index="' . $i . '" aria-label="' . esc_attr(sprintf(__('Go to slide %d', 'event-o'), $i + 1)) . '"></button>';
        }
        $out .= '</div>';
    }

    $out .= '</div>'; // .event-o-hero

    return $out;
}

/**
 * Get related events for single page (excluding current event).
 */
function event_o_get_related_events(int $excludeId, int $limit = 4): array
{
    $args = [
        'post_type' => 'event_o_event',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'post__not_in' => [$excludeId],
        'meta_key' => EVENT_O_META_START_TS,
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => EVENT_O_META_START_TS,
                'value' => time(),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ],
        ],
    ];

    $q = new WP_Query($args);
    $events = [];

    while ($q->have_posts()) {
        $q->the_post();
        $postId = get_the_ID();
        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }
        $dtParts = event_o_format_datetime_german($startTs, 0);

        $excerpt = get_the_excerpt();
        if (empty($excerpt)) {
            $excerpt = wp_trim_words(get_the_content(), 35, '...');
        } else {
            $excerpt = wp_trim_words($excerpt, 35, '...');
        }

        $events[] = [
            'id' => $postId,
            'title' => get_the_title(),
            'permalink' => get_permalink(),
            'date' => $dtParts['date'],
            'thumbnail' => has_post_thumbnail($postId) ? get_the_post_thumbnail_url($postId, 'medium') : '',
            'excerpt' => $excerpt,
        ];
    }

    wp_reset_postdata();

    return $events;
}
