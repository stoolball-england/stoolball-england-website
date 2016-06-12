<?php
require_once('data/data-edit-control.class.php');

/**
 * Edit details of a stoolball club
 *
 */
class ClubEditControl extends DataEditControl
{
	/**
	 * Creates a ClubEditControl
	 * @param SiteSettings $settings
	 * @return ClubEditControl
	 */
	public function __construct(SiteSettings $settings)
	{
		# set up element
		$this->SetDataObjectClass('Club');
		parent::__construct($settings);
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$club = new Club($this->GetSettings());
		if (isset($_POST['item'])) $club->SetId($_POST['item']);
		$club->SetName($_POST['name']);
        $club->SetClubmarkAccredited(isset($_POST['clubmark']));
        $club->SetTwitterAccount($_POST['twitter']);
        $club->SetFacebookUrl($_POST['facebook']);
        $club->SetInstagramAccount($_POST['instagram']);
		$club->SetShortUrl($_POST[$this->GetNamingPrefix() . 'ShortUrl']);

		$this->SetDataObject($club);
	}

	function CreateControls()
	{
		# add name
		$o_name_box = new TextBox('name', $this->GetDataObject()->GetName(), $this->IsValidSubmit());
		$o_name_box->AddAttribute('maxlength', 100);
		$o_name = new FormPart('Name', $o_name_box);
		$this->AddControl($o_name);

        $twitter_box = new TextBox('twitter', $this->GetDataObject()->GetTwitterAccount(), $this->IsValidSubmit());
        $twitter_box->AddAttribute('maxlength', 16);
        $twitter = new FormPart('Twitter account', $twitter_box);
        $this->AddControl($twitter);
        
        $facebook_box = new TextBox('facebook', $this->GetDataObject()->GetFacebookUrl(), $this->IsValidSubmit());
        $facebook_box->AddAttribute('maxlength', 250);
        $facebook = new FormPart('Facebook URL', $facebook_box);
        $this->AddControl($facebook);

        $instagram_box = new TextBox('instagram', $this->GetDataObject()->GetInstagramAccount(), $this->IsValidSubmit());
        $instagram_box->AddAttribute('maxlength', 50);
        $instagram = new FormPart('Instagram account', $instagram_box);
        $this->AddControl($instagram);

        $this->AddControl(new FormPart('',new CheckBox('clubmark', 'Clubmark accredited', 1, $this->GetDataObject()->GetClubmarkAccredited(), $this->IsValidSubmit())));

		# Remember short URL
		$o_short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $this->GetDataObject()->GetShortUrl());
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
        require_once('data/validation/regex-validator.class.php');
		require_once('http/short-url-validator.class.php');

		$this->a_validators[] = new RequiredFieldValidator('name', 'Please add the name of the club');
		$this->a_validators[] = new PlainTextValidator('name', 'Please use only letters, numbers and simple punctuation in the club name');
		$this->a_validators[] = new LengthValidator('name', 'Please make the club name shorter', 0, 100);
        $this->a_validators[] = new RegexValidator('facebook', 'Please enter a valid Facebook URL', '^(|https?:\/\/(m.|www.|)facebook.com\/.+)');
		$this->a_validators[] = new ShortUrlValidator($this->GetNamingPrefix() . 'ShortUrl', 'That short URL is already in use', ValidatorMode::SingleField(), $this->GetDataObject());
	}

}
?>