<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_register_dashboard_page(): void
{
    add_submenu_page(
        'edit.php?post_type=event_o_event',
        __('Dashboard', 'event-o'),
        __('Dashboard', 'event-o'),
        'edit_event_o_events',
        'event-o-dashboard',
        'event_o_render_dashboard_page'
    );
}

function event_o_enqueue_dashboard_assets(string $hookSuffix): void
{
    $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
    $postType = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';

    if ($page !== 'event-o-dashboard' || $postType !== 'event_o_event') {
        return;
    }

    wp_enqueue_style(
        'event-o-admin-dashboard',
        EVENT_O_PLUGIN_URL . 'assets/admin-dashboard.css',
        [],
        defined('EVENT_O_VERSION') ? EVENT_O_VERSION : '1.1.0'
    );
}
add_action('admin_enqueue_scripts', 'event_o_enqueue_dashboard_assets');

function event_o_render_dashboard_page(): void
{
    if (!current_user_can('edit_event_o_events')) {
        return;
    }

    $tokens = event_o_get_design_tokens();
    $dashboard = event_o_get_dashboard_data();
    $wrapperStyle = sprintf(
        '--event-o-admin-primary:%1$s;--event-o-admin-accent:%2$s;--event-o-admin-text:%3$s;--event-o-admin-muted:%4$s;',
        esc_attr($tokens['primary']),
        esc_attr($tokens['accent']),
        esc_attr($tokens['text']),
        esc_attr($tokens['muted'])
    );

    echo '<div class="wrap event-o-admin-dashboard" style="' . $wrapperStyle . '">';
    echo '<div class="event-o-admin-hero">';
    echo '<div>';
    echo '<p class="event-o-admin-eyebrow">' . esc_html__('Event-O Workspace', 'event-o') . '</p>';
    echo '<h1>' . esc_html__('Dashboard', 'event-o') . '</h1>';
    echo '<p class="event-o-admin-subtitle">' . esc_html__('Zentrale Arbeitsfläche für Redaktion, Planung und Qualitätskontrolle.', 'event-o') . '</p>';
    echo '</div>';
    echo '<div class="event-o-admin-hero-note">';
    echo '<span class="event-o-admin-pill ' . (get_option(EVENT_O_OPTION_WIZARD_MODE, false) ? 'is-good' : 'is-muted') . '">'
        . esc_html(get_option(EVENT_O_OPTION_WIZARD_MODE, false) ? __('Wizard aktiv', 'event-o') : __('Wizard deaktiviert', 'event-o'))
        . '</span>';
    echo '<span class="event-o-admin-pill is-muted">' . esc_html(sprintf(__('Sichtbar für %d Events', 'event-o'), $dashboard['visible_count'])) . '</span>';
    echo '</div>';
    echo '</div>';

    echo '<div class="event-o-admin-kpis">';
    foreach ($dashboard['kpis'] as $kpi) {
        echo '<a class="event-o-admin-kpi" href="' . esc_url($kpi['url']) . '">';
        echo '<span class="event-o-admin-kpi-label">' . esc_html($kpi['label']) . '</span>';
        echo '<strong class="event-o-admin-kpi-value">' . esc_html((string) $kpi['value']) . '</strong>';
        echo '<span class="event-o-admin-kpi-help">' . esc_html($kpi['help']) . '</span>';
        echo '</a>';
    }
    echo '</div>';

    echo '<div class="event-o-admin-grid">';
    echo '<section class="event-o-admin-card event-o-admin-actions-card">';
    echo '<div class="event-o-admin-card-head">';
    echo '<h2>' . esc_html__('Schnellstart', 'event-o') . '</h2>';
    echo '<p>' . esc_html__('Die wichtigsten Wege für tägliche Arbeit im Plugin.', 'event-o') . '</p>';
    echo '</div>';
    echo '<div class="event-o-admin-actions">';
    foreach ($dashboard['actions'] as $action) {
        echo '<a class="event-o-admin-action" href="' . esc_url($action['url']) . '">';
        echo '<span class="event-o-admin-action-title">' . esc_html($action['title']) . '</span>';
        echo '<span class="event-o-admin-action-copy">' . esc_html($action['copy']) . '</span>';
        echo '</a>';
    }
    echo '</div>';
    echo '</section>';

    echo '<section class="event-o-admin-card">';
    echo '<div class="event-o-admin-card-head">';
    echo '<h2>' . esc_html__('Nächste Events', 'event-o') . '</h2>';
    echo '<p>' . esc_html__('Kommende Termine mit direktem Zugriff auf Bearbeitung und Vorschau.', 'event-o') . '</p>';
    echo '</div>';
    if (empty($dashboard['upcoming'])) {
        echo '<p class="event-o-admin-empty">' . esc_html__('Keine kommenden Events gefunden.', 'event-o') . '</p>';
    } else {
        echo '<div class="event-o-admin-list">';
        foreach ($dashboard['upcoming'] as $event) {
            echo '<article class="event-o-admin-row">';
            echo '<div class="event-o-admin-when">';
            echo '<span class="event-o-admin-date">' . esc_html($event['date_label']) . '</span>';
            echo '<span class="event-o-admin-time">' . esc_html($event['time_label']) . '</span>';
            echo '</div>';
            echo '<div class="event-o-admin-main">';
            echo '<div class="event-o-admin-main-top">';
            echo '<a class="event-o-admin-title" href="' . esc_url($event['edit_url']) . '">' . esc_html($event['title']) . '</a>';
            foreach ($event['status_pills'] as $pill) {
                echo '<span class="event-o-admin-pill ' . esc_attr($pill['class']) . '">' . esc_html($pill['label']) . '</span>';
            }
            echo '</div>';
            echo '<div class="event-o-admin-meta">';
            if ($event['category_name'] !== '') {
                $dotStyle = $event['category_color'] !== '' ? ' style="--event-o-dot:' . esc_attr($event['category_color']) . '"' : '';
                echo '<span class="event-o-admin-meta-chip is-category"' . $dotStyle . '>' . esc_html($event['category_name']) . '</span>';
            }
            if ($event['venue_name'] !== '') {
                echo '<span class="event-o-admin-meta-chip">' . esc_html($event['venue_name']) . '</span>';
            }
            if ($event['organizer_name'] !== '') {
                echo '<span class="event-o-admin-meta-chip">' . esc_html($event['organizer_name']) . '</span>';
            }
            echo '</div>';
            echo '</div>';
            echo '<div class="event-o-admin-links">';
            echo '<a href="' . esc_url($event['edit_url']) . '">' . esc_html__('Bearbeiten', 'event-o') . '</a>';
            if ($event['view_url'] !== '') {
                echo '<a href="' . esc_url($event['view_url']) . '">' . esc_html__('Ansehen', 'event-o') . '</a>';
            }
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';
    }
    echo '</section>';

    echo '<section class="event-o-admin-card event-o-admin-card-wide">';
    echo '<div class="event-o-admin-card-head">';
    echo '<h2>' . esc_html__('Braucht Aufmerksamkeit', 'event-o') . '</h2>';
    echo '<p>' . esc_html__('Events mit fehlenden Informationen, Freigabebedarf oder Qualitätslücken.', 'event-o') . '</p>';
    echo '</div>';
    if (empty($dashboard['attention'])) {
        echo '<p class="event-o-admin-empty">' . esc_html__('Im Moment gibt es keine kritischen Punkte.', 'event-o') . '</p>';
    } else {
        echo '<div class="event-o-admin-list event-o-admin-attention-list">';
        foreach ($dashboard['attention'] as $event) {
            echo '<article class="event-o-admin-row is-attention is-attention-list">';
            echo '<div class="event-o-admin-main">';
            echo '<div class="event-o-admin-main-top">';
            echo '<a class="event-o-admin-title" href="' . esc_url($event['edit_url']) . '">' . esc_html($event['title']) . '</a>';
            echo '</div>';
            echo '<div class="event-o-admin-submeta">';
            echo '<span>' . esc_html($event['date_label']) . '</span>';
            echo '<span>' . esc_html($event['time_label']) . '</span>';
            if ($event['venue_name'] !== '') {
                echo '<span>' . esc_html($event['venue_name']) . '</span>';
            }
            if ($event['post_status'] !== 'publish') {
                echo '<span>' . esc_html(ucfirst($event['post_status'])) . '</span>';
            }
            echo '</div>';
            if (!empty($event['status_pills'])) {
                echo '<div class="event-o-admin-meta">';
                foreach ($event['status_pills'] as $pill) {
                    echo '<span class="event-o-admin-pill ' . esc_attr($pill['class']) . '">' . esc_html($pill['label']) . '</span>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '<div class="event-o-admin-attention-details">';
            echo '<span class="event-o-admin-severity">' . esc_html($event['severity_label']) . '</span>';
            echo '<div class="event-o-admin-meta">';
            foreach ($event['attention_reasons'] as $reason) {
                echo '<span class="event-o-admin-meta-chip is-warning">' . esc_html($reason) . '</span>';
            }
            echo '</div>';
            echo '</div>';
            echo '<div class="event-o-admin-links">';
            echo '<a href="' . esc_url($event['edit_url']) . '">' . esc_html__('Prüfen', 'event-o') . '</a>';
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';
    }
    echo '</section>';
    echo '</div>';
    echo '</div>';
}

function event_o_get_dashboard_data(): array
{
    $posts = get_posts(event_o_get_dashboard_query_args());
    $items = [];

    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $items[] = event_o_build_dashboard_event_item($post);
    }

    $todayStart = strtotime('today', current_time('timestamp'));
    $todayEnd = strtotime('tomorrow', current_time('timestamp'));
    $nextWeekEnd = strtotime('+7 days', $todayStart);

    $todayCount = 0;
    $nextWeekCount = 0;
    $draftCount = 0;
    $pendingCount = 0;
    $missingImageCount = 0;
    $missingVenueCount = 0;
    $cancelledCount = 0;

    foreach ($items as $item) {
        if ($item['post_status'] === 'draft') {
            $draftCount++;
        }
        if ($item['post_status'] === 'pending') {
            $pendingCount++;
        }
        if (!$item['has_thumbnail']) {
            $missingImageCount++;
        }
        if (!$item['has_venue']) {
            $missingVenueCount++;
        }
        if ($item['event_status'] === 'cancelled') {
            $cancelledCount++;
        }
        if (!$item['is_publicly_listed']) {
            continue;
        }
        if (event_o_dashboard_has_slot_in_range($item['slots'], $todayStart, $todayEnd)) {
            $todayCount++;
        }
        if (event_o_dashboard_has_slot_in_range($item['slots'], $todayStart, $nextWeekEnd)) {
            $nextWeekCount++;
        }
    }

    $upcoming = array_values(array_filter($items, static function (array $item): bool {
        return $item['next_upcoming_ts'] > 0;
    }));

    usort($upcoming, static function (array $left, array $right): int {
        if ($left['next_upcoming_ts'] === $right['next_upcoming_ts']) {
            return strcasecmp($left['title'], $right['title']);
        }

        return $left['next_upcoming_ts'] <=> $right['next_upcoming_ts'];
    });

    $attention = array_values(array_filter($items, static function (array $item): bool {
        return !empty($item['attention_reasons']);
    }));

    usort($attention, static function (array $left, array $right): int {
        if ($left['severity'] === $right['severity']) {
            if ($left['sort_ts'] === $right['sort_ts']) {
                return strcasecmp($left['title'], $right['title']);
            }
            return $left['sort_ts'] <=> $right['sort_ts'];
        }
        return $right['severity'] <=> $left['severity'];
    });

    return [
        'visible_count' => count($items),
        'kpis' => [
            [
                'label' => __('Heute', 'event-o'),
                'value' => $todayCount,
                'help' => __('Events mit Termin heute', 'event-o'),
                'url' => admin_url('edit.php?post_type=event_o_event'),
            ],
            [
                'label' => __('Nächste 7 Tage', 'event-o'),
                'value' => $nextWeekCount,
                'help' => __('Events im Wochenfenster', 'event-o'),
                'url' => admin_url('edit.php?post_type=event_o_event'),
            ],
            [
                'label' => __('Entwürfe', 'event-o'),
                'value' => $draftCount,
                'help' => __('Noch nicht eingereicht', 'event-o'),
                'url' => admin_url('edit.php?post_status=draft&post_type=event_o_event'),
            ],
            [
                'label' => __('Ausstehend', 'event-o'),
                'value' => $pendingCount,
                'help' => __('Warten auf Freigabe', 'event-o'),
                'url' => admin_url('edit.php?post_status=pending&post_type=event_o_event'),
            ],
            [
                'label' => __('Ohne Bild', 'event-o'),
                'value' => $missingImageCount,
                'help' => __('Featured Image fehlt', 'event-o'),
                'url' => admin_url('edit.php?post_type=event_o_event'),
            ],
            [
                'label' => __('Ohne Venue', 'event-o'),
                'value' => $missingVenueCount,
                'help' => __('Ort noch unvollständig', 'event-o'),
                'url' => admin_url('edit.php?post_type=event_o_event'),
            ],
            [
                'label' => __('Abgesagt', 'event-o'),
                'value' => $cancelledCount,
                'help' => __('Status ist abgesagt', 'event-o'),
                'url' => admin_url('edit.php?post_type=event_o_event'),
            ],
        ],
        'actions' => [
            [
                'title' => __('Neues Event', 'event-o'),
                'copy' => get_option(EVENT_O_OPTION_WIZARD_MODE, false) ? __('Öffnet direkt die geführte Eingabe.', 'event-o') : __('Öffnet die normale Event-Erstellung.', 'event-o'),
                'url' => admin_url('post-new.php?post_type=event_o_event'),
            ],
            [
                'title' => __('Alle Events', 'event-o'),
                'copy' => __('Liste aller Event-Einträge öffnen.', 'event-o'),
                'url' => admin_url('edit.php?post_type=event_o_event'),
            ],
            [
                'title' => __('Ausstehende prüfen', 'event-o'),
                'copy' => __('Direkt zu Events im Status “Ausstehend”.', 'event-o'),
                'url' => admin_url('edit.php?post_status=pending&post_type=event_o_event'),
            ],
            [
                'title' => __('Kategorien', 'event-o'),
                'copy' => __('Farben und Taxonomie-Struktur verwalten.', 'event-o'),
                'url' => admin_url('edit-tags.php?taxonomy=event_o_category&post_type=event_o_event'),
            ],
            [
                'title' => __('Venues', 'event-o'),
                'copy' => __('Orte anlegen und bereinigen.', 'event-o'),
                'url' => admin_url('edit-tags.php?taxonomy=event_o_venue&post_type=event_o_event'),
            ],
            [
                'title' => __('Organizers', 'event-o'),
                'copy' => __('Veranstalter und Kontakte pflegen.', 'event-o'),
                'url' => admin_url('edit-tags.php?taxonomy=event_o_organizer&post_type=event_o_event'),
            ],
            [
                'title' => __('Einstellungen', 'event-o'),
                'copy' => __('Design, Anzeige und Wizard-Modus anpassen.', 'event-o'),
                'url' => admin_url('edit.php?post_type=event_o_event&page=event-o-settings'),
            ],
        ],
        'upcoming' => array_slice($upcoming, 0, 10),
        'attention' => array_slice($attention, 0, 12),
    ];
}

function event_o_get_dashboard_query_args(): array
{
    $args = [
        'post_type' => 'event_o_event',
        'post_status' => ['publish', 'pending', 'draft', 'future', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    if (!current_user_can('edit_others_event_o_events')) {
        $args['author'] = get_current_user_id();
    }

    return $args;
}

function event_o_build_dashboard_event_item(WP_Post $post): array
{
    $postId = (int) $post->ID;
    $slots = event_o_dashboard_get_slots($postId);
    $nowTs = current_time('timestamp');
    $timezone = wp_timezone();
    $nextUpcomingTs = 0;
    $firstSlotTs = 0;

    foreach ($slots as $slot) {
        $startTs = (int) $slot['start_ts'];
        if ($startTs <= 0) {
            continue;
        }
        if ($firstSlotTs === 0 || $startTs < $firstSlotTs) {
            $firstSlotTs = $startTs;
        }
        if ($startTs >= $nowTs && ($nextUpcomingTs === 0 || $startTs < $nextUpcomingTs)) {
            $nextUpcomingTs = $startTs;
        }
    }

    $categories = wp_get_post_terms($postId, 'event_o_category');
    $venues = wp_get_post_terms($postId, 'event_o_venue');
    $organizers = wp_get_post_terms($postId, 'event_o_organizer');
    $categoryName = (!is_wp_error($categories) && !empty($categories)) ? $categories[0]->name : '';
    $categoryColor = (!is_wp_error($categories) && !empty($categories)) ? (string) get_term_meta($categories[0]->term_id, 'event_o_category_color', true) : '';
    $venueName = (!is_wp_error($venues) && !empty($venues)) ? $venues[0]->name : '';
    $organizerName = (!is_wp_error($organizers) && !empty($organizers)) ? $organizers[0]->name : '';

    $contentText = trim(wp_strip_all_tags((string) $post->post_excerpt . ' ' . (string) $post->post_content));
    $eventStatus = (string) get_post_meta($postId, EVENT_O_META_STATUS, true);
    if ($eventStatus === '') {
        $eventStatus = (string) get_post_meta($postId, EVENT_O_LEGACY_META_STATUS, true);
    }

    $attentionReasons = [];
    $severity = 0;

    if ($post->post_status === 'pending') {
        $attentionReasons[] = __('Freigabe ausstehend', 'event-o');
        $severity += 5;
    }
    if ($post->post_status === 'draft') {
        $attentionReasons[] = __('Noch Entwurf', 'event-o');
        $severity += 3;
    }
    if ($firstSlotTs === 0) {
        $attentionReasons[] = __('Kein Datum gesetzt', 'event-o');
        $severity += 5;
    }
    if (!has_post_thumbnail($postId)) {
        $attentionReasons[] = __('Featured Image fehlt', 'event-o');
        $severity += 3;
    }
    if (empty($venues) || is_wp_error($venues)) {
        $attentionReasons[] = __('Venue fehlt', 'event-o');
        $severity += 3;
    }
    if (empty($organizers) || is_wp_error($organizers)) {
        $attentionReasons[] = __('Organizer fehlt', 'event-o');
        $severity += 2;
    }
    if (empty($categories) || is_wp_error($categories)) {
        $attentionReasons[] = __('Kategorie fehlt', 'event-o');
        $severity += 2;
    }
    if ($contentText === '') {
        $attentionReasons[] = __('Beschreibung fehlt', 'event-o');
        $severity += 2;
    }
    if ($firstSlotTs > 0 && $nextUpcomingTs === 0 && $post->post_status === 'publish') {
        $attentionReasons[] = __('Nur vergangene Termine', 'event-o');
        $severity += 1;
    }

    $displayTs = $nextUpcomingTs > 0 ? $nextUpcomingTs : $firstSlotTs;
    $statusPills = [];
    if ($post->post_status === 'pending') {
        $statusPills[] = ['label' => __('Pending', 'event-o'), 'class' => 'is-warning'];
    } elseif ($post->post_status === 'draft') {
        $statusPills[] = ['label' => __('Entwurf', 'event-o'), 'class' => 'is-muted'];
    }
    if ($eventStatus === 'cancelled') {
        $statusPills[] = ['label' => __('Abgesagt', 'event-o'), 'class' => 'is-danger'];
    } elseif ($eventStatus === 'soldout') {
        $statusPills[] = ['label' => __('Ausgebucht', 'event-o'), 'class' => 'is-warning'];
    }

    return [
        'id' => $postId,
        'title' => get_the_title($postId) !== '' ? get_the_title($postId) : __('(Ohne Titel)', 'event-o'),
        'post_status' => (string) $post->post_status,
        'event_status' => $eventStatus,
        'edit_url' => (string) get_edit_post_link($postId),
        'view_url' => $post->post_status === 'publish' ? (string) get_permalink($postId) : '',
        'has_thumbnail' => has_post_thumbnail($postId),
        'has_venue' => !empty($venues) && !is_wp_error($venues),
        'is_publicly_listed' => in_array($post->post_status, ['publish', 'future', 'private'], true),
        'slots' => $slots,
        'next_upcoming_ts' => $nextUpcomingTs,
        'sort_ts' => $displayTs > 0 ? $displayTs : PHP_INT_MAX,
        'date_label' => $displayTs > 0 ? wp_date('D, d.m.Y', $displayTs, $timezone) : __('Kein Termin', 'event-o'),
        'time_label' => $displayTs > 0 ? wp_date('H:i', $displayTs, $timezone) . ' ' . __('Uhr', 'event-o') : '–',
        'category_name' => $categoryName,
        'category_color' => sanitize_hex_color($categoryColor) ?: '',
        'venue_name' => $venueName,
        'organizer_name' => $organizerName,
        'attention_reasons' => $attentionReasons,
        'severity' => $severity,
        'severity_label' => $severity >= 8 ? __('hoch', 'event-o') : ($severity >= 4 ? __('mittel', 'event-o') : __('niedrig', 'event-o')),
        'status_pills' => $statusPills,
    ];
}

function event_o_dashboard_get_slots(int $postId): array
{
    $slotKeys = [
        [EVENT_O_META_START_TS, EVENT_O_META_END_TS, EVENT_O_META_BEGIN_TIME],
        [EVENT_O_META_START_TS_2, EVENT_O_META_END_TS_2, EVENT_O_META_BEGIN_TIME_2],
        [EVENT_O_META_START_TS_3, EVENT_O_META_END_TS_3, EVENT_O_META_BEGIN_TIME_3],
    ];

    $slots = [];
    foreach ($slotKeys as $keys) {
        $startTs = (int) get_post_meta($postId, $keys[0], true);
        $endTs = (int) get_post_meta($postId, $keys[1], true);
        $beginTime = (string) get_post_meta($postId, $keys[2], true);
        if ($startTs <= 0) {
            continue;
        }
        $slots[] = [
            'start_ts' => $startTs,
            'end_ts' => $endTs,
            'begin_time' => $beginTime,
        ];
    }

    return $slots;
}

function event_o_dashboard_has_slot_in_range(array $slots, int $rangeStart, int $rangeEnd): bool
{
    foreach ($slots as $slot) {
        $startTs = isset($slot['start_ts']) ? (int) $slot['start_ts'] : 0;
        if ($startTs >= $rangeStart && $startTs < $rangeEnd) {
            return true;
        }
    }

    return false;
}