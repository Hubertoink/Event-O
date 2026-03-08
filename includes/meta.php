<?php

if (!defined('ABSPATH')) {
    exit;
}

const EVENT_O_META_START_TS = '_event_o_start_ts';
const EVENT_O_META_END_TS = '_event_o_end_ts';
const EVENT_O_META_BEGIN_TIME = '_event_o_begin_time';
const EVENT_O_META_START_TS_2 = '_event_o_start_ts_2';
const EVENT_O_META_END_TS_2 = '_event_o_end_ts_2';
const EVENT_O_META_BEGIN_TIME_2 = '_event_o_begin_time_2';
const EVENT_O_META_START_TS_3 = '_event_o_start_ts_3';
const EVENT_O_META_END_TS_3 = '_event_o_end_ts_3';
const EVENT_O_META_BEGIN_TIME_3 = '_event_o_begin_time_3';
const EVENT_O_META_PRICE = '_event_o_price';
const EVENT_O_META_STATUS = '_event_o_status';
const EVENT_O_META_BANDS = '_event_o_bands';
const EVENT_O_META_GALLERY_IDS = '_event_o_gallery_ids';

const EVENT_O_LEGACY_META_START_TS = '_evento_start_ts';
const EVENT_O_LEGACY_META_END_TS = '_evento_end_ts';
const EVENT_O_LEGACY_META_PRICE = '_evento_price';
const EVENT_O_LEGACY_META_STATUS = '_evento_status';

