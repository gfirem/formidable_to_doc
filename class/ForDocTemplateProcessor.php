<?php

//if ( ! defined( 'ABSPATH' ) ) {
//	exit;
//}
namespace PhpOffice\PhpWord;

use HTMLUtils;
use PhpOffice\PhpWord\Shared\String;

class ForDocTemplateProcessor extends TemplateProcessor {

	/**
	 * Images as array to insert into the temporary document.
	 *
	 * @var Array
	 */
	protected $tempDocumentImages = array();

	/**
	 * DocumentType into the temporary document.
	 *
	 * @var string
	 */
	protected $tempDocumentTypes;

	/**
	 * Content of main document relation (in XML format) of the temporary document.
	 *
	 * @var string
	 */
	protected $tempDocumentRelation;

	/**
	 * Internal field for relations as array
	 *
	 * @var array
	 */
	private $relationArray = array();

	/**
	 * @inheritdoc
	 */
	public function __construct( $documentTemplate ) {
		parent::__construct( $documentTemplate );
		$this->tempDocumentTypes    = $this->fixBrokenMacros( $this->zipClass->getFromName( '[Content_Types].xml' ) );
		$this->tempDocumentRelation = $this->fixBrokenMacros( $this->zipClass->getFromName( 'word/_rels/document.xml.rels' ) );
	}

	/**
	 * @param $tempDocumentImages
	 */
	public function setTempDocumentImages( $tempDocumentImages ) {
		$this->tempDocumentImages = $tempDocumentImages;
	}

	/**
	 * @return array
	 */
	public function getTempDocumentImages() {
		return $this->tempDocumentImages;
	}

	/**
	 * @return string
	 */
	public function getTempDocumentRelation() {
		return $this->tempDocumentRelation;
	}

	/**
	 * @param string $tempDocumentRelation
	 */
	public function setTempDocumentRelation( $tempDocumentRelation ) {
		$this->tempDocumentRelation = $tempDocumentRelation;
	}

	/**
	 * @return string
	 */
	public function getTempDocumentTypes() {
		return $this->tempDocumentTypes;
	}

	/**
	 * @return array
	 */
	public function getRelationArray() {
		return $this->relationArray;
	}

	/**
	 * @param string $tempDocumentTypes
	 */
	public function setTempDocumentTypes( $tempDocumentTypes ) {
		$this->tempDocumentTypes = $tempDocumentTypes;
	}

	public function save() {
		if ( count( $this->tempDocumentImages ) > 0 ) {
			foreach ( $this->tempDocumentImages as $img ) {
				if ( file_exists( $img[3] ) ) {
					$this->zipClass->addFile( $img[3], 'word/' . $img[0] );
				}
			}
		}
		$this->zipClass->addFromString( 'word/_rels/document.xml.rels', $this->tempDocumentRelation );
		$this->zipClass->addFromString( '[Content_Types].xml', $this->tempDocumentTypes );

		return parent::save();
	}

	/**
	 * @inheritdoc
	 */
	public function saveAs( $fileName ) {
		$tempFileName = $this->save();

		if ( file_exists( $fileName ) ) {
			unlink( $fileName );
		}

		copy( $tempFileName, $fileName );
		unlink( $tempFileName );
	}

	/**
	 * Transform xml Relations as Array
	 *
	 * @return array|bool
	 */
	private function getRelationsArray() {
		if ( ! empty( $this->tempDocumentRelation ) ) {
			$relationsXML        = HTMLUtils::getInternalString( $this->tempDocumentRelation, '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">', '</Relationships>' );
			$this->relationArray = HTMLUtils::parse( $relationsXML );
		}
	}

	/**
	 * Count relations base 1
	 *
	 * @return int
	 */
	public function getRelationsCount() {
		$this->getRelationsArray();

		return count( $this->relationArray );
	}

	/**
	 * Get next rId available
	 *
	 * @return string
	 */
	public function getNextRid() {
		$ids = array();
		$this->getRelationsArray();
		foreach ( $this->relationArray as $relation ) {
			$ids[] = $relation['att']['Id'];
		}
		array_walk( $ids, array( $this, 'getStringID' ) );
		$max   = max( $ids );
		$newID = $max + 1;

		return "rId" . $newID;
	}

	/**
	 * Extract numeric id from rid string
	 *
	 * @param $strID
	 */
	private function getStringID( &$strID ) {
		$strID = str_replace( "rId", '', $strID );
	}


	/**
	 * Extract images form document
	 *
	 * @param $documentPath
	 * @param $entries
	 * @param $tempPath
	 *
	 * @throws Exception
	 */
	static function extractImages( $documentPath, $entries, $tempPath ) {
		$object = new ZipArchive();
		$object->open( $documentPath, ZipArchive::CM_STORE );
		$object->extractTo( $tempPath, $entries );
		if ( false === $object->close() ) {
			throw new Exception( 'Could not close zip file.' );
		}
	}

	/**
	 * Delete directory
	 *
	 * @param string $dir
	 */
	static function deleteDir( $dir ) {
		foreach ( scandir( $dir ) as $file ) {
			if ( $file === '.' || $file === '..' ) {
				continue;
			} elseif ( is_file( $dir . "/" . $file ) ) {
				unlink( $dir . "/" . $file );
			} elseif ( is_dir( $dir . "/" . $file ) ) {
				self::deleteDir( $dir . "/" . $file );
			}
		}

		rmdir( $dir );
	}

	/**
	 * @inheritdoc
	 */
	public function setValue( $macro, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT ) {
		foreach ( $this->tempDocumentHeaders as $index => $headerXML ) {
			$this->tempDocumentHeaders[ $index ] = $this->setValueForPart( $this->tempDocumentHeaders[ $index ], $macro, $replace, $limit );
		}

		$this->tempDocumentMainPart = $this->setValueForPart( $this->tempDocumentMainPart, $macro, $replace, $limit );

		foreach ( $this->tempDocumentFooters as $index => $headerXML ) {
			$this->tempDocumentFooters[ $index ] = $this->setValueForPart( $this->tempDocumentFooters[ $index ], $macro, $replace, $limit );
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function setValueForPart( $documentPartXML, $search, $replace, $limit ) {

		if ( ! String::isUTF8( $replace ) ) {
			$replace = utf8_encode( $replace );
		}

		if ( substr( $search, 0, 2 ) !== '${' && substr( $search, - 1 ) !== '}' ) {
			$search = '${' . $search . '}';
		}

		$startKeyPosition = strpos( $documentPartXML, $search );
		if ( $startKeyPosition === false ) {
			return $documentPartXML;
		}
		$endKeyPosition   = $startKeyPosition + strlen( $search );
		$startTagPosition = HTMLUtils::findNearOpenTag( $documentPartXML, $startKeyPosition, '<w:r ' );
		$endTagPosition   = HTMLUtils::findNearCloseTag( $documentPartXML, $endKeyPosition, '</w:r>' ) + strlen( '</w:r>' );
		$startPart        = HTMLUtils::getSlice( $documentPartXML, $startTagPosition, HTMLUtils::findNearOpenTag( $documentPartXML, $startKeyPosition, '>' ) ) . '>';
		$endPart          = '</' . HTMLUtils::getSlice( $documentPartXML, HTMLUtils::findNearCloseTag( $documentPartXML, $endKeyPosition, '</' ) + strlen( '</' ), $endTagPosition );
		$replace          = str_replace( array( '\r\n', '\n\r', '\n', '\r' ), '', $endPart . $startPart . $replace . $endPart . $startPart );

		return str_replace( $search, $replace, $documentPartXML );
	}
}