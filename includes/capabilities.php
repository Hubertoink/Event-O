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
 *                          Optionally restricted to specific categories via user-meta.
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

    $allowed = (array) get_user_meta($user->ID, EVENT_O_USER_META_ALLOWED_CATS, true);
    if (!is_array($allowed)) {
        $allowed = [];
    }

    $allCats = get_terms([
        'taxonomy'   => 'event_o_category',
        'hide_empty' => false,
    ]);

    echo '<h3>' . esc_html__('Event-O: Erlaubte Kategorien', 'event-o') . '</h3>';
    echo '<p class="description">' . esc_html__('Wenn keine Kategorie ausgewählt ist, kann der/die Beitragende alle Kategorien verwenden. Sonst nur die ausgewählten.', 'event-o') . '</p>';
    echo '<table class="form-table"><tr><td>';

    if (is_array($allCats) && !empty($allCats)) {
        foreach ($allCats as $term) {
            $checked = in_array($term->slug, $allowed, true) ? ' checked' : '';
            echo '<label style="display:block;margin-bottom:6px;">';
            echo '<input type="checkbox" name="event_o_allowed_cats[]" value="' . esc_attr($term->slug) . '"' . $checked . '> ';
            echo esc_html($term->name);
            echo '</label>';
        }
    } else {
        echo '<em>' . esc_html__('Keine Event-O Kategorien vorhanden.', 'event-o') . '</em>';
    }

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

    $allowed = isset($_POST['event_o_allowed_cats']) && is_array($_POST['event_o_allowed_cats'])
        ? array_map('sanitize_text_field', $_POST['event_o_allowed_cats'])
        : [];

    update_user_meta($userId, EVENT_O_USER_META_ALLOWED_CATS, $allowed);
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

    $allowed = (array) get_user_meta($user->ID, EVENT_O_USER_META_ALLOWED_CATS, true);
    if (empty($allowed)) {
        return; // No restriction set → allow all
    }

    // Get currently assigned categories
    $assignedTerms = wp_get_object_terms($postId, 'event_o_category', ['fields' => 'slugs']);
    if (is_wp_error($assignedTerms) || empty($assignedTerms)) {
        return;
    }

    // Remove any categories that are not allowed
    $validTerms = array_intersect($assignedTerms, $allowed);
    wp_set_object_terms($postId, $validTerms, 'event_o_category');
}
add_action('save_post', 'event_o_enforce_category_restriction', 20, 3);

/* ──────────────────────────────────────────────
   5. Filter taxonomy panel in Block Editor (REST API)
   ────────────────────────────────────────────── */

/**
 * Filter REST API results for event_o_category taxonomy so that
 * event_o_contributor users only see their allowed categories.
 */
function event_o_filter_category_terms(array $args, \WP_REST_Request $request): array
{
    $user = wp_get_current_user();
    if (!$user || !$user->exists()) {
        return $args;
    }

    if (!in_array('event_o_contributor', (array) $user->roles, true)) {
        return $args;
    }

    $allowed = (array) get_user_meta($user->ID, EVENT_O_USER_META_ALLOWED_CATS, true);
    if (empty($allowed)) {
        return $args; // No restriction
    }

    // Only show allowed terms by slug
    $args['slug'] = $allowed;

    return $args;
}
add_filter('rest_event_o_category_query', 'event_o_filter_category_terms', 10, 2);

/**
 * Also filter the classic editor / admin taxonomy checklist.
 */
function event_o_filter_category_checklist(array $args, int $postId): array
{
    if (!isset($args['taxonomy']) || $args['taxonomy'] !== 'event_o_category') {
        return $args;
    }

    $user = wp_get_current_user();
    if (!$user || !in_array('event_o_contributor', (array) $user->roles, true)) {
        return $args;
    }

    $allowed = (array) get_user_meta($user->ID, EVENT_O_USER_META_ALLOWED_CATS, true);
    if (empty($allowed)) {
        return $args;
    }

    // Get term IDs from slugs
    $termIds = [];
    foreach ($allowed as $slug) {
        $term = get_term_by('slug', $slug, 'event_o_category');
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
   6. Admin notification for pending review events
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
   7. Admin: pending events count badge
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
   8. Dashboard: pending events widget for admins
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
