<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForDocAdmin {
	protected $version;
	private $slug;
	private $googleClient;

	public function __construct( $version, $slug ) {
		$this->version      = $version;
		$this->slug         = $slug;
		$this->googleClient = new Google_Client();
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

	public function addForToDocAdminMenu() {
		add_submenu_page( 'formidable', ForDocManager::t( 'F. to Doc' ), ForDocManager::t( 'F. to Doc' ), 'manage_options', 'formidable-to-document', array( $this, 'addForToDocAdminMenuPage' ) );
	}

	function addForToDocAdminMenuPage() {
		$clientId        = get_option( 'ftd_client_id' );
		$clientSecret    = get_option( 'ftd_client_secret' );
		$clientReturnUrl = get_option( 'ftd_client_return_url' );
		$token           = get_option( 'ftd_refresh_token' );

		if ( isset( $_POST['ftd_action'] ) && ! empty( $_POST['ftd_action'] ) && $_POST['ftd_action'] == "save_data_configuration" ) {
			if ( isset( $_POST['client_id'] ) ) {
				update_option( 'ftd_client_id', $_POST['client_id'] );
				$clientId = $_POST['client_id'];
			}

			if ( isset( $_POST['client_secret'] ) ) {
				update_option( 'ftd_client_secret', $_POST['client_secret'] );
				$clientSecret = $_POST['client_secret'];
			}

			if ( isset( $_POST['client_return_url'] ) ) {
				update_option( 'ftd_client_return_url', $_POST['client_return_url'] );
				$clientReturnUrl = $_POST['client_return_url'];
			}
		}

		$connected = false;
		$authUrl   = '';
		if ( $clientId != false && $clientSecret != false && $clientReturnUrl != false ) {
			$this->googleClient->setApplicationName( ForDocManager::t( "Upload Word from Formidable" ) );
			$this->googleClient->setClientId( $clientId );
			$this->googleClient->setClientSecret( $clientSecret );
			$this->googleClient->setRedirectUri( $clientReturnUrl );
			$this->googleClient->setScopes( array( 'https://www.googleapis.com/auth/drive.file' ) );
			$this->googleClient->setAccessType( "offline" );
			$this->googleClient->setApprovalPrompt( 'force' );

			if ( isset( $_GET['code'] ) ) {
				$this->googleClient->authenticate( $_GET['code'] );
				$token = $this->googleClient->getAccessToken();
				update_option( 'ftd_refresh_token', $token['refresh_token'] );
				$this->googleClient->getAccessToken( $token["refreshToken"] );
				$redirect       = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
				$secondRedirect = filter_var( $redirect, FILTER_SANITIZE_URL ) . "?page=formidable-to-document#tab-data";
				header( "Location: $secondRedirect" );

				return;
			}

			if ( ! empty( $token ) ) {
				$this->googleClient->setAccessToken( $token );
			}

			if ( isset( $_REQUEST['logout'] ) ) {
				unset( $token );
				update_option( 'ftd_refresh_token', false );
				$this->googleClient->revokeToken();
			}

			$connected = $this->googleClient->getAccessToken() && ! empty( $token );
			if ( ! $connected ) {
				$authUrl = $this->googleClient->createAuthUrl();
			}
		}

		$status = true;
		include( '/../views/admin_view.php' );
	}

	public function admin_ForToDoc_style() {
		$current_screen = get_current_screen();
		if ( $current_screen->id === 'toplevel_page_formidable' ) {
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
			FOR_DOC_CSS_PATH . 'formidable_to_document.css'
		);
	}

	public function enqueue_ForToDoc_js() {
//		wp_enqueue_script( 'for_to_doc',  plugins_url('js/for_to_doc.js', __FILE__), array('jquery', 'jquery-form'));
		wp_enqueue_script( 'jquery' );
		wp_register_script( 'jquery.hashchange.min', FOR_DOC_JS_PATH . 'jquery.hashchange.min.js' );
		wp_register_script( 'jquery.easytabs.min', FOR_DOC_JS_PATH . 'jquery.easytabs.min.js' );
		wp_enqueue_script( 'jquery.hashchange.min' );
		wp_enqueue_script( 'jquery.easytabs.min' );
		wp_register_script( 'jquery.validate.min', FOR_DOC_JS_PATH . 'jquery.validate.min.js' );
		wp_enqueue_script( 'jquery.validate.min' );
		wp_register_script( 'messages_' . self::getCurrentLanguageCode(), FOR_DOC_JS_PATH . 'localization/messages_' . self::getCurrentLanguageCode() . '.js' );
		wp_enqueue_script( 'messages_' . self::getCurrentLanguageCode() );
	}

	static function getCurrentLanguageCode() {
		return substr( get_locale(), 0, 2 );
	}

	public function onForToDocDeleteAjax() {
		if ( ! isset( $_REQUEST['security_delete'] ) || ! wp_verify_nonce( $_REQUEST['security_delete'], 'delete_template_file_nonce' ) ) {
			return;
		}

		$response['message'] = wp_delete_attachment( $_REQUEST['template_attachment_file_id'] );

		echo json_encode( $response );
		die();
	}

	public function onForToDocUploadAjax() {
		if ( ! ( is_array( $_POST ) && is_array( $_FILES ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! isset( $_POST['security-upload'] ) || ! wp_verify_nonce( $_POST['security-upload'], 'upload_template_file_nonce' ) ) {
			return;
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$upload_overrides = array( 'test_form' => false );

		$response = array();

		foreach ( $_FILES as $file ) {
			$file_info  = wp_handle_upload( $file, $upload_overrides );
			$attachment = array(
				'guid'           => $file_info['url'],
				'post_mime_type' => $file_info['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_info['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			$attach_id  = wp_insert_attachment( $attachment, $file_info['file'] );

			$response['file_id']  = $attach_id;
			$response['file_url'] = $file_info['url'];
		}

		echo json_encode( $response );
		die();
	}

	public function addForToDocFormidableAction( $actions ) {
		$actions['formidable_to_document'] = 'FormidableToDocAction';
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'class/FormidableToDocAction.php' );

		return $actions;
	}
}