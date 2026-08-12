<?php
/**
 * Event-O Capabilities, Category Restrictions & Review Workflow.
 *
 * Custom capabilities:
 *   edit_event_o_events, edit_others_event_o_events,
 *   publish_event_o_events, read_private_event_o_events,
 *   delete_event_o_events, delete_others_event_o_events,
 *   delete_published_event_o_events, delete_private_event_o_events,
 *   edit_published_event_o_events, edit_private_event_o_events,
 *   event_o_edit_scoped_events
 *
 * Roles:
 *   event_o_contributor – can create & edit own events but NOT publish (pending review).
 *                          It may also edit non-owned events within its configured
 *                          taxonomy scope via event_o_edit_scoped_events.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ──────────────────────────────────────────────
   1. Capability helpers
   ────────────────────────────────────────────── */

/**
 * All custom capabilities for the event_o_event CPT.
 */
function event_o_get_all_caps(): array
{
    return [
        // Plural / type caps
        'edit_event_o_events'            => true,
        'edit_others_event_o_events'     => true,
        'edit_published_event_o_events'  => true,
        'edit_private_event_o_events'    => true,
        'publish_event_o_events'         => true,
        'read_private_event_o_events'    => true,
        'delete_event_o_events'          => true,
        'delete_others_event_o_events'   => true,
        'delete_published_event_o_events'=> true,
        'delete_private_event_o_events'  => true,
        // Explicit capability for the optional taxonomy-scoped cross-edit workflow.
        'event_o_edit_scoped_events'     => true,
    ];
}

/**
 * Subset of caps for the contributor role (no publish, no others).
 */
function event_o_get_contributor_caps(): array
{
    return [
        'edit_event_o_events'   => true,
        'delete_event_o_events' => true,
        'event_o_edit_scoped_events' => true,
        'read'                  => true, // basic WP dashboard access
        'upload_files'          => true, // allow featured images
    ];
}

/* ──────────────────────────────────────────────
   2. Assign capabilities on plugin activation
   ────────────────────────────────────────────── */

