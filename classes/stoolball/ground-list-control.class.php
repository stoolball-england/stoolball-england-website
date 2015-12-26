<?php
require_once('xhtml/xhtml-element.class.php');

class GroundListControl extends XhtmlElement
{
	private $a_grounds;

	function GroundListControl($a_grounds=null)
	{
		parent::XhtmlElement('ul');
		$this->a_grounds = (is_array($a_grounds)) ? $a_grounds : array();
	}

	function OnPreRender()
	{
		/* @var $o_ground Ground */

		foreach ($this->a_grounds as $o_ground)
		{
			if ($o_ground instanceof Ground)
			{
				$o_link = new XhtmlElement('a', Html::Encode($o_ground->GetNameAndTown()));
				$o_link->AddAttribute('href', $o_ground->GetNavigateUrl());
				$o_li = new XhtmlElement('li');
				$o_li->AddControl($o_link);

				$precision = array(
				GeoPrecision::Unknown() => 'No map',
				GeoPrecision::Exact() => 'Exact',
				GeoPrecision::Postcode() => 'Postcode',
				GeoPrecision::StreetDescriptor() => 'Street',
				GeoPrecision::Town() => 'Town'
				);

				if (!is_null($o_ground->GetAddress()->GetGeoPrecision()))
				{
					$o_li->AddControl(' (Map: ' . Html::Encode($precision[$o_ground->GetAddress()->GetGeoPrecision()]) . ')');
				}
				$this->AddControl($o_li);
			}
		}
	}
}
?>