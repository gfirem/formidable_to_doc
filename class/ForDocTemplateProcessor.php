<?php

//if ( ! defined( 'ABSPATH' ) ) {
//	exit;
//}
namespace PhpOffice\PhpWord;

use DOMElement;
use DOMNameSpaceNode;
use DOMXPath;
use HTMLUtils;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Shared\String;
use SimpleXMLElement;
use ZipArchive;

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

	private function getDocumentStart() {
		return '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" >';
	}

	private function getDocumentEnd() {
		return '</w:document>';
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
		$escapedSearch = preg_quote( $search, '/' );
		preg_match( '/\#\{(' . $escapedSearch . ')}|\$\{(' . $escapedSearch . ')}/i', $documentPartXML, $replaceType );

		if ( count( $replaceType ) > 0 ) {
			if ( ! empty( $replaceType[2] ) ) {
				return parent::setValueForPart( $documentPartXML, $search, $replace, $limit );
			} else {
				if ( ! String::isUTF8( $replace ) ) {
					$replace = utf8_encode( $replace );
				}

				if ( substr( $search, 0, 2 ) !== '#{' && substr( $search, - 1 ) !== '}' ) {
					$search = '#{' . $search . '}';
				}

				$startKeyPosition = strpos( $documentPartXML, $search );
				if ( $startKeyPosition === false ) {
					return $documentPartXML;
				}

				$element = simplexml_load_string( $documentPartXML );
				$root    = $element->xpath( "//w:t" );

				foreach ( $root as $item ) {
					$itemOriginalString = (string) $item;

					$startKeyPosition = strpos( $itemOriginalString, $search );
					if ( $startKeyPosition !== false ) {
						$parentDom = dom_import_simplexml( $item->xpath( "../.." )[0] );
						$parentDom->parentNode->replaceChild( $parentDom->ownerDocument->createTextNode( $replace ), $parentDom );
//						libxml_use_internal_errors( true );
//
//						$xml = simplexml_load_string( $replace, 'SimpleXmlElement', LIBXML_NOERROR + LIBXML_ERR_FATAL + LIBXML_ERR_NONE );
//
//						$replaceElements = dom_import_simplexml( $xml );
//
//						if ( $replaceElements == null ) {
//							libxml_clear_errors();
//
//						} else {
//							foreach ( $replaceElements->childNodes as $replaceChild ) {
//								if ( $replaceChild->hasChildNodes() ) {
//									$childs = $replaceChild->childNodes;
//									foreach ( $childs as $child ) {
//										$this->simplexml_import_xml( $item->xpath( "../.." )[0], $replace );
//									}
//								}
//							}
//							//Remove old
//							$itemDom = dom_import_simplexml( $item );
//							$parentDom->removeChild( $itemDom );
//						}
					}
				}


//		$endKeyPosition   = $startKeyPosition + strlen( $search );
//		$startTagPosition = HTMLUtils::findNearOpenTag( $documentPartXML, $startKeyPosition, '<w:r ' );
//		$endTagPosition   = HTMLUtils::findNearCloseTag( $documentPartXML, $endKeyPosition, '</w:r>' ) + strlen( '</w:r>' );
//		$startPart        = HTMLUtils::getSlice( $documentPartXML, $startTagPosition, HTMLUtils::findNearOpenTag( $documentPartXML, $startKeyPosition, '>' ) ) . '>';
//		$endPart          = '</' . HTMLUtils::getSlice( $documentPartXML, HTMLUtils::findNearCloseTag( $documentPartXML, $endKeyPosition, '</' ) + strlen( '</' ), $endTagPosition );
//		$replace          = str_replace( array( '\r\n', '\n\r', '\n', '\r' ), '', $endPart . $startPart . $replace . $endPart . $startPart );

				return htmlspecialchars_decode($element->asXML());
			}
		}

		return $documentPartXML;
	}


	protected function separatePatterns( $xmlSource ) {
		$element = simplexml_load_string( $xmlSource );
		$root    = $element->xpath( "//w:t" );

		foreach ( $root as $item ) {
			$itemOriginalString = (string) $item;
			$replacementKeys    = $this->getVariablesForPart( $itemOriginalString );
			if ( ! empty( $replacementKeys ) ) {
				//Get part of string in array
				$slices = $this->getAllSlices( $itemOriginalString, $replacementKeys );
				if ( count( $slices ) > 1 ) {
					foreach ( $slices as $slice ) {
						$emptyWR = $this->setXmlValue( $item->xpath( ".." )[0], "w:t", $slice, true );
						$this->simplexml_import_xml( $item->xpath( "../.." )[0], $emptyWR );
					}
					$parentDom = dom_import_simplexml( $item->xpath( ".." )[0] );
					$itemDom   = dom_import_simplexml( $item );
					$parentDom->removeChild( $itemDom );
				}
			}
		}

		return $element->asXML();
	}

	/**
	 * Set value for node, from parent.
	 * Ej: setXmlValue($item->xpath( ".." )[0], "w:t", "") set the w:t from it's parent.
	 *
	 * @param SimpleXMLElement $parent
	 * @param $target
	 * @param $value
	 *
	 * @param bool $toString
	 *
	 * @return mixed
	 */
	public function setXmlValue( SimpleXMLElement $parent, $target, $value, $toString = false ) {
		$emptyWR = dom_import_simplexml( $parent->xpath( $target )[0] );
		$emptyWR->setAttribute( "xml:space", "preserve" );
		$emptyWR->nodeValue = $value;
		if ( $toString ) {
			return $parent->asXML();
		} else {
			return $parent;
		}
	}

	/**
	 * From url http://stackoverflow.com/a/14831397/4016011
	 *
	 * Insert XML into a SimpleXMLElement
	 *
	 * @param SimpleXMLElement $parent
	 * @param string $xml
	 * @param bool $before
	 *
	 * @return bool XML string added
	 */
	protected function simplexml_import_xml( SimpleXMLElement $parent, $xml, $before = false ) {
		$xml = (string) $xml;

		// check if there is something to add
		if ( $nodata = ! strlen( $xml ) or $parent[0] == null ) {
			return $nodata;
		}
		$fragmentResult = null;
		// add the XML
		$node      = dom_import_simplexml( $parent );
		$fragment  = $node->ownerDocument->createDocumentFragment();
		$parentDom = dom_import_simplexml( $parent->xpath( ".." )[0] );
		$xml       = $this->wrapFragment( $parentDom, $xml );
		$fragment->appendXML( $xml );
		foreach ( $fragment->childNodes as $item ) {
			if ( $item->hasChildNodes() ) {
				$childs = $item->childNodes;
				foreach ( $childs as $i ) {
					if ( $before ) {
						$fragmentResult = $node->parentNode->insertBefore( $fragmentResult, $node );
					} else {
						$fragmentResult = $node->appendChild( $i );
					}
				}
			}
		}

		return $fragmentResult == null;
	}

	/**
	 * From url http://www.scriptscoop2.com/t/dac211279689/xml-how-to-use-global-namespace-definitions-in-a-fragment-creation.html
	 *
	 * @param $namespaces
	 * @param $xml
	 *
	 * @return string
	 */
	private function wrapFragment( $namespaces, $xml ) {
		if ( $namespaces instanceOf DOMElement ) {
			$xpath      = new DOMXpath( $namespaces->ownerDocument );
			$namespaces = $xpath->evaluate( 'namespace::*', $namespaces );
		}
		$result = '<fragment';
		foreach ( $namespaces as $key => $value ) {
			if ( $value instanceOf DOMNamespaceNode ) {
				$prefix = $value->localName;
				$xmlns  = $value->nodeValue;
			} else {
				$prefix = $key == '#default' ? '' : $key;
				$xmlns  = $value;
			}
			$result .= ' ' . htmlspecialchars( empty( $prefix ) ? 'xmlns' : 'xmlns:' . $prefix );
			$result .= '="' . htmlspecialchars( $xmlns ) . '"';
		}

		return $result . '>' . $xml . '</fragment>';
	}

	/**
	 * Return array with all part of text divided by patterns
	 *
	 * @param $text
	 * @param $replacementKeys
	 *
	 * @return array
	 */
	public function getAllSlices( $text, $replacementKeys ) {
		$slices = array();
		if ( count( $replacementKeys ) == 1 && strpos( $text, $replacementKeys[0] ) == 0 && ( strpos( $text, $replacementKeys[0] ) + strlen( $replacementKeys[0] ) ) == strlen( $text ) ) {
			$slices[] = $text;
		} else {
			foreach ( $replacementKeys as $key ) {
				$keySubtract      = array();
				$keySubtract[]    = $key;
				$replacementKeys  = array_diff( $replacementKeys, $keySubtract );
				$startKeyPosition = strpos( $text, $key );
				$endKeyPosition   = $startKeyPosition + strlen( $key );
				$slices[]         = HTMLUtils::getSlice( $text, 0, $startKeyPosition );
				$slices[]         = $key;
				$text             = HTMLUtils::getSlice( $text, $endKeyPosition );
				if ( ! $text ) {
					array_merge( $slices, $this->getAllSlices( $text, $replacementKeys ) );
				}
			}
		}

		return $slices;
	}


	/**
	 * Get all patterns in document
	 *
	 * @return array
	 */
	public function getAllPatterns() {
		$header   = array();
		$document = array();
		$footer   = array();
		foreach ( $this->tempDocumentHeaders as $index => $headerXML ) {
			$header = $this->getVariablesForPart( $this->tempDocumentHeaders[ $index ] );
		}

		$document = $this->getVariablesForPart( $this->tempDocumentMainPart );

		foreach ( $this->tempDocumentFooters as $index => $headerXML ) {
			$footer = $this->getVariablesForPart( $this->tempDocumentFooters[ $index ] );
		}

		return array_merge( $header, $document, $footer );
	}

	/**
	 * @inheritdoc
	 */
	protected function getVariablesForPart( $documentPartXML ) {
//		preg_match_all( '/\$\{(.*?)}/i', $documentPartXML, $matches );
		preg_match_all( '/\#\{(.*?)}|\$\{(.*?)}/i', $documentPartXML, $matches1 );

		return $matches1[0];
	}

	public function preProcessPatterns() {
		foreach ( $this->tempDocumentHeaders as $index => $headerXML ) {
			$this->tempDocumentHeaders[ $index ] = $this->separatePatterns( $this->tempDocumentHeaders[ $index ] );
		}

		$this->tempDocumentMainPart = $this->separatePatterns( $this->tempDocumentMainPart );

		foreach ( $this->tempDocumentFooters as $index => $headerXML ) {
			$this->tempDocumentFooters[ $index ] = $this->separatePatterns( $this->tempDocumentFooters[ $index ] );
		}
	}
}