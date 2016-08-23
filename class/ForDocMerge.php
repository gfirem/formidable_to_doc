<?php

use PhpOffice\PhpWord\Autoloader;
use PhpOffice\PhpWord\ForDocTemplateProcessor;
use PhpOffice\PhpWord\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForDocMerge extends ForDocBase {
	private $slug;
	protected $version;
	protected $upload_dir;
	protected $tmpDir;

	public function __construct( $version, $slug ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/ForDocTemporalDocument.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/ForToDocUpload.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/HtmlUtils.php';
		Autoloader::register();

		$this->upload_dir = wp_upload_dir();
		$this->version    = $version;
		$this->slug       = $slug;
		$this->tmpDir     = Settings::getTempDir() . '/FTD/' . time();
	}

	/**
	 * Send notification to admin
	 *
	 * @param $body
	 * @param string $to
	 * @param $attachments
	 */
	static function SendMail( $body, $to = '', $attachments ) {
		if ( $to == '' ) {
//		    $to      = get_option( 'admin_email' );
			$to = 'gfirem@gmail.com';
		}
		$subject = get_bloginfo( 'name' ) . ' .:. ' . ForDocManager::t( 'Notification' );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( empty( $attachments ) ) {
			$attachments = array();
		}
		wp_mail( $to, $subject, $body, $headers, $attachments );
	}

	public function onForToDocAction( $action, $entry, $form, $event ) {
		$settings = $action->post_content;
	}

	public function onForToDocCreateAction( $action, $entry, $form ) {
		$this->processAction( $action, $entry );
	}

	public function onForToDocUpdateAction( $action, $entry, $form ) {
		$this->processAction( $action, $entry );
	}

	public function onForToDocDeleteAction( $action, $entry, $form ) {
//		$this::SendMail( 'Se ejecuto la accion Delete de formidable <br/>' . json_encode( $entry ) . '<br/>' . json_encode( $action ) . '<br/>' . json_encode( $form ) );
	}

	/**
	 * Process external document for action
	 *
	 * @param $action_key
	 * @param $key
	 * @param $single_meta
	 * @param $action_content
	 * @param $actionContentResult
	 * @param $document
	 *
	 * @return string
	 */
	public function processExternalDocument( $action_key, $key, $single_meta, $action_content, $actionContentResult, $document ) {
		if ( $action_key == $key . '_' . $single_meta ) {
			$tempDocument = new ForDocTemporalDocument( $action_content, $this->tmpDir );
			if ( $tempDocument->getBodyString() != false ) {
				$actionContentResult .= $tempDocument->mergeFiles( $document );
			}
		}

		return $actionContentResult;
	}

	/**
	 * Process action
	 *
	 * @param $action
	 * @param $entry
	 *
	 * @throws Exception
	 */
	public function processAction( $action, $entry ) {
		$file_id = $action->post_content['template_attachment_id'];
		if ( empty( $file_id ) ) {
			return;
		}
		$template_path = get_attached_file( $file_id );
		if ( empty( $template_path ) ) {
			return;
		}

		//Create temporary folder
		if ( ! mkdir( $this->tmpDir ) ) {
			throw new Exception( 'Could not create temporary directory.' );
		}

		$document = new ForDocTemplateProcessor( $template_path );

		$document->setValue( 'form_reference', htmlspecialchars( $entry->id ) );
		$generationTimeFormat = ForDocManager::getDateFormat();
		$generationTime       = date( $generationTimeFormat, time() );
		$document->setValue( 'generation_time', $generationTime );

		foreach ( $entry->metas as $id => $meta ) {

			$type = $this->get_field_type( $id );
			$key  = $this->get_key_from_id_field( $id );
			if ( ! is_array( $meta ) ) {
				$meta = trim( htmlentities( $meta, ENT_QUOTES, "UTF-8" ) );
			}

			if ( ! in_array( $type, ForDocManager::getUnUsedFields() ) ) {
				switch ( $type ) {
					case 'textarea':
						if ( ! empty( $meta ) ) {
							if ( $this->isHtml( $meta ) ) {
								$tempDocument = $tempDocument = new ForDocTemporalDocument( $meta, $this->tmpDir );
								if ( $tempDocument->getBodyString() != false ) {
									$actionContentResult = $tempDocument->mergeFiles( $document );
									$document->setValue( $key, $actionContentResult );
								}
							} else {
								$document->setValue( $key, $meta );
							}
						}
						break;
					case 'date':
						if ( ! empty( $meta ) ) {
							$document->setValue( $key, htmlspecialchars( $meta ) );
							$document->setValue( $key . '_date', htmlspecialchars( date( "d", strtotime( $meta ) ) ) );
							setlocale( LC_TIME, get_option( 'WPLANG', 'es_ES' ) );
							$document->setValue( $key . '_month', htmlspecialchars( strftime( "%B", strtotime( $meta ) ) ) );
							$document->setValue( $key . '_year', htmlspecialchars( date( "Y", strtotime( $meta ) ) ) );
						}
						break;
					case 'radio': //En este se puede seleccionar varios
					case 'checkbox': // En este se pueden seleccionar varios
					case 'select'://En los select solo se puede seleccionar uno
					case 'scale': //En este no idea
						$actionContentResult = '';
						foreach ( $action->post_content as $action_key => $action_content ) {
							if ( is_array( $meta ) ) {
								foreach ( $meta as $single_meta ) {
									if ( $action_key == $key . '_' . $single_meta ) {
										if ( ! empty( $action_content ) ) {
											$action_content = do_shortcode( $action_content, true );
											$action_content = html_entity_decode( $action_content );
											$tempDocument   = new ForDocTemporalDocument( $action_content, $this->tmpDir );
											if ( $tempDocument->getBodyString() != false ) {
												$actionContentResult .= $tempDocument->mergeFiles( $document );
											}
										} else {
											//TODO ver 1.0 convert $actionContentResult in array to separate content by coma ,
											$actionContentResult .= do_shortcode( $single_meta, true );
										}
									}
								}
							} else {
								if ( $action_key == $key . '_' . $meta ) {
									if ( ! empty( $action_content ) ) {
										$action_content = do_shortcode( $action_content, true );
										$action_content = html_entity_decode( $action_content );
										$tempDocument   = new ForDocTemporalDocument( $action_content, $this->tmpDir );
										if ( $tempDocument->getBodyString() != false ) {
											$actionContentResult = $tempDocument->mergeFiles( $document );
										}
									} else {
										$actionContentResult = do_shortcode( $meta, true );
									}
									if ( $type == 'select' || $type == 'radio' ) {
										break 1;
									}
								}
							}
						}

						$document->setValue( $key, $actionContentResult );
						break;
					default:
						$document->setValue( $key, htmlspecialchars( $meta ) );
						break;
				}
			}
		}
		$fileName     = $action->post_title . '_' . $generationTime . '.docx';
		$fullFilePath = $this->upload_dir['path'] . '/' . $fileName;
		$document->saveAs( $fullFilePath );
		$sendByEmail = false;
		if ( $sendByEmail ) {
			self::SendMail( "File uploaded by Formidable to Documents", 'gfirem@gmail.com', $fullFilePath );
		}
		$uploadToDrive = false;
		if ( $uploadToDrive ) {
			$upload      = new ForToDocUpload();
			$is_uploaded = $upload->upload( $fileName, "File uploaded by Formidable to Documents", $fullFilePath );
		}
		ForDocTemplateProcessor::deleteDir( $this->tmpDir );
	}

	private function isHtml( $string ) {
		return preg_match( "/<[^<]+>/", $string, $m ) != 0;
	}


}