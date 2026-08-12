<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$optionNames = [
    'evento_primary_color',
    'evento_accent_color',
    'evento_text_color',
    'evento_muted_color',
    'evento_enable_single_template',
    'event_o_primary_color',
    'event_o_accent_color',
    'event_o_text_color',
    'event_o_muted_color',
    'event_o_enable_single_template',
    'event_o_share_options',
    'event_o_dark_mode',
    'event_o_dark_selector',
    'event_o_light_selector',
    'event_o_high_contrast',
    'event_o_single_animation',
    'event_o_related_category_only',
    'event_o_hero_parallax',
    'event_o_single_lightbox',
    'event_o_single_category_color',
    'event_o_single_title_layout',
    'event_o_single_show_tags',
    'event_o_past_grace_days',
    'event_o_wizard_mode',
    'event_o_show_org_description',
    'event_o_capability_schema_version',
];

foreach ($optionNames as $optionName) {
    delete_option($optionName);
}

$eventCaps = [
    'edit_event_o_event',
    'read_event_o_event',
    'delete_event_o_event',
    'edit_event_o_events',
    'edit_others_event_o_events',
    'edit_published_event_o_events',
    'edit_private_event_o_events',
    'publish_event_o_events',
    'read_private_event_o_events',
    'delete_event_o_events',
    'delete_others_event_o_events',
    'delete_published_event_o_events',
    'delete_private_event_o_events',
    'event_o_edit_scoped_events',
];

foreach (['administrator', 'editor'] as $roleName) {
    $role = get_role($roleName);
    if (!$role) {
        continue;
    }
    foreach ($eventCaps as $cap) {
        $role->remove_cap($cap);
    }
}

remove_role('event_o_contributor');

foreach ([
    'event_o_allowed_categories',
    'event_o_allowed_venues',
    'event_o_allowed_organizers',
    'event_o_allow_standard_posts',
] as $metaKey) {
    delete_metadata('user', 0, $metaKey, '', true);
}
