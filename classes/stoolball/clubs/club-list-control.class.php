<?php
require_once('xhtml/xhtml-element.class.php');

class ClubListControl extends XhtmlElement
{
	var $a_clubs;
	
	function ClubListControl($a_clubs=null)
	{
		parent::XhtmlElement('ul');
		$this->a_clubs = (is_array($a_clubs)) ? $a_clubs : array();
	}
	
	function OnPreRender()
	{
		foreach ($this->a_clubs as $o_club)
		{
			if ($o_club instanceof Club)
			{
				$o_link = new XhtmlElement('a', Html::Encode($o_club->GetName()));
				$o_link->AddAttribute('href', $o_club->GetNavigateUrl());
				$o_li = new XhtmlElement('li');
				$o_li->AddControl($o_link);
				$this->AddControl($o_li);
			}
		} 
	}
}
?>