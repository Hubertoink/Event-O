<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_register_post_type(): void
{
    $labels = [
        'name' => __('Event-O Events', 'event-o'),
        'singular_name' => __('Event-O Event', 'event-o'),
        'add_new' => __('Add New', 'event-o'),
        'add_new_item' => __('Add New Event', 'event-o'),
        'edit_item' => __('Edit Event', 'event-o'),
        'new_item' => __('New Event', 'event-o'),
        'view_item' => __('View Event', 'event-o'),
        'search_items' => __('Search Events', 'event-o'),
        'not_found' => __('No events found', 'event-o'),
        'not_found_in_trash' => __('No events found in Trash', 'event-o'),
        'all_items' => __('All Events', 'event-o'),
        'menu_name' => __('Event-O', 'event-o'),
        'name_admin_bar' => __('Event-O Event', 'event-o'),
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'events'],
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'taxonomies' => ['post_tag'],
        'capability_type' => ['event_o_event', 'event_o_events'],
        'map_meta_cap' => true,
    ];

    register_post_type('event_o_event', $args);
}

function event_o_should_extend_frontend_search(\WP_Query $query): bool
{
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
        return false;
    }

    if ((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()) {
        return false;
    }

    $search = trim((string) $query->get('s'));
    return $search !== '';
}

function event_o_add_events_to_search_query(\WP_Query $query): void
{
    if (!event_o_should_extend_frontend_search($query)) {
        return;
    }

    $postTypes = $query->get('post_type');
    if (empty($postTypes) || $postTypes === 'any') {
        $postTypes = array_values(array_unique(array_merge(
            get_post_types(['exclude_from_search' => false]),
            ['event_o_event']
        )));
        $query->set('post_type', $postTypes);
        return;
    }

    if (is_string($postTypes)) {
        $postTypes = [$postTypes];
    }

    if (!is_array($postTypes) || in_array('event_o_event', $postTypes, true)) {
        return;
    }

    $postTypes[] = 'event_o_event';
    $query->set('post_type', array_values(array_unique($postTypes)));
}
add_action('pre_get_posts', 'event_o_add_events_to_search_query');

function event_o_should_extend_tag_archive_query(\WP_Query $query): bool
{
    if (is_admin() || !$query->is_main_query() || !$query->is_tag()) {
        return false;
    }

    if ((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()) {
        return false;
    }

    return true;
}

function event_o_add_events_to_tag_archive_query(\WP_Query $query): void
{
    if (!event_o_should_extend_tag_archive_query($query)) {
        return;
    }

    $postTypes = $query->get('post_type');

    if (empty($postTypes) || $postTypes === 'any') {
        $postTypes = ['post'];
    } elseif (is_string($postTypes)) {
        $postTypes = [$postTypes];
    }

    if (!is_array($postTypes)) {
        return;
    }

    if (!in_array('event_o_event', $postTypes, true)) {
        $postTypes[] = 'event_o_event';
    }

    $query->set('post_type', array_values(array_unique($postTypes)));
}
add_action('pre_get_posts', 'event_o_add_events_to_tag_archive_query');

function event_o_extend_search_with_taxonomies(string $searchSql, \WP_Query $query): string
{
    global $wpdb;

    if (!event_o_should_extend_frontend_search($query)) {
        return $searchSql;
    }

    $rawSearch = trim((string) $query->get('s'));
    if ($rawSearch === '') {
        return $searchSql;
    }

    $taxonomies = ['event_o_category', 'event_o_venue', 'event_o_organizer', 'post_tag'];
    $tokens = preg_split('/\s+/', $rawSearch);
    if (!is_array($tokens)) {
        $tokens = [$rawSearch];
    }

    $tokens = array_values(array_unique(array_filter(array_map('trim', $tokens), static function ($token) {
        return $token !== '';
    })));

    if (!$tokens) {
        return $searchSql;
    }

    $taxonomyPlaceholders = implode(', ', array_fill(0, count($taxonomies), '%s'));
    $likeClauses = [];
    $preparedArgs = $taxonomies;

    foreach ($tokens as $token) {
        $like = '%' . $wpdb->esc_like($token) . '%';
        $likeClauses[] = '(t.name LIKE %s OR t.slug LIKE %s)';
        $preparedArgs[] = $like;
        $preparedArgs[] = $like;
    }

    if (!$likeClauses) {
        return $searchSql;
    }

    $taxonomyExistsSql = $wpdb->prepare(
        "EXISTS (
            SELECT 1
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tr.object_id = {$wpdb->posts}.ID
              AND tt.taxonomy IN ($taxonomyPlaceholders)
              AND (" . implode(' OR ', $likeClauses) . ")
        )",
        $preparedArgs
    );

    if (!is_user_logged_in()) {
        $taxonomyExistsSql = '(' . $taxonomyExistsSql . " AND {$wpdb->posts}.post_password = '')";
    }

    $searchBody = preg_replace('/^\s*AND\s*/', '', $searchSql, 1);
    if (!is_string($searchBody) || trim($searchBody) === '') {
        return ' AND (' . $taxonomyExistsSql . ')';
    }

    return ' AND ((' . trim($searchBody) . ') OR ' . $taxonomyExistsSql . ')';
}
add_filter('posts_search', 'event_o_extend_search_with_taxonomies', 20, 2);

