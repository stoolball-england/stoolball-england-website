<?php
require_once('data/data-edit-control.class.php');
require_once('person/postal-address-edit-control.class.php');

class GroundEditControl extends DataEditControl
{
	var $o_address_edit;

	function GroundEditControl(SiteSettings $o_settings)
	{
		# set up element
		$this->SetDataObjectClass('Ground');
		$this->o_address_edit = new PostalAddressEditControl();
		parent::__construct($o_settings);
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data object this control is editing
	*/
	function BuildPostedDataObject()
	{
		$o_ground = new Ground($this->GetSettings());
		if (isset($_POST['item'])) $o_ground->SetId($_POST['item']);
		$o_ground->SetDirections($_POST['directions']);
		$o_ground->SetParking($_POST['parking']);
		$o_ground->SetFacilities($_POST['facilities']);
		$o_ground->SetAddress($this->o_address_edit->GetDataObject());
		$o_ground->SetShortUrl($_POST[$this->GetNamingPrefix() . 'ShortUrl']);

		$this->SetDataObject($o_ground);
	}

	function CreateControls()
	{
		/* @var $o_ground Ground */

		require_once('xhtml/xhtml-element.class.php');
		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');

		$o_ground = $this->GetDataObject();

		# add address
		$this->o_address_edit->SetDataObject($o_ground->GetAddress());
		$this->AddControl($this->o_address_edit);

		# add directions
		$o_dir_box = new TextBox('directions', $o_ground->GetDirections());
		$o_dir_box->SetMode(TextBoxMode::MultiLine());
		$o_dir_part = new FormPart('Directions', $o_dir_box);
		$this->AddControl($o_dir_part);

		# add parking
		$o_park_box = new TextBox('parking', $o_ground->GetParking());
		$o_park_box->SetMode(TextBoxMode::MultiLine());
		$o_park_part = new FormPart('Parking', $o_park_box);
		$this->AddControl($o_park_part);

		# add facilities
		$o_facilities_box = new TextBox('facilities', $o_ground->GetFacilities());
		$o_facilities_box->SetMode(TextBoxMode::MultiLine());
		$o_facilities_part = new FormPart('Facilities', $o_facilities_box);
		$this->AddControl($o_facilities_part);

		# Remember short URL
		$o_short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $o_ground->GetShortUrl());
		$this->AddControl(new FormPart('Short URL', $o_short_url));
	}

	/**
	* @return void
	* @desc Create DataValidator objects to validate the edit control
	*/
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/plain-text-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('http/short-url-validator.class.php');

		# validate data
		$this->a_validators[] = new LengthValidator('directions', 'Please make the directions shorter', 0, 10000);
		$this->a_validators[] = new LengthValidator('parking', 'Please make the parking information shorter', 0, 10000);
		$this->a_validators[] = new LengthValidator('facilities', 'Please make the facilities description shorter', 0, 10000);

		$this->a_validators = array_merge($this->a_validators, $this->o_address_edit->GetValidators());

		$this->a_validators[] = new ShortUrlValidator($this->GetNamingPrefix() . 'ShortUrl', 'That short URL is already in use', ValidatorMode::SingleField(), $this->GetDataObject());
	}
}
?>