function event_o_assign_capabilities(): void
{
    $allCaps = event_o_get_all_caps();

    // Grant all event caps to administrator
    $admin = get_role('administrator');
    if ($admin) {
        foreach ($allCaps as $cap => $grant) {
            $admin->add_cap($cap, $grant);
        }
    }

    // Grant all event caps to editor
    $editor = get_role('editor');
    if ($editor) {
        foreach ($allCaps as $cap => $grant) {
            $editor->add_cap($cap, $grant);
        }
    }

    // Meta capabilities must not be assigned directly. WordPress maps them to
    // context-specific primitive capabilities such as edit_published_*.
    $legacyDirectMetaCaps = [
        'edit_event_o_event',
        'read_event_o_event',
        'delete_event_o_event',
    ];

    foreach (['administrator', 'editor'] as $roleName) {
        $role = get_role($roleName);
        if ($role) {
            foreach ($legacyDirectMetaCaps as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    // Create or update the custom contributor role without dropping unrelated
    // role configuration added by a site administrator.
    $contributor = get_role('event_o_contributor');
    if (!$contributor) {
        add_role(
            'event_o_contributor',
            __('Event-O Beitragende/r', 'event-o'),
            event_o_get_contributor_caps()
        );
        $contributor = get_role('event_o_contributor');
    }

    if ($contributor) {
        foreach (event_o_get_contributor_caps() as $cap => $grant) {
            $contributor->add_cap($cap, $grant);
        }
        foreach ($legacyDirectMetaCaps as $cap) {
            $contributor->remove_cap($cap);
        }
    }
}

/**
 * Apply capability migrations when an existing plugin installation is updated.
 */
function event_o_maybe_update_capabilities(): void
{
    $schemaVersion = (int) get_option('event_o_capability_schema_version', 0);
    if ($schemaVersion >= 2) {
        return;
    }

    event_o_assign_capabilities();
    update_option('event_o_capability_schema_version', 2, false);
}
add_action('init', 'event_o_maybe_update_capabilities', 1);

/**
 * Remove capabilities on plugin deactivation.
 */
function event_o_remove_capabilities(): void
{
    $allCaps = event_o_get_all_caps();

    foreach (['administrator', 'editor'] as $roleName) {
        $role = get_role($roleName);
        if ($role) {
            foreach ($allCaps as $cap => $grant) {
                $role->remove_cap($cap);
            }
            foreach (['edit_event_o_event', 'read_event_o_event', 'delete_event_o_event'] as $legacyCap) {
                $role->remove_cap($legacyCap);
            }
        }
    }

    remove_role('event_o_contributor');
    delete_option('event_o_capability_schema_version');
}

/* ──────────────────────────────────────────────
   3. User profile: allowed categories
   ────────────────────────────────────────────── */

define('EVENT_O_USER_META_ALLOWED_CATS', 'event_o_allowed_categories');
define('EVENT_O_USER_META_ALLOWED_VENUES', 'event_o_allowed_venues');
define('EVENT_O_USER_META_ALLOWED_ORGANIZERS', 'event_o_allowed_organizers');
define('EVENT_O_USER_META_ALLOW_STANDARD_POSTS', 'event_o_allow_standard_posts');

/**
 * Whether an Event-O contributor may also create normal WordPress posts.
 */
function event_o_user_has_standard_posts_access(int $userId): bool
{
    return (bool) get_user_meta($userId, EVENT_O_USER_META_ALLOW_STANDARD_POSTS, true);
}

/**
 * Get allowed term slugs for a user and taxonomy.
 */
function event_o_get_user_allowed_term_slugs(int $userId, string $taxonomy): array
{
    $metaKey = '';
    if ($taxonomy === 'event_o_category') {
        $metaKey = EVENT_O_USER_META_ALLOWED_CATS;
    } elseif ($taxonomy === 'event_o_venue') {
        $metaKey = EVENT_O_USER_META_ALLOWED_VENUES;
    } elseif ($taxonomy === 'event_o_organizer') {
        $metaKey = EVENT_O_USER_META_ALLOWED_ORGANIZERS;
    }

    if ($metaKey === '') {
        return [];
    }

    $allowed = (array) get_user_meta($userId, $metaKey, true);
    if (!is_array($allowed)) {
        $allowed = [];
    }

    $allowed = array_map('sanitize_title', $allowed);
    $allowed = array_filter($allowed, static function ($value) {
        return $value !== '';
    });

    return array_values(array_unique($allowed));
}

/**
 * Render taxonomy checkboxes for contributor restrictions.
 */
function event_o_render_allowed_terms_checkboxes(string $taxonomy, string $fieldName, array $allowedSlugs): void
{
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ]);

    if (is_array($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $checked = in_array($term->slug, $allowedSlugs, true) ? ' checked' : '';
            echo '<label style="display:block;margin-bottom:6px;">';
            echo '<input type="checkbox" name="' . esc_attr($fieldName) . '[]" value="' . esc_attr($term->slug) . '"' . $checked . '> ';
            echo esc_html($term->name);
            echo '</label>';
        }
        return;
    }

    echo '<em>' . esc_html__('Keine Begriffe vorhanden.', 'event-o') . '</em>';
}

/**
 * Show "Erlaubte Event-Kategorien" section on user profile (admin only).
 */
function event_o_user_profile_fields(\WP_User $user): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Only show for users with event_o_contributor role
    if (!in_array('event_o_contributor', (array) $user->roles, true)) {
        return;
    }

    $allowedCats = event_o_get_user_allowed_term_slugs((int) $user->ID, 'event_o_category');
    $allowedVenues = event_o_get_user_allowed_term_slugs((int) $user->ID, 'event_o_venue');
    $allowedOrganizers = event_o_get_user_allowed_term_slugs((int) $user->ID, 'event_o_organizer');
    $allowStandardPosts = event_o_user_has_standard_posts_access((int) $user->ID);

    echo '<h3>' . esc_html__('Event-O: Erlaubte Kategorien', 'event-o') . '</h3>';
    echo '<p class="description">' . esc_html__('Wenn nichts ausgewählt ist, kann der/die Beitragende alle Begriffe verwenden. Sonst nur die ausgewählten.', 'event-o') . '</p>';
    echo '<table class="form-table"><tr><td>';

    echo '<p><strong>' . esc_html__('Kategorien', 'event-o') . '</strong></p>';
    event_o_render_allowed_terms_checkboxes('event_o_category', 'event_o_allowed_cats', $allowedCats);

    echo '<p style="margin-top:14px;"><strong>' . esc_html__('Venues / Orte', 'event-o') . '</strong></p>';
    event_o_render_allowed_terms_checkboxes('event_o_venue', 'event_o_allowed_venues', $allowedVenues);

    echo '<p style="margin-top:14px;"><strong>' . esc_html__('Veranstalter / Organizers', 'event-o') . '</strong></p>';
    event_o_render_allowed_terms_checkboxes('event_o_organizer', 'event_o_allowed_organizers', $allowedOrganizers);

    echo '<p style="margin-top:18px;"><strong>' . esc_html__('WordPress Beiträge', 'event-o') . '</strong></p>';
    echo '<label style="display:block;margin-bottom:6px;">';
    echo '<input type="checkbox" name="event_o_allow_standard_posts" value="1"' . checked($allowStandardPosts, true, false) . '> ';
    echo esc_html__('Darf auch normale WordPress-Beiträge erstellen und bearbeiten', 'event-o');
    echo '</label>';

    echo '</td></tr></table>';
}
add_action('show_user_profile', 'event_o_user_profile_fields');
add_action('edit_user_profile', 'event_o_user_profile_fields');

