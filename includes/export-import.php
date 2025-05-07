<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
  # Hierarchischer Export/Import und Kurslöschung
  1. Export-Mechanismus:
     - Exportiert zuerst den Kurs (top-level tax-term vom Typ "kurs").
     - Exportiert direkt zugehörige Lektionen (ohne Unterbegriffe).
     - Exportiert anschließend alle Module (child-Terme) des Kurses.
     - Exportiert die Lektionen, die diesen Modulen zugeordnet sind.
     - Exportiert den Namen des Eltern-Terms statt der ID.
  2. Import Logik:
     - Zwei-Pass-Import.
     - Pass 1: Importiert/aktualisiert Kurse/Module basierend auf "Name" und "Parent Name". Baut eine Map von Namen zu neuen Term-IDs.
     - Pass 2: Importiert/aktualisiert Lektionen basierend auf "Name" und "Parent Name". Nutzt die Map aus Pass 1, um die Lektionen den korrekten Eltern-Terms zuzuordnen.
     - Aktualisiert bestehende Einträge oder erstellt neue.
     - Optimierung: Entferne BOM-Zeichen aus den Kopfzeilen und gebe eine detaillierte Fehlerausgabe.
  3. Kurslöschfunktion:
     - Neue Admin-Seite zum Auswählen und Löschen eines Kurses.
     - Sicherheitsabfrage vor dem Löschen.
     - Option zum Exportieren des Kurses vor der Löschung.
  4. Hinweise:
     - CSV-Spalten: Type, Exported ID, Parent Name, Name, Description, Image URL, Video Link, Menu Order.
     - Hierarchie-Typen: "course", "module", "lesson".
*/

// Admin Menü für Export/Import und Löschung
function svl_add_export_import_delete_menu() {
    add_submenu_page(
        'edit.php?post_type=videolektion',
        'Kurse/Lektionen Export',
        'Export CSV',
        'manage_options',
        'svl-export',
        'svl_export_page'
    );
    add_submenu_page(
        'edit.php?post_type=videolektion',
        'Kurse/Lektionen Import',
        'Import CSV',
        'manage_options',
        'svl-import',
        'svl_import_page'
    );
    add_submenu_page(
        'edit.php?post_type=videolektion',
        'Kurs löschen',
        'Kurs löschen',
        'manage_options',
        'svl-delete-course',
        'svl_delete_course_page'
    );
}
add_action('admin_menu', 'svl_add_export_import_delete_menu');

// Export Seite und Logik
function svl_export_page() {
    ?>
    <div class="wrap">
        <h1>Kurse und Lektionen exportieren</h1>
        <p>Klicken Sie auf den Button, um alle Kurse inkl. Module und Lektionen als hierarchische CSV-Datei herunterzuladen.</p>
        <p>Die Datei enthält die Spalten: Type, Exported ID, Parent Name, Name, Description, Image URL, Video Link, Menu Order.</p>
        <form method="post" action="">
            <input type="hidden" name="svl_action" value="export_csv">
            <?php wp_nonce_field('svl_export_csv_nonce', 'svl_export_nonce'); ?>
            <button type="submit" class="button button-primary">CSV exportieren</button>
        </form>
    </div>
    <?php
}

