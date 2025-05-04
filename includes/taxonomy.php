<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Taxonomie "Kurs" registrieren
function svl_register_course_taxonomy() {
    register_taxonomy('kurs', 'videolektion', array(
        'labels' => array(
            'name' => 'Kurse',
            'singular_name' => 'Kurs'
        ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'public' => true,
    ));
}
add_action('init', 'svl_register_course_taxonomy');

// Kursbild hinzufügen (Taxonomy Image)
function svl_add_kurs_image_field($term) {
    // Sicherstellen, dass $term ein Objekt ist
    if (!is_object($term)) {
        return;
    }
    $image_url = get_term_meta($term->term_id, 'kurs_image_url', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="kurs_image_url">Kursbild (Bild-URL)</label></th>
        <td>
            <input type="text" name="kurs_image_url" id="kurs_image_url" value="<?php echo esc_url($image_url); ?>" style="width:60%;" />
            <?php if ($image_url): ?>
                <img id="kurs_image_preview" src="<?php echo esc_url($image_url); ?>" style="max-width:150px;height:auto;margin-top:10px;" /><br/>
            <?php endif; ?>
            <p class="description">Geben Sie die vollständige URL des Bildes ein (z.B. https://ihre-website.de/wp-content/uploads/.../bild.jpg).</p>
        </td>
    </tr>
    <?php
}
add_action('kurs_edit_form_fields', 'svl_add_kurs_image_field');
add_action('kurs_add_form_fields', 'svl_add_kurs_image_field');

// Funktion zum Speichern der Bild-URL
function svl_save_kurs_image($term_id) {
    if (isset($_POST['kurs_image_url'])) {
        $image_url = esc_url_raw($_POST['kurs_image_url']);
        if (!empty($image_url)) {
             update_term_meta($term_id, 'kurs_image_url', $image_url);
        } else {
             delete_term_meta($term_id, 'kurs_image_url');
        }
    } else {
        delete_term_meta($term_id, 'kurs_image_url');
    }
}
add_action('edited_kurs', 'svl_save_kurs_image');
add_action('created_kurs', 'svl_save_kurs_image');
