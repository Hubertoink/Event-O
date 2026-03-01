<?php
/**
 * Event-O Capabilities, Category Restrictions & Review Workflow.
 *
 * Custom capabilities:
 *   edit_event_o_event, edit_event_o_events, edit_others_event_o_events,
 *   publish_event_o_events, read_event_o_event, read_private_event_o_events,
 *   delete_event_o_event, delete_event_o_events, delete_others_event_o_events,
 *   delete_published_event_o_events, delete_private_event_o_events,
 *   edit_published_event_o_events, edit_private_event_o_events
 *
 * Roles:
 *   event_o_contributor – can create & edit own events but NOT publish (pending review).
 *                          Optionally restricted to specific taxonomy terms via user-meta.
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
        // Primitive / meta caps
        'edit_event_o_event'             => true,
        'read_event_o_event'             => true,
        'delete_event_o_event'           => true,
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
    ];
}

/**
 * Subset of caps for the contributor role (no publish, no others).
 */
function event_o_get_contributor_caps(): array
{
    return [
        'edit_event_o_event'    => true,
        'read_event_o_event'    => true,
        'delete_event_o_event'  => true,
        'edit_event_o_events'   => true,
        'delete_event_o_events' => true,
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

    // Create custom contributor role
    $existing = get_role('event_o_contributor');
    if ($existing) {
        remove_role('event_o_contributor');
    }

    add_role(
        'event_o_contributor',
        __('Event-O Beitragende/r', 'event-o'),
        event_o_get_contributor_caps()
    );
}

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
        }
    }

    remove_role('event_o_contributor');
}

/* ──────────────────────────────────────────────
   3. User profile: allowed categories
   ────────────────────────────────────────────── */

define('EVENT_O_USER_META_ALLOWED_CATS', 'event_o_allowed_categories');
define('EVENT_O_USER_META_ALLOWED_VENUES', 'event_o_allowed_venues');
define('EVENT_O_USER_META_ALLOWED_ORGANIZERS', 'event_o_allowed_organizers');

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

    echo '<h3>' . esc_html__('Event-O: Erlaubte Kategorien', 'event-o') . '</h3>';
    echo '<p class="description">' . esc_html__('Wenn nichts ausgewählt ist, kann der/die Beitragende alle Begriffe verwenden. Sonst nur die ausgewählten.', 'event-o') . '</p>';
    echo '<table class="form-table"><tr><td>';

    echo '<p><strong>' . esc_html__('Kategorien', 'event-o') . '</strong></p>';
    event_o_render_allowed_terms_checkboxes('event_o_category', 'event_o_allowed_cats', $allowedCats);

    echo '<p style="margin-top:14px;"><strong>' . esc_html__('Venues / Orte', 'event-o') . '</strong></p>';
    event_o_render_allowed_terms_checkboxes('event_o_venue', 'event_o_allowed_venues', $allowedVenues);

    echo '<p style="margin-top:14px;"><strong>' . esc_html__('Veranstalter / Organizers', 'event-o') . '</strong></p>';
    event_o_render_allowed_terms_checkboxes('event_o_organizer', 'event_o_allowed_organizers', $allowedOrganizers);

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

    update_user_meta($userId, EVENT_O_USER_META_ALLOWED_CATS, array_values(array_unique(array_filter($allowedCats))));
    update_user_meta($userId, EVENT_O_USER_META_ALLOWED_VENUES, array_values(array_unique(array_filter($allowedVenues))));
    update_user_meta($userId, EVENT_O_USER_META_ALLOWED_ORGANIZERS, array_values(array_unique(array_filter($allowedOrganizers))));
}
add_action('personal_options_update', 'event_o_save_user_profile_fields');
add_action('edit_user_profile_update', 'event_o_save_user_profile_fields');

/* ──────────────────────────────────────────────
   4. Restrict categories on save (server-side)
   ────────────────────────────────────────────── */

/**
 * On save_post, enforce category restriction for event_o_contributor users.
 */
function event_o_enforce_category_restriction(int $postId, \WP_Post $post, bool $update): void
{
    if ($post->post_type !== 'event_o_event') {
        return;
    }

    // Don't run during autosave or REST schema requests
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $user = wp_get_current_user();
    if (!$user || !$user->exists()) {
        return;
    }

    // Only restrict event_o_contributor role
    if (!in_array('event_o_contributor', (array) $user->roles, true)) {
        return;
    }

    foreach (['event_o_category', 'event_o_venue', 'event_o_organizer'] as $taxonomy) {
        $allowed = event_o_get_user_allowed_term_slugs((int) $user->ID, $taxonomy);
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

    return ['edit_event_o_event'];
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