// Export Logik
function svl_handle_export() {
    if ( isset($_POST['svl_action']) && $_POST['svl_action'] === 'export_csv' &&
         current_user_can('manage_options') && isset($_POST['svl_export_nonce']) &&
         wp_verify_nonce($_POST['svl_export_nonce'], 'svl_export_csv_nonce') ) {

        $course_id_to_export = isset($_POST['svl_export_course_id']) ? intval($_POST['svl_export_course_id']) : 0;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=lipalife_kurse_lektionen_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, array('Type', 'Exported ID', 'Parent Name', 'Name', 'Description', 'Image URL', 'Video Link', 'Menu Order'));

        $courses_to_export = array();
        if ($course_id_to_export > 0) {
            $course = get_term($course_id_to_export, 'kurs');
            if ($course && !is_wp_error($course)) {
                $courses_to_export[] = $course;
            }
        } else {
            $courses_to_export = get_terms(array(
                'taxonomy'   => 'kurs',
                'hide_empty' => false,
                'parent'     => 0,
                'orderby'    => 'name',
                'order'      => 'ASC'
            ));
        }

        if ( !empty($courses_to_export) && !is_wp_error($courses_to_export) ) {
            foreach ( $courses_to_export as $course ) {
                $course_image = get_term_meta($course->term_id, 'kurs_image_url', true);
                fputcsv($output, array(
                    'course',
                    $course->term_id,
                    '',
                    $course->name,
                    $course->description,
                    $course_image,
                    '',
                    ''
                ));

                $lessons_in_course = new WP_Query(array(
                    'post_type'      => 'videolektion',
                    'posts_per_page' => -1,
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'kurs',
                            'field'    => 'term_id',
                            'terms'    => $course->term_id,
                            'include_children' => false
                        )
                    )
                ));
                if ( $lessons_in_course->have_posts() ) {
                    while ( $lessons_in_course->have_posts() ) {
                        $lessons_in_course->the_post();
                        $lesson_id  = get_the_ID();
                        $video_link = get_post_meta($lesson_id, '_svl_video_link', true);
                        $menu_order = get_post_field('menu_order', $lesson_id);
                        fputcsv($output, array(
                            'lesson',
                            $lesson_id,
                            $course->name,
                            get_the_title(),
                            get_the_content(),
                            '',
                            $video_link,
                            $menu_order
                        ));
                    }
                    wp_reset_postdata();
                }

                $modules = get_terms(array(
                    'taxonomy'   => 'kurs',
                    'hide_empty' => false,
                    'parent'     => $course->term_id,
                    'orderby'    => 'name',
                    'order'      => 'ASC'
                ));
                if ( !empty($modules) && !is_wp_error($modules) ) {
                    foreach ( $modules as $module ) {
                        fputcsv($output, array(
                            'module',
                            $module->term_id,
                            $course->name,
                            $module->name,
                            $module->description,
                            get_term_meta($module->term_id, 'kurs_image_url', true),
                            '',
                            ''
                        ));

                        $lessons_in_module = new WP_Query(array(
                            'post_type'      => 'videolektion',
                            'posts_per_page' => -1,
                            'orderby'        => 'menu_order',
                            'order'          => 'ASC',
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'kurs',
                                    'field'    => 'term_id',
                                    'terms'    => $module->term_id,
                                    'include_children' => false
                                )
                            )
                        ));
                        if ( $lessons_in_module->have_posts() ) {
                            while ( $lessons_in_module->have_posts() ) {
                                $lessons_in_module->the_post();
                                $lesson_id  = get_the_ID();
                                $video_link = get_post_meta($lesson_id, '_svl_video_link', true);
                                $menu_order = get_post_field('menu_order', $lesson_id);
                                fputcsv($output, array(
                                    'lesson',
                                    $lesson_id,
                                    $module->name,
                                    get_the_title(),
                                    get_the_content(),
                                    '',
                                    $video_link,
                                    $menu_order
                                ));
                            }
                            wp_reset_postdata();
                        }
                    }
                }
            }
        }
        fclose($output);
        exit;
    }
}
add_action('admin_init', 'svl_handle_export');

