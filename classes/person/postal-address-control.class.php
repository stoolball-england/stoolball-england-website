<?php
require_once('xhtml/xhtml-element.class.php');
require_once('person/postal-address.class.php');

class PostalAddressControl extends XhtmlElement
{
	/**
	 * The address to display
	 *
	 * @var PostalAddress
	 */
	private $o_address;

	function __construct(PostalAddress $o_address)
	{
		# set up element
		parent::__construct('p');
		$this->SetCssClass('adr');
		$this->AddAttribute("typeof", "schema:PostalAddress");
		$this->SetAddress($o_address);
	}

	/**
	 * @return void
	 * @param PostalAddress $o_address
	 * @desc Sets the address this control displays
	 */
	function SetAddress(PostalAddress $o_address)
	{
		$this->o_address = $o_address;
	}

	/**
	 * @return PostalAddress
	 * @desc Gets the address this control displays
	 */
	function GetAddress()
	{
		return $this->o_address;
	}

	function OnPreRender()
	{
		$s_text = "";
		$linked_data_street_address = ($this->o_address->GetSaon() or $this->o_address->GetPaon() or $this->o_address->GetStreetDescriptor());
		if ($linked_data_street_address) $s_text .= '<span property="schema:streetAddress">';

		if ($this->o_address->GetSaon() or $this->o_address->GetPaon())
		{
			$s_text .= '<span class="extended-address">';
		}

		$s_text .= Html::Encode($this->o_address->GetSaon());
		if ($this->o_address->GetPaon())
		{
			if ($this->o_address->GetSaon()) $s_text .= '<br />';
			$s_text .= Html::Encode($this->o_address->GetPaon());
		}
		if ($this->o_address->GetSaon() or $this->o_address->GetPaon()) $s_text .= '</span>';
		if ($this->o_address->GetStreetDescriptor())
		{
			if ($s_text) $s_text .= '<br />';
			$s_text .= '<span class="street-address">' . Html::Encode($this->o_address->GetStreetDescriptor()) . '</span>';
		}

		if ($linked_data_street_address) $s_text .= '</span>';

		$b_locality = false;
		if ($this->o_address->GetLocality())
		{
			if ($s_text) $s_text .= '<br />';
			$s_text .= '<span class="locality" property="schema:addressLocality">' . Html::Encode($this->o_address->GetLocality());
			$b_locality = true;
		}
		if ($this->o_address->GetTown())
		{
			if ($s_text) $s_text .= '<br />';
			if (!$b_locality) $s_text .= '<span class="locality" property="schema:addressLocality">';
			$s_text .= Html::Encode($this->o_address->GetTown()) . '</span>';
		}
		else if ($b_locality)
		{
			$s_text .= '</span>';
		}
		if ($this->o_address->GetAdministrativeArea())
		{
			if ($s_text) $s_text .= '<br />';
			$s_text .= '<span class="region" property="schema:addressRegion">' . Html::Encode($this->o_address->GetAdministrativeArea()) . '</span>';
		}
		if ($this->o_address->GetPostcode())
		{
			if ($s_text) $s_text .= ' ';
			$s_text .= '<span class="postal-code" property="schema:postalCode">' . Html::Encode(strtoupper($this->o_address->GetPostcode())) . '</span>';
		}

		if ($s_text) $this->AddControl($s_text); else $this->SetVisible(false);
	}

}
?>