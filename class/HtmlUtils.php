<?php

/**
 * Class HTMLUtils
 */
class HTMLUtils {

	/**
	 * Detect if tag node is single
	 *
	 * @param $stringTag
	 *
	 * @return bool
	 */
	static function isSingleNode( $stringTag ) {
		return in_array( strtolower( $stringTag ), array( 'br', 'hr', 'meta', 'link', 'input', 'img', ) );
	}

	/**
	 * Parse tagged text like xml or xml
	 *
	 * @param $taggedText
	 *
	 * @return array
	 */
	static function parse( $taggedText ) {
		$taggedText   = htmlspecialchars_decode( $taggedText, ENT_QUOTES );
		$i            = 0;
		$target_count = 0;
		$length       = strlen( $taggedText );
		$target       = array();
		$open         = false;
		$end          = false;
		$content      = '';
		$eatAttr      = false;
		$stringTag    = '';
		$attributeStr = '';

		while ( $length > $i ) {
			$char = substr( $taggedText, $i ++, 1 );
			if ( $char == '<' ) {
				$content = '';
				$open    = true;
				$end     = false;
				if ( substr( $taggedText, $i, 1 ) == '/' ) {
					$end = true;
					$i ++;
				}
			} else if ( $open && $char == '>' ) {

				if ( $end ) { //close tag
					if ( ! empty( $content ) ) {
						$target[ $target_count ]['content'] = $content;
					}
				} else {
					if ( self::isSingleNode( $stringTag ) ) {
						$target[ $target_count ]['tag'] = $stringTag;
						$target[ $target_count ]['att'] = self::attributeStrToArray( $attributeStr );
					} else {
						$target[ $target_count ]['tag'] = $stringTag;
						$target[ $target_count ]['att'] = self::attributeStrToArray( $attributeStr );
					}
					$target_count ++;
				}

				$open         = false;
				$end          = false;
				$stringTag    = '';
				$eatAttr      = false;
				$attributeStr = '';

			} else if ( $open && ! $eatAttr && preg_match( '/[a-zA-Z0-9]/', $char ) ) {
				$stringTag .= $char;
			} else if ( $open ) {
				$eatAttr = true;
				$attributeStr .= $char;
			} else if ( ! $open ) {
				$content .= $char;
			}
		}

		return $target;

	}

	/**
	 * Get tag attributes as array
	 *
	 * @param $attributeStr
	 *
	 * @return array
	 */
	static function attributeStrToArray( $attributeStr ) {
		$attr         = array();
		$attributeStr = trim( $attributeStr );
		$l            = strlen( $attributeStr );
		$key          = '';
		$value        = '';
		$state        = 0;
		for ( $i = 0; $i < $l; $i ++ ) {
			$char = substr( $attributeStr, $i, 1 );
			if ( $state == 0 && $char == '=' ) {
				$state = 1;
			} else if ( $state == 1 && $char == '"' ) {
				$state = 2;
			} else if ( $state == 1 && $char == '\'' ) {
				$state = 3;
			} else if ( ( $state == 3 && $char == '\'' ) || ( $state == 2 && $char == '"' ) ) {
				$attr[ trim( $key ) ] = $value;
				$key                  = '';
				$value                = '';
				$state                = 0;
			} else if ( $state == 2 || $state == 3 ) {
				$value .= $char;
			} else if ( $state == 0 ) {
				$key .= $char;
			}
		}

		return $attr;
	}

	/**
	 * Get internal string using $start and $end as needle
	 *
	 * @param $haystack
	 * @param $start
	 * @param $end
	 *
	 * @return bool|string
	 */
	static function getInternalString( $haystack, $start, $end ) {
		if ( empty( $haystack ) ) {
			return false;
		}

		$str           = strstr( $haystack, $start, true );
		$startPosition = strlen( $str );
		$endPosition   = strlen( $haystack ) - strlen( $end ) - ( $startPosition + strlen( $start ) );
		$str           = substr( $haystack, $startPosition + strlen( $start ), $endPosition );

		return $str;
	}

	static function findNearOpenTag( $string, $offset, $tag ) {
		return strrpos( $string, $tag, ( ( strlen( $string ) - $offset ) * - 1 ) );
	}

	static function findNearCloseTag( $string, $offset, $tag ) {
		return strpos( $string, $tag, $offset );
	}

	static function getSlice( $source, $startPosition, $endPosition = 0 ) {
		if ( ! $endPosition ) {
			$endPosition = strlen( $source );
		}

		return substr( $source, $startPosition, ( $endPosition - $startPosition ) );
	}
}