// Import Seite und Logik
function svl_import_page() {
    ?>
    <div class="wrap">
        <h1>Kurse und Lektionen importieren</h1>
        <?php
        if (isset($_GET['svl_import_message'])) {
            $message = sanitize_text_field($_GET['svl_import_message']);
            $type = isset($_GET['svl_import_type']) ? sanitize_text_field($_GET['svl_import_type']) : 'success';
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        $detailed_errors = get_transient('svl_import_detailed_errors');
        if (!empty($detailed_errors)) {
            echo '<div class="notice notice-error">';
            echo '<h2>Detaillierte Importfehler:</h2>';
            echo '<ul>';
            foreach ($detailed_errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            delete_transient('svl_import_detailed_errors');
        }
        ?>
        <p>Laden Sie eine CSV-Datei hoch, um Kurse, Module und Lektionen zu importieren. Die Datei sollte die Spalten "Type", "Exported ID", "Parent Name", "Name", "Description", "Image URL", "Video Link" und "Menu Order" enthalten.</p>
        <p><b>Hinweis:</b> Der Import versucht, bestehende Einträge anhand des 'Name' zu finden und zu aktualisieren. Neue Einträge werden erstellt. Die Zuordnung erfolgt über den 'Parent Name'.</p>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="svl_action" value="import_csv">
            <?php wp_nonce_field('svl_import_csv_nonce', 'svl_import_nonce'); ?>
            <input type="file" name="svl_import_file" accept=".csv">
            <button type="submit" class="button button-primary">CSV importieren</button>
        </form>
    </div>
    <?php
}

// Import Logik
function svl_handle_import() {
    if (isset($_POST['svl_action']) && $_POST['svl_action'] === 'import_csv' && current_user_can('manage_options') && isset($_POST['svl_import_nonce']) && wp_verify_nonce($_POST['svl_import_nonce'], 'svl_import_csv_nonce')) {

        delete_transient('svl_import_detailed_errors');

        if (empty($_FILES['svl_import_file']['name'])) {
            wp_redirect(add_query_arg(array('svl_import_message' => 'Bitte wählen Sie eine Datei aus.', 'svl_import_type' => 'error'), admin_url('edit.php?post_type=videolektion&page=svl-import')));
            exit;
        }

        $file = $_FILES['svl_import_file'];
        $filepath = $file['tmp_name'];
        $filename = $file['name'];
        $filetype = wp_check_filetype($filename, array('csv' => 'text/csv'));

        if ($filetype['type'] !== 'text/csv') {
            wp_redirect(add_query_arg(array('svl_import_message' => 'Ungültiger Dateityp.', 'svl_import_type' => 'error'), admin_url('edit.php?post_type=videolektion&page=svl-import')));
            exit;
        }

        if (($handle = fopen($filepath, 'r')) !== FALSE) {
            // Read header row and remove BOM if present
            $header = fgetcsv($handle, 0, ',');
            if (isset($header[0])) {
                // Entferne BOM-Zeichen aus dem ersten Element
                $header[0] = str_replace("\xEF\xBB\xBF", "", $header[0]);
            }
            $expected_header = array('Type', 'Exported ID', 'Parent Name', 'Name', 'Description', 'Image URL', 'Video Link', 'Menu Order');

            $sanitized_header = array_map(function($col) {
                return trim(trim((string)$col, '"'));
            }, $header);

            // If header columns don't match, provide detailed error message.
            if (count($sanitized_header) < count($expected_header) || array_diff($expected_header, $sanitized_header)) {
                $error_detail = 'Erwartet: [' . implode(', ', $expected_header) . '] | Gefunden: [' . implode(', ', $sanitized_header) . ']';
                fclose($handle);
                wp_redirect(add_query_arg(array('svl_import_message' => 'Ungültiges CSV-Format. ' . $error_detail, 'svl_import_type' => 'error'), admin_url('edit.php?post_type=videolektion&page=svl-import')));
                exit;
            }

            $imported_count = 0;
            $errors = array();
            $term_name_to_id = array();
            $row_index = 1; // Start after header

            // Pass 1: Import/Update Terms (Courses and Modules)
            rewind($handle);
            fgetcsv($handle); // Skip header

            while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                $row_index++;
                if (count($data) < count($expected_header)) {
                    $errors[] = 'Zeile ' . $row_index . ': Zu wenige Spalten. Daten: ' . implode(',', $data);
                    continue;
                }
                $type = sanitize_text_field($data[0]);
                $parent_name = sanitize_text_field($data[2]);
                $name = sanitize_text_field($data[3]);
                $description = sanitize_textarea_field($data[4]);
                $image_url = esc_url_raw($data[5]);

                if ($type === 'course' || $type === 'module') {
                    $parent_term_id = 0;
                    if (!empty($parent_name)) {
                        $parent_term = get_term_by('name', $parent_name, 'kurs');
                        if ($parent_term && !is_wp_error($parent_term)) {
                            $parent_term_id = $parent_term->term_id;
                        } else {
                            $errors[] = 'Zeile ' . $row_index . ': Eltern-Kurs/Modul "' . esc_html($parent_name) . '" für "' . esc_html($name) . '" nicht gefunden (Pass 1).';
                            continue;
                        }
                    }

                    $existing_term = get_term_by('name', $name, 'kurs');

                    if ($existing_term && !is_wp_error($existing_term)) {
                        $updated = wp_update_term($existing_term->term_id, 'kurs', array(
                            'description' => $description,
                            'parent' => $parent_term_id,
                        ));
                        if (is_wp_error($updated)) {
                            $errors[] = 'Zeile ' . $row_index . ': Fehler beim Aktualisieren von "' . esc_html($name) . '": ' . $updated->get_error_message();
                        } else {
                            if (!empty($image_url)) {
                                update_term_meta($existing_term->term_id, 'kurs_image_url', $image_url);
                            } else {
                                delete_term_meta($existing_term->term_id, 'kurs_image_url');
                            }
                            $term_name_to_id[$name] = $existing_term->term_id;
                            $imported_count++;
                        }
                    } else {
                        $inserted = wp_insert_term($name, 'kurs', array(
                            'description' => $description,
                            'parent' => $parent_term_id,
                        ));
                        if (is_wp_error($inserted)) {
                            $errors[] = 'Zeile ' . $row_index . ': Fehler beim Erstellen von "' . esc_html($name) . '": ' . $inserted->get_error_message();
                        } else {
                            if (!empty($image_url)) {
                                update_term_meta($inserted['term_id'], 'kurs_image_url', $image_url);
                            }
                            $term_name_to_id[$name] = $inserted['term_id'];
                            $imported_count++;
                        }
                    }
                }
            }

            // Pass 2: Import/Update Lessons
            rewind($handle);
            fgetcsv($handle); // Skip header
            $row_index = 1;

            while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                $row_index++;
                if (count($data) < count($expected_header)) {
                    continue;
                }
                $type = sanitize_text_field($data[0]);
                $parent_name = sanitize_text_field($data[2]);
                $name = sanitize_text_field($data[3]);
                $content = wp_kses_post($data[4]);
                $video_link = sanitize_text_field($data[6]);
                $menu_order = intval($data[7]);

                if ($type === 'lesson') {
                    $parent_term_id = 0;
                    if (!empty($parent_name)) {
                        if (isset($term_name_to_id[$parent_name])) {
                            $parent_term_id = $term_name_to_id[$parent_name];
                        } else {
                            $parent_term = get_term_by('name', $parent_name, 'kurs');
                            if ($parent_term && !is_wp_error($parent_term)) {
                                $parent_term_id = $parent_term->term_id;
                                $term_name_to_id[$parent_name] = $parent_term_id;
                            } else {
                                $errors[] = 'Zeile ' . $row_index . ': Eltern-Kurs/Modul "' . esc_html($parent_name) . '" für Lektion "' . esc_html($name) . '" nicht gefunden (Pass 2). Lektion wird ohne Kurs/Modul importiert.';
                            }
                        }
                    }

                    $existing_lesson = get_page_by_title($name, OBJECT, 'videolektion');
                    $post_data = array(
                        'post_title'    => $name,
                        'post_content'  => $content,
                        'post_status'   => 'publish',
                        'post_type'     => 'videolektion',
                        'menu_order'    => $menu_order,
                    );

                    if ($existing_lesson) {
                        $post_data['ID'] = $existing_lesson->ID;
                        $updated_id = wp_update_post($post_data);
                        if (is_wp_error($updated_id)) {
                            $errors[] = 'Zeile ' . $row_index . ': Fehler beim Aktualisieren von Lektion "' . esc_html($name) . '": ' . $updated_id->get_error_message();
                        } else {
                            update_post_meta($updated_id, '_svl_video_link', $video_link);
                            if ($parent_term_id > 0) {
                                wp_set_post_terms($updated_id, array($parent_term_id), 'kurs');
                            } else {
                                wp_set_post_terms($updated_id, null, 'kurs');
                            }
                        }
                    } else {
                        $inserted_id = wp_insert_post($post_data);
                        if (is_wp_error($inserted_id)) {
                            $errors[] = 'Zeile ' . $row_index . ': Fehler beim Erstellen der Lektion "' . esc_html($name) . '": ' . $inserted_id->get_error_message();
                        } else {
                            update_post_meta($inserted_id, '_svl_video_link', $video_link);
                            if ($parent_term_id > 0) {
                                wp_set_post_terms($inserted_id, array($parent_term_id), 'kurs');
                            }
                        }
                    }
                }
            }

            fclose($handle);

            $message = 'Import abgeschlossen. ' . $imported_count . ' Terme (Kurse/Module) verarbeitet.';
            $type = 'success';
            if (!empty($errors)) {
                $message .= ' Es gab ' . count($errors) . ' Fehler oder Warnungen während des Imports.';
                $type = 'warning';
                set_transient('svl_import_detailed_errors', $errors, 60 * 5);
            }
            wp_redirect(add_query_arg(array('svl_import_message' => $message, 'svl_import_type' => $type), admin_url('edit.php?post_type=videolektion&page=svl-import')));
            exit;
        } else {
            wp_redirect(add_query_arg(array('svl_import_message' => 'Fehler beim Öffnen der Datei.', 'svl_import_type' => 'error'), admin_url('edit.php?post_type=videolektion&page=svl-import')));
            exit;
        }
    }
}
add_action('admin_init', 'svl_handle_import');

