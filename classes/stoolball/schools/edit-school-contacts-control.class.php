<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/schools/school.class.php');

/**
 * Edit control for details of a school that plays stoolball
 *
 */
class EditSchoolContactsControl extends DataEditControl
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
        $school->SetTwitterAccount($_POST['twitter']);
        $school->SetFacebookUrl($_POST['facebook']);
        $school->SetInstagramAccount($_POST['instagram']);

		$this->SetDataObject($school);
	}

	public function CreateControls()
	{
	    $this->AddCssClass('form');
        
		require_once('xhtml/forms/textbox.class.php');

        $this->SetButtonText('Save school');
        $school = $this->GetDataObject();

        /* @var $school School */
        
        # add Twitter
    	$twitter_box = new TextBox('twitter', $school->GetTwitterAccount(), $this->IsValidSubmit());
    	$twitter_box->AddAttribute('maxlength', 16);
        $twitter_box->AddCssClass("twitter");
    	$twitter = new XhtmlElement('label', 'Twitter', "twitter");
        $twitter->AddAttribute('for', $twitter_box->GetXhtmlId());
    	$this->AddControl($twitter);
        $this->AddControl($twitter_box);

        # add Facebook
        $facebook_box = new TextBox('facebook', $school->GetFacebookUrl(), $this->IsValidSubmit());
        $facebook_box->AddAttribute('maxlength', 250);
        $facebook_box->AddAttribute("type", "url");
        $facebook = new XhtmlElement('label', 'Facebook URL', "facebook");
        $facebook->AddAttribute('for', $facebook_box->GetXhtmlId());
        $this->AddControl($facebook);
        $this->AddControl($facebook_box);

        # add Instagram
        $instagram_box = new TextBox('instagram', $school->GetInstagramAccount(), $this->IsValidSubmit());
        $instagram_box->AddAttribute('maxlength', 50);
        $instagram = new XhtmlElement('label', 'Instagram account', "instagram");
        $instagram->AddAttribute('for', $instagram_box->GetXhtmlId());
        $this->AddControl($instagram);
        $this->AddControl($instagram_box);
    }

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
        require_once('data/validation/length-validator.class.php');
        require_once ('data/validation/regex-validator.class.php');

		$this->AddValidator(new LengthValidator('twitter', 'Please make the Twitter handle shorter', 0, 16));
        $this->AddValidator(new RegexValidator('facebook', 'Please enter a valid Facebook URL', '^(|https?:\/\/(m.|www.|)facebook.com\/.+)'));
        $this->AddValidator(new LengthValidator('facebook', 'Please make the Facebook URL shorter', 0, 250));
        $this->AddValidator(new LengthValidator('instagram', 'Please make the Instagram account shorter', 0, 50));
    }
}
?>