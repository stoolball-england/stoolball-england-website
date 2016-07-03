<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/clubs/club.class.php');

/**
 * Edit control for details of a school that plays stoolball
 *
 */
class SchoolEditControl extends DataEditControl
{
    private $is_admin;

	/**
	 * Creates a new TeamEditControl
	 *
	 * @param SiteSettings $settings
	 */
	public function __construct(SiteSettings $settings)
	{
		# set up element
		$this->SetDataObjectClass('Club');
		parent::__construct($settings);

        $this->is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS);
    }

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	public function BuildPostedDataObject()
	{
		$school = new Club($this->GetSettings());
        if (isset($_POST['item'])) {
            $school->SetId($_POST['item']);
        }
        $school->SetTypeOfClub(Club::SCHOOL);
        $school->SetName($_POST['school-name'] . ", " . $_POST["town"]);       
		$this->SetDataObject($school);
	}

	public function CreateControls()
	{
	    $this->AddCssClass('form');
        
		require_once('xhtml/forms/textbox.class.php');

        if (is_null($this->GetDataObject())) {
            $this->SetButtonText('Add new school');
            $school = new Club($this->GetSettings());
        } 
        else {
            $this->SetButtonText('Save school');
            $school = $this->GetDataObject();
        }
        /* @var $school Club */
        
        $school_name = $school->GetName();
        $town_name = '';
        $comma = strrpos($school_name, ",");
        if ($comma !== false) {
            $town_name = trim(substr($school_name,$comma+1));
            $school_name = substr($school_name, 0, $comma);
        }
        
        # add name
    	$name_box = new TextBox('school-name', $school_name, $this->IsValidSubmit());
    	$name_box->AddAttribute('maxlength', 100);
        $name_box->AddAttribute('autocomplete', "off");
        $name_box->AddCssClass("searchable");
    	$name = new XhtmlElement('label', 'School name');
        $name->AddAttribute('for', $name_box->GetXhtmlId());
    	$this->AddControl($name);
        $this->AddControl($name_box);

        # add town
        $town_box = new TextBox('town', $town_name, $this->IsValidSubmit());
        $town_box->AddAttribute('maxlength', 100);
        $town_box->AddAttribute('autocomplete', "off");
        $town_box->AddCssClass("searchable town");
        $town = new XhtmlElement('label', 'Town or village');
        $town->AddAttribute('for', $town_box->GetXhtmlId());
        $this->AddControl($town);
        $this->AddControl($town_box);
        
        # add hook for suggestions
        $this->AddControl('<div class="suggestions"></div>');
    }

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
        require_once('data/validation/length-validator.class.php');

		$this->AddValidator(new RequiredFieldValidator('name', 'Please add the name of the school'));
		$this->AddValidator(new LengthValidator('name', 'Please make the school name shorter', 0, 100));
        $this->AddValidator(new RequiredFieldValidator('town', 'Please add the town or village'));
        $this->AddValidator(new LengthValidator('town', 'Please make the town or village shorter', 0, 100));
	}
}
?>