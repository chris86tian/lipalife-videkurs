<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// CPT registrieren
function svl_register_lesson_cpt() {
    register_post_type('videolektion', array(
        'labels' => array(
            'name' => 'Videolektionen',
            'singular_name' => 'Videolektion'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'svl_register_lesson_cpt');
