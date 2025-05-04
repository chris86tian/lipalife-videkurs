<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Kursübersicht Shortcode mit Bildern und Fortschritt
function svl_courses_shortcode() {
    if (!is_user_logged_in()) return '<p>Bitte einloggen, um die Kurse zu sehen.</p>';

    $terms = get_terms(array(
        'taxonomy' => 'kurs',
        'hide_empty' => false,
        'parent' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
    ));

    $output = '<div class="svl-courses-overview" style="display:flex;flex-wrap:wrap;gap:20px;">';

    if (!empty($terms) && !is_wp_error($terms)) {
        // Get the overview button color from settings
        $overview_button_color = get_option('svl_overview_button_color', '#007bff'); // Standardfarbe als Fallback

        foreach ($terms as $term) {
            $image_url = get_term_meta($term->term_id, 'kurs_image_url', true);
            $image_html = $image_url ? '<div style="height:180px;background-size:cover;background-position:center;border-top-left-radius:8px;border-top-right-radius:8px;background-image:url(' . esc_url($image_url) . ');"></div>' : '<div style="height:180px;background-color:#222;border-top-left-radius:8px;border-top-right-radius:8px;"></div>';

            $args = array(
                'post_type' => 'videolektion',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'kurs',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                        'include_children' => true
                    )
                ),
                'fields' => 'ids'
            );
            $query = new WP_Query($args);
            $total = $query->found_posts;
            $completed = 0;
            if ($total > 0) {
                foreach ($query->posts as $lesson_post_id) {
                    if (get_user_meta(get_current_user_id(), 'svl_completed_' . $lesson_post_id, true)) {
                        $completed++;
                    }
                }
            }
            wp_reset_postdata();

            $percent = $total > 0 ? intval(($completed / $total) * 100) : 0;

            $first_lesson_args = array(
                'post_type' => 'videolektion',
                'posts_per_page' => 1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'kurs',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                        'include_children' => true
                    )
                )
            );
            $first_lesson_query = new WP_Query($first_lesson_args);
            $first_link = '#';
            if ($first_lesson_query->have_posts()) {
                $first_lesson_query->the_post();
                $first_link = get_permalink();
            }
            wp_reset_postdata();

            $output .= '<div style="flex:1 0 30%;border:1px solid #ccc;border-radius:8px;overflow:hidden;background-color:#f8f9fa;">
                ' . $image_html . '
                <div style="padding:15px;">
                    <h3 style="margin-top:0;">' . esc_html($term->name) . '</h3>
                    <p>' . esc_html($term->description) . '</p>
                    <a href="' . esc_url($first_link) . '" style="display:inline-block;padding:8px 15px;background-color:' . esc_attr($overview_button_color) . ';color:white;border-radius:5px;text-decoration:none;">Kurs starten</a>
                    <div style="margin-top:10px;">
                        <div style="background-color:#ddd;border-radius:10px;height:10px;width:100%;">
                            <div style="background-color:#28a745;height:10px;border-radius:10px;width:' . $percent . '%;"></div>
                        </div>
                        <small>' . $percent . '% abgeschlossen</small>
                    </div>
                </div>
            </div>';
        }
    } else {
        $output .= '<p>Keine Kurse gefunden.</p>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('svl_courses', 'svl_courses_shortcode');
