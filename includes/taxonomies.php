<?php

if (!defined('ABSPATH')) {
    exit;
}

function event_o_register_taxonomies(): void
{
    register_taxonomy(
        'event_o_category',
        ['event_o_event'],
        [
            'labels' => [
                'name' => __('Event-O Categories', 'event-o'),
                'singular_name' => __('Event-O Category', 'event-o'),
                'add_new_item' => __('Kategorie hinzufügen', 'event-o'),
                'new_item_name' => __('Neue Kategorie', 'event-o'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'event-o-category'],
        ]
    );

    register_taxonomy(
        'event_o_venue',
        ['event_o_event'],
        [
            'labels' => [
                'name' => __('Event-O Venues', 'event-o'),
                'singular_name' => __('Event-O Venue', 'event-o'),
                'add_new_item' => __('Ort hinzufügen', 'event-o'),
                'new_item_name' => __('Neuer Ort', 'event-o'),
                'search_items' => __('Orte suchen', 'event-o'),
                'all_items' => __('Alle Orte', 'event-o'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'event-o-venue'],
        ]
    );

    register_taxonomy(
        'event_o_organizer',
        ['event_o_event'],
        [
            'labels' => [
                'name' => __('Event-O Organizers', 'event-o'),
                'singular_name' => __('Event-O Organizer', 'event-o'),
                'add_new_item' => __('Organisation hinzufügen', 'event-o'),
                'new_item_name' => __('Neue Organisation', 'event-o'),
                'search_items' => __('Organisationen suchen', 'event-o'),
                'all_items' => __('Alle Organisationen', 'event-o'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'event-o-organizer'],
        ]
    );
}

/* =========================================================================
   Category Color Meta – Color picker on add/edit category screens
   ========================================================================= */

/**
 * Register the term meta for category color.
 */
function event_o_register_category_color_meta(): void
{
    register_term_meta('event_o_category', 'event_o_category_color', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '',
    ]);
}
add_action('init', 'event_o_register_category_color_meta');

/**
 * Add color picker field on the "Add New Category" form.
 */
function event_o_category_add_color_field(): void
{
    ?>
    <div class="form-field">
        <label for="event_o_category_color"><?php esc_html_e('Kategorie-Farbe', 'event-o'); ?></label>
        <div class="event-o-color-field-wrap">
            <input type="color" name="event_o_category_color" id="event_o_category_color" value="#333333" class="event-o-color-input">
            <input type="text" id="event_o_category_color_hex" value="#333333" class="event-o-color-hex" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#333333">
            <button type="button" class="button event-o-color-clear"><?php esc_html_e('Zurücksetzen', 'event-o'); ?></button>
        </div>
        <p class="description"><?php esc_html_e('Wird als Textfarbe der Kategorie in allen Blöcken verwendet.', 'event-o'); ?></p>
    </div>
    <script>
    (function(){
        var ci = document.getElementById('event_o_category_color');
        var hi = document.getElementById('event_o_category_color_hex');
        var cl = document.querySelector('.event-o-color-clear');
        ci.addEventListener('input', function(){ hi.value = ci.value; });
        hi.addEventListener('input', function(){ if(/^#[0-9a-fA-F]{6}$/.test(hi.value)) ci.value = hi.value; });
        cl.addEventListener('click', function(){ ci.value = '#333333'; hi.value = ''; });
    })();
    </script>
    <?php
}
add_action('event_o_category_add_form_fields', 'event_o_category_add_color_field');

/**
 * Add color picker field on the "Edit Category" form.
 */
function event_o_category_edit_color_field(WP_Term $term): void
{
    $color = get_term_meta($term->term_id, 'event_o_category_color', true);
    $displayHex = $color ?: '';
    $pickerValue = $color ?: '#333333';
    ?>
    <tr class="form-field">
        <th scope="row"><label for="event_o_category_color"><?php esc_html_e('Kategorie-Farbe', 'event-o'); ?></label></th>
        <td>
            <div class="event-o-color-field-wrap">
                <input type="color" name="event_o_category_color" id="event_o_category_color" value="<?php echo esc_attr($pickerValue); ?>" class="event-o-color-input">
                <input type="text" id="event_o_category_color_hex" value="<?php echo esc_attr($displayHex); ?>" class="event-o-color-hex" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#333333">
                <button type="button" class="button event-o-color-clear"><?php esc_html_e('Zurücksetzen', 'event-o'); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Wird als Textfarbe der Kategorie in allen Blöcken verwendet.', 'event-o'); ?></p>
        </td>
    </tr>
    <script>
    (function(){
        var ci = document.getElementById('event_o_category_color');
        var hi = document.getElementById('event_o_category_color_hex');
        var cl = document.querySelector('.event-o-color-clear');
        ci.addEventListener('input', function(){ hi.value = ci.value; });
        hi.addEventListener('input', function(){ if(/^#[0-9a-fA-F]{6}$/.test(hi.value)) ci.value = hi.value; });
        cl.addEventListener('click', function(){ ci.value = '#333333'; hi.value = ''; });
    })();
    </script>
    <?php
}
add_action('event_o_category_edit_form_fields', 'event_o_category_edit_color_field');

/**
 * Save category color on create/edit.
 */
function event_o_save_category_color(int $termId): void
{
    if (!isset($_POST['event_o_category_color'])) {
        return;
    }

    $color = sanitize_hex_color($_POST['event_o_category_color']);
    if ($color) {
        update_term_meta($termId, 'event_o_category_color', $color);
    } else {
        delete_term_meta($termId, 'event_o_category_color');
    }
}
add_action('created_event_o_category', 'event_o_save_category_color');
add_action('edited_event_o_category', 'event_o_save_category_color');

/**
 * Enqueue admin styles for the category color picker.
 */
function event_o_category_admin_styles(string $hook): void
{
    if (!in_array($hook, ['edit-tags.php', 'term.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->taxonomy !== 'event_o_category') {
        return;
    }
    wp_add_inline_style('wp-admin', '
        .event-o-color-field-wrap { display: flex; align-items: center; gap: 8px; }
        .event-o-color-input { width: 48px; height: 36px; padding: 2px; border: 1px solid #8c8f94; border-radius: 4px; cursor: pointer; }
        .event-o-color-hex { width: 90px; font-family: monospace; }
    ');
}
add_action('admin_enqueue_scripts', 'event_o_category_admin_styles');

/**
 * Show color swatch in the category list table.
 */
function event_o_category_columns(array $columns): array
{
    $columns['event_o_color'] = __('Farbe', 'event-o');
    return $columns;
}
add_filter('manage_edit-event_o_category_columns', 'event_o_category_columns');

function event_o_category_column_content(string $content, string $columnName, int $termId): string
{
    if ($columnName === 'event_o_color') {
        $color = get_term_meta($termId, 'event_o_category_color', true);
        if ($color) {
            return '<span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:' . esc_attr($color) . ';border:1px solid rgba(0,0,0,.15);vertical-align:middle;"></span>';
        }
        return '—';
    }
    return $content;
}
add_filter('manage_event_o_category_custom_column', 'event_o_category_column_content', 10, 3);