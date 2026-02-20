<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) {
    the_post();

    $postId = get_the_ID();
    $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
    $endTs = (int) get_post_meta($postId, EVENT_O_META_END_TS, true);
    $price = (string) get_post_meta($postId, EVENT_O_META_PRICE, true);
    $status = (string) get_post_meta($postId, EVENT_O_META_STATUS, true);

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

    $dtParts = event_o_format_datetime_german($startTs, $endTs);
    $venueData = event_o_get_venue_data($postId);
    $categoryName = event_o_get_first_term_name($postId, 'event_o_category');
    $organizerData = event_o_get_organizer_data($postId);
    $permalink = get_permalink();
    $title = get_the_title();
    $imageUrl = has_post_thumbnail($postId) ? get_the_post_thumbnail_url($postId, 'full') : '';

    // Get related events
    $relatedEvents = event_o_get_related_events($postId, 4);

    echo '<main class="event-o event-o-single" data-animation="' . esc_attr((string) get_option(EVENT_O_OPTION_SINGLE_ANIMATION, 'none')) . '">';

    // Hero section with full-width image
    if ($imageUrl !== '') {
        echo '<div class="event-o-single-hero eo-anim">';
        echo '<img src="' . esc_url($imageUrl) . '" alt="' . esc_attr($title) . '" class="event-o-single-hero-img">';
        echo '</div>';
    }

    echo '<div class="event-o-single-container">';
    
    // Main content area with sidebar layout
    echo '<div class="event-o-single-layout">';

    // Sidebar
    echo '<aside class="event-o-single-sidebar eo-anim">';

    // Date & Time card
    echo '<div class="event-o-single-card">';
    echo '<h3 class="event-o-single-card-title">' . esc_html__('WANN', 'event-o') . '</h3>';
    echo '<div class="event-o-single-date">' . esc_html($dtParts['date']) . '</div>';
    echo '<div class="event-o-single-time">' . esc_html($dtParts['time']) . '</div>';
    echo '</div>';

    // Venue card
    if ($venueData) {
        echo '<div class="event-o-single-card">';
        echo '<h3 class="event-o-single-card-title">' . esc_html__('ORT', 'event-o') . '</h3>';
        echo '<div class="event-o-single-venue">' . esc_html($venueData['name']) . '</div>';
        if (!empty($venueData['address'])) {
            echo '<div class="event-o-venue-address">' . nl2br(esc_html($venueData['address'])) . '</div>';
        }
        echo '</div>';
    }

    // Organizer card
    if ($organizerData) {
        echo '<div class="event-o-single-card">';
        if (!empty($organizerData['logo'])) {
            echo '<div class="event-o-org-logo"><img src="' . esc_url($organizerData['logo']) . '" alt="' . esc_attr($organizerData['name']) . '"></div>';
        }
        echo '<h3 class="event-o-single-card-title">' . esc_html__('VERANSTALTER', 'event-o') . '</h3>';
        echo '<div class="event-o-single-organizer">' . esc_html($organizerData['name']) . '</div>';

        if (!empty($organizerData['phone'])) {
            echo '<div class="event-o-org-row">';
            echo '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>';
            echo '<span class="event-o-label">TEL</span>';
            echo '<a href="tel:' . esc_attr($organizerData['phone']) . '">' . esc_html($organizerData['phone']) . '</a>';
            echo '</div>';
        }
        if (!empty($organizerData['email'])) {
            echo '<div class="event-o-org-row">';
            echo '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
            echo '<span class="event-o-label">E-MAIL</span>';
            echo '<a href="mailto:' . esc_attr($organizerData['email']) . '">' . esc_html($organizerData['email']) . '</a>';
            echo '</div>';
        }
        if (!empty($organizerData['website'])) {
            echo '<div class="event-o-org-row">';
            echo '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>';
            echo '<span class="event-o-label">WEB</span>';
            echo '<a href="' . esc_url($organizerData['website']) . '" target="_blank" rel="noopener">' . esc_html(preg_replace('#^https?://#', '', $organizerData['website'])) . '</a>';
            echo '</div>';
        }

        // Social links
        if (!empty($organizerData['instagram']) || !empty($organizerData['facebook'])) {
            echo '<div class="event-o-org-social">';
            if (!empty($organizerData['instagram'])) {
                echo '<a href="' . esc_url($organizerData['instagram']) . '" target="_blank" rel="noopener" class="event-o-social-link event-o-instagram-link">';
                echo '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>';
                echo '<span class="event-o-instagram-text">INSTAGRAM</span>';
                echo '</a>';
            }
            if (!empty($organizerData['facebook'])) {
                echo '<a href="' . esc_url($organizerData['facebook']) . '" target="_blank" rel="noopener" class="event-o-social-link event-o-facebook-link">';
                echo '<svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
                echo '<span>FACEBOOK</span>';
                echo '</a>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    // Price card
    if ($price !== '') {
        echo '<div class="event-o-single-card">';
        echo '<h3 class="event-o-single-card-title">' . esc_html__('PREIS', 'event-o') . '</h3>';
        echo '<div class="event-o-single-price">' . esc_html($price) . '</div>';
        echo '</div>';
    }

    echo '</aside>';

    // Main content
    echo '<article class="event-o-single-main">';

    // Header
    echo '<header class="event-o-single-header eo-anim">';
    echo '<h1 class="event-o-single-title">' . esc_html($title);
    if ($categoryName !== '') {
        echo ' <span class="event-o-category-hint">(' . esc_html($categoryName) . ')</span>';
    }
    echo '</h1>';
    
    // Status badge
    if ($status !== '' && $status !== 'scheduled') {
        $statusLabels = [
            'cancelled' => __('Abgesagt', 'event-o'),
            'postponed' => __('Verschoben', 'event-o'),
            'sold-out' => __('Ausverkauft', 'event-o'),
        ];
        $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : $status;
        echo '<span class="event-o-status event-o-status-' . esc_attr($status) . '">' . esc_html($statusLabel) . '</span>';
    }
    echo '</header>';

    // Content
    echo '<div class="event-o-content eo-anim">';
    the_content();
    echo '</div>';

    // Band links
    echo event_o_render_bands($postId, 'event-o-bands event-o-single-bands');

    // Share section
    echo '<div class="event-o-share-section eo-anim">';
    echo '<span class="event-o-share-label">' . esc_html__('TEILE DIESE VERANSTALTUNG', 'event-o') . '</span>';
    $calendarData = [
        'postId' => $postId,
        'title' => $title,
        'startTs' => $startTs,
        'endTs' => $endTs,
        'description' => wp_strip_all_tags(get_the_excerpt()),
        'location' => $venueData ? $venueData['name'] . ($venueData['address'] ? ', ' . $venueData['address'] : '') : '',
    ];
    echo event_o_render_share_buttons($permalink, $title, $calendarData);
    echo '</div>';

    echo '</article>';

    echo '</div>'; // .event-o-single-layout
    echo '</div>'; // .event-o-single-container

    // Related events section
    if (!empty($relatedEvents)) {
        echo '<section class="event-o-related eo-anim">';
        echo '<div class="event-o-related-container">';
        echo '<h2 class="event-o-related-title">' . esc_html__('WEITERE VERANSTALTUNGEN', 'event-o') . '</h2>';
        echo '<div class="event-o-related-grid">';
        
        foreach ($relatedEvents as $event) {
            echo '<a href="' . esc_url($event['permalink']) . '" class="event-o-related-card">';
            if ($event['thumbnail']) {
                echo '<div class="event-o-related-image">';
                echo '<img src="' . esc_url($event['thumbnail']) . '" alt="' . esc_attr($event['title']) . '" loading="lazy">';
                if (!empty($event['excerpt'])) {
                    echo '<div class="event-o-related-overlay">';
                    echo '<p class="event-o-related-excerpt">' . esc_html($event['excerpt']) . '</p>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<div class="event-o-related-image event-o-related-placeholder"></div>';
            }
            echo '<div class="event-o-related-body">';
            echo '<div class="event-o-related-date">' . esc_html($event['date']) . '</div>';
            echo '<h3 class="event-o-related-card-title">' . esc_html($event['title']) . '</h3>';
            echo '</div>';
            echo '</a>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }

    echo '</main>';
}

get_footer();
