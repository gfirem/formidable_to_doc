<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ForDocManager {
	protected $loader;

	protected $plugin_slug;
	protected $plugin_short = 'ForToDoc';

	protected $version;

	public function __construct() {

		$this->plugin_slug = 'formidable-to-document';
		$this->version     = '1.0';

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	private function load_dependencies() {

		require_once plugin_dir_path( __FILE__ ) . 'ForDocLoader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/ForDocAdmin.php';

		$this->loader = new ForDocLoader();
	}

	private function define_admin_hooks() {

		$admin = new ForDocAdmin( $this->get_version(), $this->plugin_slug );

		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_' . $this->plugin_short . '_js' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_' . $this->plugin_short . '_style' );
		$this->loader->add_action( 'admin_head', $admin, 'admin_' . $this->plugin_short . '_style' );
		$this->loader->add_action( 'admin_menu', $admin, 'add' . $this->plugin_short . 'AdminMenu' );
		$this->loader->add_action( 'frm_registered_form_actions', $admin, 'add' . $this->plugin_short . 'FormidableAction' );
		$this->loader->add_action( 'frm_trigger_formidable_to_document_action', $admin, 'on' . $this->plugin_short . 'Action', 10, 4 );
		$this->loader->add_action( 'frm_trigger_formidable_to_document_create_action', $admin, 'on' . $this->plugin_short . 'CreateAction', 10, 3 );
		$this->loader->add_action( 'frm_trigger_formidable_to_document_update_action', $admin, 'on' . $this->plugin_short . 'UpdateAction', 10, 3 );
		$this->loader->add_action( 'frm_trigger_formidable_to_document_delete_action', $admin, 'on' . $this->plugin_short . 'DeleteAction', 10, 3 );
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
}