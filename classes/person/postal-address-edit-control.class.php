<?php
require_once('xhtml/placeholder.class.php');
require_once('person/postal-address.class.php');

class PostalAddressEditControl extends Placeholder
{
	var $s_data_object_class;
	/**
	 * The address to edit
	 *
	 * @var PostalAddress
	 */
	var $o_data_object;
	var $a_validators;

	function PostalAddressEditControl()
	{
		# set up element
		parent::Placeholder();
		$this->s_data_object_class = 'PostalAddress';
	}

	/**
	* @return void
	* @param object $o_object
	* @desc Gets the data object this control is editing
	*/
	function SetDataObject($o_object)
	{
		if (!isset($this->s_data_object_class)) die('Data object class not set in edit control');
		if ($o_object instanceof $this->s_data_object_class) $this->o_data_object = $o_object;
	}

	/**
	* @return void
	* @desc Gets the data object this control is editing
	*/
	function &GetDataObject()
	{
		if (!isset($this->o_data_object) and $_SERVER['REQUEST_METHOD'] == 'POST') $this->BuildPostedDataObject();

		return $this->o_data_object;
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data object this control is editing
	*/
	function BuildPostedDataObject()
	{
		$this->o_data_object = new PostalAddress();
		$this->o_data_object->SetSaon($_POST['saon']);
		$this->o_data_object->SetPaon($_POST['paon']);
		$this->o_data_object->SetStreetDescriptor($_POST['streetDescriptor']);
		$this->o_data_object->SetLocality($_POST['locality']);
		$this->o_data_object->SetTown($_POST['town']);
		$this->o_data_object->SetAdministrativeArea($_POST['county']);
		$this->o_data_object->SetPostcode($_POST['postcode']);
		$this->o_data_object->SetGeoLocation(isset($_POST['lat']) ? $_POST['lat'] : null, isset($_POST['long']) ? $_POST['long'] : null, isset($_POST['geoprecision']) ? $_POST['geoprecision'] : null);
	}

	function OnPreRender()
	{
		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');

		# add lines
		$o_saon_box = new TextBox('saon', $this->o_data_object->GetSaon());
		$o_saon_box->AddAttribute('maxlength', 250);
		$o_saon = new FormPart('Address within building', $o_saon_box);
		$this->AddControl($o_saon);

		$o_line1_box = new TextBox('paon', $this->o_data_object->GetPaon());
		$o_line1_box->AddAttribute('maxlength', 250);
		$o_line1 = new FormPart('Building name or number', $o_line1_box);
		$this->AddControl($o_line1);

		$o_line2_box = new TextBox('streetDescriptor', $this->o_data_object->GetStreetDescriptor());
		$o_line2_box->AddAttribute('maxlength', 250);
		$o_line2 = new FormPart('Street name', $o_line2_box);
		$this->AddControl($o_line2);

		$o_line3_box = new TextBox('locality', $this->o_data_object->GetLocality());
		$o_line3_box->AddAttribute('maxlength', 250);
		$o_line3 = new FormPart('Village or part of town', $o_line3_box);
		$this->AddControl($o_line3);

		# add town
		$o_town_box = new TextBox('town', $this->o_data_object->GetTown());
		$o_town_box->AddAttribute('maxlength', 100);
		$o_town = new FormPart('Town', $o_town_box);
		$this->AddControl($o_town);

		# add county
		$o_county_box = new TextBox('county', $this->o_data_object->GetAdministrativeArea());
		$o_county_box->AddAttribute('maxlength', 100);
		$o_county = new FormPart('County', $o_county_box);
		$this->AddControl($o_county);

		# add postcode
		$o_postcode_box = new TextBox('postcode', $this->o_data_object->GetPostcode());
		$o_postcode_box->AddAttribute('maxlength', 8);
		$o_postcode = new FormPart('Postcode', $o_postcode_box);
		$this->AddControl($o_postcode);

		# add geolocation
		$o_lat_box = new TextBox('lat', $this->o_data_object->GetLatitude());
		$o_lat_box->SetMaxLength(20);
		$o_lat = new FormPart('Latitude', $o_lat_box);
		$this->AddControl($o_lat);

		$o_long_box = new TextBox('long', $this->o_data_object->GetLongitude());
		$o_long_box->SetMaxLength(20);
		$o_long = new FormPart('Longitude', $o_long_box);
		$this->AddControl($o_long);

		$o_precision = new XhtmlSelect('geoprecision');
		$o_precision->AddControl(new XhtmlOption("Don't know", ''));
		$o_precision->AddControl(new XhtmlOption('Exact', GeoPrecision::Exact()));
		$o_precision->AddControl(new XhtmlOption('Postcode', GeoPrecision::Postcode()));
		$o_precision->AddControl(new XhtmlOption('Street', GeoPrecision::StreetDescriptor()));
		$o_precision->AddControl(new XhtmlOption('Town', GeoPrecision::Town()));
		$o_precision->SelectOption($this->o_data_object->GetGeoPrecision());
		$o_precision_part = new FormPart('How accurate is lat/long?', $o_precision);
		$this->AddControl($o_precision_part);

		$o_map = new XhtmlElement('div');
		$o_map->SetXhtmlId('map');
		$o_map->SetCssClass('formControl');
		$o_map_container = new XhtmlElement('div', $o_map);
		$o_map_container->SetCssClass('formPart');
		$this->AddControl($o_map_container);
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
		require_once('data/validation/numeric-validator.class.php');

		# validate data
		$this->a_validators[] = new PlainTextValidator('paon', 'Please use only letters, numbers and simple punctuation in the building name or number');
		$this->a_validators[] = new LengthValidator('paon', 'Please make the building name or number shorter', 0, 250);
		$this->a_validators[] = new PlainTextValidator('streetDescriptor', 'Please use only letters, numbers and simple punctuation in the street name');
		$this->a_validators[] = new LengthValidator('streetDescriptor', 'Please make the street name shorter', 0, 250);
		$this->a_validators[] = new PlainTextValidator('locality', 'Please use only letters, numbers and simple punctuation in the village or part of town');
		$this->a_validators[] = new LengthValidator('locality', 'Please make the village or part of town shorter', 0, 250);
		$this->a_validators[] = new RequiredFieldValidator('town', 'Please add the town');
		$this->a_validators[] = new LengthValidator('town', 'Please make the town shorter', 0, 100);
		$this->a_validators[] = new PlainTextValidator('town', 'Please use only letters, numbers and simple punctuation in the town');
		$this->a_validators[] = new PlainTextValidator('county', 'Please use only letters, numbers and simple punctuation in the county');
		$this->a_validators[] = new LengthValidator('county', 'Please make the county shorter', 0, 100);
		$this->a_validators[] = new LengthValidator('postcode', 'Postcodes cannot be more than eight characters', 0, 8);

		# TODO: Add postcode validator

		$this->a_validators[] = new LengthValidator(array('lat', 'long'), 'Latitude and longitude must be 20 digits or fewer', 0, 20, ValidatorMode::AllFields());
		$this->a_validators[] = new NumericValidator(array('lat', 'long'), 'Latitude and longitude must be a number', ValidatorMode::AllFields());
		$this->a_validators[] = new LengthValidator(array('geoprecision'), 'Geo precision must be one character', 0, 1);
		$this->a_validators[] = new NumericValidator(array('geoprecision'), 'Geo precision must be a number');

/*        public static final int MIN_LATITUDE  =  -90 * 1000000;
        public static final int MAX_LATITUDE  =   90 * 1000000;
        public static final int MIN_LONGITUDE = -180 * 1000000;
        public static final int MAX_LONGITUDE =  180 * 1000000;*/
	}

	/**
	* @return DataValidator[]
	* @desc Gets DataValidator objects to validate the edit control
	*/
	function &GetValidators()
	{
		if (!isset($this->a_validators))
		{
			$this->a_validators = array();
			$this->CreateValidators();
		}

		return $this->a_validators;
	}
}
?>