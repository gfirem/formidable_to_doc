<?php
/**
*  Class SpanStyleNode
* 
*  @version 0.1
*  @author gfirem
*  @package vsword.node
*/
class SpanStyleNode extends Node implements INodeStyle {
	protected $color;

	/**
	 * @param int $size enum px
	 * @param int $cSize enum px
	 */
	public function __construct($color = NULL) {
		if(!is_null($color)){
			$this->color = $color;
		}
	}

	public function getWord() {
		return !is_null($this->color) ? '<w:color w:val="'.$this->color.'"/>' : '';
	}
}