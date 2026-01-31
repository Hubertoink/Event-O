<?php

if (!defined('ABSPATH')) {
    exit;
}

const EVENT_O_META_START_TS = '_event_o_start_ts';
const EVENT_O_META_END_TS = '_event_o_end_ts';
const EVENT_O_META_PRICE = '_event_o_price';
const EVENT_O_META_STATUS = '_event_o_status';

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
}

function event_o_admin_meta_boxes(): void
{
    add_meta_box(
        'event_o_event_details',
        __('Event Details', 'event-o'),
        'event_o_render_event_details_metabox',
        'event_o_event',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_event_o_event', 'event_o_admin_meta_boxes');

function event_o_render_event_details_metabox(WP_Post $post): void
{
    wp_nonce_field('event_o_save_event_meta', 'event_o_event_meta_nonce');

    $startTs = (int) get_post_meta($post->ID, EVENT_O_META_START_TS, true);
    $endTs = (int) get_post_meta($post->ID, EVENT_O_META_END_TS, true);
    $price = (string) get_post_meta($post->ID, EVENT_O_META_PRICE, true);
    $status = (string) get_post_meta($post->ID, EVENT_O_META_STATUS, true);

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

    $statuses = [
        '' => __('Normal', 'event-o'),
        'cancelled' => __('Cancelled', 'event-o'),
        'postponed' => __('Postponed', 'event-o'),
        'soldout' => __('Sold out', 'event-o'),
    ];

    echo '<p><label for="event_o_start_datetime"><strong>' . esc_html__('Start', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_start_datetime" name="event_o_start_datetime" value="' . esc_attr($startValue) . '" style="width:100%" /></p>';

    echo '<p><label for="event_o_end_datetime"><strong>' . esc_html__('End (optional)', 'event-o') . '</strong></label></p>';
    echo '<p><input type="datetime-local" id="event_o_end_datetime" name="event_o_end_datetime" value="' . esc_attr($endValue) . '" style="width:100%" /></p>';

    echo '<p><label for="event_o_price"><strong>' . esc_html__('Price', 'event-o') . '</strong></label></p>';
    echo '<p><input type="text" id="event_o_price" name="event_o_price" value="' . esc_attr($price) . '" placeholder="z.B. Frei / 5 €" style="width:100%" /></p>';

    echo '<p><label for="event_o_status"><strong>' . esc_html__('Status', 'event-o') . '</strong></label></p>';
    echo '<p><select id="event_o_status" name="event_o_status" style="width:100%">';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';

    echo '<p style="color:#666;font-size:12px;margin-top:10px">' . esc_html__('Tip: Use taxonomies for Organizer/Venue to keep data consistent.', 'event-o') . '</p>';
}

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

    $startRaw = isset($_POST['event_o_start_datetime']) ? (string) $_POST['event_o_start_datetime'] : '';
    $endRaw = isset($_POST['event_o_end_datetime']) ? (string) $_POST['event_o_end_datetime'] : '';

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
        update_post_meta($postId, EVENT_O_META_START_TS, $startTs);
    } else {
        delete_post_meta($postId, EVENT_O_META_START_TS);
    }

    if ($endTs > 0) {
        update_post_meta($postId, EVENT_O_META_END_TS, $endTs);
    } else {
        delete_post_meta($postId, EVENT_O_META_END_TS);
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
}
add_action('save_post_event_o_event', 'event_o_save_event_meta');
