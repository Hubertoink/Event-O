<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_render_event_grid_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    event_o_ensure_frontend_assets();

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
    $showHighlightBadge = !empty($attrs['showHighlightBadge']);
    $highlightColor = event_o_get_highlight_badge_style_value($attrs);

    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = '--event-o-grid-cols:' . esc_attr((string) $columns) . ';';
    if ($accentColor !== '') {
        $styleAttr .= '--event-o-block-accent:' . esc_attr($accentColor) . ';';
    }

    $out = '<div class="event-o event-o-grid' . ($showFilters ? ' has-filters' : '') . '" style="' . $styleAttr . '">';

    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $filterStyle = isset($attrs['filterStyle']) ? $attrs['filterStyle'] : 'dropdown';
        $out .= ($filterStyle === 'tabs') ? event_o_render_filter_bar_tabs($filterTerms, $attrs) : event_o_render_filter_bar($filterTerms, $attrs);
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
        $endTs = (int) get_post_meta($postId, EVENT_O_META_END_TS, true);
        if ($endTs <= 0) {
            $endTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_END_TS, true);
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
        $categoryMarkup = $showCategory ? event_o_render_event_category_labels($postId, [
            'wrapper_class' => 'event-o-grid-cats',
            'item_class' => 'event-o-grid-category',
        ]) : '';
        $venueName = $showVenue ? event_o_get_first_term_name($postId, 'event_o_venue') : '';
        $excerpt = get_the_excerpt();
        if ($excerpt === '') {
            $excerpt = wp_trim_words(wp_strip_all_tags(get_the_content()), 20, '...');
        } else {
            $excerpt = wp_trim_words($excerpt, 20, '...');
        }

        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';
        $dateSlots = event_o_get_all_date_slots($postId);
        $dayNum = '';
        $monthName = '';
        $year = '';
        $extraSlotCount = max(0, count($dateSlots) - 1);
        if ($startTs > 0) {
            $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);
            $dayNum = $start->format('j');
            $monthName = event_o_get_german_month((int) $start->format('n'));
            $year = $start->format('Y');
        }

        $out .= '<a href="' . esc_url($permalink) . '" class="event-o-grid-card"' . $filterDataAttrs . '>';
        $out .= '<div class="event-o-grid-media">';
        if ($showHighlightBadge && event_o_is_event_highlight_active($postId)) {
            $out .= event_o_render_highlight_badge($highlightColor);
        }
        if ($showImage) {
            $imageUrls = event_o_get_event_image_urls($postId, 'large');
            if (!empty($imageUrls)) {
                $out .= event_o_render_event_image_crossfade($imageUrls, 'event-o-grid-fade', 'event-o-grid-img', $title);
                if ($excerpt !== '') {
                    $out .= '<div class="event-o-grid-overlay">';
                    $out .= '<p class="event-o-grid-excerpt">' . esc_html($excerpt) . '</p>';
                    $out .= '</div>';
                }
            } else {
                $out .= '<div class="event-o-grid-placeholder"></div>';
            }
        } else {
            $out .= '<div class="event-o-grid-placeholder"></div>';
        }
        if ($dayNum !== '') {
            $badgeClass = 'event-o-grid-badge';
            if ($extraSlotCount > 0) {
                $badgeClass .= ' has-extra-slots';
            }
            $out .= '<div class="' . esc_attr($badgeClass) . '">';
            $out .= '<span class="event-o-grid-badge-day">' . esc_html($dayNum) . '.</span>';
            $out .= '<span class="event-o-grid-badge-month">' . esc_html($monthName) . '</span>';
            if ($extraSlotCount > 0) {
                $out .= '<span class="event-o-grid-badge-end">+' . $extraSlotCount . ' ' . esc_html__('Termin(e)', 'event-o') . '</span>';
            }
            $out .= '<span class="event-o-grid-badge-year">' . esc_html($year) . '</span>';
            $out .= '</div>';
        }
        $out .= '</div>';

        $out .= '<div class="event-o-grid-body">';
        $out .= '<h3 class="event-o-grid-title">' . esc_html($title) . '</h3>';

        if ($organizerName !== '') {
            $out .= '<div class="event-o-grid-organizer">' . esc_html($organizerName) . '</div>';
        }
        if ($categoryMarkup !== '') {
            $out .= $categoryMarkup;
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

    $out .= '</div>';

    if ($eventCount > 1) {
        $out .= '<div class="event-o-grid-dots">';
        for ($i = 0; $i < $eventCount; $i++) {
            $activeClass = $i === 0 ? ' is-active' : '';
            $out .= '<button type="button" class="event-o-grid-dot' . $activeClass . '" data-index="' . $i . '" aria-label="' . esc_attr(sprintf(__('Go to event %d', 'event-o'), $i + 1)) . '"></button>';
        }
        $out .= '</div>';
    }

    $out .= '</div>';

    return $out;
}