/**
 * Save allowed categories from user profile.
 */
function event_o_save_user_profile_fields(int $userId): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $user = get_userdata($userId);
    if (!$user || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return;
    }

    $allowedCats = isset($_POST['event_o_allowed_cats']) && is_array($_POST['event_o_allowed_cats'])
        ? array_map('sanitize_title', $_POST['event_o_allowed_cats'])
        : [];

    $allowedVenues = isset($_POST['event_o_allowed_venues']) && is_array($_POST['event_o_allowed_venues'])
        ? array_map('sanitize_title', $_POST['event_o_allowed_venues'])
        : [];

    $allowedOrganizers = isset($_POST['event_o_allowed_organizers']) && is_array($_POST['event_o_allowed_organizers'])
        ? array_map('sanitize_title', $_POST['event_o_allowed_organizers'])
        : [];

    $allowStandardPosts = !empty($_POST['event_o_allow_standard_posts']);

    update_user_meta($userId, EVENT_O_USER_META_ALLOWED_CATS, array_values(array_unique(array_filter($allowedCats))));
    update_user_meta($userId, EVENT_O_USER_META_ALLOWED_VENUES, array_values(array_unique(array_filter($allowedVenues))));
    update_user_meta($userId, EVENT_O_USER_META_ALLOWED_ORGANIZERS, array_values(array_unique(array_filter($allowedOrganizers))));
    update_user_meta($userId, EVENT_O_USER_META_ALLOW_STANDARD_POSTS, $allowStandardPosts ? '1' : '0');
}
add_action('personal_options_update', 'event_o_save_user_profile_fields');
add_action('edit_user_profile_update', 'event_o_save_user_profile_fields');

/**
 * Optionally allow Event-O contributors to also manage standard WP posts.
 */
function event_o_maybe_grant_standard_post_caps(array $allcaps, array $caps, array $args, \WP_User $user): array
{
    if (!$user->exists() || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return $allcaps;
    }

    if (!event_o_user_has_standard_posts_access((int) $user->ID)) {
        return $allcaps;
    }

    $allcaps['edit_posts'] = true;
    $allcaps['delete_posts'] = true;
    $allcaps['create_posts'] = true;

    return $allcaps;
}
add_filter('user_has_cap', 'event_o_maybe_grant_standard_post_caps', 20, 4);

/**
 * Add Event-O event counts to the users list table.
 */
