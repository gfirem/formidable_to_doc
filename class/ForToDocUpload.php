<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForToDocUpload {

	private $googleClient;

	private $clientId;

	private $clientSecret;

	private $clientReturnUrl;

	private $token;

	function __construct() {


		$this->googleClient    = new Google_Client();
		$this->clientId        = get_option( 'ftd_client_id' );
		$this->clientSecret    = get_option( 'ftd_client_secret' );
		$this->clientReturnUrl = get_option( 'ftd_client_return_url' );
		$this->token           = get_option( 'ftd_refresh_token' );


	}

	/**
	 * @param $fileName
	 * @param $fileDescription
	 * @param $filePath
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function upload( $fileName, $fileDescription, $filePath ) {
		$result = '';
		if ( ! file_exists( $filePath ) ) {
			throw new Exception( "File to upload not exists, contact administrator:" + get_option( 'admin_email' ) );
		}

		if ( empty( $fileName ) ) {
			throw new Exception( "File Name required" );
		}

		if ( empty( $fileDescription ) ) {
			throw new Exception( "File Description required" );
		}

		if ( $this->clientId != false && $this->clientSecret != false && $this->clientReturnUrl != false ) {
			$this->googleClient->setApplicationName( ForDocManager::t( "Upload Word from Formidable" ) );
			$this->googleClient->setClientId( $this->clientId );
			$this->googleClient->setClientSecret( $this->clientSecret );
			$this->googleClient->setRedirectUri( $this->clientReturnUrl );
			$this->googleClient->setScopes( array( 'https://www.googleapis.com/auth/drive.file' ) );
			$this->googleClient->setAccessType( "offline" );
			$this->googleClient->setApprovalPrompt( 'force' );

			if ( ! empty( $this->token ) ) {
				$this->googleClient->setAccessToken( $this->token );
			}

			$connected = $this->googleClient->getAccessToken() && ! empty( $this->token );
			if ( $connected ) {
				$this->googleClient->refreshToken( $this->token );
				$tokens = $this->googleClient->getAccessToken();
				$this->googleClient->setAccessToken( $tokens );

				$service = new Google_Service_Drive( $this->googleClient );

				$file = new Google_Service_Drive_DriveFile();
				$file->setName( $fileName );
				$file->setDescription( $fileDescription );
				$file->setMimeType( "application/vnd.openxmlformats-officedocument.wordprocessingml.document" );

				$fileUploaded = $service->files->create( $file, array(
					'data'       => file_get_contents( $filePath ),
					'mimeType'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'uploadType' => 'media'
				) );
				$fileId       = $fileUploaded->getId();

				if ( empty( $fileId ) ) {
					$result = false;
				} else {
					$result = $fileId;
				}

				return $result;
			}
			else{
				ForDocMerge::SendMail(ForDocManager::t('Formidable to Document plugins is not connected to google drive. Check plugins configuration.'));
			}
		}
	}


}