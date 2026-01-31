<?php
/**
 * Organizer taxonomy term meta fields.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Meta keys for organizer term meta.
const EVENT_O_ORG_META_PHONE = 'event_o_phone';
const EVENT_O_ORG_META_EMAIL = 'event_o_email';
const EVENT_O_ORG_META_WEBSITE = 'event_o_website';
const EVENT_O_ORG_META_INSTAGRAM = 'event_o_instagram';
const EVENT_O_ORG_META_FACEBOOK = 'event_o_facebook';
const EVENT_O_ORG_META_LOGO = 'event_o_logo';

/**
 * Register term meta for organizers.
 */
function event_o_register_organizer_meta(): void
{
    $meta_keys = [
        EVENT_O_ORG_META_PHONE,
        EVENT_O_ORG_META_EMAIL,
        EVENT_O_ORG_META_WEBSITE,
        EVENT_O_ORG_META_INSTAGRAM,
        EVENT_O_ORG_META_FACEBOOK,
        EVENT_O_ORG_META_LOGO,
    ];

    foreach ($meta_keys as $key) {
        register_term_meta('event_o_organizer', $key, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }
}
add_action('init', 'event_o_register_organizer_meta');

/**
 * Add form fields to "Add New Organizer" screen.
 */
function event_o_organizer_add_form_fields(): void
{
    ?>
    <div class="form-field">
        <label for="event_o_phone"><?php esc_html_e('Phone', 'event-o'); ?></label>
        <input type="text" name="event_o_phone" id="event_o_phone" value="">
        <p class="description"><?php esc_html_e('Contact phone number.', 'event-o'); ?></p>
    </div>
    <div class="form-field">
        <label for="event_o_email"><?php esc_html_e('E-Mail', 'event-o'); ?></label>
        <input type="email" name="event_o_email" id="event_o_email" value="">
        <p class="description"><?php esc_html_e('Contact email address.', 'event-o'); ?></p>
    </div>
    <div class="form-field">
        <label for="event_o_website"><?php esc_html_e('Website', 'event-o'); ?></label>
        <input type="url" name="event_o_website" id="event_o_website" value="">
        <p class="description"><?php esc_html_e('Website URL.', 'event-o'); ?></p>
    </div>
    <div class="form-field">
        <label for="event_o_instagram"><?php esc_html_e('Instagram', 'event-o'); ?></label>
        <input type="url" name="event_o_instagram" id="event_o_instagram" value="">
        <p class="description"><?php esc_html_e('Instagram profile URL.', 'event-o'); ?></p>
    </div>
    <div class="form-field">
        <label for="event_o_facebook"><?php esc_html_e('Facebook', 'event-o'); ?></label>
        <input type="url" name="event_o_facebook" id="event_o_facebook" value="">
        <p class="description"><?php esc_html_e('Facebook page URL.', 'event-o'); ?></p>
    </div>
    <div class="form-field">
        <label for="event_o_logo"><?php esc_html_e('Logo', 'event-o'); ?></label>
        <input type="hidden" name="event_o_logo" id="event_o_logo" value="">
        <div id="event_o_logo_preview" style="margin-bottom:8px;"></div>
        <button type="button" class="button event-o-upload-logo"><?php esc_html_e('Select Logo', 'event-o'); ?></button>
        <button type="button" class="button event-o-remove-logo" style="display:none;"><?php esc_html_e('Remove', 'event-o'); ?></button>
        <p class="description"><?php esc_html_e('Organizer logo image.', 'event-o'); ?></p>
    </div>
    <?php
}
add_action('event_o_organizer_add_form_fields', 'event_o_organizer_add_form_fields');

/**
 * Add form fields to "Edit Organizer" screen.
 */
function event_o_organizer_edit_form_fields($term): void
{
    $phone = get_term_meta($term->term_id, EVENT_O_ORG_META_PHONE, true);
    $email = get_term_meta($term->term_id, EVENT_O_ORG_META_EMAIL, true);
    $website = get_term_meta($term->term_id, EVENT_O_ORG_META_WEBSITE, true);
    $instagram = get_term_meta($term->term_id, EVENT_O_ORG_META_INSTAGRAM, true);
    $facebook = get_term_meta($term->term_id, EVENT_O_ORG_META_FACEBOOK, true);
    $logo = get_term_meta($term->term_id, EVENT_O_ORG_META_LOGO, true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="event_o_phone"><?php esc_html_e('Phone', 'event-o'); ?></label></th>
        <td>
            <input type="text" name="event_o_phone" id="event_o_phone" value="<?php echo esc_attr($phone); ?>">
            <p class="description"><?php esc_html_e('Contact phone number.', 'event-o'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="event_o_email"><?php esc_html_e('E-Mail', 'event-o'); ?></label></th>
        <td>
            <input type="email" name="event_o_email" id="event_o_email" value="<?php echo esc_attr($email); ?>">
            <p class="description"><?php esc_html_e('Contact email address.', 'event-o'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="event_o_website"><?php esc_html_e('Website', 'event-o'); ?></label></th>
        <td>
            <input type="url" name="event_o_website" id="event_o_website" value="<?php echo esc_attr($website); ?>">
            <p class="description"><?php esc_html_e('Website URL.', 'event-o'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="event_o_instagram"><?php esc_html_e('Instagram', 'event-o'); ?></label></th>
        <td>
            <input type="url" name="event_o_instagram" id="event_o_instagram" value="<?php echo esc_attr($instagram); ?>">
            <p class="description"><?php esc_html_e('Instagram profile URL.', 'event-o'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="event_o_facebook"><?php esc_html_e('Facebook', 'event-o'); ?></label></th>
        <td>
            <input type="url" name="event_o_facebook" id="event_o_facebook" value="<?php echo esc_attr($facebook); ?>">
            <p class="description"><?php esc_html_e('Facebook page URL.', 'event-o'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="event_o_logo"><?php esc_html_e('Logo', 'event-o'); ?></label></th>
        <td>
            <input type="hidden" name="event_o_logo" id="event_o_logo" value="<?php echo esc_attr($logo); ?>">
            <div id="event_o_logo_preview" style="margin-bottom:8px;">
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" style="max-width:150px;max-height:80px;">
                <?php endif; ?>
            </div>
            <button type="button" class="button event-o-upload-logo"><?php esc_html_e('Select Logo', 'event-o'); ?></button>
            <button type="button" class="button event-o-remove-logo" <?php echo $logo ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Remove', 'event-o'); ?></button>
            <p class="description"><?php esc_html_e('Organizer logo image.', 'event-o'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('event_o_organizer_edit_form_fields', 'event_o_organizer_edit_form_fields');

/**
 * Save organizer term meta.
 */
function event_o_save_organizer_meta($term_id): void
{
    $fields = [
        'event_o_phone' => EVENT_O_ORG_META_PHONE,
        'event_o_email' => EVENT_O_ORG_META_EMAIL,
        'event_o_website' => EVENT_O_ORG_META_WEBSITE,
        'event_o_instagram' => EVENT_O_ORG_META_INSTAGRAM,
        'event_o_facebook' => EVENT_O_ORG_META_FACEBOOK,
        'event_o_logo' => EVENT_O_ORG_META_LOGO,
    ];

    foreach ($fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            $value = sanitize_text_field(wp_unslash($_POST[$post_key]));
            update_term_meta($term_id, $meta_key, $value);
        }
    }
}
add_action('created_event_o_organizer', 'event_o_save_organizer_meta');
add_action('edited_event_o_organizer', 'event_o_save_organizer_meta');

/**
 * Enqueue media uploader for organizer logo.
 */
function event_o_organizer_admin_scripts($hook): void
{
    $screen = get_current_screen();
    if (!$screen || $screen->taxonomy !== 'event_o_organizer') {
        return;
    }

    wp_enqueue_media();
    wp_add_inline_script('media-editor', "
        jQuery(function($){
            var frame;
            $(document).on('click', '.event-o-upload-logo', function(e){
                e.preventDefault();
                if(frame){ frame.open(); return; }
                frame = wp.media({
                    title: '" . esc_js(__('Select Logo', 'event-o')) . "',
                    button: { text: '" . esc_js(__('Use as Logo', 'event-o')) . "' },
                    multiple: false
                });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#event_o_logo').val(attachment.url);
                    $('#event_o_logo_preview').html('<img src=\"'+attachment.url+'\" style=\"max-width:150px;max-height:80px;\">');
                    $('.event-o-remove-logo').show();
                });
                frame.open();
            });
            $(document).on('click', '.event-o-remove-logo', function(e){
                e.preventDefault();
                $('#event_o_logo').val('');
                $('#event_o_logo_preview').html('');
                $(this).hide();
            });
        });
    ");
}
add_action('admin_enqueue_scripts', 'event_o_organizer_admin_scripts');

/**
 * Get organizer data for a given post ID.
 */
function event_o_get_organizer_data(int $post_id): ?array
{
    $terms = get_the_terms($post_id, 'event_o_organizer');
    if (!is_array($terms) || empty($terms)) {
        return null;
    }

    $term = array_shift($terms);
    return [
        'name' => $term->name,
        'phone' => get_term_meta($term->term_id, EVENT_O_ORG_META_PHONE, true),
        'email' => get_term_meta($term->term_id, EVENT_O_ORG_META_EMAIL, true),
        'website' => get_term_meta($term->term_id, EVENT_O_ORG_META_WEBSITE, true),
        'instagram' => get_term_meta($term->term_id, EVENT_O_ORG_META_INSTAGRAM, true),
        'facebook' => get_term_meta($term->term_id, EVENT_O_ORG_META_FACEBOOK, true),
        'logo' => get_term_meta($term->term_id, EVENT_O_ORG_META_LOGO, true),
    ];
}
