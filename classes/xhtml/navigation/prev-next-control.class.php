<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/xhtml-anchor.class.php');

class PrevNextControl extends XhtmlElement
{
	var $o_prev;
	var $o_next;
	
	function &PrevNextControl(&$o_prev, &$o_next)
	{
		parent::XhtmlElement('div');
		$this->SetXhtmlId('prev-next');
		$this->o_prev = &$o_prev;
		$this->o_next = &$o_next;
		return $this;
	}
	
	function OnPreRender()
	{
		if (isset($this->o_prev) || isset($this->o_next))
		{
			$b_prev_is_category = ($this->o_prev instanceof Category);
			$b_next_is_category = ($this->o_next instanceof Category);

			if ($b_prev_is_category)
			{
				$this->AddControl(new XhtmlAnchor('&laquo; Prev', $this->o_prev->GetNavigateUrl()));
			}
			if ($b_prev_is_category and $b_next_is_category) $this->AddControl(' | ');
			if ($b_next_is_category)
			{
				$this->AddControl(new XhtmlAnchor('Next &raquo;', $this->o_next->GetNavigateUrl()));
			}
		}
	}

}