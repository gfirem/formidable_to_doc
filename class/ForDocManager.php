<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ForDocManager {
	protected $loader;

	protected $plugin_slug;
	private static $plugin_short = 'ForToDoc';

	protected $version;

	public function __construct() {

		$this->plugin_slug = 'formidable-to-document';
		$this->version     = '1.0';

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	static function getShort(){
		return self::$plugin_short;
	}

	private function load_dependencies() {

		require_once plugin_dir_path( __FILE__ ) . 'ForDocLoader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/ForDocBase.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/ForDocAdmin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/ForDocTemplateProcessor.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/ForDocMerge.php';

		$this->loader = new ForDocLoader();
	}

	private function define_admin_hooks() {

		$admin = new ForDocAdmin( $this->get_version(), $this->plugin_slug );
		$merge = new ForDocMerge( $this->get_version(), $this->plugin_slug );

		$this->loader->add_action( 'wp_ajax_delete_template_file', $admin, 'on' . self::getShort() . 'DeleteAjax' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_' . self::getShort() . '_js' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_' . self::getShort() . '_style' );
		$this->loader->add_action( 'admin_head', $admin, 'admin_' . self::getShort() . '_style' );
		$this->loader->add_action( 'admin_menu', $admin, 'add' . self::getShort() . 'AdminMenu' );
		$this->loader->add_action( 'frm_registered_form_actions', $admin, 'add' . self::getShort() . 'FormidableAction' );
		$this->loader->add_action( 'wp_ajax_upload_template_file', $admin, 'on' . self::getShort() . 'UploadAjax' );

		$this->loader->add_action( 'frm_trigger_formidable_to_document_action', $merge, 'on' . self::getShort() . 'Action', 10, 4 );
		$this->loader->add_action( 'frm_trigger_formidable_to_document_create_action', $merge, 'on' . self::getShort() . 'CreateAction', 10, 3 );
		$this->loader->add_action( 'frm_trigger_formidable_to_document_update_action', $merge, 'on' . self::getShort() . 'UpdateAction', 10, 3 );
		$this->loader->add_action( 'frm_trigger_formidable_to_document_delete_action', $merge, 'on' . self::getShort() . 'DeleteAction', 10, 3 );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_version() {
		return $this->version;
	}

	/**
	 * Translate string to main Domain
	 *
	 * @param $str
	 *
	 * @return string|void
	 */
	public static function t( $str ) {
		return __( $str, 'formidable_document-locale' );
	}

	/**
	 * Get WP option for date format
	 *
	 * @return mixed|void
	 */
	public static function getDateFormat(){
		return get_option('date_format');
	}

	/**
	 * Return array of unused filed as array
	 *
	 * @return array
	 */
	public static function getUnUsedFields(){
		return array('divider', 'end_divider', 'file');
	}
}