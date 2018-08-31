<?php
require_once('xhtml/placeholder.class.php');
require_once('person/postal-address.class.php');

class PostalAddressEditControl extends Placeholder
{
	/**
	 * The address to edit
	 *
	 * @var PostalAddress
	 */
	private $address;
	private $validators;

	/**
	* @return void
	* @param object $address
	* @desc Gets the data object this control is editing
	*/
	function SetDataObject(PostalAddress $address)
	{
		$this->address = $address;
	}

	/**
	* @return void
	* @desc Gets the data object this control is editing
	*/
	function &GetDataObject()
	{
		if (!isset($this->address) and $_SERVER['REQUEST_METHOD'] == 'POST') $this->BuildPostedDataObject();

		return $this->address;
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data object this control is editing
	*/
	function BuildPostedDataObject()
	{
		$this->address = new PostalAddress();
		$this->address->SetSaon($_POST['saon']);
		$this->address->SetPaon($_POST['paon']);
		$this->address->SetStreetDescriptor($_POST['streetDescriptor']);
		$this->address->SetLocality($_POST['locality']);
		$this->address->SetTown($_POST['town']);
		$this->address->SetAdministrativeArea($_POST['county']);
		$this->address->SetPostcode($_POST['postcode']);
		$this->address->SetGeoLocation(isset($_POST['lat']) ? $_POST['lat'] : null, isset($_POST['long']) ? $_POST['long'] : null, isset($_POST['geoprecision']) ? $_POST['geoprecision'] : null);
	}

	function OnPreRender()
	{
		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');

		# add lines
		$saon_box = new TextBox('saon', $this->address->GetSaon());
		$saon_box->AddAttribute('maxlength', 100);
		$saon = new FormPart('Address within building', $saon_box);
		$this->AddControl($saon);

		$line1_box = new TextBox('paon', $this->address->GetPaon());
		$line1_box->AddAttribute('maxlength', 100);
		$line1 = new FormPart('Building name or number', $line1_box);
		$this->AddControl($line1);

		$line2_box = new TextBox('streetDescriptor', $this->address->GetStreetDescriptor());
		$line2_box->AddAttribute('maxlength', 100);
		$line2 = new FormPart('Street name', $line2_box);
		$this->AddControl($line2);

		$line3_box = new TextBox('locality', $this->address->GetLocality());
		$line3_box->AddAttribute('maxlength', 35);
		$line3 = new FormPart('Village or part of town', $line3_box);
		$this->AddControl($line3);

		# add town
		$town_box = new TextBox('town', $this->address->GetTown());
		$town_box->AddAttribute('maxlength', 30);
		$town = new FormPart('Town', $town_box);
		$this->AddControl($town);

		# add county
		$county_box = new TextBox('county', $this->address->GetAdministrativeArea());
		$county_box->AddAttribute('maxlength', 30);
		$county = new FormPart('County', $county_box);
		$this->AddControl($county);

		# add postcode
		$postcode_box = new TextBox('postcode', $this->address->GetPostcode());
		$postcode_box->AddAttribute('maxlength', 8);
		$postcode = new FormPart('Postcode', $postcode_box);
		$this->AddControl($postcode);

		# add geolocation
		$lat_box = new TextBox('lat', $this->address->GetLatitude());
		$lat_box->SetMaxLength(20);
		$lat = new FormPart('Latitude', $lat_box);
		$this->AddControl($lat);

		$long_box = new TextBox('long', $this->address->GetLongitude());
		$long_box->SetMaxLength(20);
		$long = new FormPart('Longitude', $long_box);
		$this->AddControl($long);

		$precision = new XhtmlSelect('geoprecision');
		$precision->AddControl(new XhtmlOption("Don't know", ''));
		$precision->AddControl(new XhtmlOption('Exact', GeoPrecision::Exact()));
		$precision->AddControl(new XhtmlOption('Postcode', GeoPrecision::Postcode()));
		$precision->AddControl(new XhtmlOption('Street', GeoPrecision::StreetDescriptor()));
		$precision->AddControl(new XhtmlOption('Town', GeoPrecision::Town()));
		$precision->SelectOption($this->address->GetGeoPrecision());
		$precision_part = new FormPart('How accurate is lat/long?', $precision);
		$this->AddControl($precision_part);

		$map = new XhtmlElement('div');
		$map->SetXhtmlId('map');
		$map->SetCssClass('formControl');
		$map_container = new XhtmlElement('div', $map);
		$map_container->SetCssClass('formPart');
		$this->AddControl($map_container);
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
		$this->validators[] = new PlainTextValidator('paon', 'Please use only letters, numbers and simple punctuation in the building name or number');
		$this->validators[] = new LengthValidator('paon', 'Please make the building name or number shorter', 0, 250);
		$this->validators[] = new PlainTextValidator('streetDescriptor', 'Please use only letters, numbers and simple punctuation in the street name');
		$this->validators[] = new LengthValidator('streetDescriptor', 'Please make the street name shorter', 0, 250);
		$this->validators[] = new PlainTextValidator('locality', 'Please use only letters, numbers and simple punctuation in the village or part of town');
		$this->validators[] = new LengthValidator('locality', 'Please make the village or part of town shorter', 0, 250);
		$this->validators[] = new RequiredFieldValidator('town', 'Please add the town');
		$this->validators[] = new LengthValidator('town', 'Please make the town shorter', 0, 100);
		$this->validators[] = new PlainTextValidator('town', 'Please use only letters, numbers and simple punctuation in the town');
		$this->validators[] = new PlainTextValidator('county', 'Please use only letters, numbers and simple punctuation in the county');
		$this->validators[] = new LengthValidator('county', 'Please make the county shorter', 0, 100);
		$this->validators[] = new LengthValidator('postcode', 'Postcodes cannot be more than eight characters', 0, 8);

		# TODO: Add postcode validator

		$this->validators[] = new LengthValidator(array('lat', 'long'), 'Latitude and longitude must be 20 digits or fewer', 0, 20, ValidatorMode::AllFields());
		$this->validators[] = new NumericValidator(array('lat', 'long'), 'Latitude and longitude must be a number', ValidatorMode::AllFields());
		$this->validators[] = new LengthValidator(array('geoprecision'), 'Geo precision must be one character', 0, 1);
		$this->validators[] = new NumericValidator(array('geoprecision'), 'Geo precision must be a number');

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
		if (!isset($this->validators))
		{
			$this->validators = array();
			$this->CreateValidators();
		}

		return $this->validators;
	}
}
?>