function event_o_get_duplicate_event_url(int $postId): string
{
    return wp_nonce_url(
        admin_url('admin.php?action=event_o_duplicate_event&post=' . $postId),
        'event_o_duplicate_event_' . $postId
    );
}

function event_o_duplicate_event_row_action(array $actions, WP_Post $post): array
{
    if ($post->post_type !== 'event_o_event') {
        return $actions;
    }

    if (!current_user_can('edit_post', $post->ID)) {
        return $actions;
    }

    $duplicateLink = '<a href="' . esc_url(event_o_get_duplicate_event_url((int) $post->ID)) . '">' . esc_html__('Duplizieren', 'event-o') . '</a>';
    $updated = [];

    foreach ($actions as $key => $label) {
        $updated[$key] = $label;
        if ($key === 'edit') {
            $updated['event_o_duplicate'] = $duplicateLink;
        }
    }

    if (!isset($updated['event_o_duplicate'])) {
        $updated['event_o_duplicate'] = $duplicateLink;
    }

    return $updated;
}
add_filter('post_row_actions', 'event_o_duplicate_event_row_action', 10, 2);

function event_o_duplicate_event_submitbox_action(): void
{
    global $post;

    if (!$post instanceof WP_Post || $post->post_type !== 'event_o_event') {
        return;
    }

    if (!current_user_can('edit_post', $post->ID)) {
        return;
    }

    echo '<div class="misc-pub-section event-o-duplicate-action">';
    echo '<a class="button" href="' . esc_url(event_o_get_duplicate_event_url((int) $post->ID)) . '">' . esc_html__('Als Entwurf duplizieren', 'event-o') . '</a>';
    echo '<p style="margin:8px 0 0;color:#646970;">' . esc_html__('Erstellt eine Kopie mit allen Inhalten, Kategorien und Einstellungen. Danach nur noch Datum anpassen.', 'event-o') . '</p>';
    echo '</div>';
}
add_action('post_submitbox_misc_actions', 'event_o_duplicate_event_submitbox_action');

function event_o_handle_duplicate_event(): void
{
    $sourceId = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if ($sourceId <= 0) {
        wp_die(esc_html__('Ungueltige Event-ID.', 'event-o'));
    }

    check_admin_referer('event_o_duplicate_event_' . $sourceId);

    $source = get_post($sourceId);
    if (!$source instanceof WP_Post || $source->post_type !== 'event_o_event') {
        wp_die(esc_html__('Event nicht gefunden.', 'event-o'));
    }

    if (!current_user_can('edit_post', $sourceId)) {
        wp_die(esc_html__('Du darfst dieses Event nicht duplizieren.', 'event-o'));
    }

    $newPostId = wp_insert_post([
        'post_type' => 'event_o_event',
        'post_status' => 'draft',
        'post_title' => sprintf(__('%s (Kopie)', 'event-o'), $source->post_title),
        'post_content' => $source->post_content,
        'post_excerpt' => $source->post_excerpt,
        'post_author' => get_current_user_id(),
        'menu_order' => (int) $source->menu_order,
        'comment_status' => $source->comment_status,
        'ping_status' => $source->ping_status,
    ], true);

    if (is_wp_error($newPostId)) {
        wp_die(esc_html($newPostId->get_error_message()));
    }

    $taxonomies = get_object_taxonomies('event_o_event');
    foreach ($taxonomies as $taxonomy) {
        $termIds = wp_get_object_terms($sourceId, $taxonomy, ['fields' => 'ids']);
        if (is_wp_error($termIds) || empty($termIds)) {
            continue;
        }
        wp_set_object_terms($newPostId, $termIds, $taxonomy);
    }

    $skipMetaKeys = [
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
    ];

    $allMeta = get_post_meta($sourceId);
    foreach ($allMeta as $metaKey => $values) {
        if (in_array($metaKey, $skipMetaKeys, true)) {
            continue;
        }

        foreach ((array) $values as $value) {
            add_post_meta($newPostId, $metaKey, maybe_unserialize($value));
        }
    }

    $redirectUrl = add_query_arg([
        'post' => $newPostId,
        'action' => 'edit',
        'event_o_duplicated' => 1,
    ], admin_url('post.php'));

    wp_safe_redirect($redirectUrl);
    exit;
}
add_action('admin_action_event_o_duplicate_event', 'event_o_handle_duplicate_event');

function event_o_duplicate_event_admin_notice(): void
{
    if (empty($_GET['event_o_duplicated'])) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'event_o_event') {
        return;
    }

    echo '<div class="notice notice-success is-dismissible"><p>';
    echo esc_html__('Event dupliziert. Bitte jetzt nur noch Datum und Zeiten anpassen.', 'event-o');
    echo '</p></div>';
}
add_action('admin_notices', 'event_o_duplicate_event_admin_notice');
