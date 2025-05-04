<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Video-Link Metabox
function svl_add_video_metabox() {
    add_meta_box('svl_video_link', 'Video-Link (YouTube/Vimeo)', function($post) {
        $value = get_post_meta($post->ID, '_svl_video_link', true);
        echo '<input type="text" name="svl_video_link" value="' . esc_attr($value) . '" style="width:100%;" placeholder="https://youtube.com/...">';
    }, 'videolektion');
}
add_action('add_meta_boxes', 'svl_add_video_metabox');

// Speichern des Video-Links
function svl_save_video_link($post_id) {
    if (isset($_POST['svl_video_link'])) {
        update_post_meta($post_id, '_svl_video_link', sanitize_text_field($_POST['svl_video_link']));
    }
}
add_action('save_post', 'svl_save_video_link');