function event_o_register_meta(): void
{
    $metaArgs = [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
    ];

    register_post_meta('event_o_event', EVENT_O_META_START_TS, $metaArgs);
    register_post_meta('event_o_event', EVENT_O_META_END_TS, $metaArgs);
    register_post_meta('event_o_event', EVENT_O_META_BEGIN_TIME, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_post_meta('event_o_event', EVENT_O_META_START_TS_2, $metaArgs);
    register_post_meta('event_o_event', EVENT_O_META_END_TS_2, $metaArgs);
    register_post_meta('event_o_event', EVENT_O_META_BEGIN_TIME_2, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_post_meta('event_o_event', EVENT_O_META_START_TS_3, $metaArgs);
    register_post_meta('event_o_event', EVENT_O_META_END_TS_3, $metaArgs);
    register_post_meta('event_o_event', EVENT_O_META_BEGIN_TIME_3, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('event_o_event', EVENT_O_META_PRICE, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('event_o_event', EVENT_O_META_STATUS, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('event_o_event', EVENT_O_META_BANDS, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_textarea_field',
    ]);

    register_post_meta('event_o_event', EVENT_O_META_GALLERY_IDS, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => static function () {
            return current_user_can('edit_posts');
        },
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}

function event_o_admin_meta_boxes(): void
{
    add_meta_box(
        'event_o_event_details',
        __('Event Details', 'event-o'),
        'event_o_render_event_details_metabox',
        'event_o_event',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_event_o_event', 'event_o_admin_meta_boxes');

function event_o_render_event_details_metabox(WP_Post $post): void
{
    wp_nonce_field('event_o_save_event_meta', 'event_o_event_meta_nonce');

    $startTs = (int) get_post_meta($post->ID, EVENT_O_META_START_TS, true);
    $endTs = (int) get_post_meta($post->ID, EVENT_O_META_END_TS, true);
    $beginTime = (string) get_post_meta($post->ID, EVENT_O_META_BEGIN_TIME, true);
    $startTs2 = (int) get_post_meta($post->ID, EVENT_O_META_START_TS_2, true);
    $endTs2 = (int) get_post_meta($post->ID, EVENT_O_META_END_TS_2, true);
    $beginTime2 = (string) get_post_meta($post->ID, EVENT_O_META_BEGIN_TIME_2, true);
    $startTs3 = (int) get_post_meta($post->ID, EVENT_O_META_START_TS_3, true);
    $endTs3 = (int) get_post_meta($post->ID, EVENT_O_META_END_TS_3, true);
    $beginTime3 = (string) get_post_meta($post->ID, EVENT_O_META_BEGIN_TIME_3, true);
    $price = (string) get_post_meta($post->ID, EVENT_O_META_PRICE, true);
    $status = (string) get_post_meta($post->ID, EVENT_O_META_STATUS, true);
    $galleryRaw = (string) get_post_meta($post->ID, EVENT_O_META_GALLERY_IDS, true);
    $galleryIds = array_values(array_filter(array_map('absint', array_map('trim', explode(',', $galleryRaw))), static fn($id) => $id > 0));
    $galleryIds = array_slice(array_unique($galleryIds), 0, 2);

    // Backward compat if someone tested earlier builds.
    if ($startTs <= 0) {
        $startTs = (int) get_post_meta($post->ID, EVENT_O_LEGACY_META_START_TS, true);
    }
    if ($endTs <= 0) {
        $endTs = (int) get_post_meta($post->ID, EVENT_O_LEGACY_META_END_TS, true);
    }
    if ($price === '') {
        $price = (string) get_post_meta($post->ID, EVENT_O_LEGACY_META_PRICE, true);
    }
    if ($status === '') {
        $status = (string) get_post_meta($post->ID, EVENT_O_LEGACY_META_STATUS, true);
    }

    $tz = wp_timezone();

    $startValue = '';
    if ($startTs > 0) {
        $startValue = (new DateTimeImmutable('@' . $startTs))->setTimezone($tz)->format('Y-m-d\\TH:i');
    }

    $endValue = '';
    if ($endTs > 0) {
        $endValue = (new DateTimeImmutable('@' . $endTs))->setTimezone($tz)->format('Y-m-d\\TH:i');
    }

    $startValue2 = '';
    if ($startTs2 > 0) {
        $startValue2 = (new DateTimeImmutable('@' . $startTs2))->setTimezone($tz)->format('Y-m-d\\TH:i');
    }
    $endValue2 = '';
    if ($endTs2 > 0) {
        $endValue2 = (new DateTimeImmutable('@' . $endTs2))->setTimezone($tz)->format('Y-m-d\\TH:i');
    }
    $startValue3 = '';
    if ($startTs3 > 0) {
        $startValue3 = (new DateTimeImmutable('@' . $startTs3))->setTimezone($tz)->format('Y-m-d\\TH:i');
    }
    $endValue3 = '';
    if ($endTs3 > 0) {
        $endValue3 = (new DateTimeImmutable('@' . $endTs3))->setTimezone($tz)->format('Y-m-d\\TH:i');
    }

    $statuses = [
        '' => __('Normal', 'event-o'),
        'cancelled' => __('Cancelled', 'event-o'),
        'postponed' => __('Postponed', 'event-o'),
        'soldout' => __('Sold out', 'event-o'),
    ];

    $hasSecondTerm = $startTs2 > 0 || $endTs2 > 0 || $beginTime2 !== '';
    $hasThirdTerm = $startTs3 > 0 || $endTs3 > 0 || $beginTime3 !== '';

    echo '<style>';
    echo '.event-o-term-fieldset{border:1px solid #ddd;padding:10px 12px;margin-bottom:12px;border-radius:4px}';
    echo '.event-o-term-grid{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}';
    echo '.event-o-term-col{flex:1;min-width:180px}';
    echo '.event-o-term-actions{display:flex;gap:8px;margin:0 0 12px}';
    echo '.event-o-term-remove{margin-left:auto}';
    echo '.event-o-term-wrapper.is-hidden{display:none}';
    echo '.event-o-begin-hint{display:inline-block;width:16px;height:16px;border-radius:50%;background:#ddd;color:#555;font-size:11px;line-height:16px;text-align:center;cursor:help;vertical-align:middle;margin-left:4px}';
    echo '</style>';

    echo '<fieldset class="event-o-term-fieldset">';
    echo '<legend style="font-weight:600;padding:0 6px">' . esc_html__('Termin 1', 'event-o') . '</legend>';
    echo '<div class="event-o-term-grid">';
    echo '<div class="event-o-term-col"><p><label for="event_o_start_datetime"><strong>' . esc_html__('Von', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_start_datetime" name="event_o_start_datetime" value="' . esc_attr($startValue) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col"><p><label for="event_o_end_datetime"><strong>' . esc_html__('Bis', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_end_datetime" name="event_o_end_datetime" value="' . esc_attr($endValue) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col"><p><label for="event_o_begin_time"><strong>' . esc_html__('Beginn', 'event-o') . '</strong> <span class="event-o-begin-hint" title="' . esc_attr__('Nur ausfüllen wenn Beginn von Einlass abweicht (z.B. Einlass 19 Uhr, Beginn 20 Uhr). Leer lassen wenn nicht benötigt.', 'event-o') . '">i</span></label></p>';
    echo '<p><input type="time" id="event_o_begin_time" name="event_o_begin_time" value="' . esc_attr($beginTime) . '" style="width:100%" /></p></div>';
    echo '</div>';
    echo '</fieldset>';

    echo '<div class="event-o-term-actions">';
    echo '<button type="button" class="button button-secondary event-o-term-add" data-target="event_o_term_2"' . ($hasSecondTerm ? ' style="display:none"' : '') . '>+ ' . esc_html__('Termin 2 hinzufügen', 'event-o') . '</button>';
    echo '<button type="button" class="button button-secondary event-o-term-add" data-target="event_o_term_3"' . ($hasThirdTerm || !$hasSecondTerm ? ' style="display:none"' : '') . '>+ ' . esc_html__('Termin 3 hinzufügen', 'event-o') . '</button>';
    echo '</div>';

    echo '<div id="event_o_term_2" class="event-o-term-wrapper' . ($hasSecondTerm ? '' : ' is-hidden') . '">';
    echo '<fieldset class="event-o-term-fieldset">';
    echo '<legend style="font-weight:600;padding:0 6px">' . esc_html__('Termin 2 (optional)', 'event-o') . '</legend>';
    echo '<div class="event-o-term-grid">';
    echo '<div class="event-o-term-col"><p><label for="event_o_start_datetime_2"><strong>' . esc_html__('Von', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_start_datetime_2" name="event_o_start_datetime_2" value="' . esc_attr($startValue2) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col"><p><label for="event_o_end_datetime_2"><strong>' . esc_html__('Bis', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_end_datetime_2" name="event_o_end_datetime_2" value="' . esc_attr($endValue2) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col"><p><label for="event_o_begin_time_2"><strong>' . esc_html__('Beginn', 'event-o') . '</strong> <span class="event-o-begin-hint" title="' . esc_attr__('Nur ausfüllen wenn Beginn von Einlass abweicht.', 'event-o') . '">i</span></label></p>';
    echo '<p><input type="time" id="event_o_begin_time_2" name="event_o_begin_time_2" value="' . esc_attr($beginTime2) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col" style="flex:0 0 auto">';
    echo '<p><button type="button" class="button-link-delete event-o-term-remove" data-target="event_o_term_2">' . esc_html__('Termin entfernen', 'event-o') . '</button></p>';
    echo '</div>';
    echo '</div>';
    echo '</fieldset>';
    echo '</div>';

    echo '<div id="event_o_term_3" class="event-o-term-wrapper' . ($hasThirdTerm ? '' : ' is-hidden') . '">';
    echo '<fieldset class="event-o-term-fieldset">';
    echo '<legend style="font-weight:600;padding:0 6px">' . esc_html__('Termin 3 (optional)', 'event-o') . '</legend>';
    echo '<div class="event-o-term-grid">';
    echo '<div class="event-o-term-col"><p><label for="event_o_start_datetime_3"><strong>' . esc_html__('Von', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_start_datetime_3" name="event_o_start_datetime_3" value="' . esc_attr($startValue3) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col"><p><label for="event_o_end_datetime_3"><strong>' . esc_html__('Bis', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_end_datetime_3" name="event_o_end_datetime_3" value="' . esc_attr($endValue3) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col"><p><label for="event_o_begin_time_3"><strong>' . esc_html__('Beginn', 'event-o') . '</strong> <span class="event-o-begin-hint" title="' . esc_attr__('Nur ausfüllen wenn Beginn von Einlass abweicht.', 'event-o') . '">i</span></label></p>';
    echo '<p><input type="time" id="event_o_begin_time_3" name="event_o_begin_time_3" value="' . esc_attr($beginTime3) . '" style="width:100%" /></p></div>';
    echo '<div class="event-o-term-col" style="flex:0 0 auto">';
    echo '<p><button type="button" class="button-link-delete event-o-term-remove" data-target="event_o_term_3">' . esc_html__('Termin entfernen', 'event-o') . '</button></p>';
    echo '</div>';
    echo '</div>';
    echo '</fieldset>';
    echo '</div>';

    echo '<p><label for="event_o_price"><strong>' . esc_html__('Price', 'event-o') . '</strong></label></p>';
    echo '<p><input type="text" id="event_o_price" name="event_o_price" value="' . esc_attr($price) . '" placeholder="z.B. Frei / 5 €" style="width:100%" /></p>';

    echo '<p><label for="event_o_status"><strong>' . esc_html__('Status', 'event-o') . '</strong></label></p>';
    echo '<p><select id="event_o_status" name="event_o_status" style="width:100%">';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';

    echo '<hr style="margin:14px 0">';
    echo '<p><label><strong>' . esc_html__('Event-Galerie (max. 2 zusätzliche Bilder)', 'event-o') . '</strong></label></p>';
    echo '<p style="color:#666;font-size:12px;margin-top:2px">' . esc_html__('Das Beitragsbild bleibt das Hauptbild. Hier können optional bis zu 2 weitere Bilder für Crossfade hinzugefügt werden.', 'event-o') . '</p>';
    echo '<input type="hidden" id="event_o_gallery_ids" name="event_o_gallery_ids" value="' . esc_attr(implode(',', $galleryIds)) . '">';
    echo '<div id="event_o_gallery_preview" style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0">';
    foreach ($galleryIds as $imageId) {
        $thumbUrl = wp_get_attachment_image_url($imageId, 'thumbnail');
        if (!$thumbUrl) {
            continue;
        }
        echo '<div class="event-o-gallery-item" data-id="' . esc_attr((string) $imageId) . '" style="position:relative">';
        echo '<img src="' . esc_url($thumbUrl) . '" alt="" style="width:90px;height:90px;object-fit:cover;border:1px solid #ddd;border-radius:4px;display:block">';
        echo '<button type="button" class="button-link-delete event-o-gallery-remove" style="position:absolute;top:2px;right:4px;font-size:16px;line-height:1;text-decoration:none">×</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<p><button type="button" class="button event-o-gallery-add">' . esc_html__('Bilder auswählen', 'event-o') . '</button></p>';

    // --- Bands / Artists ---
    $bands = (string) get_post_meta($post->ID, EVENT_O_META_BANDS, true);
    echo '<hr style="margin:14px 0">';
    echo '<p><label for="event_o_bands"><strong>' . esc_html__('Bands / Artists', 'event-o') . '</strong></label></p>';
    echo '<p><textarea id="event_o_bands" name="event_o_bands" rows="4" style="width:100%" placeholder="Band Name | spotify-url | bandcamp-url | website-url">' . esc_textarea($bands) . '</textarea></p>';
    echo '<p style="color:#666;font-size:11px;margin-top:2px">' . esc_html__('One band per line. Format: Name | Spotify URL | Bandcamp URL | Website URL (each part optional).', 'event-o') . '</p>';

    echo '<p style="color:#666;font-size:12px;margin-top:10px">' . esc_html__('Tip: Use taxonomies for Organizer/Venue to keep data consistent.', 'event-o') . '</p>';
}

function event_o_event_admin_scripts($hook): void
{
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'event_o_event') {
        return;
    }

    wp_enqueue_media();

    // Wizard mode – conditionally enqueue wizard assets
    if (get_option(EVENT_O_OPTION_WIZARD_MODE, false)) {
        $wizVer = defined('EVENT_O_VERSION') ? EVENT_O_VERSION : '1.0.0';
        wp_enqueue_style('event-o-wizard', EVENT_O_PLUGIN_URL . 'assets/wizard.css', [], $wizVer);
        wp_enqueue_script('event-o-wizard', EVENT_O_PLUGIN_URL . 'assets/wizard.js', ['jquery', 'media-editor', 'wp-data', 'wp-blocks', 'wp-block-editor'], $wizVer, true);

        // Per-user term restrictions (empty = all allowed)
        $currentUserId = get_current_user_id();
        $allowedCatSlugs = event_o_get_user_allowed_term_slugs($currentUserId, 'event_o_category');
        $allowedVenueSlugs = event_o_get_user_allowed_term_slugs($currentUserId, 'event_o_venue');
        $allowedOrgSlugs = event_o_get_user_allowed_term_slugs($currentUserId, 'event_o_organizer');

        $catTerms = get_terms(['taxonomy' => 'event_o_category', 'hide_empty' => false]);
        $wizCats = [];
        if (!is_wp_error($catTerms)) {
            foreach ($catTerms as $t) {
                if (!empty($allowedCatSlugs) && !in_array($t->slug, $allowedCatSlugs, true)) {
                    continue;
                }
                $wizCats[] = [
                    'id'    => $t->term_id,
                    'name'  => $t->name,
                    'color' => get_term_meta($t->term_id, 'event_o_category_color', true) ?: '',
                ];
            }
        }

        $venueTerms = get_terms(['taxonomy' => 'event_o_venue', 'hide_empty' => false]);
        $wizVenues = [];
        if (!is_wp_error($venueTerms)) {
            foreach ($venueTerms as $t) {
                if (!empty($allowedVenueSlugs) && !in_array($t->slug, $allowedVenueSlugs, true)) {
                    continue;
                }
                $wizVenues[] = [
                    'id'   => $t->term_id,
                    'name' => $t->name,
                ];
            }
        }

        $orgTerms = get_terms(['taxonomy' => 'event_o_organizer', 'hide_empty' => false]);
        $wizOrgs = [];
        if (!is_wp_error($orgTerms)) {
            foreach ($orgTerms as $t) {
                if (!empty($allowedOrgSlugs) && !in_array($t->slug, $allowedOrgSlugs, true)) {
                    continue;
                }
                $wizOrgs[] = [
                    'id'   => $t->term_id,
                    'name' => $t->name,
                ];
            }
        }

        $isNewPost = isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'post-new.php';

        wp_localize_script('event-o-wizard', 'eoWizardData', [
            'categories'       => $wizCats,
            'venues'           => $wizVenues,
            'organizers'       => $wizOrgs,
            'preselectCats'    => count($wizCats) === 1 ? '1' : '0',
            'preselectVenues'  => count($wizVenues) === 1 ? '1' : '0',
            'preselectOrgs'    => count($wizOrgs) === 1 ? '1' : '0',
            'isNew'      => $isNewPost ? '1' : '0',
            'canPublish' => current_user_can('publish_posts') ? '1' : '0',
            'texts'      => [
                'openWizard'    => __('Event-Wizard öffnen', 'event-o'),
                'classicEditor' => __('Klassischer Editor', 'event-o'),
                'back'          => __('Zurück', 'event-o'),
                'next'          => __('Weiter', 'event-o'),
                'publish'       => current_user_can('publish_posts') ? __('Veröffentlichen', 'event-o') : __('Zur Überprüfung einreichen', 'event-o'),
                'saveDraft'     => __('Entwurf speichern', 'event-o'),
            ],
        ]);
    }

    wp_add_inline_script('media-editor', "
        jQuery(function($){
            var frame;

            function idsFromInput(){
                var raw = ($('#event_o_gallery_ids').val() || '').trim();
                if(!raw){ return []; }
                return raw.split(',').map(function(v){ return parseInt(v, 10); }).filter(function(v){ return !isNaN(v) && v > 0; });
            }

            function writeIds(ids){
                $('#event_o_gallery_ids').val(ids.join(','));
            }

            function renderItem(id, url){
                var html = '<div class=\"event-o-gallery-item\" data-id=\"'+id+'\" style=\"position:relative\">';
                html += '<img src=\"'+url+'\" alt=\"\" style=\"width:90px;height:90px;object-fit:cover;border:1px solid #ddd;border-radius:4px;display:block\">';
                html += '<button type=\"button\" class=\"button-link-delete event-o-gallery-remove\" style=\"position:absolute;top:2px;right:4px;font-size:16px;line-height:1;text-decoration:none\">×</button>';
                html += '</div>';
                $('#event_o_gallery_preview').append(html);
            }

            $(document).on('click', '.event-o-gallery-add', function(e){
                e.preventDefault();

                var existingIds = idsFromInput();
                if (existingIds.length >= 2) {
                    alert('" . esc_js(__('Maximal 2 Galerie-Bilder möglich.', 'event-o')) . "');
                    return;
                }

                if(!frame){
                    frame = wp.media({
                        title: '" . esc_js(__('Bilder auswählen', 'event-o')) . "',
                        button: { text: '" . esc_js(__('Bilder übernehmen', 'event-o')) . "' },
                        multiple: true,
                        library: { type: 'image' }
                    });
                }

                frame.off('select');
                frame.on('select', function(){
                    var ids = idsFromInput();
                    var selection = frame.state().get('selection').toArray();

                    selection.forEach(function(model){
                        if (ids.length >= 2) { return; }
                        var att = model.toJSON();
                        var id = parseInt(att.id, 10);
                        if (!id || ids.indexOf(id) !== -1) { return; }
                        ids.push(id);

                        var thumb = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
                        renderItem(id, thumb);
                    });

                    ids = ids.slice(0, 2);
                    writeIds(ids);
                });

                frame.open();
            });

            $(document).on('click', '.event-o-gallery-remove', function(e){
                e.preventDefault();
                var item = $(this).closest('.event-o-gallery-item');
                var id = parseInt(item.attr('data-id'), 10);
                var ids = idsFromInput().filter(function(v){ return v !== id; });
                writeIds(ids);
                item.remove();
            });

            function toggleAddButtons(){
                var term2Visible = !$('#event_o_term_2').hasClass('is-hidden');
                var term3Visible = !$('#event_o_term_3').hasClass('is-hidden');

                $('.event-o-term-add[data-target=\'event_o_term_2\']').toggle(!term2Visible);
                $('.event-o-term-add[data-target=\'event_o_term_3\']').toggle(term2Visible && !term3Visible);
            }

            $(document).on('click', '.event-o-term-add', function(e){
                e.preventDefault();
                var target = $('#' + $(this).data('target'));
                target.removeClass('is-hidden');
                toggleAddButtons();
            });

            $(document).on('click', '.event-o-term-remove', function(e){
                e.preventDefault();
                var targetId = $(this).data('target');
                var wrap = $('#' + targetId);
                wrap.find('input').val('');
                wrap.addClass('is-hidden');

                if (targetId === 'event_o_term_2') {
                    $('#event_o_term_3').find('input').val('');
                    $('#event_o_term_3').addClass('is-hidden');
                }

                toggleAddButtons();
            });

            toggleAddButtons();
        });
    ");
}

add_action('admin_enqueue_scripts', 'event_o_event_admin_scripts');

function event_o_save_event_meta(int $postId): void
{
    if (!isset($_POST['event_o_event_meta_nonce']) || !wp_verify_nonce($_POST['event_o_event_meta_nonce'], 'event_o_save_event_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $postId)) {
        return;
    }

    $tz = wp_timezone();

    // Parse and save up to 3 date slots
    $dateFields = [
        ['start_field' => 'event_o_start_datetime', 'end_field' => 'event_o_end_datetime', 'begin_field' => 'event_o_begin_time', 'start_key' => EVENT_O_META_START_TS, 'end_key' => EVENT_O_META_END_TS, 'begin_key' => EVENT_O_META_BEGIN_TIME],
        ['start_field' => 'event_o_start_datetime_2', 'end_field' => 'event_o_end_datetime_2', 'begin_field' => 'event_o_begin_time_2', 'start_key' => EVENT_O_META_START_TS_2, 'end_key' => EVENT_O_META_END_TS_2, 'begin_key' => EVENT_O_META_BEGIN_TIME_2],
        ['start_field' => 'event_o_start_datetime_3', 'end_field' => 'event_o_end_datetime_3', 'begin_field' => 'event_o_begin_time_3', 'start_key' => EVENT_O_META_START_TS_3, 'end_key' => EVENT_O_META_END_TS_3, 'begin_key' => EVENT_O_META_BEGIN_TIME_3],
    ];

    foreach ($dateFields as $df) {
        $startRaw = isset($_POST[$df['start_field']]) ? (string) $_POST[$df['start_field']] : '';
        $endRaw = isset($_POST[$df['end_field']]) ? (string) $_POST[$df['end_field']] : '';
        $beginRaw = isset($_POST[$df['begin_field']]) ? sanitize_text_field((string) $_POST[$df['begin_field']]) : '';

        $startTs = 0;
        if ($startRaw !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $startRaw, $tz);
            if ($dt instanceof DateTimeImmutable) {
                $startTs = $dt->getTimestamp();
            }
        }

        $endTs = 0;
        if ($endRaw !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $endRaw, $tz);
            if ($dt instanceof DateTimeImmutable) {
                $endTs = $dt->getTimestamp();
            }
        }

        if ($startTs > 0) {
            update_post_meta($postId, $df['start_key'], $startTs);
        } else {
            delete_post_meta($postId, $df['start_key']);
        }

        if ($endTs > 0) {
            update_post_meta($postId, $df['end_key'], $endTs);
        } else {
            delete_post_meta($postId, $df['end_key']);
        }

        if ($beginRaw !== '' && preg_match('/^\d{2}:\d{2}$/', $beginRaw)) {
            update_post_meta($postId, $df['begin_key'], $beginRaw);
        } else {
            delete_post_meta($postId, $df['begin_key']);
        }
    }

    $price = isset($_POST['event_o_price']) ? sanitize_text_field((string) $_POST['event_o_price']) : '';
    if ($price !== '') {
        update_post_meta($postId, EVENT_O_META_PRICE, $price);
    } else {
        delete_post_meta($postId, EVENT_O_META_PRICE);
    }

    $status = isset($_POST['event_o_status']) ? sanitize_text_field((string) $_POST['event_o_status']) : '';
    if ($status !== '') {
        update_post_meta($postId, EVENT_O_META_STATUS, $status);
    } else {
        delete_post_meta($postId, EVENT_O_META_STATUS);
    }

    $bands = isset($_POST['event_o_bands']) ? sanitize_textarea_field((string) $_POST['event_o_bands']) : '';
    if ($bands !== '') {
        update_post_meta($postId, EVENT_O_META_BANDS, $bands);
    } else {
        delete_post_meta($postId, EVENT_O_META_BANDS);
    }

    $galleryRaw = isset($_POST['event_o_gallery_ids']) ? (string) $_POST['event_o_gallery_ids'] : '';
    $galleryIds = array_values(array_filter(array_map('absint', array_map('trim', explode(',', $galleryRaw))), static fn($id) => $id > 0));
    $galleryIds = array_slice(array_unique($galleryIds), 0, 2);
    if (!empty($galleryIds)) {
        update_post_meta($postId, EVENT_O_META_GALLERY_IDS, implode(',', $galleryIds));
    } else {
        delete_post_meta($postId, EVENT_O_META_GALLERY_IDS);
    }
}
add_action('save_post_event_o_event', 'event_o_save_event_meta');

function event_o_admin_add_event_date_column(array $columns): array
{
    $updated = [];

    foreach ($columns as $key => $label) {
        if ($key === 'date') {
            $updated['event_o_event_date'] = __('Eventdatum', 'event-o');
        }

        $updated[$key] = $label;
    }

    if (!isset($updated['event_o_event_date'])) {
        $updated['event_o_event_date'] = __('Eventdatum', 'event-o');
    }

    return $updated;
}
add_filter('manage_event_o_event_posts_columns', 'event_o_admin_add_event_date_column');

function event_o_admin_render_event_date_column(string $column, int $postId): void
{
    if ($column !== 'event_o_event_date') {
        return;
    }

    $startTs = (int) get_post_meta($postId, EVENT_O_META_START_TS, true);
    if ($startTs <= 0) {
        $startTs = (int) get_post_meta($postId, EVENT_O_LEGACY_META_START_TS, true);
    }

    if ($startTs <= 0) {
        echo '&mdash;';
        return;
    }

    echo esc_html(wp_date('d.m.Y', $startTs, wp_timezone()));
}
add_action('manage_event_o_event_posts_custom_column', 'event_o_admin_render_event_date_column', 10, 2);
