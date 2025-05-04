<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Funktionen zur Anpassung der Button-Farben wurden in includes/settings.php verschoben
 * und werden nun über die WordPress Options API gelesen.
 */

// Fortschritt speichern
function svl_mark_lesson_complete() {
    if (isset($_POST['svl_complete_lesson']) && is_user_logged_in()) {
        $lesson_id = intval($_POST['svl_complete_lesson']);
        update_user_meta(get_current_user_id(), 'svl_completed_' . $lesson_id, 1);
    }
    if (isset($_POST['svl_uncomplete_lesson']) && is_user_logged_in()) {
        $lesson_id = intval($_POST['svl_uncomplete_lesson']);
        delete_user_meta(get_current_user_id(), 'svl_completed_' . $lesson_id);
    }
}
add_action('init', 'svl_mark_lesson_complete');

// Inhalt und Fortschritt im Frontend
function svl_completion_button($content) {
    if (get_post_type() != 'videolektion' || !is_user_logged_in()) return $content;

    $user_id = get_current_user_id();
    $lesson_id = get_the_ID();
    $completed = get_user_meta($user_id, 'svl_completed_' . $lesson_id, true);

    // Video-Link
    $video_link = get_post_meta($lesson_id, '_svl_video_link', true);
    $video_embed = '';
    if ($video_link) {
        if (strpos($video_link, 'youtube.com/watch') !== false || strpos($video_link, 'youtu.be') !== false) {
            if (preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_link, $matches)) {
                $video_id = $matches[1];
                $video_link = 'https://www.youtube.com/embed/' . $video_id;
            }
        } elseif (strpos($video_link, 'vimeo.com') !== false) {
             if (preg_match('/vimeo\.com\/(\d+)/', $video_link, $matches)) {
                $video_id = $matches[1];
                $video_link = 'https://player.vimeo.com/video/' . $video_id;
            }
        }
        $video_embed = '<iframe width="100%" height="400" src="' . esc_url($video_link) . '" frameborder="0" allowfullscreen></iframe>';
    }

    // Button (Abschließen oder rückgängig) unter Verwendung der anpassbaren Farben aus den Einstellungen
    $complete_color = get_option('svl_complete_button_color', '#28a745'); // Standardfarbe als Fallback
    $uncomplete_color = get_option('svl_uncomplete_button_color', '#ffc107'); // Standardfarbe als Fallback

    if (!$completed) {
        $button = '<form method="post" class="svl-complete-button">
            <input type="hidden" name="svl_complete_lesson" value="' . $lesson_id . '">
            <button type="submit" style="background-color:' . esc_attr($complete_color) . ';color:white;padding:6px 12px;border:none;border-radius:5px;font-size:14px;cursor:pointer;">✅ Abschließen</button>
        </form>';
    } else {
        $button = '<form method="post" class="svl-complete-button">
            <input type="hidden" name="svl_uncomplete_lesson" value="' . $lesson_id . '">
            <button type="submit" style="background-color:' . esc_attr($uncomplete_color) . ';color:black;padding:6px 12px;border:none;border-radius:5px;font-size:14px;cursor:pointer;">🔄 Nochmals ansehen</button>
        </form>';
    }

    $video_and_button = '<div style="position:relative;">' . $video_embed . '</div>' . $button;

    // Navigation
    $kurs_terms = get_the_terms($lesson_id, 'kurs');
    $kursname = 'Kein Kurs';
    $nav = '';
    $fortschritt_balken = '';

    if ($kurs_terms && !is_wp_error($kurs_terms)) {
        $main_kurs_term = null;
        foreach ($kurs_terms as $term) {
            if (is_object($term) && isset($term->parent) && $term->parent == 0) {
                $main_kurs_term = $term;
                break;
            }
        }

        if ($main_kurs_term) {
            $kurs_id = $main_kurs_term->term_id;
            $kursname = esc_html($main_kurs_term->name);

            $course_terms_hierarchy = get_terms(array(
                'taxonomy' => 'kurs',
                'parent' => $kurs_id,
                'hide_empty' => false,
                'orderby' => 'term_order',
                'order' => 'ASC',
            ));

            if (empty($course_terms_hierarchy) || is_wp_error($course_terms_hierarchy)) {
                 $course_terms_hierarchy = array($main_kurs_term);
            } else {
                 $lessons_in_main_term = get_posts(array(
                    'post_type' => 'videolektion',
                    'posts_per_page' => -1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'kurs',
                            'field' => 'term_id',
                            'terms' => $main_kurs_term->term_id,
                            'include_children' => false
                        )
                    ),
                    'fields' => 'ids'
                 ));
                 if (!empty($lessons_in_main_term)) {
                     array_unshift($course_terms_hierarchy, $main_kurs_term);
                 }
            }

            $all_lessons_args = array(
                'post_type' => 'videolektion',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'kurs',
                        'field' => 'term_id',
                        'terms' => $kurs_id,
                        'include_children' => true
                    )
                ),
                'fields' => 'ids'
            );
            $all_lessons_query = new WP_Query($all_lessons_args);
            $total_lessons = $all_lessons_query->found_posts;
            $completed_count = 0;
            if ($total_lessons > 0) {
                 foreach ($all_lessons_query->posts as $lesson_post_id) {
                     if (get_user_meta($user_id, 'svl_completed_' . $lesson_post_id, true)) {
                         $completed_count++;
                     }
                 }
            }
            wp_reset_postdata();

            $percent = $total_lessons > 0 ? intval(($completed_count / $total_lessons) * 100) : 0;
            $fortschritt_balken = '<div style="margin-bottom:10px;">
                <div style="background-color:#ddd;border-radius:10px;height:10px;width:100%;">
                    <div style="background-color:#28a745;height:10px;border-radius:10px;width:' . $percent . '%;"></div>
                </div>
                <small>' . $percent . '% abgeschlossen</small>
            </div>';

            $nav = '<div style="border:1px solid #ccc;padding:15px;border-radius:8px;background-color:#f8f9fa;">';
            $nav .= '<h4 style="margin-top:0;font-size:18px;">' . $kursname . '</h4>';
            $nav .= $fortschritt_balken;

            foreach ($course_terms_hierarchy as $term) {
                $nav .= '<div class="svl-term-item">';
                if (is_object($term) && isset($term->term_id) && ($term->term_id != $main_kurs_term->term_id || !empty($lessons_in_main_term))) {
                     $nav .= '<h5>' . esc_html($term->name) . '</h5>';
                }
                $lessons_args = array(
                    'post_type' => 'videolektion',
                    'posts_per_page' => -1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'kurs',
                            'field' => 'term_id',
                            'terms' => is_object($term) && isset($term->term_id) ? $term->term_id : 0,
                            'include_children' => false
                        )
                    )
                );
                $lessons_query = new WP_Query($lessons_args);
                if ($lessons_query->have_posts()) {
                    $nav .= '<ul class="svl-lesson-list">';
                    while ($lessons_query->have_posts()) {
                        $lessons_query->the_post();
                        $lesson_post_id = get_the_ID();
                        $is_active = $lesson_post_id == $lesson_id ? ' active' : '';
                        $is_completed = get_user_meta($user_id, 'svl_completed_' . $lesson_post_id, true) ? ' completed' : '';
                        $nav .= '<li><a href="' . get_permalink() . '" class="' . $is_active . $is_completed . '">' . get_the_title() . '</a></li>';
                    }
                    $nav .= '</ul>';
                }
                wp_reset_postdata();
                $nav .= '</div>';
            }
            $nav .= '</div>';
        }
    }
    $html = '<div style="display:flex;gap:20px;flex-wrap:wrap;">
        <div style="flex:1 1 25%;min-width:220px;">' . $nav . '</div>
        <div style="flex:1 1 70%;min-width:300px;">' . $video_and_button . '<div style="margin-top:20px;">' . $content . '</div></div>
    </div>';
    $html = '<div class="svl-wrapper">' . $html . '</div>';
    return $html;
}
add_filter('the_content', 'svl_completion_button');
