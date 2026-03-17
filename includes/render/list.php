<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_render_event_list_block(array $attrs, string $content = '', WP_Block $block = null): string
{
    event_o_ensure_frontend_assets();

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
    $showTags = !empty($attrs['showTags']);
    $showMoreLink = isset($attrs['showMoreLink']) ? $attrs['showMoreLink'] : true;
    $showFilters = !empty($attrs['showFilters']);
    $showHighlightBadge = !empty($attrs['showHighlightBadge']);
    $highlightColor = event_o_get_highlight_badge_style_value($attrs);

    $accentColor = isset($attrs['accentColor']) && $attrs['accentColor'] !== '' ? $attrs['accentColor'] : '';
    $styleAttr = $accentColor !== '' ? ' style="--event-o-block-accent:' . esc_attr($accentColor) . ';"' : '';
    $singleOpenAttr = ' data-single-open="' . ($singleOpen ? '1' : '0') . '"';
    $animationType = isset($attrs['animation']) ? $attrs['animation'] : 'none';
    $animAttr = $animationType !== 'none' ? ' data-animation="' . esc_attr($animationType) . '"' : '';

    $out = '<div class="event-o event-o-event-list' . ($showFilters ? ' has-filters' : '') . '"' . $styleAttr . $singleOpenAttr . $animAttr . '>';

    if ($showFilters) {
        $filterTerms = event_o_collect_filter_terms($q, $attrs);
        $filterStyle = isset($attrs['filterStyle']) ? $attrs['filterStyle'] : 'dropdown';
        $out .= ($filterStyle === 'tabs') ? event_o_render_filter_bar_tabs($filterTerms, $attrs) : event_o_render_filter_bar($filterTerms, $attrs);
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
        $status = event_o_get_event_status($postId);

        if ($startTs <= 0) {
            $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
        }
        if ($endTs <= 0) {
            $endTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_END_TS, true);
        }
        if ($price === '') {
            $price = (string) get_post_meta($postId, EVENT_O_LEGACY_META_PRICE, true);
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
        $dateSlots = event_o_get_all_date_slots($postId);

        $categoryMarkup = event_o_render_event_category_labels($postId, [
            'wrapper_class' => 'event-o-category-hints',
            'item_class' => 'event-o-category-hint',
            'parentheses' => true,
        ]);

        $venueData = $showVenue ? event_o_get_venue_data($postId) : null;
        $showOrgDescription = (bool) get_option(EVENT_O_OPTION_SHOW_ORG_DESCRIPTION, false);
        $organizerData = ($showOrganizer || $showOrgDescription) ? event_o_get_organizer_data($postId) : null;

        $openAttr = ($openFirst && $index === 0) ? ' open' : '';
        $filterDataAttrs = $showFilters ? event_o_get_filter_data_attrs($postId) : '';
        $imageUrls = $showImage ? event_o_get_event_image_urls($postId, 'large') : [];

        $isHighlighted = $showHighlightBadge && event_o_is_event_highlight_active($postId);
        $highlightItemClass = $isHighlighted ? ' is-highlighted' : '';
        $highlightItemStyle = '';
        if ($isHighlighted && $highlightColor !== '') {
            if (stripos($highlightColor, 'gradient') !== false) {
                $highlightItemClass .= ' has-highlight-gradient';
                $highlightItemStyle = ' style="--eo-list-highlight-gradient:' . esc_attr($highlightColor) . ';--eo-list-highlight-contrast:#fff;"';
            } else {
                $highlightItemClass .= ' has-highlight-color';
                $contrastColor = '#fff';
                if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $highlightColor)) {
                    $contrastColor = event_o_contrast_text_color($highlightColor);
                }
                $highlightItemStyle = ' style="--eo-list-highlight-accent:' . esc_attr($highlightColor) . ';--eo-list-highlight-contrast:' . esc_attr($contrastColor) . ';"';
            }
        }

        $out .= '<details class="event-o-accordion-item eo-block-anim' . $highlightItemClass . '"' . $highlightItemStyle . $openAttr . $filterDataAttrs . '>';
        $out .= '<summary class="event-o-accordion-summary">';
        $out .= '<div class="event-o-when">';
        if (!empty($dateSlots)) {
            foreach ($dateSlots as $slot) {
                $slotStartTs = isset($slot['start_ts']) ? (int) $slot['start_ts'] : 0;
                $slotEndTs = isset($slot['end_ts']) ? (int) $slot['end_ts'] : 0;

                if ($slotStartTs > 0) {
                    $slotStart = (new DateTimeImmutable('@' . $slotStartTs))->setTimezone($tz);
                    $slotDate = $slotStart->format('j') . '. ' . event_o_get_german_month((int) $slotStart->format('n')) . ' ' . $slotStart->format('Y');
                    $slotTime = $slotStart->format('H:i');

                    if ($slotEndTs > 0) {
                        $slotEnd = (new DateTimeImmutable('@' . $slotEndTs))->setTimezone($tz);
                        $slotTime .= ' – ' . $slotEnd->format('H:i');
                    }

                    $out .= '<div class="event-o-date-slot">';
                    $out .= '<span class="event-o-date">' . esc_html($slotDate) . '</span>';
                    $out .= '<span class="event-o-time">' . esc_html($slotTime . ' Uhr') . '</span>';
                    $out .= '</div>';
                } else {
                    $out .= '<span class="event-o-date-slot event-o-date-slot-legacy">' . esc_html($slot['formatted']) . '</span>';
                }
            }
        }
        $out .= '</div>';

        $out .= '<div class="event-o-title-wrap">';
        $out .= '<span class="event-o-title">' . esc_html($title) . '</span>';
        if ($categoryMarkup !== '') {
            $out .= $categoryMarkup;
        }
        $out .= '</div>';
        $out .= '<div class="event-o-chevron" aria-hidden="true"></div>';
        $out .= '</summary>';

        $out .= '<div class="event-o-accordion-panel">';
        $out .= '<div class="event-o-panel-content">';
        $out .= '<aside class="event-o-sidebar">';

        if ($organizerData) {
            $out .= '<div class="event-o-organizer-card">';
            $out .= '<h4 class="event-o-sidebar-title">' . esc_html__('VERANSTALTER', 'event-o') . '</h4>';
            if (!empty($organizerData['logo'])) {
                $out .= '<div class="event-o-org-logo"><img src="' . esc_url($organizerData['logo']) . '" alt="' . esc_attr($organizerData['name']) . '"></div>';
            }
            $out .= '<div class="event-o-org-name">' . esc_html($organizerData['name']) . '</div>';

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

        $primarySlot = !empty($dateSlots) ? $dateSlots[0] : null;
        $listBeginTime = $primarySlot && !empty($primarySlot['begin_time']) ? (string) $primarySlot['begin_time'] : '';
        if ($listBeginTime !== '') {
            $listDoorTime = $primarySlot ? wp_date('H:i', (int) $primarySlot['start_ts'], $tz) : '';
            $out .= '<div class="event-o-schedule-card">';
            $out .= '<h4 class="event-o-sidebar-title">' . esc_html__('ZEITEN', 'event-o') . '</h4>';
            if ($listDoorTime !== '') {
                $out .= '<div class="event-o-schedule-row">' . esc_html__('Einlass', 'event-o') . ' ' . esc_html($listDoorTime) . ' ' . esc_html__('Uhr', 'event-o') . '</div>';
            }
            $out .= '<div class="event-o-schedule-row">' . esc_html__('Beginn', 'event-o') . ' ' . esc_html($listBeginTime) . ' ' . esc_html__('Uhr', 'event-o') . '</div>';
            $out .= '</div>';
        }

        if ($showPrice && $price !== '') {
            $out .= '<div class="event-o-price-card">';
            $out .= '<h4 class="event-o-sidebar-title">' . esc_html__('EINTRITT', 'event-o') . '</h4>';
            $out .= '<div class="event-o-price-value">';
            $out .= '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-2-1.46c-1.19.69-2 1.99-2 3.46s.81 2.77 2 3.46V18H4v-2.54c1.19-.69 2-1.99 2-3.46 0-1.48-.8-2.77-1.99-3.46L4 6h16v2.54zM11 15h2v2h-2zm0-4h2v2h-2zm0-4h2v2h-2z"/></svg>';
            $out .= '<span>' . esc_html($price) . '</span>';
            $out .= '</div>';
            $out .= '</div>';
        }

        $out .= event_o_render_referenced_event_card($postId, [
            'wrapper_class' => 'event-o-reference-card-wrap event-o-reference-card-wrap-sidebar',
            'card_class' => 'event-o-reference-card event-o-reference-card-sidebar',
            'label' => __('Passendes Event', 'event-o'),
            'title_tag' => 'h4',
        ]);

        $out .= '</aside>';
        $out .= '<div class="event-o-main">';

        if (!empty($imageUrls)) {
            $out .= '<div class="event-o-featured-image-wrap">';
            if ($isHighlighted) {
                $out .= event_o_render_highlight_badge($highlightColor);
            }
            $out .= event_o_render_event_image_crossfade($imageUrls, 'event-o-featured-image', '', $title);
            $out .= '</div>';
        } elseif ($isHighlighted) {
            $out .= '<div class="event-o-list-highlight-fallback">' . event_o_render_highlight_badge($highlightColor) . '</div>';
        }

        if ($showTags) {
            $tagMarkup = event_o_render_event_tag_links($postId, [
                'wrapper_class' => 'event-o-tag-list event-o-list-tags',
            ]);
            if ($tagMarkup !== '') {
                $out .= $tagMarkup;
            }
        }

        $excerpt = get_the_excerpt();
        $content_text = apply_filters('the_content', get_the_content());
        if ($content_text !== '' && trim(strip_tags($content_text)) !== '') {
            $out .= '<div class="event-o-content">' . $content_text . '</div>';
        } elseif ($excerpt !== '') {
            $out .= '<div class="event-o-excerpt">' . wp_kses_post(wpautop($excerpt)) . '</div>';
        }

        $showOrgDescription = (bool) get_option(EVENT_O_OPTION_SHOW_ORG_DESCRIPTION, false);
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

        if ($showMoreLink) {
            $out .= '<div class="event-o-actions">';
            $out .= '<a class="event-o-link" href="' . esc_url($permalink) . '">' . esc_html__('MORE', 'event-o') . '</a>';
            $out .= '</div>';
        }

        $out .= event_o_render_bands($postId, 'event-o-bands');

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

        $out .= '</div>';
        $out .= '</div>';
        $out .= '</div>';
        $out .= '</details>';

        $index++;
    }

    wp_reset_postdata();

    $out .= '</div>';
    return $out;
}
