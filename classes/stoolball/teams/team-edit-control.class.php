<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/player-type.enum.php');

/**
 * Edit control for details of a stoolball team
 *
 */
class TeamEditControl extends DataEditControl
{
	private $a_clubs;
	private $a_grounds;
    private $is_admin;

	/**
	 * Creates a new TeamEditControl
	 *
	 * @param SiteSettings $settings
	 */
	public function __construct(SiteSettings $settings)
	{
		# set up element
		$this->SetDataObjectClass('Team');
		parent::__construct($settings);

		# Set up aggregated editors
		$this->a_clubs = array();
		$this->a_grounds = array();        
        $this->is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS);
    }

	function SetClubs($a_clubs)
	{
		if (is_array($a_clubs)) $this->a_clubs = $a_clubs;
	}

	function GetClubs()
	{
		return $this->a_clubs;
	}

	function SetGrounds($a_grounds)
	{
		if (is_array($a_grounds)) $this->a_grounds = $a_grounds;
	}

	function GetGrounds()
	{
		return $this->a_grounds;
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$team = new Team($this->GetSettings());
		if (isset($_POST['item'])) $team->SetId($_POST['item']);
        if (isset($_POST['name'])) $team->SetName($_POST['name']);
		$team->SetWebsiteUrl($_POST['websiteUrl']);
		$team->SetIsActive(isset($_POST['playing']));
		if (isset($_POST['team_type'])) $team->SetTeamType($_POST['team_type']);
		$team->SetIntro(ucfirst(trim($_POST['intro'])));
		$team->SetPlayingTimes($_POST['times']);
		$team->SetCost($_POST['yearCost']);
		$team->SetContact($_POST['contact']);
		$team->SetPrivateContact($_POST['private']);

		$ground = new Ground($this->GetSettings());
		$ground->SetId($_POST['ground']);
		$team->SetGround($ground);

        if ($this->is_admin)
        { 
            $team->SetShortUrl($_POST[$this->GetNamingPrefix() . 'ShortUrl']);
            $team->SetPlayerType($_POST['playerType']);
    
            if (isset($_POST['club']) and is_numeric($_POST['club']))
            {
                $club = new Club($this->GetSettings());
                $club->SetId($_POST['club']);
                $team->SetClub($club);
            }
        }

		$this->SetDataObject($team);
	}

	function CreateControls()
	{
	    $this->AddCssClass('form');
        
		/* @var $club Club */
		/* @var $competition Competition */
		/* @var $season Season */
		/* @var $ground Ground */

		require_once('xhtml/forms/textbox.class.php');
		require_once('xhtml/forms/checkbox.class.php');
		require_once('stoolball/season-select.class.php');

		$team = $this->GetDataObject();
        $this->SetButtonText('Save team');
		/* @var $team Team */

		$is_once_only = ($team->GetTeamType() == Team::ONCE);
		
		
		if ($this->is_admin)
        { 
    		# add club
    		$club_list = new XhtmlSelect('club', null, $this->IsValidSubmit());
    		$club_list->SetBlankFirst(true);
    		foreach($this->a_clubs as $club)
    		{
    			if ($club instanceof Club)
    			{
    				$opt = new XhtmlOption($club->GetName(), $club->GetId());
    				$club_list->AddControl($opt);
    				unset($opt);
    			}
    		}
    		if (!is_null($team->GetClub()) and $this->IsValidSubmit()) $club_list->SelectOption($team->GetClub()->GetId());
    		$club_label= new XhtmlElement('label', 'Club');
            $club_label->AddAttribute('for', $club_list->GetXhtmlId());
            
    		$this->AddControl($club_label);
            $this->AddControl($club_list);
        }
        
        if ($this->is_admin or $is_once_only)
        { 
            # add name
    		$name_box = new TextBox('name', $team->GetName(), $this->IsValidSubmit());
    		$name_box->AddAttribute('maxlength', 100);
    		$name = new XhtmlElement('label', 'Team name');
            $name->AddAttribute('for', $name_box->GetXhtmlId());
    		$this->AddControl($name);
            $this->AddControl($name_box);
        }
        
        if ($this->is_admin)
        {     
    		# add player type
    		$type_list = new XhtmlSelect('playerType', null, $this->IsValidSubmit());
    		$type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::MIXED), PlayerType::MIXED));
    		$type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::LADIES), PlayerType::LADIES));
    		$type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::JUNIOR_MIXED), PlayerType::JUNIOR_MIXED));
    		$type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::GIRLS), PlayerType::GIRLS));
    		$type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::MEN), PlayerType::MEN));
    		$type_list->AddControl(new XhtmlOption(PlayerType::Text(PlayerType::BOYS), PlayerType::BOYS));
    
    		if (!is_null($team->GetPlayerType()) and $this->IsValidSubmit()) $type_list->SelectOption($team->GetPlayerType());
    		$type_part = new XhtmlElement('label', 'Player type');
            $type_part->AddAttribute('for', $type_list->GetXhtmlId());
            
    		$this->AddControl($type_part);
            $this->AddControl($type_list);

            # Remember short URL
            $short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $team->GetShortUrl(), $this->IsValidSubmit());
            $short_url_label = new XhtmlElement('label', 'Short URL');
            $short_url_label->AddAttribute('for', $short_url->GetXhtmlId());
            
            $this->AddControl($short_url_label);
            $this->AddControl($short_url);
		}

		# add intro
		$intro_box = new TextBox('intro', $team->GetIntro(), $this->IsValidSubmit());
		$intro_box->SetMode(TextBoxMode::MultiLine());
        
        $intro = new XhtmlElement('label', 'Introduction');
        $intro->AddAttribute('for', $intro_box->GetXhtmlId());
        
        $this->AddControl($intro);
        $this->AddControl('<p class="label-hint">If you need to change your team name, please email us.</p>');
		$this->AddControl($intro_box);

        if (!$is_once_only)
        {
            # Can we join in?
            $team_type = new XhtmlSelect('team_type', null, $this->IsValidSubmit());
            $team_type->AddControl(new XhtmlOption("plays regularly", Team::REGULAR, $team->GetTeamType() == Team::REGULAR));
            $team_type->AddControl(new XhtmlOption("represents a league or group", Team::REPRESENTATIVE, $team->GetTeamType() == Team::REPRESENTATIVE));
            $team_type->AddControl(new XhtmlOption("closed group (example: only pupils can join a school team)", Team::CLOSED_GROUP, $team->GetTeamType() == Team::CLOSED_GROUP));
            $team_type->AddControl(new XhtmlOption("plays occasionally", Team::OCCASIONAL, $team->GetTeamType() == Team::OCCASIONAL));
            
            $type_label = new XhtmlElement('label', "Type of team");
            $type_label->AddAttribute('for', $team_type->GetXhtmlId()); 
            
            $this->AddControl($type_label);
            $this->AddControl('<p class="label-hint">We use this to decide when to list your team.</p>');
            $this->AddControl($team_type);

            $this->AddControl(new CheckBox('playing', 'This team still plays matches', 1, $team->GetIsActive(), $this->IsValidSubmit()));
    
       		# add ground
    		$ground_list = new XhtmlSelect('ground', null, $this->IsValidSubmit());
    		foreach($this->a_grounds as $ground)
    		{
    			if ($ground instanceof Ground)
    			{
    				$opt = new XhtmlOption($ground->GetNameAndTown(), $ground->GetId());
    				$ground_list->AddControl($opt);
    				unset($opt);
    			}
    		}
    		$ground = $team->GetGround();
    		if (is_numeric($ground->GetId()) and $this->IsValidSubmit()) $ground_list->SelectOption($ground->GetId());
    		$ground_label = new XhtmlElement('label','Where do you play?');
            $ground_label->AddAttribute('for', $ground_list->GetXhtmlId());
    		$this->AddControl($ground_label);
            $this->AddControl('<p class="label-hint">If your ground isn\'t listed, please email us the address.</p>');
            $this->AddControl($ground_list);
        }
        
        # Add seasons
        $this->AddControl(new XhtmlElement('label', 'Leagues and competitions'));
        $this->AddControl('<p class="label-hint">We manage the leagues and competitions you\'re listed in. Please email us with any changes.</p>');

		if (!$is_once_only)
        { 
        	# add times
    		$times_box = new TextBox('times', $team->GetPlayingTimes(), $this->IsValidSubmit());
    		$times_box->SetMode(TextBoxMode::MultiLine());
    		$times = new XhtmlElement("label", 'Which days of the week do you play, and at what time?');
            $times->AddAttribute('for', $times_box->GetXhtmlId());
    		$this->AddControl($times);
            $this->AddControl($times_box);
        }

		# add cost
		$year_box = new TextBox('yearCost', $team->GetCost(), $this->IsValidSubmit());
		$year_box->SetMode(TextBoxMode::MultiLine());
		$year = new XhtmlElement('label', 'Cost to play');
        $year->AddAttribute('for', $year_box->GetXhtmlId());
		$this->AddControl($year);
        $this->AddControl('<p class="label-hint">Do you have an annual membership fee? Match fees? Special rates for juniors?</p>');
        $this->AddControl($year_box);

		# add contact info
		$contact_box = new TextBox('contact', $team->GetContact(), $this->IsValidSubmit());
		$contact_box->SetMode(TextBoxMode::MultiLine());
		$contact = new XhtmlElement('label', 'Contact details for the public');
        $contact->AddAttribute('for', $contact_box->GetXhtmlId());
		$this->AddControl($contact);
        $this->AddControl('<p class="label-hint">We recommend you publish a phone number and email so new players can get in touch.</p>');
        $this->AddControl($contact_box);

		$private_box = new TextBox('private', $team->GetPrivateContact(), $this->IsValidSubmit());
		$private_box->SetMode(TextBoxMode::MultiLine());
		$private = new XhtmlElement('label', 'Contact details for Stoolball England (if different)');
        $private->AddAttribute('for', $private_box->GetXhtmlId());
		$this->AddControl($private);
        $this->AddControl('<p class="label-hint">We won\'t share this with anyone else.</p>');
        $this->AddControl($private_box);

		# add website url
		$website_url_box = new TextBox('websiteUrl', $team->GetWebsiteUrl(), $this->IsValidSubmit());
		$website_url_box->AddAttribute('maxlength', 250);
		$website_url = new XhtmlElement('label', 'Team website');
        $website_url->AddAttribute('for', $website_url_box->GetXhtmlId());
		$this->AddControl($website_url);
        $this->AddControl($website_url_box);
	

    }

    /**
     * Add audit data after save button 
     */
    function OnPreRender()
    {
        parent::OnPreRender();

        # Show audit data
        $team = $this->GetDataObject();
        if ($team->GetLastAudit() != null)
        {
            require_once("data/audit-control.class.php");
            $this->AddControl(new AuditControl($team->GetLastAudit(), "team"));
        }
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
		require_once('data/validation/url-validator.class.php');
		require_once('http/short-url-validator.class.php');
        require_once("stoolball/team-name-validator.class.php");

		$this->AddValidator(new NumericValidator('club', 'The club identifier should be a number'));
		$this->AddValidator(new RequiredFieldValidator('name', 'Please add the name of the team'));
		$this->AddValidator(new LengthValidator('name', 'Please make the team name shorter', 0, 100));
        if ($this->is_admin)
        {     
            $this->AddValidator(new TeamNameValidator(array("item","name", "playerType"),"There is already a team with a very similar name. Please change the name."));
        }
    	$this->AddValidator(new RequiredFieldValidator('playerType', 'Please specify the type of player allowed in the competiton'));
		$this->AddValidator(new NumericValidator('playerType', 'The player type identifier should be a number'));
        $this->AddValidator(new LengthValidator('intro', 'Please make the introduction shorter', 0, 10000));
		$this->AddValidator(new LengthValidator('times', 'Please make the playing times shorter', 0, 5000));
		$this->AddValidator(new RequiredFieldValidator('ground', "Please select the team's home ground"));
		$this->AddValidator(new NumericValidator('ground', 'The ground identifier should be a number'));
		$this->AddValidator(new UrlValidator('websiteUrl', 'Please enter a valid web address, eg http://www.mystoolballsite.com/'));
        $this->AddValidator(new LengthValidator('contact', 'Please make the contact details shorter', 0, 10000));
		$this->AddValidator(new LengthValidator('private', 'Please make the Stoolball England contact details shorter', 0, 10000));
		$this->AddValidator(new ShortUrlValidator($this->GetNamingPrefix() . 'ShortUrl', 'That short URL is already in use', ValidatorMode::SingleField(), $this->GetDataObject()));
	}
}
?>