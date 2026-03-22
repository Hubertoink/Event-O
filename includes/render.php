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
    return event_o_collect_filter_terms_from_posts($q->posts, $attrs);
}

function event_o_collect_filter_terms_from_posts(array $posts, array $attrs): array
{
    $filterByCategory = !empty($attrs['filterByCategory']);
    $filterByVenue = !empty($attrs['filterByVenue']);
    $filterByOrganizer = !empty($attrs['filterByOrganizer']);

    $categories = [];
    $venues = [];
    $organizers = [];

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
 * Render the filter bar as tabs/pills.
 */
function event_o_render_filter_bar_tabs(array $filterTerms, array $attrs): string
{
    $filterByCategory = !empty($attrs['filterByCategory']);
    $filterByVenue = !empty($attrs['filterByVenue']);
    $filterByOrganizer = !empty($attrs['filterByOrganizer']);
    $useCategoryColors = !empty($attrs['filterCategoryColors']);

    $out = '<div class="event-o-filter-bar is-tabs' . ($useCategoryColors ? ' has-colored-category-filters' : '') . '">';

    if ($filterByCategory && !empty($filterTerms['categories'])) {
        $out .= '<div class="event-o-filter-tab-group" data-filter="category">';
        $out .= '<button type="button" class="event-o-filter-tab is-active" data-value="">' . esc_html__('Alle', 'event-o') . '</button>';
        foreach ($filterTerms['categories'] as $slug => $name) {
            $colorAttr = '';
            $classAttr = 'event-o-filter-tab';

            if ($useCategoryColors) {
                $categoryColor = event_o_get_category_color_by_slug((string) $slug);
                if ($categoryColor !== '') {
                    $classAttr .= ' has-cat-color';
                    $colorAttr = ' style="--eo-cat-color:' . esc_attr($categoryColor) . ';--eo-cat-text:' . esc_attr(event_o_contrast_text_color($categoryColor)) . ';"';
                }
            }

            $out .= '<button type="button" class="' . esc_attr($classAttr) . '" data-value="' . esc_attr($slug) . '"' . $colorAttr . '>' . esc_html($name) . '</button>';
        }
        $out .= '</div>';
    }

    if ($filterByVenue && !empty($filterTerms['venues'])) {
        $out .= '<div class="event-o-filter-tab-group" data-filter="venue">';
        $out .= '<button type="button" class="event-o-filter-tab is-active" data-value="">' . esc_html__('Alle', 'event-o') . '</button>';
        foreach ($filterTerms['venues'] as $slug => $name) {
            $out .= '<button type="button" class="event-o-filter-tab" data-value="' . esc_attr($slug) . '">' . esc_html($name) . '</button>';
        }
        $out .= '</div>';
    }

    if ($filterByOrganizer && !empty($filterTerms['organizers'])) {
        $out .= '<div class="event-o-filter-tab-group" data-filter="organizer">';
        $out .= '<button type="button" class="event-o-filter-tab is-active" data-value="">' . esc_html__('Alle', 'event-o') . '</button>';
        foreach ($filterTerms['organizers'] as $slug => $name) {
            $out .= '<button type="button" class="event-o-filter-tab" data-value="' . esc_attr($slug) . '">' . esc_html($name) . '</button>';
        }
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

function event_o_get_past_grace_days(): int
{
    return max(0, (int) get_option(EVENT_O_OPTION_PAST_GRACE_DAYS, 3));
}

function event_o_get_slot_visibility_until(int $startTs, int $endTs = 0, ?int $graceDays = null): int
{
    if ($startTs <= 0) {
        return max(0, $endTs);
    }

    $graceDays = $graceDays ?? event_o_get_past_grace_days();
    $start = (new DateTimeImmutable('@' . $startTs))->setTimezone(wp_timezone());
    $visibleUntil = $start
        ->setTime(23, 59, 59)
        ->modify('+' . max(0, $graceDays) . ' days')
        ->getTimestamp();

    if ($endTs > $visibleUntil) {
        $visibleUntil = $endTs;
    }

    return $visibleUntil;
}

function event_o_get_visibility_threshold_start(?int $graceDays = null): int
{
    $graceDays = $graceDays ?? event_o_get_past_grace_days();
    return (new DateTimeImmutable('now', wp_timezone()))
        ->setTime(0, 0, 0)
        ->modify('-' . max(0, $graceDays) . ' days')
        ->getTimestamp();
}

function event_o_get_upcoming_meta_query(?int $graceDays = null): array
{
    $graceDays = $graceDays ?? event_o_get_past_grace_days();
    $thresholdStart = event_o_get_visibility_threshold_start($graceDays);
    $now = time();
    $metaQuery = ['relation' => 'OR'];

    foreach ([EVENT_O_META_START_TS, EVENT_O_META_START_TS_2, EVENT_O_META_START_TS_3, EVENT_O_LEGACY_META_START_TS] as $startKey) {
        $metaQuery[] = [
            'key' => $startKey,
            'value' => $thresholdStart,
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
    }

    foreach ([EVENT_O_META_END_TS, EVENT_O_META_END_TS_2, EVENT_O_META_END_TS_3, EVENT_O_LEGACY_META_END_TS] as $endKey) {
        $metaQuery[] = [
            'key' => $endKey,
            'value' => $now,
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
    }

    return $metaQuery;
}

function event_o_is_event_visible(int $postId, ?int $now = null, ?int $graceDays = null): bool
{
    $slots = event_o_get_all_date_slots($postId);
    if (empty($slots)) {
        return false;
    }

    $now = $now ?? time();
    $graceDays = $graceDays ?? event_o_get_past_grace_days();

    foreach ($slots as $slot) {
        $startTs = isset($slot['start_ts']) ? (int) $slot['start_ts'] : 0;
        $endTs = isset($slot['end_ts']) ? (int) $slot['end_ts'] : 0;

        if (event_o_get_slot_visibility_until($startTs, $endTs, $graceDays) >= $now) {
            return true;
        }
    }

    return false;
}

function event_o_filter_event_posts(array $posts, array $attrs, ?int $postsPerPage = null): array
{
    $showPast = !empty($attrs['showPast']);

    if (!$showPast) {
        $now = time();
        $graceDays = event_o_get_past_grace_days();
        $posts = array_values(array_filter($posts, static function ($post) use ($now, $graceDays): bool {
            return $post instanceof WP_Post && event_o_is_event_visible((int) $post->ID, $now, $graceDays);
        }));
    }

    $limit = $postsPerPage !== null ? $postsPerPage : (isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 10);
    if ($limit > -1) {
        $posts = array_slice($posts, 0, $limit);
    }

    return $posts;
}

function event_o_get_event_sort_timestamp(int $postId, string $order = 'ASC', bool $showPast = false, ?int $now = null, ?int $graceDays = null): int
{
    $slots = event_o_get_all_date_slots($postId);
    if (!$slots) {
        return $order === 'DESC' ? PHP_INT_MIN : PHP_INT_MAX;
    }

    $timestamps = [];
    $now = $now ?? time();
    $graceDays = $graceDays ?? event_o_get_past_grace_days();

    foreach ($slots as $slot) {
        $startTs = isset($slot['start_ts']) ? (int) $slot['start_ts'] : 0;
        $endTs = isset($slot['end_ts']) ? (int) $slot['end_ts'] : 0;
        if ($startTs <= 0) {
            continue;
        }

        if (!$showPast && event_o_get_slot_visibility_until($startTs, $endTs, $graceDays) < $now) {
            continue;
        }

        $timestamps[] = $startTs;
    }

    if (!$timestamps) {
        foreach ($slots as $slot) {
            $startTs = isset($slot['start_ts']) ? (int) $slot['start_ts'] : 0;
            if ($startTs > 0) {
                $timestamps[] = $startTs;
            }
        }
    }

    if (!$timestamps) {
        return $order === 'DESC' ? PHP_INT_MIN : PHP_INT_MAX;
    }

    return $order === 'DESC' ? max($timestamps) : min($timestamps);
}

function event_o_sort_event_posts(array $posts, array $attrs): array
{
    $order = 'ASC';
    $preferHighlights = !empty($attrs['preferHighlights']);
    if (isset($attrs['sortOrder'])) {
        $candidate = strtoupper((string) $attrs['sortOrder']);
        if (in_array($candidate, ['ASC', 'DESC'], true)) {
            $order = $candidate;
        }
    } elseif (!empty($attrs['showPast'])) {
        $order = 'DESC';
    }

    $showPast = !empty($attrs['showPast']);
    $now = time();
    $graceDays = event_o_get_past_grace_days();

    usort($posts, static function ($left, $right) use ($order, $showPast, $now, $graceDays, $preferHighlights): int {
        if (!$left instanceof WP_Post || !$right instanceof WP_Post) {
            return 0;
        }

        if ($preferHighlights) {
            $leftHighlighted = event_o_is_event_highlight_active((int) $left->ID);
            $rightHighlighted = event_o_is_event_highlight_active((int) $right->ID);

            if ($leftHighlighted !== $rightHighlighted) {
                return $leftHighlighted ? -1 : 1;
            }
        }

        if ($showPast && $order === 'ASC') {
            $leftUpcomingTs = event_o_get_event_sort_timestamp((int) $left->ID, 'ASC', false, $now, $graceDays);
            $rightUpcomingTs = event_o_get_event_sort_timestamp((int) $right->ID, 'ASC', false, $now, $graceDays);
            $leftHasUpcoming = $leftUpcomingTs !== PHP_INT_MAX;
            $rightHasUpcoming = $rightUpcomingTs !== PHP_INT_MAX;

            if ($leftHasUpcoming !== $rightHasUpcoming) {
                return $leftHasUpcoming ? -1 : 1;
            }

            if ($leftHasUpcoming && $rightHasUpcoming) {
                if ($leftUpcomingTs === $rightUpcomingTs) {
                    return strcasecmp($left->post_title, $right->post_title);
                }

                return $leftUpcomingTs <=> $rightUpcomingTs;
            }

            $leftPastTs = event_o_get_event_sort_timestamp((int) $left->ID, 'DESC', true, $now, $graceDays);
            $rightPastTs = event_o_get_event_sort_timestamp((int) $right->ID, 'DESC', true, $now, $graceDays);

            if ($leftPastTs === $rightPastTs) {
                return strcasecmp($left->post_title, $right->post_title);
            }

            return $rightPastTs <=> $leftPastTs;
        }

        $leftTs = event_o_get_event_sort_timestamp((int) $left->ID, $order, $showPast, $now, $graceDays);
        $rightTs = event_o_get_event_sort_timestamp((int) $right->ID, $order, $showPast, $now, $graceDays);

        if ($leftTs === $rightTs) {
            return strcasecmp($left->post_title, $right->post_title);
        }

        return $order === 'DESC' ? ($rightTs <=> $leftTs) : ($leftTs <=> $rightTs);
    });

    return $posts;
}

function event_o_get_event_query_args(array $attrs, ?int $postsPerPage = null): array
{
    $perPage = isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 10;
    $showPast = !empty($attrs['showPast']);
    $order = $showPast ? 'DESC' : 'ASC';

    if (isset($attrs['sortOrder'])) {
        $candidate = strtoupper((string) $attrs['sortOrder']);
        if (in_array($candidate, ['ASC', 'DESC'], true)) {
            $order = $candidate;
        }
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
        'posts_per_page' => $postsPerPage !== null ? $postsPerPage : $perPage,
        'meta_key' => EVENT_O_META_START_TS,
        'orderby' => 'meta_value_num',
        'order' => $order,
    ];

    if (!$showPast) {
        $args['meta_query'] = event_o_get_upcoming_meta_query();
    }

    if (count($taxQuery) > 1) {
        $args['tax_query'] = $taxQuery;
    }

    return $args;
}

function event_o_event_query(array $attrs): WP_Query
{
    $query = new WP_Query(event_o_get_event_query_args($attrs, -1));
    $filteredPosts = event_o_filter_event_posts($query->posts, $attrs, -1);
    $filteredPosts = event_o_sort_event_posts($filteredPosts, $attrs);

    $limit = isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 10;
    $filteredPosts = array_slice($filteredPosts, 0, $limit);

    $query->posts = $filteredPosts;
    $query->post_count = count($filteredPosts);
    $query->found_posts = $query->post_count;
    $query->max_num_pages = $query->post_count > 0 ? 1 : 0;

    return $query;
}

function event_o_is_event_highlight_active(int $postId): bool
{
    $isHighlight = (bool) get_post_meta($postId, EVENT_O_META_HIGHLIGHT, true);
    if (!$isHighlight) {
        return false;
    }

    $highlightUntilTs = (int) get_post_meta($postId, EVENT_O_META_HIGHLIGHT_UNTIL, true);
    if ($highlightUntilTs > 0 && $highlightUntilTs < time()) {
        return false;
    }

    return true;
}

function event_o_get_highlight_badge_style_value(array $attrs): string
{
    $gradient = isset($attrs['highlightGradient']) ? trim((string) $attrs['highlightGradient']) : '';
    if ($gradient !== '') {
        return $gradient;
    }

    return isset($attrs['highlightColor']) ? trim((string) $attrs['highlightColor']) : '';
}

function event_o_is_event_today(int $postId, ?DateTimeZone $timezone = null): bool
{
    static $cache = [];

    $timezone = $timezone ?: wp_timezone();
    $today = new DateTimeImmutable('now', $timezone);
    $cacheBucket = $timezone->getName() . '|' . $today->format('Y-m-d');

    if (isset($cache[$cacheBucket][$postId])) {
        return $cache[$cacheBucket][$postId];
    }

    $todayStart = $today->setTime(0, 0, 0)->getTimestamp();
    $todayEnd = $today->setTime(23, 59, 59)->getTimestamp();
    $dateSlots = event_o_get_all_date_slots($postId);

    foreach ($dateSlots as $slot) {
        $slotStartTs = isset($slot['start_ts']) ? (int) $slot['start_ts'] : 0;
        if ($slotStartTs >= $todayStart && $slotStartTs <= $todayEnd) {
            $cache[$cacheBucket][$postId] = true;
            return true;
        }
    }

    $cache[$cacheBucket][$postId] = false;
    return false;
}

function event_o_render_highlight_badge(string $highlightColor = ''): string
{
    $class = 'event-o-highlight-badge';
    $style = '';
    if ($highlightColor !== '') {
        $isGradient = stripos($highlightColor, 'gradient') !== false;
        if ($isGradient) {
            $class .= ' is-gradient';
        }
        $style = ' style="--event-o-hl-color:' . esc_attr($highlightColor) . ';"';
    }
    return '<span class="' . esc_attr($class) . '"' . $style . '>' . esc_html__('Highlight', 'event-o') . '</span>';
}

function event_o_get_hero_display_posts(array $attrs): array
{
    $perPage = isset($attrs['perPage']) ? max(1, (int) $attrs['perPage']) : 5;
    $onePerCategory = !empty($attrs['onePerCategory']);
    $preferHighlights = !array_key_exists('preferHighlights', $attrs) || !empty($attrs['preferHighlights']);
    $query = new WP_Query(event_o_get_event_query_args($attrs, -1));
    $orderedPosts = event_o_filter_event_posts($query->posts, $attrs, -1);
    $orderedPosts = event_o_sort_event_posts($orderedPosts, $attrs);

    if ($preferHighlights && count($orderedPosts) > 1) {
        $highlightedPosts = [];
        $regularPosts = [];

        foreach ($orderedPosts as $post) {
            if (event_o_is_event_highlight_active($post->ID)) {
                $highlightedPosts[] = $post;
            } else {
                $regularPosts[] = $post;
            }
        }

        $orderedPosts = array_merge($highlightedPosts, $regularPosts);
    }

    if (!$onePerCategory) {
        return array_slice($orderedPosts, 0, $perPage);
    }

    $displayPosts = [];
    $seenCategories = [];

    foreach ($orderedPosts as $post) {
        $categoryName = event_o_get_first_term_name($post->ID, 'event_o_category');
        if ($categoryName !== '' && in_array($categoryName, $seenCategories, true)) {
            continue;
        }
        if ($categoryName !== '') {
            $seenCategories[] = $categoryName;
        }

        $displayPosts[] = $post;
        if (count($displayPosts) >= $perPage) {
            break;
        }
    }

    return $displayPosts;
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

function event_o_normalize_event_status(string $status): string
{
    $status = strtolower(trim($status));
    if ($status === 'sold-out') {
        return 'soldout';
    }
    return $status;
}

function event_o_get_event_status(int $postId): string
{
    $status = (string) get_post_meta($postId, EVENT_O_META_STATUS, true);
    if ($status === '') {
        $status = (string) get_post_meta($postId, EVENT_O_LEGACY_META_STATUS, true);
    }

    return event_o_normalize_event_status($status);
}

function event_o_get_event_status_label(int $postId, string $status = ''): string
{
    $status = $status !== '' ? event_o_normalize_event_status($status) : event_o_get_event_status($postId);
    if ($status === '' || $status === 'normal' || $status === 'scheduled') {
        return '';
    }

    if ($status === 'soldout') {
        $customLabel = trim((string) get_post_meta($postId, EVENT_O_META_STATUS_LABEL, true));
        if ($customLabel !== '') {
            return $customLabel;
        }
    }

    $statusLabels = [
        'cancelled' => __('Abgesagt', 'event-o'),
        'postponed' => __('Verschoben', 'event-o'),
        'soldout' => __('Ausverkauft', 'event-o'),
    ];

    return $statusLabels[$status] ?? mb_strtoupper($status);
}

/**
 * Parse bands meta and render band links HTML.
 * Returns empty string if no bands found.
 */
function event_o_render_bands(int $postId, string $wrapperClass = 'event-o-bands'): string
{
    $bandsRaw = (string) get_post_meta($postId, EVENT_O_META_BANDS, true);
    if ($bandsRaw === '') {
        return '';
    }

    $lines = array_filter(array_map('trim', explode("\n", $bandsRaw)));
    if (empty($lines)) {
        return '';
    }

    $out = '<div class="' . esc_attr($wrapperClass) . '">';
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        $name = $parts[0] ?? '';
        $spotify = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : '';
        $bandcamp = isset($parts[2]) && $parts[2] !== '' ? $parts[2] : '';
        $website = isset($parts[3]) && $parts[3] !== '' ? $parts[3] : '';

        $out .= '<div class="event-o-band-item">';
        if ($name !== '') {
            $out .= '<span class="event-o-band-name">' . esc_html($name) . '</span>';
        }
        if ($spotify !== '') {
            $out .= '<a href="' . esc_url($spotify) . '" target="_blank" rel="noopener noreferrer" class="event-o-band-link event-o-band-spotify" title="Spotify">';
            $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>';
            $out .= '</a>';
        }
        if ($bandcamp !== '') {
            $out .= '<a href="' . esc_url($bandcamp) . '" target="_blank" rel="noopener noreferrer" class="event-o-band-link event-o-band-bandcamp" title="Bandcamp">';
            $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M0 18.75l7.437-13.5H24l-7.438 13.5H0z"/></svg>';
            $out .= '</a>';
        }
        if ($website !== '') {
            $out .= '<a href="' . esc_url($website) . '" target="_blank" rel="noopener noreferrer" class="event-o-band-link event-o-band-website" title="Website">';
            $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
            $out .= '</a>';
        }
        $out .= '</div>';
    }
    $out .= '</div>';
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
        return ['date' => '', 'time' => '', 'end_date' => ''];
    }

    $tz = wp_timezone();
    $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);

    $day = $start->format('j');
    $month = event_o_get_german_month((int) $start->format('n'));
    $year = $start->format('Y');
    $date = $day . '. ' . $month . ' ' . $year;

    $time = $start->format('H:i');
    $endDate = '';

    if ($endTs > 0) {
        $end = (new DateTimeImmutable('@' . $endTs))->setTimezone($tz);
        $sameDay = $start->format('Y-m-d') === $end->format('Y-m-d');

        if ($sameDay) {
            // Same day: just show end time
            $time .= ' – ' . $end->format('H:i') . ' Uhr';
        } else {
            // Different day: show start time + end date with time
            $time .= ' Uhr';
            $endDay = $end->format('j');
            $endMonth = event_o_get_german_month((int) $end->format('n'));
            $endYear = $end->format('Y');
            $endDate = $endDay . '. ' . $endMonth . ' ' . $endYear . ', ' . $end->format('H:i') . ' Uhr';
        }
    } else {
        $time .= ' Uhr';
    }

    return ['date' => $date, 'time' => $time, 'end_date' => $endDate];
}

/**
 * Format a single date slot as "02. April 2026 16:00 – 19:00 Uhr".
 */
function event_o_format_date_slot(int $startTs, int $endTs = 0): string
{
    if ($startTs <= 0) {
        return '';
    }

    $tz = wp_timezone();
    $start = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz);

    $day = $start->format('j');
    $month = event_o_get_german_month((int) $start->format('n'));
    $year = $start->format('Y');
    $line = $day . '. ' . $month . ' ' . $year . ' ' . $start->format('H:i');

    if ($endTs > 0) {
        $end = (new DateTimeImmutable('@' . $endTs))->setTimezone($tz);
        $line .= ' – ' . $end->format('H:i');
    }

    $line .= ' Uhr';
    return $line;
}

/**
 * Get all date slots for an event (up to 3).
 * Returns an array of ['start_ts' => int, 'end_ts' => int, 'formatted' => string].
 */
function event_o_get_all_date_slots(int $postId): array
{
    $slots = [];

    $pairs = [
        [EVENT_O_META_START_TS, EVENT_O_META_END_TS, EVENT_O_META_BEGIN_TIME],
        [EVENT_O_META_START_TS_2, EVENT_O_META_END_TS_2, EVENT_O_META_BEGIN_TIME_2],
        [EVENT_O_META_START_TS_3, EVENT_O_META_END_TS_3, EVENT_O_META_BEGIN_TIME_3],
    ];

    // Backward compat for slot 1
    $legacyPairs = [
        [EVENT_O_LEGACY_META_START_TS, EVENT_O_LEGACY_META_END_TS],
    ];

    foreach ($pairs as $i => $pair) {
        $startTs = (int) get_post_meta($postId, $pair[0], true);
        $endTs = (int) get_post_meta($postId, $pair[1], true);
        $beginTime = (string) get_post_meta($postId, $pair[2], true);

        // Backward compat for first slot only
        if ($i === 0) {
            if ($startTs <= 0) {
                $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
            }
            if ($endTs <= 0) {
                $endTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_END_TS, true);
            }
        }

        if ($startTs > 0) {
            $slots[] = [
                'start_ts' => $startTs,
                'end_ts' => $endTs > 0 ? $endTs : 0,
                'begin_time' => $beginTime,
                'formatted' => event_o_format_date_slot($startTs, $endTs),
            ];
        }
    }

    usort($slots, static function (array $left, array $right): int {
        return ((int) ($left['start_ts'] ?? 0)) <=> ((int) ($right['start_ts'] ?? 0));
    });

    return $slots;
}

/**
 * Render date slots HTML (stacked lines).
 */
function event_o_render_date_slots_html(array $slots, string $itemClass = 'event-o-date-slot'): string
{
    if (empty($slots)) {
        return '';
    }
    $out = '';
    foreach ($slots as $slot) {
        $out .= '<div class="' . esc_attr($itemClass) . '">' . esc_html($slot['formatted']) . '</div>';
    }
    return $out;
}

/**
 * Get ordered event images: featured image first + up to 2 gallery images.
 */
function event_o_get_event_image_urls(int $postId, string $size = 'large'): array
{
    $urls = [];
    $usedIds = [];

    $featuredId = (int) get_post_thumbnail_id($postId);
    if ($featuredId > 0) {
        $featuredUrl = wp_get_attachment_image_url($featuredId, $size);
        if ($featuredUrl) {
            $urls[] = $featuredUrl;
            $usedIds[$featuredId] = true;
        }
    }

    $galleryRaw = (string) get_post_meta($postId, EVENT_O_META_GALLERY_IDS, true);
    if ($galleryRaw !== '') {
        $galleryIds = array_values(array_filter(array_map('absint', array_map('trim', explode(',', $galleryRaw))), static fn($id) => $id > 0));
        $galleryIds = array_slice(array_unique($galleryIds), 0, 2);

        foreach ($galleryIds as $imageId) {
            if (isset($usedIds[$imageId])) {
                continue;
            }
            $url = wp_get_attachment_image_url($imageId, $size);
            if ($url) {
                $urls[] = $url;
                $usedIds[$imageId] = true;
            }
        }
    }

    return $urls;
}

function event_o_render_event_image_crossfade(array $urls, string $wrapperClass, string $imgClass = '', string $alt = ''): string
{
    if (empty($urls)) {
        return '';
    }

    $wrapperClass = trim($wrapperClass . ' event-o-crossfade');
    $out = '<div class="' . esc_attr($wrapperClass) . '"';
    if (count($urls) > 1) {
        $out .= ' data-event-o-crossfade="1" data-crossfade-interval="4500"';
    }
    $out .= '>';

    foreach ($urls as $index => $url) {
        $slideClass = trim('event-o-crossfade-slide ' . $imgClass . ($index === 0 ? ' is-active' : ''));
        $loading = $index === 0 ? 'eager' : 'lazy';
        $out .= '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($slideClass) . '" loading="' . esc_attr($loading) . '">';
    }

    $out .= '</div>';
    return $out;
}

function event_o_render_event_bg_crossfade(array $urls, string $wrapperClass = 'event-o-hero-bg'): string
{
    if (empty($urls)) {
        return '<div class="' . esc_attr($wrapperClass) . ' event-o-hero-bg-placeholder"></div>';
    }

    if (count($urls) === 1) {
        return '<div class="' . esc_attr($wrapperClass) . '" style="background-image: url(\'' . esc_url($urls[0]) . '\');"></div>';
    }

    $out = '<div class="' . esc_attr($wrapperClass) . ' event-o-bg-crossfade" data-event-o-crossfade="1" data-crossfade-interval="5000">';
    foreach ($urls as $index => $url) {
        $slideClass = 'event-o-crossfade-slide event-o-bg-crossfade-slide' . ($index === 0 ? ' is-active' : '');
        $out .= '<div class="' . esc_attr($slideClass) . '" style="background-image: url(\'' . esc_url($url) . '\');"></div>';
    }
    $out .= '</div>';

    return $out;
}

function event_o_get_event_terms(int $postId, string $taxonomy): array
{
    $terms = get_the_terms($postId, $taxonomy);
    if (!is_array($terms) || !$terms) {
        return [];
    }

    return array_values(array_filter($terms, static function ($term) {
        return $term instanceof WP_Term && !empty($term->name);
    }));
}

function event_o_get_first_term_name(int $postId, string $taxonomy): string
{
    $terms = event_o_get_event_terms($postId, $taxonomy);
    $first = $terms[0] ?? null;
    if (!$first || empty($first->name)) {
        return '';
    }

    return (string) $first->name;
}

function event_o_get_event_category_terms(int $postId): array
{
    return event_o_get_event_terms($postId, 'event_o_category');
}

function event_o_get_event_tag_terms(int $postId): array
{
    return event_o_get_event_terms($postId, 'post_tag');
}

function event_o_get_event_category_names(int $postId): array
{
    return array_map(static function (WP_Term $term): string {
        return (string) $term->name;
    }, event_o_get_event_category_terms($postId));
}

function event_o_get_event_category_details(int $postId): array
{
    $details = [];

    foreach (event_o_get_event_category_terms($postId) as $term) {
        $details[] = [
            'name' => (string) $term->name,
            'color' => (string) get_term_meta($term->term_id, 'event_o_category_color', true),
        ];
    }

    return $details;
}

function event_o_render_event_category_labels(int $postId, array $args = []): string
{
    $terms = event_o_get_event_category_terms($postId);
    $fallback = isset($args['fallback']) ? (string) $args['fallback'] : '';
    $wrapperClass = isset($args['wrapper_class']) ? (string) $args['wrapper_class'] : '';
    $itemClass = isset($args['item_class']) ? (string) $args['item_class'] : 'event-o-category-hint';
    $uppercase = !empty($args['uppercase']);
    $useColors = !array_key_exists('use_colors', $args) || !empty($args['use_colors']);
    $parentheses = !empty($args['parentheses']);

    if (!$terms && $fallback === '') {
        return '';
    }

    $items = [];

    if ($terms) {
        foreach ($terms as $term) {
            $label = (string) $term->name;
            if ($uppercase) {
                $label = mb_strtoupper($label);
            }
            if ($parentheses) {
                $label = '(' . $label . ')';
            }

            $style = '';
            if ($useColors) {
                $termColor = (string) get_term_meta($term->term_id, 'event_o_category_color', true);
                if ($termColor !== '') {
                    $style = ' style="color:' . esc_attr($termColor) . '"';
                }
            }

            $items[] = '<span class="' . esc_attr($itemClass) . '"' . $style . '>' . esc_html($label) . '</span>';
        }
    } elseif ($fallback !== '') {
        $label = $uppercase ? mb_strtoupper($fallback) : $fallback;
        if ($parentheses) {
            $label = '(' . $label . ')';
        }
        $items[] = '<span class="' . esc_attr($itemClass) . '">' . esc_html($label) . '</span>';
    }

    if (!$items) {
        return '';
    }

    $html = implode('', $items);
    if ($wrapperClass !== '') {
        $html = '<div class="' . esc_attr($wrapperClass) . '">' . $html . '</div>';
    }

    return $html;
}

function event_o_render_event_tag_links(int $postId, array $args = []): string
{
    $terms = event_o_get_event_tag_terms($postId);
    if (!$terms) {
        return '';
    }

    $wrapperClass = isset($args['wrapper_class']) ? (string) $args['wrapper_class'] : 'event-o-tag-list';
    $label = isset($args['label']) ? (string) $args['label'] : __('Schlagwörter:', 'event-o');
    $labelClass = isset($args['label_class']) ? (string) $args['label_class'] : 'event-o-tag-label';
    $itemsClass = isset($args['items_class']) ? (string) $args['items_class'] : 'event-o-tag-items';
    $itemClass = isset($args['item_class']) ? (string) $args['item_class'] : 'event-o-tag-chip';

    $links = [];
    foreach ($terms as $term) {
        $termLink = get_term_link($term);
        if (is_wp_error($termLink)) {
            continue;
        }

        $links[] = '<a class="' . esc_attr($itemClass) . '" href="' . esc_url($termLink) . '">' . esc_html($term->name) . '</a>';
    }

    if (!$links) {
        return '';
    }

    return '<div class="' . esc_attr($wrapperClass) . '">'
        . '<span class="' . esc_attr($labelClass) . '">' . esc_html($label) . '</span>'
        . '<div class="' . esc_attr($itemsClass) . '">' . implode('', $links) . '</div>'
        . '</div>';
}

/**
 * Get the color assigned to the first category term of an event.
 * Returns an empty string if no color is set.
 */
function event_o_get_first_category_color(int $postId): string
{
    $terms = event_o_get_event_category_terms($postId);
    $first = $terms[0] ?? null;
    if (!$first) {
        return '';
    }

    $color = get_term_meta($first->term_id, 'event_o_category_color', true);
    return is_string($color) ? $color : '';
}

function event_o_get_category_color_by_slug(string $slug): string
{
    if ($slug === '') {
        return '';
    }

    $term = get_term_by('slug', $slug, 'event_o_category');
    if (!$term instanceof WP_Term) {
        return '';
    }

    $color = get_term_meta($term->term_id, 'event_o_category_color', true);
    return is_string($color) ? $color : '';
}

/**
 * Return '#fff' or '#000' depending on which has better contrast against the given hex color.
 */
function event_o_contrast_text_color(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    // Relative luminance (W3C formula)
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.55 ? '#000' : '#fff';
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

    $out = '<div class="event-o-share-buttons">';

    // Facebook
    if (in_array('facebook', $enabledOptions, true)) {
        $out .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl . '" target="_blank" rel="noopener noreferrer" class="event-o-share-btn event-o-share-facebook" aria-label="Auf Facebook teilen" title="Facebook">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073c0 6.019 4.388 10.998 10.125 11.854v-8.385H7.078v-3.47h3.047V9.41c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.49 0-1.956.926-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.071 24 18.092 24 12.073z"/></svg>';
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

    // Instagram
    if (in_array('instagram', $enabledOptions, true)) {
        $out .= '<a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" class="event-o-share-btn event-o-share-instagram" aria-label="Auf Instagram teilen" title="Instagram">';
        $out .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5M12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10m0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>';
        $out .= '</a>';
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

function event_o_get_referenced_event_data(int $postId): ?array
{
    $relatedEventId = (int) get_post_meta($postId, EVENT_O_META_RELATED_EVENT_ID, true);
    if ($relatedEventId <= 0 || $relatedEventId === $postId) {
        return null;
    }

    $relatedPost = get_post($relatedEventId);
    if (!$relatedPost instanceof WP_Post || $relatedPost->post_type !== 'event_o_event' || $relatedPost->post_status !== 'publish') {
        return null;
    }

    $dateSlots = event_o_get_all_date_slots($relatedEventId);
    $imageUrls = event_o_get_event_image_urls($relatedEventId, 'medium');

    return [
        'id' => $relatedEventId,
        'title' => get_the_title($relatedEventId),
        'permalink' => get_permalink($relatedEventId),
        'dateSlots' => $dateSlots,
        'imageUrls' => $imageUrls,
        'categoryMarkup' => event_o_render_event_category_labels($relatedEventId, [
            'wrapper_class' => 'event-o-reference-card-cats',
            'item_class' => 'event-o-reference-card-cat',
            'uppercase' => true,
        ]),
    ];
}

function event_o_render_referenced_event_card(int $postId, array $args = []): string
{
    $reference = event_o_get_referenced_event_data($postId);
    if (!$reference) {
        return '';
    }

    $wrapperClass = isset($args['wrapper_class']) ? (string) $args['wrapper_class'] : 'event-o-reference-card-wrap';
    $cardClass = isset($args['card_class']) ? (string) $args['card_class'] : 'event-o-reference-card';
    $label = isset($args['label']) ? (string) $args['label'] : __('Verweist auf', 'event-o');
    $titleTag = isset($args['title_tag']) ? (string) $args['title_tag'] : 'h4';
    if (!in_array($titleTag, ['h3', 'h4', 'h5', 'div'], true)) {
        $titleTag = 'h4';
    }

    $out = '<div class="' . esc_attr($wrapperClass) . '">';
    $out .= '<span class="event-o-reference-card-label">' . esc_html($label) . '</span>';
    $out .= '<a class="' . esc_attr($cardClass) . '" href="' . esc_url($reference['permalink']) . '">';

    if (!empty($reference['imageUrls'])) {
        $out .= '<div class="event-o-reference-card-image">';
        $out .= event_o_render_event_image_crossfade($reference['imageUrls'], 'event-o-reference-card-fade', '', $reference['title']);
        $out .= '</div>';
    }

    $out .= '<div class="event-o-reference-card-body">';
    if ($reference['categoryMarkup'] !== '') {
        $out .= $reference['categoryMarkup'];
    }
    if (!empty($reference['dateSlots'])) {
        $out .= '<div class="event-o-reference-card-date">' . event_o_render_date_slots_html($reference['dateSlots'], 'event-o-reference-card-date-slot') . '</div>';
    }
    $out .= '<' . $titleTag . ' class="event-o-reference-card-title">' . esc_html($reference['title']) . '</' . $titleTag . '>';
    $out .= '</div>';

    $out .= '</a>';
    $out .= '</div>';

    return $out;
}

require_once EVENT_O_PLUGIN_DIR . 'includes/render/list.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/render/carousel.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/render/grid.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/render/program.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/render/calendar.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/render/hero.php';

/**
 * Get related events for single page (excluding current event).
 */
function event_o_get_related_events(int $excludeId, int $limit = 4, int $categoryTermId = 0): array
{
    $args = [
        'post_type' => 'event_o_event',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'post__not_in' => [$excludeId],
        'meta_key' => EVENT_O_META_START_TS,
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => event_o_get_upcoming_meta_query(),
    ];

    if ($categoryTermId > 0) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'event_o_category',
                'field' => 'term_id',
                'terms' => $categoryTermId,
            ],
        ];
    }

    $q = new WP_Query($args);
    $posts = event_o_filter_event_posts($q->posts, ['showPast' => false], $limit);
    $events = [];

    foreach ($posts as $post) {
        $postId = $post->ID;
        $dateSlots = event_o_get_all_date_slots($postId);

        $excerpt = isset($post->post_excerpt) ? (string) $post->post_excerpt : '';
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($post->post_content, 35, '...');
        } else {
            $excerpt = wp_trim_words($excerpt, 35, '...');
        }

        $events[] = [
            'id' => $postId,
            'title' => get_the_title($postId),
            'permalink' => get_permalink($postId),
            'date' => !empty($dateSlots) ? $dateSlots[0]['formatted'] : '',
            'dateSlots' => $dateSlots,
            'thumbnail' => has_post_thumbnail($postId) ? get_the_post_thumbnail_url($postId, 'medium') : '',
            'imageUrls' => event_o_get_event_image_urls($postId, 'medium'),
            'excerpt' => $excerpt,
            'category' => event_o_get_first_term_name($postId, 'event_o_category'),
        ];
    }

    return $events;
}
