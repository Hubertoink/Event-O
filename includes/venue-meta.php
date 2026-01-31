<?php
/**
 * Venue taxonomy term meta fields.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Meta key for venue term meta.
const EVENT_O_VENUE_META_ADDRESS = 'event_o_venue_address';

/**
 * Register term meta for venues.
 */
function event_o_register_venue_meta(): void
{
    register_term_meta('event_o_venue', EVENT_O_VENUE_META_ADDRESS, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_textarea_field',
    ]);
}
add_action('init', 'event_o_register_venue_meta');

/**
 * Add form fields to "Add New Venue" screen.
 */
function event_o_venue_add_form_fields(): void
{
    ?>
    <div class="form-field">
        <label for="event_o_venue_address"><?php esc_html_e('Adresse', 'event-o'); ?></label>
        <textarea name="event_o_venue_address" id="event_o_venue_address" rows="3"></textarea>
        <p class="description"><?php esc_html_e('Vollständige Adresse des Veranstaltungsortes.', 'event-o'); ?></p>
    </div>
    <?php
}
add_action('event_o_venue_add_form_fields', 'event_o_venue_add_form_fields');

/**
 * Add form fields to "Edit Venue" screen.
 */
function event_o_venue_edit_form_fields($term): void
{
    $address = get_term_meta($term->term_id, EVENT_O_VENUE_META_ADDRESS, true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="event_o_venue_address"><?php esc_html_e('Adresse', 'event-o'); ?></label></th>
        <td>
            <textarea name="event_o_venue_address" id="event_o_venue_address" rows="3"><?php echo esc_textarea($address); ?></textarea>
            <p class="description"><?php esc_html_e('Vollständige Adresse des Veranstaltungsortes.', 'event-o'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('event_o_venue_edit_form_fields', 'event_o_venue_edit_form_fields');

/**
 * Save venue term meta on create.
 */
function event_o_save_venue_meta_on_create($term_id): void
{
    if (isset($_POST['event_o_venue_address'])) {
        update_term_meta($term_id, EVENT_O_VENUE_META_ADDRESS, sanitize_textarea_field($_POST['event_o_venue_address']));
    }
}
add_action('created_event_o_venue', 'event_o_save_venue_meta_on_create');

/**
 * Save venue term meta on update.
 */
function event_o_save_venue_meta_on_update($term_id): void
{
    if (isset($_POST['event_o_venue_address'])) {
        update_term_meta($term_id, EVENT_O_VENUE_META_ADDRESS, sanitize_textarea_field($_POST['event_o_venue_address']));
    }
}
add_action('edited_event_o_venue', 'event_o_save_venue_meta_on_update');

/**
 * Get venue data for a post.
 */
function event_o_get_venue_data(int $post_id): ?array
{
    $terms = get_the_terms($post_id, 'event_o_venue');
    if (!is_array($terms) || empty($terms)) {
        return null;
    }

    $term = array_shift($terms);
    $address = get_term_meta($term->term_id, EVENT_O_VENUE_META_ADDRESS, true);

    return [
        'name' => $term->name,
        'address' => $address ?: '',
    ];
}
