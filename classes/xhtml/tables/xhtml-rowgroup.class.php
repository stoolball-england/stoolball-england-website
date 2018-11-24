<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/tables/xhtml-row.class.php');

/**
 * A group of rows in an XHTML table
 *
 */
class XhtmlRowGroup extends XhtmlElement
{
	function __construct($b_is_header=false)
	{
		parent::__construct($b_is_header ? 'thead' : 'tbody');
	}
	
	/**
	 * Sets whether the row group is a header row group
	 *
	 * @param bool $b_is_header
	 */
	function SetIsHeader($b_is_header)
	{
		$this->SetElementName($b_is_header ? 'thead' : 'tbody');
	}
	
	/**
	 * Gets whether the row group is a header row group
	 *
	 * @return bool
	 */
	function GetIsHeader()
	{
		return $this->GetElementName() == 'thead';
	}

	/**
	 * Sets whether the row group is a footer row group
	 *
	 * @param bool $b_is_footer
	 */
	function SetIsFooter($b_is_footer)
	{
		$this->SetElementName($b_is_footer ? 'tfoot' : 'tbody');;
	}
	
	/**
	 * Gets whether the row group is a footer row group
	 *
	 * @return bool
	 */
	function GetIsFooter()
	{
		return $this->GetElementName() == 'tfoot';
	}
}
?>