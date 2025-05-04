<?php
/*
Plugin Name: Lipa LIFE Videokurs
Description: Videolektionen mit Kursübersicht, Fortschritt und Navigation.
Version: 2.4
Author: www.lipalife.de
*/

// Sicherheitscheck
if ( ! defined( 'ABSPATH' ) ) exit;

// Inkludiere die einzelnen Modul-Dateien
require_once plugin_dir_path(__FILE__) . 'includes/cpt.php';
require_once plugin_dir_path(__FILE__) . 'includes/custom-css.php';
require_once plugin_dir_path(__FILE__) . 'includes/taxonomy.php';
require_once plugin_dir_path(__FILE__) . 'includes/video-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/completion.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/export-import.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php'; // Einstellungsdatei