function event_o_add_users_event_column(array $columns): array
{
    $updated = [];

    foreach ($columns as $key => $label) {
        if ($key === 'posts') {
            $updated['event_o_events'] = __('Event-O Events', 'event-o');
        }

        $updated[$key] = $label;
    }

    if (!isset($updated['event_o_events'])) {
        $updated['event_o_events'] = __('Event-O Events', 'event-o');
    }

    return $updated;
}
add_filter('manage_users_columns', 'event_o_add_users_event_column');

/**
 * Collect Event-O event counts for the currently visible users.
 *
 * Returns:
 * [ user_id => [ 'total' => int, 'publish' => int ] ]
 */
function event_o_get_users_page_event_counts(array $fallbackUserIds = []): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    global $wpdb, $wp_list_table;

    $userIds = [];
    if (isset($wp_list_table) && isset($wp_list_table->items) && is_array($wp_list_table->items)) {
        foreach ($wp_list_table->items as $user) {
            if (isset($user->ID)) {
                $userIds[] = (int) $user->ID;
            }
        }
    }

    if (empty($userIds)) {
        $userIds = array_map('intval', $fallbackUserIds);
    }

    $userIds = array_values(array_filter(array_unique($userIds), static fn($userId) => $userId > 0));
    $cache = [];

    if (empty($userIds)) {
        return $cache;
    }

    foreach ($userIds as $userId) {
        $cache[$userId] = [
            'total' => 0,
            'publish' => 0,
        ];
    }

    $placeholders = implode(', ', array_fill(0, count($userIds), '%d'));
    $sql = $wpdb->prepare(
        "SELECT post_author, post_status, COUNT(ID) AS event_count
         FROM {$wpdb->posts}
         WHERE post_type = %s
           AND post_author IN ($placeholders)
           AND post_status NOT IN ('auto-draft', 'trash', 'inherit')
         GROUP BY post_author, post_status",
        array_merge(['event_o_event'], $userIds)
    );

    $rows = $wpdb->get_results($sql);
    if (!is_array($rows)) {
        return $cache;
    }

    foreach ($rows as $row) {
        $authorId = isset($row->post_author) ? (int) $row->post_author : 0;
        $status = isset($row->post_status) ? (string) $row->post_status : '';
        $count = isset($row->event_count) ? (int) $row->event_count : 0;

        if ($authorId <= 0 || !isset($cache[$authorId])) {
            continue;
        }

        $cache[$authorId]['total'] += $count;
        if ($status === 'publish') {
            $cache[$authorId]['publish'] += $count;
        }
    }

    return $cache;
}

/**
 * Render the Event-O event count column in the users table.
 */
function event_o_render_users_event_column(string $output, string $columnName, int $userId): string
{
    if ($columnName !== 'event_o_events') {
        return $output;
    }

    $counts = event_o_get_users_page_event_counts([$userId]);
    $userCounts = $counts[$userId] ?? ['total' => 0, 'publish' => 0];
    $total = (int) $userCounts['total'];
    $published = (int) $userCounts['publish'];

    if ($total <= 0) {
        return '0';
    }

    $eventsUrl = add_query_arg([
        'post_type' => 'event_o_event',
        'author' => $userId,
    ], admin_url('edit.php'));

    $publishedHtml = sprintf(
        /* translators: %d = number of published events */
        __('%d veröffentlicht', 'event-o'),
        $published
    );

    if (!current_user_can('edit_event_o_events')) {
        return sprintf(
            /* translators: 1: total event count, 2: published event count */
            __('%1$d gesamt, %2$s', 'event-o'),
            $total,
            $publishedHtml
        );
    }

    return '<a href="' . esc_url($eventsUrl) . '"><strong>' . esc_html((string) $total) . '</strong></a>'
        . '<div style="color:#646970;font-size:12px;line-height:1.4">'
        . esc_html($publishedHtml)
        . '</div>';
}
add_filter('manage_users_custom_column', 'event_o_render_users_event_column', 10, 3);

/* ──────────────────────────────────────────────
   4. Restrict categories on save (server-side)
   ────────────────────────────────────────────── */

/**
 * On save_post, enforce category restriction for event_o_contributor users.
 */
