<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('evento_primary_color');
delete_option('evento_accent_color');
delete_option('evento_text_color');
delete_option('evento_muted_color');
delete_option('evento_enable_single_template');

delete_option('event_o_primary_color');
delete_option('event_o_accent_color');
delete_option('event_o_text_color');
delete_option('event_o_muted_color');
delete_option('event_o_enable_single_template');
