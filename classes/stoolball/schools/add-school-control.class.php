<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/clubs/club.class.php');

/**
 * Control to add a new school that plays stoolball
 *
 */
class AddSchoolControl extends DataEditControl
{
	/**
	 * Creates a new AddSchoolControl
	 *
	 * @param SiteSettings $settings
	 * @param string $csrf_token
	 */
	public function __construct(SiteSettings $settings, $csrf_token)
	{
		# set up element
		$this->SetDataObjectClass('Club');
		parent::__construct($settings, $csrf_token);
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

        $this->SetButtonText('Add new school');
        $school = new Club($this->GetSettings());

        /* @var $school Club */
        
        # add name
    	$name_box = new TextBox('school-name', '', $this->IsValidSubmit());
    	$name_box->AddAttribute('maxlength', 100);
        $name_box->AddAttribute('autocomplete', "off");
        $name_box->AddCssClass("searchable");
    	$name = new XhtmlElement('label', 'School name');
        $name->AddAttribute('for', $name_box->GetXhtmlId());
    	$this->AddControl($name);
        $this->AddControl($name_box);

        # add town
        $town_box = new TextBox('town', '', $this->IsValidSubmit());
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

		$this->AddValidator(new RequiredFieldValidator('school-name', 'Please add the name of the school'));
		$this->AddValidator(new LengthValidator('school-name', 'Please make the school name shorter', 0, 100));
        $this->AddValidator(new RequiredFieldValidator('town', 'Please add the town or village'));
        $this->AddValidator(new LengthValidator('town', 'Please make the town or village shorter', 0, 100));
	}
}
?>