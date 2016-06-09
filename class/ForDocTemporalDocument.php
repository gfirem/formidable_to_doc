<?php

//if ( ! defined( 'ABSPATH' ) ) {
//	exit;
//}

class ForDocTemporalDocument {

	/**
	 * @var string
	 */
	protected $htmlContent;

	/**
	 * @var string
	 */
	protected $relations;

	/**
	 * @var string
	 */
	protected $bodyString;

	/**
	 * External temporary directory
	 *
	 * @var string
	 */
	protected $tmpDirPath;

	/**
	 * Current document path
	 *
	 * @var string
	 */
	protected $filePath;

	function __construct( $htmlContent, $filePath ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/vsword/VsWord.php';
		VsWord::autoLoad();

		$doc    = new VsWord();
		$parser = new HtmlParser( $doc );
		$parser->parse( $htmlContent );
		//Set internal properties
		$this->htmlContent = $htmlContent;
		$this->tmpDirPath  = $filePath;
		$this->relations   = $doc->getRels();
		$document          = $doc->getDocument();
		$documentString    = $document->getContent();
		$this->bodyString  = $this->getBodyRunAsString( $this->getBodyAsString( $documentString ) );
		//Create temporary filename
		$this->filePath = tempnam( $filePath, ForDocManager::getShort() );
		if ( false === $this->filePath ) {
			throw new CreateTemporaryFileException();
		}
		$doc->saveAs( $this->filePath );
	}

	/**
	 * @return string
	 */
	public function getRelations() {
		return $this->relations;
	}

	/**
	 * @return string
	 */
	public function getBodyString() {
		return $this->bodyString;
	}

	/**
	 * @return string
	 */
	public function getFilePath() {
		return $this->filePath;
	}


	/**
	 * Merge files image
	 *
	 * @param ForDocTemplateProcessor $document
	 *
	 * @return bool|string The bodyString
	 *
	 * @throws Exception
	 */
	public function mergeFiles( ForDocTemplateProcessor $document ) {
		$i                     = 0;
		$images_relations      = array();
		$images_for_extraction = array();

		foreach ( $this->relations->stack as $item ) {
			if ( $item['1'] == WordDirRelsDirDocumentStructureDocFile::TYPE_IMAGE ) {
				//Next available id in final document
				$finalID = $document->getNextRid();

				$images_relations[ $i ][0] = $item[0];
				$images_relations[ $i ][1] = $item[1];
				$images_relations[ $i ][2] = $finalID;
				$images_relations[ $i ][3] = $this->tmpDirPath . '/word/' . $item[0];
				$images_for_extraction[]   = 'word/' . $item[0];

				//Prepare relation string
				$relation = '<Relationship Id="' . $finalID . '" Type="' . $item[1] . '" Target="' . $item[0] . '"/>';

				$relationsDocument = $document->getTempDocumentRelation();
				//Set new string in final string relations
				$document->setTempDocumentRelation( str_replace( '</Relationships>', $relation . '</Relationships>', $relationsDocument ) );

				//Replace old id for new in body string
				if ( strpos( $this->bodyString, $item[2] ) !== false ) {
					$this->bodyString = str_replace( $item[2], $finalID, $this->bodyString );
				}

				$documentTypes = $document->getTempDocumentTypes();
				//Replace string document type un final document
				$this->replaceStringDocumentTypes( $document, $documentTypes );


			}
			$i ++;
		}
		//If have image then extract
		if ( count( $images_for_extraction ) > 0 ) {
			ForDocTemplateProcessor::extractImages( $this->filePath, $images_for_extraction, $this->tmpDirPath );
		}

		//Set images array for merge into final file
		if ( count( $images_for_extraction ) > 0 ) {
			$moreImages = array_merge( $document->getTempDocumentImages(), $images_relations );
			$document->setTempDocumentImages( $moreImages );
		}

		return $this->bodyString;
	}

	/**
	 * Get body of document as string without body tags
	 *
	 * @param $bodyContent
	 *
	 * @return bool|string
	 */
	private function getBodyAsString( $bodyContent ) {
		if ( empty( $bodyContent ) ) {
			return false;
		}
		$str = strstr( $bodyContent, '<w:body>' );
		if ( $str != false ) {
			$str = strstr( $str, '</w:body>', true );
		}
		if ( $str != false ) {
			$str = substr( $str, 8 );
		}

		return $str;
	}

	private function getBodyRunAsString( $bodyContent ) {
		if ( empty( $bodyContent ) ) {
			return false;
		}
		$str = strstr( $bodyContent, '<w:r>' );
		$str = HTMLUtils::getSlice( $str, 0, strrpos( $str, '</w:r>' ) + strlen( '</w:r>' ) );

		return $str;
	}

	/**
	 * Replace document types in final string types for jpg and png
	 *
	 * @param ForDocTemplateProcessor $document
	 * @param $documentTypes
	 */
	public function replaceStringDocumentTypes( ForDocTemplateProcessor $document, $documentTypes ) {
		if ( strpos( $documentTypes, 'jpg' ) === false ) {
			$document->setTempDocumentTypes( str_replace( '<Default Extension="xml" ContentType="application/xml"/>', '<Default Extension="xml" ContentType="application/xml"/><Default Extension="jpg" ContentType="application/octet-stream"/>', $document->getTempDocumentTypes() ) );
		}
		if ( strpos( $documentTypes, 'png' ) === false ) {
			$document->setTempDocumentTypes( str_replace( '<Default Extension="xml" ContentType="application/xml"/>', '<Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="application/octet-stream"/>', $document->getTempDocumentTypes() ) );
		}
	}


}