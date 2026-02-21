<?php
/**
 * iCal Download Handler
 *
 * Generates and serves .ics files for calendar integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the iCal download endpoint.
 */
function event_o_register_ical_endpoint(): void
{
    add_rewrite_rule(
        '^event-o-ical/([0-9]+)/?$',
        'index.php?event_o_ical_download=$matches[1]',
        'top'
    );
}
add_action('init', 'event_o_register_ical_endpoint');

/**
 * Register query var.
 */
function event_o_ical_query_vars(array $vars): array
{
    $vars[] = 'event_o_ical_download';
    return $vars;
}
add_filter('query_vars', 'event_o_ical_query_vars');

/**
 * Handle iCal download request.
 */
function event_o_handle_ical_download(): void
{
    $postId = (int) get_query_var('event_o_ical_download', 0);
    
    if ($postId <= 0) {
        return;
    }
    
    $post = get_post($postId);
    
    if (!$post || $post->post_type !== 'event_o_event' || $post->post_status !== 'publish') {
        wp_die(__('Event nicht gefunden.', 'event-o'), 404);
    }
    
    // Get event data
    $title = get_the_title($postId);
    
    // Get all date slots
    $dateSlots = event_o_get_all_date_slots($postId);
    
    if (empty($dateSlots)) {
        wp_die(__('Event hat kein gültiges Datum.', 'event-o'), 400);
    }
    
    $description = wp_strip_all_tags(get_the_excerpt($postId));
    $url = get_permalink($postId);
    
    // Get venue
    $location = '';
    $venueTerms = wp_get_post_terms($postId, 'event_o_venue');
    if (!is_wp_error($venueTerms) && !empty($venueTerms)) {
        $venueTerm = $venueTerms[0];
        $location = $venueTerm->name;
        $address = get_term_meta($venueTerm->term_id, 'event_o_venue_address', true);
        if ($address) {
            $location .= ', ' . $address;
        }
    }
    
    // Generate iCal content with multiple VEVENTs for multi-date events
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Event_O//Event_O Plugin//DE\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "METHOD:PUBLISH\r\n";
    
    foreach ($dateSlots as $i => $slot) {
        $startTs = $slot['start_ts'];
        $endTs = $slot['end_ts'] > 0 ? $slot['end_ts'] : $startTs + 7200;
        
        $start = gmdate('Ymd\THis\Z', $startTs);
        $end = gmdate('Ymd\THis\Z', $endTs);
        $now = gmdate('Ymd\THis\Z');
        $uid = 'event-o-' . $postId . '-' . ($i + 1) . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
        
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $uid . "\r\n";
        $ical .= "DTSTAMP:" . $now . "\r\n";
        $ical .= "DTSTART:" . $start . "\r\n";
        $ical .= "DTEND:" . $end . "\r\n";
        $eventTitle = count($dateSlots) > 1 ? $title . ' (Termin ' . ($i + 1) . ')' : $title;
        $ical .= "SUMMARY:" . event_o_ical_fold(event_o_ical_escape_text($eventTitle)) . "\r\n";
        
        if ($description) {
            $ical .= "DESCRIPTION:" . event_o_ical_fold(event_o_ical_escape_text($description)) . "\r\n";
        }
        if ($location) {
            $ical .= "LOCATION:" . event_o_ical_fold(event_o_ical_escape_text($location)) . "\r\n";
        }
        if ($url) {
            $ical .= "URL:" . $url . "\r\n";
        }
        
        $ical .= "END:VEVENT\r\n";
    }
    
    $ical .= "END:VCALENDAR\r\n";
    
    // Generate safe filename
    $filename = sanitize_file_name($title) . '.ics';
    
    // Send headers
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($ical));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    echo $ical;
    exit;
}
add_action('template_redirect', 'event_o_handle_ical_download');

/**
 * Generate iCal content.
 */
function event_o_generate_ical(string $title, int $startTs, int $endTs, string $description, string $location, string $url, int $postId): string
{
    $start = gmdate('Ymd\THis\Z', $startTs);
    $end = gmdate('Ymd\THis\Z', $endTs);
    $now = gmdate('Ymd\THis\Z');
    $uid = 'event-o-' . $postId . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
    
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Event_O//Event_O Plugin//DE\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "METHOD:PUBLISH\r\n";
    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:" . $uid . "\r\n";
    $ical .= "DTSTAMP:" . $now . "\r\n";
    $ical .= "DTSTART:" . $start . "\r\n";
    $ical .= "DTEND:" . $end . "\r\n";
    $ical .= "SUMMARY:" . event_o_ical_fold(event_o_ical_escape_text($title)) . "\r\n";
    
    if ($description) {
        $ical .= "DESCRIPTION:" . event_o_ical_fold(event_o_ical_escape_text($description)) . "\r\n";
    }
    if ($location) {
        $ical .= "LOCATION:" . event_o_ical_fold(event_o_ical_escape_text($location)) . "\r\n";
    }
    if ($url) {
        $ical .= "URL:" . $url . "\r\n";
    }
    
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";
    
    return $ical;
}

/**
 * Escape text for iCal format.
 */
function event_o_ical_escape_text(string $str): string
{
    // Replace problematic characters
    $str = str_replace(['\\', ';', ',', "\r\n", "\r", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'], $str);
    return $str;
}

/**
 * Fold long lines per iCal spec (max 75 octets per line).
 */
function event_o_ical_fold(string $str): string
{
    // For simplicity, just return as-is if short enough
    if (strlen($str) <= 60) {
        return $str;
    }
    
    // Break into chunks and fold with space prefix
    $lines = [];
    $current = '';
    $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach ($chars as $char) {
        if (strlen($current . $char) > 60) {
            $lines[] = $current;
            $current = $char;
        } else {
            $current .= $char;
        }
    }
    
    if ($current !== '') {
        $lines[] = $current;
    }
    
    return implode("\r\n ", $lines);
}

/**
 * Get the iCal download URL for an event.
 */
function event_o_get_ical_url(int $postId): string
{
    return home_url('/event-o-ical/' . $postId . '/');
}

/**
 * Flush rewrite rules on activation.
 */
function event_o_ical_flush_rules(): void
{
    event_o_register_ical_endpoint();
    flush_rewrite_rules();
}