function event_o_restrict_contributor_event_terms(int $postId, int $userId): void
{
    foreach (['event_o_category', 'event_o_venue', 'event_o_organizer'] as $taxonomy) {
        $allowed = event_o_get_user_allowed_term_slugs($userId, $taxonomy);
        if (empty($allowed)) {
            continue; // No restriction set → allow all
        }

        $assignedTerms = wp_get_object_terms($postId, $taxonomy, ['fields' => 'slugs']);
        if (is_wp_error($assignedTerms)) {
            continue;
        }

        $validTerms = array_values(array_intersect((array) $assignedTerms, $allowed));

        // If exactly one term is allowed and nothing valid is assigned, auto-assign it.
        if (empty($validTerms) && count($allowed) === 1) {
            $validTerms = [$allowed[0]];
        }

        wp_set_object_terms($postId, $validTerms, $taxonomy);
    }
}

function event_o_enforce_category_restriction(int $postId, \WP_Post $post, bool $update): void
{
    if ($post->post_type !== 'event_o_event' || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
        return;
    }

    $user = wp_get_current_user();
    if (!$user || !$user->exists() || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return;
    }

    event_o_restrict_contributor_event_terms($postId, (int) $user->ID);
}
add_action('save_post', 'event_o_enforce_category_restriction', 20, 3);

/* ──────────────────────────────────────────────
   5. Filter taxonomy panel in Block Editor (REST API)
   ────────────────────────────────────────────── */

/**
 * Filter REST API results for event_o_category taxonomy so that
 * event_o_contributor users only see their allowed categories.
 */
function event_o_filter_allowed_terms_for_rest(array $args, string $taxonomy): array
{
    $user = wp_get_current_user();
    if (!$user || !$user->exists()) {
        return $args;
    }

    if (!in_array('event_o_contributor', (array) $user->roles, true)) {
        return $args;
    }

    $allowed = event_o_get_user_allowed_term_slugs((int) $user->ID, $taxonomy);
    if (empty($allowed)) {
        return $args; // No restriction
    }

    // Only show allowed terms by slug
    $args['slug'] = $allowed;

    return $args;
}

function event_o_filter_category_terms(array $args, \WP_REST_Request $request): array
{
    return event_o_filter_allowed_terms_for_rest($args, 'event_o_category');
}
add_filter('rest_event_o_category_query', 'event_o_filter_category_terms', 10, 2);

function event_o_filter_venue_terms(array $args, \WP_REST_Request $request): array
{
    return event_o_filter_allowed_terms_for_rest($args, 'event_o_venue');
}
add_filter('rest_event_o_venue_query', 'event_o_filter_venue_terms', 10, 2);

function event_o_filter_organizer_terms(array $args, \WP_REST_Request $request): array
{
    return event_o_filter_allowed_terms_for_rest($args, 'event_o_organizer');
}
add_filter('rest_event_o_organizer_query', 'event_o_filter_organizer_terms', 10, 2);

/**
 * Reject forged REST requests that try to assign terms outside a contributor's
 * configured scope. Filtering the term list is only a UI convenience; this is
 * the actual access-control check.
 */
function event_o_validate_rest_event_terms($preparedPost, \WP_REST_Request $request)
{
    $user = wp_get_current_user();
    if (!$user || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return $preparedPost;
    }

    foreach (['event_o_category', 'event_o_venue', 'event_o_organizer'] as $taxonomy) {
        if (!$request->has_param($taxonomy)) {
            continue;
        }

        $allowedSlugs = event_o_get_user_allowed_term_slugs((int) $user->ID, $taxonomy);
        if (empty($allowedSlugs)) {
            continue;
        }

        $termIds = array_values(array_unique(array_filter(array_map('absint', (array) $request->get_param($taxonomy)))));
        if (empty($termIds)) {
            continue;
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'include' => $termIds,
            'hide_empty' => false,
        ]);
        if (is_wp_error($terms) || count($terms) !== count($termIds)) {
            return new \WP_Error(
                'event_o_rest_invalid_term',
                __('Ein angegebener Event-O Begriff ist ungültig.', 'event-o'),
                ['status' => 400]
            );
        }

        foreach ($terms as $term) {
            if (!in_array($term->slug, $allowedSlugs, true)) {
                return new \WP_Error(
                    'event_o_rest_term_not_allowed',
                    __('Du darfst diesen Event-O Begriff nicht verwenden.', 'event-o'),
                    ['status' => 403]
                );
            }
        }
    }

    return $preparedPost;
}
add_filter('rest_pre_insert_event_o_event', 'event_o_validate_rest_event_terms', 10, 2);

