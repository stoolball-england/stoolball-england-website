<?php
require_once('xhtml/xhtml-element.class.php');

/**
 * Panel which will be displayed next to a supporting sidebar
 *
 */
class SupportedContentControl extends XhtmlElement 
{
	/**
	 * Instantiates a SupportedContentControl
	 *
	 * @param XhtmlElement $o_all_content
	 */
	public function __construct(XhtmlElement $o_all_content)
	{
		parent::XhtmlElement('div');
		$this->SetCssClass('supportedContentContainer');
		$o_all_content->AddCssClass('supportedContent');
		$this->AddControl($o_all_content);
	}
}
?>