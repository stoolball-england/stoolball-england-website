<?php
require_once('xhtml/xhtml-element.class.php');
require_once('ratings/rated-item.class.php');

class RatingSummary extends XhtmlElement
{
	var $o_rated;

	function &RatingSummary()
	{
		parent::XhtmlElement('p');
		return $this;
	}

	/**
	* @return void
	* @param RatedItem $o_rated
	* @desc Sets the item being rated
	*/
	function SetRatedItem(RatedItem $o_rated)
	{
		$this->o_rated = &$o_rated;
	}

	/**
	* @return RatedItem
	* @desc Gets the item being rated
	*/
	function GetRatedItem()
	{
		return $this->o_rated;
	}

	function OnPreRender()
	{
		/* @var $o_rated RatedItem */
		$o_rated = &$this->o_rated;
		if (!is_object($o_rated))
		{
			$this->SetVisible(false);
		}
		else
		{
			if ($o_rated->GetAverageRating())
			{
				$this->AddControl('Rating: <strong>' . $o_rated->GetAverageRating() . '/10</strong> <span class="detail-link">(<a href="' . $o_rated->GetBreakdownUrl() . '">Rating details</a>)</span>');
			}
			else
			{
				$o_link = new XhtmlElement('a', 'Be the first to rate this ' . ContentType::Text($o_rated->GetType()) . '!');
				$o_link->AddAttribute('href', '#rateForm');
				$o_link->AddAttribute('title', 'Rate \'' . $o_rated->GetTitle() . '\' at the bottom of this page');
				$this->AddControl($o_link);
			}
		}
	}
}