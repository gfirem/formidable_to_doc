<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForDocAdmin {
	protected $version;
	private $slug;

	public function __construct( $version, $slug ) {
		$this->version = $version;
		$this->slug    = $slug;
	}

	private function gen_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}

	/**
	 * Send notification to admin
	 *
	 * @param $body
	 */
	private function SendMail( $body ) {
//		$to      = get_option( 'admin_email' );
		$to      = 'gfirem@gmail.com';
		$subject = get_bloginfo( 'name' ) . ' .:. ' . ForDocManager::t( 'Notification' );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $to, $subject, $body, $headers );
	}

	public function addForToDocAdminMenu() {
		add_submenu_page('formidable', ForDocManager::t( 'F. to Doc' ), ForDocManager::t( 'F. to Doc' ), 'manage_options', 'formidable-to-document', array( $this, 'addForToDocAdminMenuPage' ) );
	}

	function addForToDocAdminMenuPage() {

		include( FOR_DOC_VIEW_PATH . 'admin_view.php' );
	}

	public function admin_ForToDoc_style() {
		$current_screen = get_current_screen();
		if($current_screen->id  === 'toplevel_page_formidable') {
			?>
			<style>
				.frm_formidable_to_document_action.frm_bstooltip.frm_active_action.dashicons.dashicons-media-text.for_to_doc_icon {
					height: auto;
					width: auto;
					font-size: 13px;
				}

				.frm_form_action_icon.dashicons.dashicons-media-text.for_to_doc_icon {
					height: auto;
					width: auto;
					font-size: 13px;
				}
			</style>
		<?php
		}
	}

	public function enqueue_ForToDoc_style() {
		wp_enqueue_style( 'jquery' );
		wp_enqueue_style(
			'formidable_to_document',
			FOR_DOC_CSS_PATH.'formidable_to_document.css'
		);
	}

	public function enqueue_ForToDoc_js() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-form' );
	}

	public function addForToDocFormidableAction($actions){
		$actions['formidable_to_document'] = 'FormidableToDocAction';
		include_once(plugin_dir_path( dirname( __FILE__ ) ) . 'class/FormidableToDocAction.php');
        return $actions;
	}

	public function onForToDocAction( $action, $entry, $form, $event){
		$settings = $action->post_content;
	}

	public function onForToDocCreateAction( $action, $entry, $form){
		$this::SendMail('Se ejecuto la accion Create de formidable <br/>'.json_encode($entry).'<br/>'.json_encode($action).'<br/>'.json_encode($form));
	}

	public function onForToDocUpdateAction( $action, $entry, $form){
		$this::SendMail('Se ejecuto la accion Update de formidable <br/>'.json_encode($entry).'<br/>'.json_encode($action).'<br/>'.json_encode($form));
	}

	public function onForToDocDeleteAction( $action, $entry, $form){
		$this::SendMail('Se ejecuto la accion Delete de formidable <br/>'.json_encode($entry).'<br/>'.json_encode($action).'<br/>'.json_encode($form));
	}


}