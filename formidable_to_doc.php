<?php
/*
 * Plugin Name:       Formidable to Documets
 * Plugin URI:        https://github.com/gfirem/formidable_to_doc
 * Description:       Formidable action to create document based on template
 * Version:           1.0
 * Author:            Guillermo Figueroa Mesa
 * Author URI:        http://wwww.gfirem.com
 * Text Domain:       formidable_document-locale
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class/ForDocManager.php';
define( 'FOR_DOC_VIEW_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/views/' );
define( 'FOR_DOC_CSS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/css/' );
define( 'FOR_DOC_JS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/js/' );

function for_doc_boot_loader() {
	add_action( 'plugins_loaded', 'setForDocTranslation' );
	$manager = new ForDocManager();
	$manager->run();

}

/**
 * Add translation files
 */
function setForDocTranslation() {
	load_plugin_textdomain( 'formidable_document-locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

for_doc_boot_loader();