// Kurs löschen Seite
function svl_delete_course_page() {
    ?>
    <div class="wrap">
        <h1>Kurs löschen</h1>
        <?php
        if (isset($_GET['svl_delete_message'])) {
            $message = sanitize_text_field($_GET['svl_delete_message']);
            $type = isset($_GET['svl_delete_type']) ? sanitize_text_field($_GET['svl_delete_type']) : 'success';
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        $courses = get_terms(array(
            'taxonomy'   => 'kurs',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ));

        if (empty($courses) || is_wp_error($courses)) {
            echo '<p>Keine Kurse zum Löschen gefunden.</p>';
            return;
        }
        ?>
        <p>Wählen Sie einen Kurs aus, den Sie löschen möchten. <b>WARNUNG:</b> Das Löschen eines Kurses entfernt auch alle zugehörigen Module und Lektionen sowie den Fortschritt der Benutzer für diese Lektionen. Dies kann nicht rückgängig gemacht werden!</p>
        <form method="post" action="">
            <input type="hidden" name="svl_action" value="prepare_delete_course">
            <?php wp_nonce_field('svl_delete_course_nonce', 'svl_delete_nonce'); ?>
            <label for="svl_course_to_delete">Kurs auswählen:</label>
            <select name="svl_course_to_delete" id="svl_course_to_delete">
                <option value="">-- Kurs auswählen --</option>
                <?php
                foreach ($courses as $course) {
                    echo '<option value="' . esc_attr($course->term_id) . '">' . esc_html($course->name) . '</option>';
                }
                ?>
            </select>
            <button type="submit" class="button button-secondary">Weiter</button>
        </form>
        <?php
        if (isset($_POST['svl_action']) && $_POST['svl_action'] === 'prepare_delete_course' &&
            current_user_can('manage_options') && isset($_POST['svl_delete_nonce']) &&
            wp_verify_nonce($_POST['svl_delete_nonce'], 'svl_delete_course_nonce') &&
            isset($_POST['svl_course_to_delete']) && intval($_POST['svl_course_to_delete']) > 0 ) {

            $course_id = intval($_POST['svl_course_to_delete']);
            $course = get_term($course_id, 'kurs');

            if ($course && !is_wp_error($course)) {
                ?>
                <hr>
                <h2>Kurs "<?php echo esc_html($course->name); ?>" löschen</h2>
                <p style="color: red; font-weight: bold;">Sind Sie sicher, dass Sie diesen Kurs und alle zugehörigen Module und Lektionen unwiderruflich löschen möchten?</p>
                <p>Es wird empfohlen, den Kurs vorher zu exportieren, um eine Sicherung zu haben.</p>
                <form method="post" action="">
                    <input type="hidden" name="svl_action" value="export_csv">
                    <input type="hidden" name="svl_export_course_id" value="<?php echo esc_attr($course_id); ?>">
                    <?php wp_nonce_field('svl_export_csv_nonce', 'svl_export_nonce'); ?>
                    <button type="submit" class="button button-primary">Kurs jetzt exportieren</button>
                </form>
                <form method="post" action="" onsubmit="return confirm('Dies kann NICHT rückgängig gemacht werden. Sind Sie ABSOLUT sicher, dass Sie den Kurs \'<?php echo esc_js($course->name); ?>\' und alle zugehörigen Daten löschen möchten?');">
                    <input type="hidden" name="svl_action" value="confirm_delete_course">
                    <input type="hidden" name="svl_course_id_to_delete" value="<?php echo esc_attr($course_id); ?>">
                    <?php wp_nonce_field('svl_delete_course_nonce', 'svl_delete_nonce'); ?>
                    <button type="submit" class="button button-danger" style="margin-top: 10px;">Ja, Kurs unwiderruflich löschen</button>
                </form>
                <?php
            } else {
                 echo '<div class="notice notice-error"><p>Ausgewählter Kurs wurde nicht gefunden.</p></div>';
            }
        }
        ?>
    </div>
    <?php
}

function svl_handle_delete_course() {
    if (isset($_POST['svl_action']) && $_POST['svl_action'] === 'confirm_delete_course' &&
        current_user_can('manage_options') && isset($_POST['svl_delete_nonce']) &&
        wp_verify_nonce($_POST['svl_delete_nonce'], 'svl_delete_course_nonce') &&
        isset($_POST['svl_course_id_to_delete']) && intval($_POST['svl_course_id_to_delete']) > 0 ) {

        $course_id = intval($_POST['svl_course_id_to_delete']);
        $course = get_term($course_id, 'kurs');

        if ($course && !is_wp_error($course)) {
            $terms_to_delete = get_terms(array(
                'taxonomy'   => 'kurs',
                'hide_empty' => false,
                'child_of'   => $course_id,
                'fields'     => 'ids'
            ));
            $terms_to_delete[] = $course_id;

            $lessons_to_delete_args = array(
                'post_type'      => 'videolektion',
                'posts_per_page' => -1,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'kurs',
                        'field'    => 'term_id',
                        'terms'    => $terms_to_delete,
                        'include_children' => true
                    )
                ),
                'fields' => 'ids'
            );
            $lessons_query = new WP_Query($lessons_to_delete_args);
            $lessons_to_delete_ids = $lessons_query->posts;
            wp_reset_postdata();

            $deleted_lessons_count = 0;
            $deleted_terms_count = 0;
            $delete_errors = array();

            if (!empty($lessons_to_delete_ids)) {
                foreach ($lessons_to_delete_ids as $lesson_id) {
                    $users = get_users(array('fields' => 'ID'));
                    foreach ($users as $user_id) {
                        delete_user_meta($user_id, 'svl_completed_' . $lesson_id);
                    }
                    $deleted = wp_delete_post($lesson_id, true);
                    if ($deleted) {
                        $deleted_lessons_count++;
                    } else {
                        $delete_errors[] = 'Fehler beim Löschen der Lektion ID: ' . $lesson_id;
                    }
                }
            }

            $terms_to_delete = array_reverse($terms_to_delete);
            if (!empty($terms_to_delete)) {
                foreach ($terms_to_delete as $term_id) {
                    $deleted = wp_delete_term($term_id, 'kurs');
                    if ($deleted && !is_wp_error($deleted)) {
                        $deleted_terms_count++;
                    } else {
                        $delete_errors[] = 'Fehler beim Löschen des Terms ID: ' . $term_id . (is_wp_error($deleted) ? ' - ' . $deleted->get_error_message() : '');
                    }
                    delete_term_meta($term_id, 'kurs_image_url');
                }
            }

            $message = sprintf('Kurs "%s" erfolgreich gelöscht. %d Lektion(en) und %d Term(e) gelöscht.', esc_html($course->name), $deleted_lessons_count, $deleted_terms_count);
            $type = 'success';

            if (!empty($delete_errors)) {
                $message .= ' Es gab Fehler: ' . implode('; ', $delete_errors);
                $type = 'warning';
            }

            wp_redirect(add_query_arg(array('svl_delete_message' => $message, 'svl_delete_type' => $type), admin_url('edit.php?post_type=videolektion&page=svl-delete-course')));
            exit;
        } else {
            wp_redirect(add_query_arg(array('svl_delete_message' => 'Fehler: Kurs zum Löschen nicht gefunden.', 'svl_delete_type' => 'error'), admin_url('edit.php?post_type=videolektion&page=svl-delete-course')));
            exit;
        }
    }
}
add_action('admin_init', 'svl_handle_delete_course');

// Ensure the new menu item is added
add_action('admin_menu', 'svl_add_export_import_delete_menu');
?>
