<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_render_event_program_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    event_o_ensure_frontend_assets();

    $programAttrs = $attrs;
    $perPage = isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 8;
    $programAttrs['perPage'] = 200;
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

    $showHighlightBadge = !empty($attrs['showHighlightBadge']);
    $highlightColor = event_o_get_highlight_badge_style_value($attrs);
    $preferHighlights = !array_key_exists('preferHighlights', $attrs) || !empty($attrs['preferHighlights']);

    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = $accentColor !== '' ? ' style="--event-o-block-accent:' . esc_attr($accentColor) . ';"' : '';
    $highContrast = (bool) get_option(EVENT_O_OPTION_HIGH_CONTRAST, false);
    $hcClass = $highContrast ? ' is-high-contrast' : '';
    $animationType = isset($attrs['animation']) ? $attrs['animation'] : 'none';
    $animAttr = $animationType !== 'none' ? ' data-animation="' . esc_attr($animationType) . '"' : '';
    $showFilters = !empty($attrs['showFilters']);

    $tz = wp_timezone();
    $todayStart = (new DateTimeImmutable('now', $tz))->setTime(0, 0, 0)->getTimestamp();
    $todayEnd = (new DateTimeImmutable('now', $tz))->setTime(23, 59, 59)->getTimestamp();

    $out = '<div class="event-o event-o-program' . $hcClass . ($showFilters ? ' has-filters' : '') . '"' . $styleAttr . $animAttr . '>';

    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $filterStyle = isset($attrs['filterStyle']) ? $attrs['filterStyle'] : 'dropdown';
        $out .= ($filterStyle === 'tabs') ? event_o_render_filter_bar_tabs($filterTerms, $attrs) : event_o_render_filter_bar($filterTerms, $attrs);
    }

    $allPosts = [];
    while ($q->have_posts()) {
        $q->the_post();
        $pid = get_the_ID();
        $sts = (int) get_post_meta($pid, EVENT_O_META_START_TS, true);
        if ($sts <= 0) {
            $sts = (int) get_post_meta($pid, EVENT_O_LEGACY_META_START_TS, true);
        }
        $allPosts[] = ['id' => $pid, 'start' => $sts];
    }
    usort($allPosts, function ($a, $b) use ($todayStart, $todayEnd, $preferHighlights) {
        if ($preferHighlights) {
            $aHighlighted = event_o_is_event_highlight_active((int) $a['id']) ? 1 : 0;
            $bHighlighted = event_o_is_event_highlight_active((int) $b['id']) ? 1 : 0;
            if ($aHighlighted !== $bHighlighted) {
                return $bHighlighted - $aHighlighted;
            }
        }

        $aToday = ($a['start'] >= $todayStart && $a['start'] <= $todayEnd) ? 1 : 0;
        $bToday = ($b['start'] >= $todayStart && $b['start'] <= $todayEnd) ? 1 : 0;
        if ($aToday !== $bToday) {
            return $bToday - $aToday;
        }
        return $a['start'] - $b['start'];
    });

    $eventIndex = 0;

    foreach ($allPosts as $postItem) {
        $postId = $postItem['id'];
        $postObj = get_post($postId);
        setup_postdata($GLOBALS['post'] = $postObj);
        $eventIndex++;

        $title = get_the_title();
        $permalink = get_permalink();

        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        $endTs = (int) get_post_meta($postId, EVENT_O_META_END_TS, true);
        $price = (string) get_post_meta($postId, EVENT_O_META_PRICE, true);
        $status = event_o_get_event_status($postId);
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
        $isToday = ($startTs >= $todayStart && $startTs <= $todayEnd);
        $dateSlots = event_o_get_all_date_slots($postId);

        $categoryMarkup = $showCategory ? event_o_render_event_category_labels($postId, [
            'wrapper_class' => 'event-o-program-cats',
            'item_class' => 'event-o-program-category',
            'uppercase' => true,
        ]) : '';
        $venueData = $showVenue ? event_o_get_venue_data($postId) : null;
        $showOrgDescription = (bool) get_option(EVENT_O_OPTION_SHOW_ORG_DESCRIPTION, false);
        $organizerData = $showOrgDescription ? event_o_get_organizer_data($postId) : null;

        $bands = [];
        if ($showBands && $bandsRaw !== '') {
            $lines = array_filter(array_map('trim', explode("\n", $bandsRaw)));
            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));
                $bands[] = [
                    'name' => $parts[0] ?? '',
                    'spotify' => isset($parts[1]) && $parts[1] !== '' ? $parts[1] : '',
                    'bandcamp' => isset($parts[2]) && $parts[2] !== '' ? $parts[2] : '',
                    'website' => isset($parts[3]) && $parts[3] !== '' ? $parts[3] : '',
                ];
            }
        }

        $excerpt = '';
        if ($showDescription) {
            $rawContent = get_the_content(null, false, $postObj);
            $rawContent = strip_shortcodes($rawContent);
            $rawContent = apply_filters('the_content', $rawContent);
            $fullText = wp_strip_all_tags($rawContent);
            $fullText = trim(preg_replace('/\s+/', ' ', $fullText));

            $words = preg_split('/\s+/', $fullText);
            $wordCount = count($words);
            if ($wordCount > 60) {
                $shortText = implode(' ', array_slice($words, 0, 60)) . '…';
            } else {
                $shortText = $fullText;
            }
        }

        $hiddenClass = $eventIndex > $perPage ? ' is-hidden' : '';
        $todayClass = $isToday ? ' is-today' : '';
        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';

        $out .= '<article class="event-o-program-item eo-block-anim' . $todayClass . $hiddenClass . '"' . $filterDataAttrs . '>';

        if ($isToday) {
            $out .= '<div class="event-o-program-heute">' . esc_html__('HEUTE', 'event-o') . '</div>';
        }

        $out .= '<div class="event-o-program-left">';

        if (!empty($dateSlots)) {
            $weekdayNames = ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
            $wdIndex = (int) (new DateTimeImmutable('@' . (int) $dateSlots[0]['start_ts']))->setTimezone($tz)->format('w');
            $out .= '<div class="event-o-program-weekday">' . esc_html($weekdayNames[$wdIndex]) . '</div>';
        }

        $out .= '<div class="event-o-program-when">';
        foreach ($dateSlots as $slot) {
            $slotStart = (new DateTimeImmutable('@' . (int) $slot['start_ts']))->setTimezone($tz);
            $slotDateStr = $slotStart->format('j') . '. ' . event_o_get_german_month((int) $slotStart->format('n')) . ' ' . $slotStart->format('Y');
            $slotTimeStr = $slotStart->format('H:i');
            if (!empty($slot['end_ts']) && $slot['end_ts'] > 0) {
                $slotEnd = (new DateTimeImmutable('@' . (int) $slot['end_ts']))->setTimezone($tz);
                $slotTimeStr .= ' – ' . $slotEnd->format('H:i');
            }
            $slotTimeStr .= ' Uhr';
            $out .= '<div class="event-o-date-slot">';
            $out .= '<span class="event-o-date-slot-date">' . esc_html($slotDateStr) . '</span>';
            $out .= '<span class="event-o-date-slot-time">' . esc_html($slotTimeStr) . '</span>';
            $out .= '</div>';
        }
        $out .= '</div>';

        $primarySlot = !empty($dateSlots) ? $dateSlots[0] : null;
        $doorTime = $primarySlot ? wp_date('H:i', (int) $primarySlot['start_ts'], $tz) : '';
        $beginTime = $primarySlot && !empty($primarySlot['begin_time']) ? (string) $primarySlot['begin_time'] : '';

        $out .= '<h3 class="event-o-program-title"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';

        if ($status !== '' && $status !== 'normal') {
            $statusLabel = event_o_get_event_status_label($postId, $status);
            $out .= '<span class="event-o-program-status event-o-status-' . esc_attr($status) . '">' . esc_html($statusLabel) . '</span>';
        }

        if ($showImage) {
            $imageUrls = event_o_get_event_image_urls($postId, 'medium_large');
        }
        if ($showImage && !empty($imageUrls)) {
            $out .= '<div class="event-o-program-image">';
            if ($showHighlightBadge && event_o_is_event_highlight_active($postId)) {
                $out .= event_o_render_highlight_badge($highlightColor);
            }
            $out .= '<a href="' . esc_url($permalink) . '">';
            $out .= event_o_render_event_image_crossfade($imageUrls, 'event-o-program-image-fade', '', $title);
            $out .= '</a>';
            $out .= '</div>';
        }

        if ($showShare) {
            $out .= '<div class="event-o-program-share">';
            $calendarDataForShare = [];
            if ($showCalendar) {
                $calendarDataForShare = [
                    'postId' => $postId,
                    'title' => $title,
                    'startTs' => $startTs,
                    'endTs' => $endTs,
                    'description' => wp_strip_all_tags($fullText ?? ''),
                    'location' => $venueData ? $venueData['name'] . ($venueData['address'] ? ', ' . $venueData['address'] : '') : '',
                ];
            }
            $out .= event_o_render_share_buttons($permalink, $title, $calendarDataForShare);
            $out .= '</div>';
        }

        $out .= '</div>';
        $out .= '<div class="event-o-program-right">';

        if ($categoryMarkup !== '') {
            $out .= $categoryMarkup;
        }

        if ($venueData) {
            $out .= '<div class="event-o-program-venue">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
            $out .= '<span class="event-o-program-venue-text">';
            $out .= '<span class="event-o-program-venue-name">' . esc_html($venueData['name']) . '</span>';
            if (!empty($venueData['address'])) {
                $out .= '<span class="event-o-program-venue-address">' . esc_html($venueData['address']) . '</span>';
            }
            $out .= '</span>';
            $out .= '</div>';
        }

        if ($beginTime !== '') {
            $out .= '<div class="event-o-program-schedule">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>';
            $out .= '<span class="event-o-program-schedule-text">';
            if ($doorTime !== '') {
                $out .= '<span>' . esc_html__('Einlass', 'event-o') . ' ' . esc_html($doorTime) . ' ' . esc_html__('Uhr', 'event-o') . '</span>';
            }
            $out .= '<span>' . esc_html__('Beginn', 'event-o') . ' ' . esc_html($beginTime) . ' ' . esc_html__('Uhr', 'event-o') . '</span>';
            $out .= '</span>';
            $out .= '</div>';
        }

        if ($showPrice && $price !== '') {
            $out .= '<div class="event-o-program-price">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-2-1.46c-1.19.69-2 1.99-2 3.46s.81 2.77 2 3.46V18H4v-2.54c1.19-.69 2-1.99 2-3.46 0-1.48-.8-2.77-1.99-3.46L4 6h16v2.54zM11 15h2v2h-2zm0-4h2v2h-2zm0-4h2v2h-2z"/></svg>';
            $out .= '<span>' . esc_html($price) . '</span>';
            $out .= '</div>';
        }

        $out .= event_o_render_referenced_event_card($postId, [
            'wrapper_class' => 'event-o-reference-card-wrap event-o-reference-card-wrap-program',
            'card_class' => 'event-o-reference-card event-o-reference-card-program',
            'label' => __('Verweist auf Event', 'event-o'),
            'title_tag' => 'h4',
        ]);

        if ($showDescription && !empty($fullText)) {
            if ($wordCount > 60) {
                $out .= '<div class="event-o-program-desc event-o-desc-expandable">';
                $out .= '<div class="event-o-desc-inner">';
                $out .= '<span class="event-o-desc-short">' . esc_html($shortText) . '</span>';
                $out .= '<span class="event-o-desc-full">' . esc_html($fullText) . '</span>';
                $out .= '</div>';
                $out .= '<button type="button" class="event-o-desc-toggle">' . esc_html__('mehr…', 'event-o') . '</button>';
                $out .= '</div>';
            } else {
                $out .= '<div class="event-o-program-desc">' . esc_html($fullText) . '</div>';
            }
        }

        if ($showOrgDescription && $organizerData && !empty($organizerData['description'])) {
            $out .= '<div class="event-o-org-description">';
            $out .= '<div class="event-o-org-description-inner">';
            $out .= '<span class="event-o-org-description-label">' . esc_html($organizerData['name']) . '</span>';
            $orgDesc = $organizerData['description'];
            $orgDescHtml = preg_match('/<(p|h[1-6]|ul|ol|div|blockquote)[\s>]/i', $orgDesc) ? $orgDesc : wpautop($orgDesc);
            $out .= '<div class="event-o-org-description-text">' . wp_kses_post($orgDescHtml) . '</div>';
            $out .= '</div>';
            $out .= '</div>';
        }

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
                if ($band['website'] !== '') {
                    $out .= '<a href="' . esc_url($band['website']) . '" target="_blank" rel="noopener noreferrer" class="event-o-band-link event-o-band-website" title="Website">';
                    $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
                    $out .= '</a>';
                }
                $out .= '</div>';
            }
            $out .= '</div>';
        }

        if ($showCalendar && !$showShare) {
            $calendarData = [
                'postId' => $postId,
                'title' => $title,
                'startTs' => $startTs,
                'endTs' => $endTs,
                'description' => wp_strip_all_tags($fullText ?? ''),
                'location' => $venueData ? $venueData['name'] . ($venueData['address'] ? ', ' . $venueData['address'] : '') : '',
            ];

            $googleUrl = event_o_get_google_calendar_url($title, $startTs, $endTs, wp_strip_all_tags($fullText ?? ''), $venueData ? $venueData['name'] : '');
            $outlookUrl = event_o_get_outlook_calendar_url($title, $startTs, $endTs, wp_strip_all_tags($fullText ?? ''), $venueData ? $venueData['name'] : '');
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

        $out .= '</div>';
        $out .= '</article>';
    }

    wp_reset_postdata();

    if ($q->found_posts > $perPage) {
        $out .= '<div class="event-o-program-loadmore-wrap">';
        $out .= '<button type="button" class="event-o-program-loadmore">' . esc_html__('Mehr laden', 'event-o') . '</button>';
        $out .= '</div>';
    }

    $out .= '</div>';

    return $out;
}
