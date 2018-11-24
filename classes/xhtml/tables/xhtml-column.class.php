<?php
require_once('xhtml/xhtml-element.class.php');

class XhtmlColumn extends XhtmlElement 
{
	/**
	 * A column in an XHTML table
	 *
	 * @return XhtmlColumn
	 */
	public function __construct()
	{
		parent::__construct('col');
		$this->SetEmpty(true);
	}
}
?>