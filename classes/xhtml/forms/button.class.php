<?php
require_once('xhtml/xhtml-element.class.php');

class Button extends XhtmlElement 
{
	function Button($id, $text)
	{
		parent::XhtmlElement('input');
		$this->SetXhtmlId($id);
		$this->AddAttribute('name', $this->GetXhtmlId());
		$this->AddAttribute('type', 'submit');
		$this->SetEmpty(true);
		$this->AddAttribute('value', $text);
	}	
}
?>