/**
 * Defence in depth for REST writes: terms are applied after save_post by core.
 */
function event_o_enforce_rest_event_term_restriction(\WP_Post $post, \WP_REST_Request $request, bool $creating): void
{
    $user = wp_get_current_user();
    if (!$user || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return;
    }

    event_o_restrict_contributor_event_terms((int) $post->ID, (int) $user->ID);
}
add_action('rest_after_insert_event_o_event', 'event_o_enforce_rest_event_term_restriction', 10, 3);

/**
 * Also filter the classic editor / admin taxonomy checklist.
 */
function event_o_filter_category_checklist(array $args, int $postId): array
{
    if (!isset($args['taxonomy']) || !in_array($args['taxonomy'], ['event_o_category', 'event_o_venue', 'event_o_organizer'], true)) {
        return $args;
    }

    $user = wp_get_current_user();
    if (!$user || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return $args;
    }

    $taxonomy = (string) $args['taxonomy'];
    $allowed = event_o_get_user_allowed_term_slugs((int) $user->ID, $taxonomy);
    if (empty($allowed)) {
        return $args;
    }

    // Get term IDs from slugs
    $termIds = [];
    foreach ($allowed as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);
        if ($term) {
            $termIds[] = (int) $term->term_id;
        }
    }

    if (!empty($termIds)) {
        $args['include'] = $termIds;
    }

    return $args;
}
add_filter('wp_terms_checklist_args', 'event_o_filter_category_checklist', 10, 2);

/* ──────────────────────────────────────────────
   6. Scoped cross-edit permission for contributors
   ────────────────────────────────────────────── */

/**
 * Check whether a post matches the contributor's allowed taxonomy scope.
 *
 * Rules:
 * - Only active when at least one allowed list is configured.
 * - For each configured taxonomy list, the post must share at least one term.
 */
function event_o_post_matches_contributor_scope(int $postId, int $userId): bool
{
    $scopeMap = [
        'event_o_category'  => event_o_get_user_allowed_term_slugs($userId, 'event_o_category'),
        'event_o_venue'     => event_o_get_user_allowed_term_slugs($userId, 'event_o_venue'),
        'event_o_organizer' => event_o_get_user_allowed_term_slugs($userId, 'event_o_organizer'),
    ];

    $hasScopedRules = false;
    foreach ($scopeMap as $allowedSlugs) {
        if (!empty($allowedSlugs)) {
            $hasScopedRules = true;
            break;
        }
    }

    if (!$hasScopedRules) {
        return false;
    }

    foreach ($scopeMap as $taxonomy => $allowedSlugs) {
        if (empty($allowedSlugs)) {
            continue;
        }

        $assignedSlugs = wp_get_object_terms($postId, $taxonomy, ['fields' => 'slugs']);
        if (is_wp_error($assignedSlugs)) {
            return false;
        }

        if (empty(array_intersect((array) $assignedSlugs, $allowedSlugs))) {
            return false;
        }
    }

    return true;
}

/**
 * Grant edit access to matching non-owned Event-O events for scoped contributors.
 */
function event_o_map_meta_cap_scoped_edit(array $caps, string $cap, int $userId, array $args): array
{
    if ($cap !== 'edit_post') {
        return $caps;
    }

    $postId = isset($args[0]) ? (int) $args[0] : 0;
    if ($postId <= 0) {
        return $caps;
    }

    $post = get_post($postId);
    if (!$post || $post->post_type !== 'event_o_event') {
        return $caps;
    }

    $user = get_userdata($userId);
    if (!$user || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return $caps;
    }

    // Keep default behavior for own posts.
    if ((int) $post->post_author === $userId) {
        return $caps;
    }

    if (!event_o_post_matches_contributor_scope($postId, $userId)) {
        return $caps;
    }

    return ['event_o_edit_scoped_events'];
}
add_filter('map_meta_cap', 'event_o_map_meta_cap_scoped_edit', 20, 4);

