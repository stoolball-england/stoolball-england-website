<?php
require_once('xhtml/xhtml-element.class.php');

class Button extends XhtmlElement 
{
	function __construct($id, $text)
	{
		parent::__construct('input');
		$this->SetXhtmlId($id);
		$this->AddAttribute('name', $this->GetXhtmlId());
		$this->AddAttribute('type', 'submit');
		$this->SetEmpty(true);
		$this->AddAttribute('value', $text);
	}	
}
?>