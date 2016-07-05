<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/schools/school.class.php');

/**
 * Edit control for details of a school that plays stoolball
 *
 */
class EditSchoolControl extends DataEditControl
{
    private $is_admin;

	/**
	 * Creates a new EditSchoolControl
	 *
	 * @param SiteSettings $settings
	 */
	public function __construct(SiteSettings $settings)
	{
		# set up element
		$this->SetDataObjectClass('School');
		parent::__construct($settings);

        $this->is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS);
    }

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	public function BuildPostedDataObject()
	{
		$school = new School($this->GetSettings());
        if (isset($_POST['item'])) {
            $school->SetId($_POST['item']);
        }
        $school->SetName($_POST['school-name'] . ", " . $_POST["town"]);
        
        $school->Ground()->SetId($_POST['ground-id']);
        $address = new PostalAddress();
        $address->SetPaon($_POST['school-name']);
        $address->SetStreetDescriptor($_POST['street']);
        $address->SetLocality($_POST['locality']);
        $address->SetTown($_POST['town']);
        $address->SetAdministrativeArea($_POST['county']);
        $address->SetPostcode($_POST['postcode']);
        $address->SetGeoLocation($_POST['latitude'], $_POST['longitude'], $_POST['geoprecision']);
        $school->Ground()->SetAddress($address);  
             
		$this->SetDataObject($school);
	}

	public function CreateControls()
	{
	    $this->AddCssClass('form');
        
		require_once('xhtml/forms/textbox.class.php');

        $this->SetButtonText('Save school');
        $school = $this->GetDataObject();

        /* @var $school School */
                
        # Get ground id
        $ground_id = new TextBox('ground-id', $school->Ground()->GetId(), $this->IsValidSubmit());
        $ground_id->SetMode(TextBoxMode::Hidden());
        $this->AddControl($ground_id);
        
        # add name
    	$name_box = new TextBox('school-name', $school->Ground()->GetAddress()->GetPaon(), $this->IsValidSubmit());
    	$name_box->AddAttribute('maxlength', 100);
        $name_box->AddAttribute('autocomplete', "off");
    	$name = new XhtmlElement('label', 'School name');
        $name->AddAttribute('for', $name_box->GetXhtmlId());
    	$this->AddControl($name);
        $this->AddControl($name_box);

        # add street
        $street_box = new TextBox('street', $school->Ground()->GetAddress()->GetStreetDescriptor(), $this->IsValidSubmit());
        $street_box->AddAttribute('maxlength', 250);
        $street_box->AddAttribute('autocomplete', "off");
        $street_box->AddCssClass("street");
        $street = new XhtmlElement('label', 'Road');
        $street->AddAttribute('for', $street_box->GetXhtmlId());
        $this->AddControl($street);
        $this->AddControl($street_box);

        # add locality
        $locality_box = new TextBox('locality', $school->Ground()->GetAddress()->GetLocality(), $this->IsValidSubmit());
        $locality_box->AddAttribute('maxlength', 250);
        $locality_box->AddAttribute('autocomplete', "off");
        $locality_box->AddCssClass("town");
        $locality = new XhtmlElement('label', 'Part of town');
        $locality->AddAttribute('for', $locality_box->GetXhtmlId());
        $this->AddControl($locality);
        $this->AddControl($locality_box);

        # add town
        $town_box = new TextBox('town', $school->Ground()->GetAddress()->GetTown(), $this->IsValidSubmit());
        $town_box->AddAttribute('maxlength', 100);
        $town_box->AddAttribute('autocomplete', "off");
        $town_box->AddCssClass("town");
        $town = new XhtmlElement('label', 'Town or village');
        $town->AddAttribute('for', $town_box->GetXhtmlId());
        $this->AddControl($town);
        $this->AddControl($town_box);
        
        # add administrative area
        $county_box = new TextBox('county', $school->Ground()->GetAddress()->GetAdministrativeArea(), $this->IsValidSubmit());
        $county_box->AddAttribute('maxlength', 100);
        $county_box->AddAttribute('autocomplete', "off");
        $county_box->AddCssClass("county");
        $county = new XhtmlElement('label', 'County');
        $county->AddAttribute('for', $county_box->GetXhtmlId());
        $this->AddControl($county);
        $this->AddControl($county_box);
        
        # add postcode
        $postcode_box = new TextBox('postcode', $school->Ground()->GetAddress()->GetPostcode(), $this->IsValidSubmit());
        $postcode_box->AddAttribute('maxlength', 8);
        $postcode_box->AddAttribute('autocomplete', "off");
        $postcode_box->AddCssClass("postcode");
        $postcode = new XhtmlElement('label', 'Postcode');
        $postcode->AddAttribute('for', $postcode_box->GetXhtmlId());
        $this->AddControl($postcode);
        $this->AddControl($postcode_box);
        
        # add lat/long
        $latitude = new TextBox('latitude', $school->Ground()->GetAddress()->GetLatitude(), $this->IsValidSubmit());
        $latitude->SetMode(TextBoxMode::Hidden());
        $this->AddControl($latitude);        

        $longitude = new TextBox('longitude', $school->Ground()->GetAddress()->GetLongitude(), $this->IsValidSubmit());
        $longitude->SetMode(TextBoxMode::Hidden());
        $this->AddControl($longitude);

        $geoprecision = new TextBox('geoprecision', $school->Ground()->GetAddress()->GetGeoPrecision(), $this->IsValidSubmit());
        $geoprecision->SetMode(TextBoxMode::Hidden());
        $this->AddControl($geoprecision);
        
        # placeholder for client-side map
        $map = new XhtmlElement('div', null, "#map");
        $map->AddCssClass("map");
        $this->AddControl($map);
    }

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
        require_once('data/validation/length-validator.class.php');
        require_once('data/validation/numeric-validator.class.php');

		$this->AddValidator(new RequiredFieldValidator('name', 'Please add the name of the school'));
		$this->AddValidator(new LengthValidator('name', 'Please make the school name shorter', 0, 100));
        $this->AddValidator(new LengthValidator('street', 'Please make the road name shorter', 0, 250));
        $this->AddValidator(new LengthValidator('locality', 'Please make the part of town shorter', 0, 250));
        $this->AddValidator(new RequiredFieldValidator('town', 'Please add the town or village'));
        $this->AddValidator(new LengthValidator('town', 'Please make the town or village shorter', 0, 100));
        $this->AddValidator(new LengthValidator('county', 'Please make the county shorter', 0, 100));
        $this->AddValidator(new LengthValidator('postcode', 'Postcodes cannot be more than eight characters', 0, 8));
        $this->AddValidator(new LengthValidator(array('latitude', 'longitude'), 'Latitude and longitude must be 20 digits or fewer', 0, 20, ValidatorMode::AllFields()));
        $this->AddValidator(new NumericValidator(array('latitude', 'longitude'), 'Latitude and longitude must be a number', ValidatorMode::AllFields()));
        $this->AddValidator(new LengthValidator(array('geoprecision'), 'Geo precision must be one character', 0, 1));
        $this->AddValidator(new NumericValidator(array('geoprecision'), 'Geo precision must be a number'));
    }
}
?>