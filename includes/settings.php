<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
  # Plugin Einstellungen
  1. Registriert eine Einstellungsseite für das Plugin.
  2. Ermöglicht die Konfiguration der Button-Farben im Backend.
*/

// Einstellungsseite zum Menü hinzufügen
function svl_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=videolektion', // Parent slug (unter Videolektionen)
        'Videokurs Einstellungen',         // Page title
        'Einstellungen',                   // Menu title
        'manage_options',                  // Capability
        'svl-settings',                    // Menu slug
        'svl_settings_page_html'           // Callback function
    );
}
add_action('admin_menu', 'svl_add_settings_page');

// Einstellungen registrieren
function svl_settings_init() {
    // Register settings for lesson completion buttons
    register_setting('svl_settings_group', 'svl_complete_button_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color', // WordPress built-in sanitizer for hex colors
        'default' => '#28a745',
    ));
    register_setting('svl_settings_group', 'svl_uncomplete_button_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ffc107',
    ));

    // Register setting for course overview button
    register_setting('svl_settings_group', 'svl_overview_button_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#007bff', // Default blue color
    ));


    // Register a new section in the "svl_settings_page" page
    add_settings_section(
        'svl_button_colors_section',         // ID
        'Button Farben',                     // Title
        'svl_button_colors_section_callback', // Callback
        'svl_settings_page'                  // Page
    );

    // Register fields for lesson completion buttons
    add_settings_field(
        'svl_complete_button_color',         // ID
        'Farbe "Abschließen" Button (Lektion)', // Title
        'svl_complete_button_color_callback', // Callback
        'svl_settings_page',                 // Page
        'svl_button_colors_section'          // Section
    );

    add_settings_field(
        'svl_uncomplete_button_color',       // ID
        'Farbe "Nochmals ansehen" Button (Lektion)', // Title
        'svl_uncomplete_button_color_callback', // Callback
        'svl_settings_page',                 // Page
        'svl_button_colors_section'          // Section
    );

    // Register field for course overview button
    add_settings_field(
        'svl_overview_button_color',         // ID
        'Farbe "Kurs starten" Button (Übersicht)', // Title
        'svl_overview_button_color_callback', // Callback
        'svl_settings_page',                 // Page
        'svl_button_colors_section'          // Section
    );
}
add_action('admin_init', 'svl_settings_init');

// Section callback function
function svl_button_colors_section_callback() {
    echo '<p>Stellen Sie die Farben für die verschiedenen Buttons des Plugins ein.</p>';
}

// Field callback functions
function svl_complete_button_color_callback() {
    $color = get_option('svl_complete_button_color', '#28a745'); // Standardfarbe als Fallback
    echo '<input type="text" name="svl_complete_button_color" value="' . esc_attr($color) . '" class="svl-color-picker" data-default-color="#28a745" />';
}

function svl_uncomplete_button_color_callback() {
    $color = get_option('svl_uncomplete_button_color', '#ffc107'); // Standardfarbe als Fallback
    echo '<input type="text" name="svl_uncomplete_button_color" value="' . esc_attr($color) . '" class="svl-color-picker" data-default-color="#ffc107" />';
}

function svl_overview_button_color_callback() {
    $color = get_option('svl_overview_button_color', '#007bff'); // Standardfarbe als Fallback
    echo '<input type="text" name="svl_overview_button_color" value="' . esc_attr($color) . '" class="svl-color-picker" data-default-color="#007bff" />';
}


// Einstellungsseite HTML
function svl_settings_page_html() {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the form
            settings_fields('svl_settings_group');
            // output setting sections and their fields
            do_settings_sections('svl_settings_page');
            // output save settings button
            submit_button('Einstellungen speichern');
            ?>
        </form>
    </div>
    <?php
}

// Skript für den Color Picker im Backend laden
function svl_load_color_picker_script($hook_suffix) {
    // Nur auf der Einstellungsseite laden
    if ('videolektion_page_svl-settings' !== $hook_suffix) {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_script('svl-color-picker-init', plugin_dir_url(__FILE__) . '../js/color-picker-init.js', array('wp-color-picker'), null, true);
}
add_action('admin_enqueue_scripts', 'svl_load_color_picker_script');

// JavaScript-Datei für den Color Picker Initialisierung
// Diese Datei existiert bereits im js-Ordner
?>