/* ──────────────────────────────────────────────
   7. Admin notification for pending review events
   ────────────────────────────────────────────── */

/**
 * Send email to admin when an event is submitted for review.
 */
function event_o_notify_pending_review(string $newStatus, string $oldStatus, \WP_Post $post): void
{
    if ($post->post_type !== 'event_o_event') {
        return;
    }

    if ($newStatus !== 'pending' || $oldStatus === 'pending') {
        return;
    }

    $adminEmail = get_option('admin_email');
    $author = get_userdata($post->post_author);
    $authorName = $author ? $author->display_name : __('Unbekannt', 'event-o');

    $subject = sprintf(
        /* translators: %s: event title */
        __('[Event-O] Neues Event zur Überprüfung: %s', 'event-o'),
        $post->post_title
    );

    $editLink = admin_url('post.php?post=' . $post->ID . '&action=edit');

    $message = sprintf(
        __("Ein neues Event wurde zur Überprüfung eingereicht.\n\nTitel: %1\$s\nEingereicht von: %2\$s\n\nBearbeiten & freigeben:\n%3\$s", 'event-o'),
        $post->post_title,
        $authorName,
        $editLink
    );

    wp_mail($adminEmail, $subject, $message);
}
add_action('transition_post_status', 'event_o_notify_pending_review', 10, 3);

/* ──────────────────────────────────────────────
    8. Admin: pending events count badge
   ────────────────────────────────────────────── */

/**
 * Add pending event count bubble to the Event-O admin menu.
 */
function event_o_pending_count_badge(): void
{
    $count = wp_count_posts('event_o_event');
    $pending = isset($count->pending) ? (int) $count->pending : 0;

    if ($pending < 1) {
        return;
    }

    global $menu;
    foreach ($menu as &$item) {
        if (isset($item[2]) && $item[2] === 'edit.php?post_type=event_o_event') {
            $item[0] .= sprintf(
                ' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>',
                $pending
            );
            break;
        }
    }
}
add_action('admin_menu', 'event_o_pending_count_badge', 99);

/* ──────────────────────────────────────────────
    9. Dashboard: pending events widget for admins
   ────────────────────────────────────────────── */

/**
 * Register dashboard widget showing pending events.
 */
function event_o_register_dashboard_widget(): void
{
    if (!current_user_can('publish_event_o_events')) {
        return;
    }

    wp_add_dashboard_widget(
        'event_o_pending_widget',
        __('Event-O: Ausstehende Events', 'event-o'),
        'event_o_render_pending_widget'
    );
}
add_action('wp_dashboard_setup', 'event_o_register_dashboard_widget');

function event_o_render_pending_widget(): void
{
    $pending = get_posts([
        'post_type'   => 'event_o_event',
        'post_status' => 'pending',
        'numberposts' => 10,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    if (empty($pending)) {
        echo '<p>' . esc_html__('Keine ausstehenden Events.', 'event-o') . '</p>';
        return;
    }

    echo '<ul style="margin:0;">';
    foreach ($pending as $post) {
        $author = get_userdata($post->post_author);
        $authorName = $author ? $author->display_name : '–';
        $editUrl = get_edit_post_link($post->ID);
        $date = get_the_date('', $post);

        echo '<li style="padding:6px 0;border-bottom:1px solid #eee;">';
        echo '<a href="' . esc_url($editUrl) . '"><strong>' . esc_html($post->post_title) . '</strong></a>';
        echo '<br><small style="color:#666;">von ' . esc_html($authorName) . ' – ' . esc_html($date) . '</small>';
        echo '</li>';
    }
    echo '</ul>';

    $count = wp_count_posts('event_o_event');
    $total = isset($count->pending) ? (int) $count->pending : 0;
    if ($total > 10) {
        echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=event_o_event&post_status=pending')) . '">';
        echo sprintf(esc_html__('Alle %d ausstehenden Events anzeigen →', 'event-o'), $total);
        echo '</a></p>';
    }
}
