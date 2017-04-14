<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/competition.class.php');
require_once('stoolball/player-type.enum.php');
require_once('category/category-select-control.class.php');

class CompetitionEditControl extends DataEditControl
{
	private $categories;

	public function __construct(SiteSettings $settings, CategoryCollection $categories)
	{
		# set up element
		$this->SetDataObjectClass('Competition');
		parent::__construct($settings);

		# store params
		$this->categories = $categories;
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$o_comp = new Competition($this->GetSettings());

		if (isset($_POST['item'])) $o_comp->SetId($_POST['item']);
		$o_comp->SetIsActive(isset($_POST['active']));
		$o_comp->SetName($_POST['name']);
		$o_comp->SetIntro(ucfirst(trim($_POST['intro'])));
		$o_comp->SetContact($_POST['contact']);
		$o_comp->SetWebsiteUrl($_POST['website']);
        $o_comp->SetTwitterAccount($_POST['twitter']);
        $o_comp->SetFacebookUrl($_POST['facebook']);
        $o_comp->SetInstagramAccount($_POST['instagram']);
		$o_comp->SetNotificationEmail($_POST['notification']);
		$o_comp->SetShortUrl($_POST[$this->GetNamingPrefix() . 'ShortUrl']);
		$o_comp->SetPlayerType($_POST['playerType']);
		$o_comp->SetMaximumPlayersPerTeam($_POST['players']);
		$o_comp->SetOvers($_POST['overs']);

		$s_key = $this->GetNamingPrefix() . 'category';
		if (isset($_POST[$s_key]) && $_POST[$s_key] && is_numeric($_POST[$s_key]))
		{
			$cat = new Category();
			$cat->SetId($_POST[$s_key]);
			$o_comp->SetCategory($cat);
		}

		$this->SetDataObject($o_comp);
	}


