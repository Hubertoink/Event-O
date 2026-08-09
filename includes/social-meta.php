<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds a per-event social preview. Social networks read these tags before they
 * render a link, so JavaScript-based galleries cannot provide the preview.
 */
function event_o_output_social_meta(): void
{
    if (!is_singular('event_o_event')) {
        return;
    }

    $postId = get_queried_object_id();
    if ($postId <= 0 || !has_post_thumbnail($postId)) {
        return;
    }

    $imageId = (int) get_post_thumbnail_id($postId);
    $image = wp_get_attachment_image_src($imageId, 'full');
    if (!is_array($image) || empty($image[0])) {
        return;
    }

    $title = wp_strip_all_tags(get_the_title($postId));
    $description = wp_strip_all_tags(get_the_excerpt($postId));
    if ($description === '') {
        $description = wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $postId)), 30, '…');
    }

    $url = get_permalink($postId);
    $imageUrl = $image[0];
    $imageAlt = get_post_meta($imageId, '_wp_attachment_image_alt', true);
    if (!is_string($imageAlt) || $imageAlt === '') {
        $imageAlt = $title;
    }

    echo "\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($imageUrl) . '">' . "\n";
    echo '<meta property="og:image:width" content="' . (int) $image[1] . '">' . "\n";
    echo '<meta property="og:image:height" content="' . (int) $image[2] . '">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr($imageAlt) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($imageUrl) . '">' . "\n";
}
add_action('wp_head', 'event_o_output_social_meta', 5);
