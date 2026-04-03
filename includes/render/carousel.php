<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_render_event_carousel_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    event_o_ensure_frontend_assets();

    $queryAttrs = $attrs;
    if (!isset($queryAttrs['sortOrder']) || $queryAttrs['sortOrder'] === '' || $queryAttrs['sortOrder'] === 'auto') {
        $queryAttrs['sortOrder'] = 'asc';
    }

    $q = event_o_event_query($queryAttrs);
    if (!$q->have_posts()) {
        return '<div class="event-o event-o-carousel"><p class="event-o-empty">' . esc_html__('No events found.', 'event-o') . '</p></div>';
    }

    $slidesToShow = isset($attrs['slidesToShow']) ? max(1, min(6, (int) $attrs['slidesToShow'])) : 3;
    $showImage = !empty($attrs['showImage']);
    $showVenue = !empty($attrs['showVenue']);
    $showPrice = !empty($attrs['showPrice']);
    $showFilters = !empty($attrs['showFilters']);
    $showHighlightBadge = !empty($attrs['showHighlightBadge']);
    $hoverExcerptWords = isset($attrs['hoverExcerptWords']) ? max(10, min(80, (int) $attrs['hoverExcerptWords'])) : 32;
    $highlightColor = event_o_get_highlight_badge_style_value($attrs);

    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = '--event-o-slides:' . esc_attr((string) $slidesToShow) . ';';
    if ($accentColor !== '') {
        $styleAttr .= '--event-o-block-accent:' . esc_attr($accentColor) . ';';
    }

    $autoPlay = !empty($attrs['autoPlay']);
    $autoPlayInterval = isset($attrs['autoPlayInterval']) ? max(1, (int) $attrs['autoPlayInterval']) : 5;

    $uid = 'event-o-carousel-' . wp_generate_uuid4();

    $out = '<div class="event-o event-o-carousel' . ($showFilters ? ' has-filters' : '') . '" id="' . esc_attr($uid) . '" data-slides="' . esc_attr((string) $slidesToShow) . '"' . ($autoPlay ? ' data-autoplay="1" data-autoplay-interval="' . esc_attr((string) $autoPlayInterval) . '"' : '') . ' style="' . $styleAttr . '">';

    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $filterStyle = isset($attrs['filterStyle']) ? $attrs['filterStyle'] : 'dropdown';
        $out .= ($filterStyle === 'tabs') ? event_o_render_filter_bar_tabs($filterTerms, $attrs) : event_o_render_filter_bar($filterTerms, $attrs);
    }

    $out .= '<button type="button" class="event-o-carousel-nav" data-dir="prev" aria-label="' . esc_attr__('Previous', 'event-o') . '"><span class="event-o-carousel-nav-label" aria-hidden="true">&lt;</span></button>';
    $out .= '<button type="button" class="event-o-carousel-nav" data-dir="next" aria-label="' . esc_attr__('Next', 'event-o') . '"><span class="event-o-carousel-nav-label" aria-hidden="true">&gt;</span></button>';

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
        $dateSlots = event_o_get_all_date_slots($postId);
        $categoryMarkup = event_o_render_event_category_labels($postId, [
            'wrapper_class' => 'event-o-card-cats',
            'item_class' => 'event-o-card-category',
        ]);
        $venueName = $showVenue ? event_o_get_first_term_name($postId, 'event_o_venue') : '';
        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';

        $out .= '<article class="event-o-card"' . $filterDataAttrs . '>';

        $highlightBadgeHtml = '';
        if ($showHighlightBadge && event_o_is_event_highlight_active($postId)) {
            $highlightBadgeHtml = event_o_render_highlight_badge($highlightColor);
        }

        $badgeHtml = '';
        if (!empty($dateSlots) && isset($dateSlots[0]['start_ts']) && (int) $dateSlots[0]['start_ts'] > 0) {
            $badgeStart = (new DateTimeImmutable('@' . (int) $dateSlots[0]['start_ts']))->setTimezone(wp_timezone());
            $shortMonth = mb_strtoupper(mb_substr(event_o_get_german_month((int) $badgeStart->format('n')), 0, 3));
            $extraSlotCount = max(0, count($dateSlots) - 1);
            $badgeClass = 'event-o-card-badge';
            if ($extraSlotCount > 0) {
                $badgeClass .= ' has-extra-slots';
            }
            $badgeHtml = '<span class="' . esc_attr($badgeClass) . '">'
                . '<span class="event-o-card-badge-day">' . esc_html($badgeStart->format('j')) . '</span>'
                . '<span class="event-o-card-badge-month">' . esc_html($shortMonth) . '</span>'
                . ($extraSlotCount > 0 ? '<span class="event-o-card-badge-extra">+' . esc_html((string) $extraSlotCount) . '</span>' : '')
                . '</span>';
        }

        $excerptSource = get_the_excerpt($postId);
        if ($excerptSource === '') {
            $excerptSource = get_post_field('post_content', $postId);
        }
        $excerpt = wp_trim_words(wp_strip_all_tags((string) $excerptSource), $hoverExcerptWords, '...');

        if ($showImage) {
            $imageUrls = event_o_get_event_image_urls($postId, 'large');
            if (!empty($imageUrls)) {
                $out .= '<div class="event-o-card-media-wrap">';
                $out .= $badgeHtml;
                $out .= $highlightBadgeHtml;
                $out .= '<a class="event-o-card-media" href="' . esc_url($permalink) . '">';
                $out .= event_o_render_event_image_crossfade($imageUrls, 'event-o-card-media-inner', '', $title);
                $out .= '</a>';
                if ($excerpt !== '') {
                    $out .= '<div class="event-o-card-overlay"><p class="event-o-card-excerpt">' . esc_html($excerpt) . '</p></div>';
                }
                $out .= '</div>';
            } else {
                $out .= '<div class="event-o-card-media-wrap event-o-card-media-wrap--empty">' . $badgeHtml . $highlightBadgeHtml . '</div>';
            }
        } elseif ($badgeHtml !== '' || $highlightBadgeHtml !== '') {
            $out .= '<div class="event-o-card-media-wrap event-o-card-media-wrap--empty">' . $badgeHtml . $highlightBadgeHtml . '</div>';
        }

        $out .= '<div class="event-o-card-body">';
        $out .= '<div class="event-o-card-when">';
        foreach ($dateSlots as $slot) {
            $out .= '<div class="event-o-date-slot">' . esc_html($slot['formatted']) . '</div>';
        }
        $out .= '</div>';
        if ($categoryMarkup !== '') {
            $out .= $categoryMarkup;
        }
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
