<?php
require_once('xhtml/forms/xhtml-form.class.php');
require_once('xhtml/forms/textbox.class.php');
require_once('data/content-type.enum.php');

class RatingForm extends XhtmlForm
{
	var $o_settings;

	/**
	 * The item being rated
	 *
	 * @var RatedItem
	 */
	var $o_review_item;

	function __construct(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;

		parent::XhtmlForm();
		$this->SetXhtmlId('rateForm');
		$this->SetNavigateUrl('rating.php');
	}

	/**
	* @return void
	* @param RatedItem $o_input
	* @desc Sets the item being reviewed
	*/
	function SetReviewItem(RatedItem $o_input)
	{
		$this->o_review_item = $o_input;
	}

	/**
	* @return RatedItem
	* @desc Gets the item being reviewed
	*/
	function GetReviewItem()
	{
		return $this->o_review_item;
	}


	function OnPreRender()
	{
		/* @var $o_review_item RatedItem */
		/* @var $o_settings SiteSettings */
		$o_review_item = &$this->o_review_item;
		$o_settings = $this->o_settings;

		# Register script
		$o_script = new XhtmlElement('script');
		$o_script->AddAttribute('type', 'text/javascript');
		$o_script->AddAttribute('src', '/scripts/rating-form.js');
		$this->AddControl($o_script);

		# Build header
		$o_head = new XhtmlElement('div');
		$o_head->SetCssClass('rateBoxHead');
		$this->AddControl($o_head);

		$o_msg = new XhtmlElement('div', ($this->o_review_item->GetAverageRating() > 0) ? 'Currently ' . $this->o_review_item->GetAverageRating() . ' / 10' : 'Be the first to rate it!');
		$o_msg->SetCssClass('rateCurrent');
		$o_head->AddControl($o_msg);

		$i_type = $o_review_item->GetType();
		$o_this = new XhtmlElement('div', 'Rate this ' . ContentType::Text($i_type) . '&#8230;');
		$o_this->SetCssClass('rateThis');
		$o_head->AddControl($o_this);

		# Build body
		$o_body = new XhtmlElement('div');
		$o_body->SetCssClass('rateBoxControls');
		$this->AddControl($o_body);

		# Build hidden fields
		$o_id = new TextBox('id', $o_review_item->GetId());
		$o_id->SetMode(TextBoxMode::Hidden());
		$o_body->AddControl($o_id);

		$o_cat = new TextBox('cid');
		$o_cat->SetMode(TextBoxMode::Hidden());
		if (isset($_GET['cid'])) $o_cat->SetText($_GET['cid']);
		$o_body->AddControl($o_cat);

		$o_type = new TextBox('type', $i_type);
		$o_type->SetMode(TextBoxMode::Hidden());
		$o_body->AddControl($o_type);

		$o_page = new TextBox('page', $_SERVER['PHP_SELF'] . '?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']));
		$o_page->SetMode(TextBoxMode::Hidden());
		$o_body->AddControl($o_page);

		$o_js_hook = new TextBox('rateSelected');
		$o_js_hook->SetMode(TextBoxMode::Hidden());
		$o_body->AddControl($o_js_hook);

		$s_buttons = <<<_BUTTONS_
<label for="rate01" id="rateLabel01"><input type="radio" name="rating" id="rate01" value="1" /> 1</label>
<label for="rate02" id="rateLabel02"><input type="radio" name="rating" id="rate02" value="2" /> 2</label>
<label for="rate03" id="rateLabel03"><input type="radio" name="rating" id="rate03" value="3" /> 3</label>
<label for="rate04" id="rateLabel04"><input type="radio" name="rating" id="rate04" value="4" /> 4</label>
<label for="rate05" id="rateLabel05"><input type="radio" name="rating" id="rate05" value="5" /> 5</label>
<label for="rate06" id="rateLabel06"><input type="radio" name="rating" id="rate06" value="6" /> 6</label>
<label for="rate07" id="rateLabel07"><input type="radio" name="rating" id="rate07" value="7" /> 7</label>
<label for="rate08" id="rateLabel08"><input type="radio" name="rating" id="rate08" value="8" /> 8</label>
<label for="rate09" id="rateLabel09"><input type="radio" name="rating" id="rate09" value="9" /> 9</label>
<label for="rate10" id="rateLabel10"><input type="radio" name="rating" id="rate10" value="10" /> 10</label>
_BUTTONS_;

		$o_body->AddControl($s_buttons);

		$o_submit = new XhtmlElement('input');
		$o_submit->SetEmpty(true);
		$o_submit->AddAttribute('type', 'image');
		$o_submit->AddAttribute('src', $o_settings->GetFolder('Images') . 'go_red.gif');
		$o_submit->AddAttribute('alt', 'Go!');
		$o_submit->SetCssClass('rateGo');
		$o_body->AddControl($o_submit);
	}
}
?>