	function CreateControls()
	{
		require_once('xhtml/xhtml-element.class.php');
		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');

        $this->AddCssClass('legacy-form');

		$o_comp = $this->GetDataObject();

		/* @var $o_type IdValue */
		/* @var $o_comp Competition */
		/* @var $o_season Season */

		# add name
		$o_name_box = new TextBox('name', $o_comp->GetName(), $this->IsValidSubmit());
		$o_name_box->AddAttribute('maxlength', 100);
		$o_name = new FormPart('Competition name', $o_name_box);
		$this->AddControl($o_name);

		# Add seasons once competition saved
		if ($o_comp->GetId())
		{
			$a_seasons = $o_comp->GetSeasons();
			$o_season_part = new FormPart('Seasons');
			$o_season_control = new Placeholder();

			# List exisiting seasons
			if (is_array($a_seasons) and count($a_seasons))
			{
				$o_seasons = new XhtmlElement('ul');
				foreach ($a_seasons as $o_season)
				{
					$o_season_link = new XhtmlAnchor(Html::Encode($o_season->GetName()), $o_season->GetEditSeasonUrl());
					$o_seasons->AddControl(new XhtmlElement('li', $o_season_link));
				}
				$o_season_control->AddControl($o_seasons);
			}

			$o_new_season = new XhtmlAnchor('Add season', '/play/competitions/seasonedit.php?competition=' . $o_comp->GetId());
			$o_season_control->AddControl($o_new_season);

			$o_season_part->SetControl($o_season_control);
			$this->AddControl($o_season_part);
		}

		# Still going?
		$this->AddControl(new CheckBox('active', 'This competition is still played', 1, $o_comp->GetIsActive(), $this->IsValidSubmit()));

		# add player type
		$o_type_list = new XhtmlSelect('playerType', null, $this->IsValidSubmit());
		$o_type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::MIXED), PlayerType::MIXED));
		$o_type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::LADIES), PlayerType::LADIES));
		$o_type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::JUNIOR_MIXED), PlayerType::JUNIOR_MIXED));
		$o_type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::GIRLS), PlayerType::GIRLS));
		$o_type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::MEN), PlayerType::MEN));
		$o_type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::BOYS), PlayerType::BOYS));

		if (!is_null($o_comp->GetPlayerType()) and $this->IsValidSubmit()) $o_type_list->SelectOption($o_comp->GetPlayerType());
		$o_type_part = new FormPart('Player type', $o_type_list);
		$this->AddControl($o_type_part);

		# add players per team
		$players_box = new TextBox('players', $o_comp->GetMaximumPlayersPerTeam(), $this->IsValidSubmit());
		$players_box->SetMaxLength(2);
		$players = new FormPart('Max players in league/cup team', $players_box);
		$this->AddControl($players);

		# add overs
		$overs_box = new TextBox('overs', $o_comp->GetOvers(), $this->IsValidSubmit());
		$overs_box->SetMaxLength(2);
		$overs = new FormPart('Overs per innings', $overs_box);
		$this->AddControl($overs);

		# category
		$cat_select = new CategorySelectControl($this->categories, $this->IsValidSubmit());
		$cat_select->SetXhtmlId($this->GetNamingPrefix() . 'category');
		if (!is_null($o_comp->GetCategory()) and $this->IsValidSubmit()) $cat_select->SelectOption($o_comp->GetCategory()->GetId());
		$this->AddControl(new FormPart('Category', $cat_select));

		# add intro
		$o_intro_box = new TextBox('intro', $o_comp->GetIntro(), $this->IsValidSubmit());
		$o_intro_box->SetMode(TextBoxMode::MultiLine());
		$o_intro = new FormPart('Introduction', $o_intro_box);
		$this->AddControl($o_intro);

		# add contact info
		$o_contact_box = new TextBox('contact', $o_comp->GetContact(), $this->IsValidSubmit());
		$o_contact_box->SetMode(TextBoxMode::MultiLine());
		$o_contact = new FormPart('Contact details', $o_contact_box);
		$this->AddControl($o_contact);

		# Add notification email
		$o_notify_box = new TextBox('notification', $o_comp->GetNotificationEmail(), $this->IsValidSubmit());
		$o_notify_box->SetMaxLength(200);
		$o_notify = new FormPart('Notification email', $o_notify_box);
		$this->AddControl($o_notify);

		# add website
		$o_website_box = new TextBox('website', $o_comp->GetWebsiteUrl(), $this->IsValidSubmit());
		$o_website_box->SetMaxLength(250);
		$o_website = new FormPart('Website', $o_website_box);
		$this->AddControl($o_website);

        # add social media
        $twitter_box = new TextBox('twitter', $o_comp->GetTwitterAccount(), $this->IsValidSubmit());
        $twitter_box->SetMaxLength(16);
        $twitter = new FormPart('Twitter', $twitter_box);
        $this->AddControl($twitter);

        $facebook_box = new TextBox('facebook', $o_comp->GetFacebookUrl(), $this->IsValidSubmit());
        $facebook_box->SetMaxLength(250);
        $facebook = new FormPart('Facebook', $facebook_box);
        $this->AddControl($facebook);

        $instagram_box = new TextBox('instagram', $o_comp->GetInstagramAccount(), $this->IsValidSubmit());
        $instagram_box->SetMaxLength(50);
        $instagram = new FormPart('Instagram', $instagram_box);
        $this->AddControl($instagram);

		# Remember short URL
		$o_short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $o_comp->GetShortUrl(), $this->IsValidSubmit());
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
		require_once('data/validation/email-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/url-validator.class.php');
        require_once ('data/validation/regex-validator.class.php');
		require_once('http/short-url-validator.class.php');

		$this->AddValidator(new RequiredFieldValidator('name', 'Please add the name of the competition'));
		$this->AddValidator(new PlainTextValidator('name', 'Please use only letters, numbers and simple punctuation in the competition name'));
		$this->AddValidator(new LengthValidator('name', 'Please make the competition name shorter', 0, 100));
		$this->AddValidator(new RequiredFieldValidator('playerType', 'Please specify the type of player allowed in the competiton'));
		$this->AddValidator(new NumericValidator('playerType', 'The player type identifier should be a number'));
		$this->AddValidator(new RequiredFieldValidator('players', 'Please specify the maximum number of players per team'));
		$this->AddValidator(new NumericValidator('players', "The number of players per team should use only digits, for example '11' not 'eleven'"));
		$this->AddValidator(new RequiredFieldValidator('overs', 'Please specify the number of overs played'));
		$this->AddValidator(new NumericValidator('overs', "The number of overs played should use only digits, for example '10' not 'ten'"));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'category', 'The category identifier should be a number'));
		$this->AddValidator(new LengthValidator('intro', 'Please make the introduction shorter', 0, 10000));
		$this->AddValidator(new LengthValidator('contact', 'Please make the contact details shorter', 0, 10000));
		$this->AddValidator(new EmailValidator('notification', 'Please enter a valid notification email address'));
		$this->AddValidator(new LengthValidator('website', 'Please make the website URL shorter', 0, 250));
		$this->AddValidator(new UrlValidator('website', 'Please enter a valid web address, eg http://www.mystoolballsite.com/'));
	   $this->AddValidator(new RegexValidator('facebook', 'Please enter a valid Facebook URL', '^(|https?:\/\/(m.|www.|)facebook.com\/.+)'));
		$this->AddValidator(new ShortUrlValidator($this->GetNamingPrefix() . 'ShortUrl', 'That short URL is already in use', ValidatorMode::SingleField(), $this->GetDataObject()));
	}

}
?>