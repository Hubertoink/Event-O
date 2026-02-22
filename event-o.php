<?php
/**
 * Plugin Name:       Event_O
 * Description:       Clean event management (CPT + Gutenberg blocks: list/accordion + carousel).
 * Version:           1.0.0
 * Author:            Hubertoink
 * Text Domain:       event-o
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EVENT_O_VERSION', '1.0.0');
define('EVENT_O_PLUGIN_FILE', __FILE__);
define('EVENT_O_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVENT_O_PLUGIN_URL', plugin_dir_url(__FILE__));

define('EVENT_O_OPTION_PRIMARY', 'event_o_primary_color');
define('EVENT_O_OPTION_ACCENT', 'event_o_accent_color');
define('EVENT_O_OPTION_TEXT', 'event_o_text_color');
define('EVENT_O_OPTION_MUTED', 'event_o_muted_color');
define('EVENT_O_OPTION_ENABLE_SINGLE', 'event_o_enable_single_template');
define('EVENT_O_OPTION_SHARE_OPTIONS', 'event_o_share_options');
define('EVENT_O_OPTION_DARK_MODE', 'event_o_dark_mode');
define('EVENT_O_OPTION_DARK_SELECTOR', 'event_o_dark_selector');
define('EVENT_O_OPTION_LIGHT_SELECTOR', 'event_o_light_selector');
define('EVENT_O_OPTION_HIGH_CONTRAST', 'event_o_high_contrast');
define('EVENT_O_OPTION_SINGLE_ANIMATION', 'event_o_single_animation');
define('EVENT_O_OPTION_RELATED_CATEGORY_ONLY', 'event_o_related_category_only');
define('EVENT_O_OPTION_HERO_PARALLAX', 'event_o_hero_parallax');
define('EVENT_O_OPTION_SINGLE_CATEGORY_COLOR', 'event_o_single_category_color');

require_once EVENT_O_PLUGIN_DIR . 'includes/cpt.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/taxonomies.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/meta.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/organizer-meta.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/venue-meta.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/settings.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/capabilities.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/render.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/blocks.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/assets.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/template.php';
require_once EVENT_O_PLUGIN_DIR . 'includes/ical-download.php';

function event_o_init(): void
{
    event_o_register_post_type();
    event_o_register_taxonomies();
    event_o_register_meta();
    event_o_register_blocks();
}
add_action('init', 'event_o_init');

function event_o_admin_init(): void
{
    event_o_register_settings();
}
add_action('admin_init', 'event_o_admin_init');

function event_o_admin_menu(): void
{
    event_o_register_settings_page();
}
add_action('admin_menu', 'event_o_admin_menu');

register_activation_hook(__FILE__, function (): void {
    event_o_register_post_type();
    event_o_register_taxonomies();
    event_o_register_ical_endpoint();
    event_o_assign_capabilities();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function (): void {
    event_o_remove_capabilities();
    flush_rewrite_rules();
});
