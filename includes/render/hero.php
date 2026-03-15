<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_render_event_hero_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    event_o_ensure_frontend_assets();

    $posts = event_o_get_hero_display_posts($attrs);
    if (empty($posts)) {
        return '<div class="event-o event-o-hero"><p class="event-o-empty">' . esc_html__('No events found.', 'event-o') . '</p></div>';
    }

    $showFilters = !empty($attrs['showFilters']);
    $onePerCategory = !empty($attrs['onePerCategory']);
    $preferHighlights = !array_key_exists('preferHighlights', $attrs) || !empty($attrs['preferHighlights']);
    $highlightColor = event_o_get_highlight_badge_style_value($attrs);
    $showDate = !array_key_exists('showDate', $attrs) || !empty($attrs['showDate']);
    $dateVariant = isset($attrs['dateVariant']) && $attrs['dateVariant'] === 'date-time' ? 'date-time' : 'date';
    $showDesc = !array_key_exists('showDesc', $attrs) || !empty($attrs['showDesc']);
    $descWordLimit = isset($attrs['descWordLimit']) ? max(5, min(60, (int) $attrs['descWordLimit'])) : 20;
    $showButton = !array_key_exists('showButton', $attrs) || !empty($attrs['showButton']);
    $buttonStyle = isset($attrs['buttonStyle']) ? $attrs['buttonStyle'] : 'rounded';
    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $heroHeight = isset($attrs['heroHeight']) ? max(520, min(720, (int) $attrs['heroHeight'])) : 520;
    $overlayColor = isset($attrs['overlayColor']) && $attrs['overlayColor'] === 'white' ? 'white' : 'black';
    $autoPlay = !isset($attrs['autoPlay']) || !empty($attrs['autoPlay']);
    $autoPlayInterval = isset($attrs['autoPlayInterval']) ? max(2, min(15, (int) $attrs['autoPlayInterval'])) : 5;
    $align = isset($attrs['align']) ? $attrs['align'] : '';
    $styleAttr = '';
    if ($accentColor !== '') {
        $styleAttr .= '--event-o-block-accent:' . esc_attr($accentColor) . ';';
    }
    $styleAttr .= '--event-o-hero-height:' . esc_attr((string) $heroHeight) . 'px;';

    $uid = 'event-o-hero-' . wp_generate_uuid4();
    $alignClass = $align !== '' ? ' align' . esc_attr($align) : '';
    $overlayClass = $overlayColor === 'white' ? ' event-o-hero-overlay-white' : '';

    $dataAttrs = '';
    if ($autoPlay) {
        $dataAttrs .= ' data-autoplay="1" data-autoplay-interval="' . esc_attr((string) $autoPlayInterval) . '"';
    }

    $out = '<div class="event-o event-o-hero' . $alignClass . $overlayClass . ($showFilters ? ' has-filters' : '') . '" id="' . esc_attr($uid) . '" style="' . $styleAttr . '"' . $dataAttrs . '>';

    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms_from_posts($posts, $attrs);
        $filterStyle = isset($attrs['filterStyle']) ? $attrs['filterStyle'] : 'dropdown';
        $out .= ($filterStyle === 'tabs') ? event_o_render_filter_bar_tabs($filterTerms, $attrs) : event_o_render_filter_bar($filterTerms, $attrs);
    }

    $out .= '<div class="event-o-hero-viewport">';
    $out .= '<div class="event-o-hero-track">';

    $eventCount = 0;
    $seenCategories = [];
    foreach ($posts as $post) {
        $postId = $post->ID;

        $categoryName = event_o_get_first_term_name($postId, 'event_o_category');
        $categoryMarkup = event_o_render_event_category_labels($postId, [
            'wrapper_class' => 'event-o-hero-cats',
            'item_class' => 'event-o-hero-category',
            'uppercase' => true,
            'fallback' => __('VERANSTALTUNGEN', 'event-o'),
        ]);

        if ($onePerCategory && $categoryName !== '') {
            if (in_array($categoryName, $seenCategories, true)) {
                continue;
            }
            $seenCategories[] = $categoryName;
        }

        $eventCount++;

        $title = get_the_title($postId);
        $permalink = get_permalink($postId);

        $excerpt = get_the_excerpt($postId);
        if (empty($excerpt)) {
            $excerpt = wp_trim_words((string) get_post_field('post_content', $postId), $descWordLimit, '…');
        } else {
            $excerpt = wp_trim_words($excerpt, $descWordLimit, '…');
        }

        $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }
        $dateSlots = [];
        if ($showDate && $startTs > 0) {
            $dateSlots = event_o_get_all_date_slots($postId);
        }

        $imageUrls = event_o_get_event_image_urls($postId, 'full');
        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';

        $out .= '<div class="event-o-hero-slide"' . $filterDataAttrs . '>';
        $out .= event_o_render_event_bg_crossfade($imageUrls, 'event-o-hero-bg');
        $out .= '<div class="event-o-hero-overlay"></div>';

        if ($preferHighlights && event_o_is_event_highlight_active($postId)) {
            $out .= event_o_render_highlight_badge($highlightColor);
        }

        $out .= '<div class="event-o-hero-content">';
        $out .= $categoryMarkup;
        if (!empty($dateSlots)) {
            $dateClasses = 'event-o-hero-date';
            if ($dateVariant === 'date-time') {
                $dateClasses .= ' has-time';
            }
            $out .= '<div class="' . esc_attr($dateClasses) . '">';
            foreach ($dateSlots as $slot) {
                if ($dateVariant === 'date-time') {
                    $out .= '<span class="event-o-hero-date-main">' . esc_html($slot['formatted']) . '</span>';
                } else {
                    $tz = wp_timezone();
                    $start = (new DateTimeImmutable('@' . $slot['start_ts']))->setTimezone($tz);
                    $dateOnly = $start->format('j') . '. ' . event_o_get_german_month((int) $start->format('n')) . ' ' . $start->format('Y');
                    $out .= '<span class="event-o-hero-date-main">' . esc_html($dateOnly) . '</span>';
                }
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
            $buttonText = isset($attrs['buttonText']) && $attrs['buttonText'] !== '' ? $attrs['buttonText'] : __('Zu den Events', 'event-o');
            $out .= '<a href="' . esc_url($permalink) . '" class="' . esc_attr($btnClass) . '">' . esc_html($buttonText) . '</a>';
        }
        $out .= '</div>';
        $out .= '</div>';
    }

    wp_reset_postdata();

    $out .= '</div>';
    $out .= '</div>';

    if ($eventCount > 1) {
        $out .= '<div class="event-o-hero-dots">';
        for ($i = 0; $i < $eventCount; $i++) {
            $activeClass = $i === 0 ? ' is-active' : '';
            $out .= '<button type="button" class="event-o-hero-dot' . $activeClass . '" data-index="' . $i . '" aria-label="' . esc_attr(sprintf(__('Go to slide %d', 'event-o'), $i + 1)) . '"></button>';
        }
        $out .= '</div>';
    }

    $out .= '</div>';

    return $out;
}
