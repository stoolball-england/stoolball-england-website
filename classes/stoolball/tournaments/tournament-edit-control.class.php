<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');
require_once('stoolball/match-type.enum.php');
require_once('stoolball/player-type.enum.php');
require_once('data/date.class.php');
require_once('xhtml/forms/date-control.class.php');

/**
 * Editor for the who, what, where and when details of a stoolball tournament
 *
 */
class TournamentEditControl extends DataEditControl
{
	/**
	 * The tournament of which the match is a part, if any
	 *
	 * @var Match
	 */
	private $tournament;

	/**
	 * Team for which the tournament is being added, assumed to be playing in the tournament
	 *
	 * @var Team
	 */
	private $context_team;

	/**
	 * Season from which the tournament is being added, and of which the tournament is assumed to be a part
	 *
	 * @var Season
	 */
	private $context_season;

	/**
	 * Creates a TournamentEditControl
	 *
	 * @param SiteSettings $o_settings
	 * @param Match $match
	 * @param bool $b_entire_form
	 */
	public function __construct(SiteSettings $o_settings, Match $match=null, $b_entire_form=true)
	{
		$this->SetDataObjectClass('Match');
		if (!is_null($match))
		{
			$match->SetMatchType(MatchType::TOURNAMENT);
			$this->SetDataObject($match);
		}
		parent::__construct($o_settings, $b_entire_form);

        $this->probable_teams = new Collection();
		$this->grounds = new Collection();
		$this->SetAllowCancel(true);

        # Change Save button to "Save tournament"
        $this->SetButtonText('Save tournament');
    }

    /**
     * Probable teams
     *
     * @var Collection
     */
    private $probable_teams;

    /**
     * Gets the teams that are likely to play
     *
     * @return Collection
     */
    public function ProbableTeams()
    {
        return $this->probable_teams;
    }

	/**
	 * Sets the team for which the match is being added - could be home or away
	 *
	 * @param Team $team
	 */
	public function SetContextTeam(Team $team) { $this->context_team = $team; }

	/**
	 * Gets the team for which the match is being added - could be home or away
	 *
	 * @return Team
	 */
	public function GetContextTeam() { return $this->context_team; }

	/**
	 * Possible grounds
	 *
	 * @var Collection
	 */
	private $grounds;

	/**
	 * Gets the grounds where the tournament might be played
	 *
	 * @return Collection
	 */
	public function Grounds()
	{
		return $this->grounds;
	}

	/**
	 * the default time for the tournament to start
	 *
	 * @var int
	 */
	private $i_default_time;

	/**
	 * Sets the the default time for the tournament to start
	 *
	 * @param int $i_default_time
	 * @return void
	 */
	public function SetDefaultTime($i_default_time)
	{
		$this->i_default_time = (int)$i_default_time;
	}

	/**
	 * Gets the the default time for the tournament to start
	 *
	 * @return int
	 */
	public function GetDefaultTime()
	{
		return $this->i_default_time;
	}

	/**
	 * Sets the season from which the tournament is being added, and of which the tournament is assumed to be a part
	 *
	 * @param Season $season
	 */
	public function SetContextSeason(Season $season) { $this->context_season = $season; }

	/**
	 * Gets the season from which the tournament is being added, and of which the tournament is assumed to be a part
	 *
	 * @return Season
	 */
	public function GetContextSeason() { return $this->context_season; }
    
    private $show_step_number = false;
    
    /**
     * Sets whether to show 'Step x of x' in the panel header
     * @param bool $show
     */
    public function SetShowStepNumber($show) {
        $this->show_step_number = (bool)$show;
    }
    
    /**
     * Gets whether to show 'Step x of x' in the panel header
     */
    public function GetShowStepNumber() {
        return $this->show_step_number;
    }   
    
	/**
	 * Builds a match object containing the result information posted by the control
	 *
	 */
	public function BuildPostedDataObject()
	{
		$match = new Match($this->GetSettings());
		$match->SetMatchType(MatchType::TOURNAMENT);

		# Get match id
		$s_key = $this->GetNamingPrefix() . 'item';
		if (isset($_POST[$s_key]))
		{
			$s_id = $_POST[$s_key];
			if (strlen($s_id))
			{
				$match->SetId($s_id);
			}
		}

    	# Get the title
		$s_key = $this->GetNamingPrefix() . 'Title';
		if (isset($_POST[$s_key])) $match->SetTitle(strip_tags($_POST[$s_key]));

	    # Get the qualification type
        $s_key = $this->GetNamingPrefix() . 'Qualify';
        if (isset($_POST[$s_key])) $match->SetQualificationType($_POST[$s_key]);
        
		# Get the player type
		$s_key = $this->GetNamingPrefix() . 'PlayerType';
		if (isset($_POST[$s_key])) $match->SetPlayerType($_POST[$s_key]);

		$s_key = $this->GetNamingPrefix() . "Players";
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key])) $match->SetMaximumPlayersPerTeam($_POST[$s_key]);

		# Get the number of overs
		$s_key = $this->GetNamingPrefix() . "Overs";
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key])) $match->SetOvers($_POST[$s_key]);

		# Get the short URL
		$s_key = $this->GetNamingPrefix() . 'ShortUrl';
		if (isset($_POST[$s_key])) $match->SetShortUrl($_POST[$s_key]);

		# Get the start date
		$s_key = $this->GetNamingPrefix() . 'Start';
		$match->SetStartTime(DateControl::GetPostedTimestampUtc($s_key));
		$match->SetIsStartTimeKnown(DateControl::GetIsTimePosted($s_key));

		# Get the initial team
		$team = new Team($this->GetSettings());
		$s_key = $this->GetNamingPrefix() . 'ContextTeam';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]))
		{
			$team->SetId($_POST[$s_key]);
			$match->AddAwayTeam($team);
		}

		# Get the ground
		$s_key = $this->GetNamingPrefix() . 'Ground';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]))
		{
			$o_ground = new Ground($this->GetSettings());
			$o_ground->SetId($_POST[$s_key]);
			$match->SetGround($o_ground);
		}

		# Get the notes
		$s_key = $this->GetNamingPrefix() . 'Notes';
		if (isset($_POST[$s_key]))
		{
			$match->SetNotes($_POST[$s_key]);
		}

		$this->SetDataObject($match);
	}

	protected function CreateControls()
	{
		$match = $this->GetDataObject();
		if (is_null($match))
		{
			$match = new Match($this->GetSettings());
			$match->SetMatchType(MatchType::TOURNAMENT);
		}

		/* @var $match Match */

		$match_box = new XhtmlElement('div');

		$this->CreateFixtureControls($match, $match_box);
	}

	/**
	 * Creates the controls when the editor is in its fixture view
	 *
	 */
	private function CreateFixtureControls(Match $match, XhtmlElement $match_box)
	{
        $css_class = 'TournamentEdit';
        if ($this->GetCssClass()) $css_class .= ' ' . $this->GetCssClass();

        $match_outer_1 = new XhtmlElement('div');
        $match_outer_1->SetCssClass($css_class);
        $this->SetCssClass('');
        $match_outer_1->SetXhtmlId($this->GetNamingPrefix());

        $match_outer_2 = new XhtmlElement('div');
        $this->AddControl($match_outer_1);
        $match_outer_1->AddControl($match_outer_2);
        $match_outer_2->AddControl($match_box);


        if ($match->GetId())
        {
            $heading = "Edit tournament";
        }
        else 
        {
            $heading = "Add your tournament";
        }
        
        if ($this->show_step_number) {
            $heading .= ' &#8211; step 1 of 3';
        }
        $o_title_inner_1 = new XhtmlElement('span', $heading);
        $o_title_inner_2 = new XhtmlElement('span', $o_title_inner_1);
        $o_title_inner_3 = new XhtmlElement('span', $o_title_inner_2);
        $match_box->AddControl(new XhtmlElement('h2', $o_title_inner_3, "large"));

		# Tournament title
		$suggested_title = $match->GetTitle();
		if (isset($this->context_season))
		{
			$suggested_title = $this->GetContextSeason()->GetCompetition()->GetName();
			if (strpos(strtolower($suggested_title), 'tournament') === false) $suggested_title .= ' tournament';
		}
		else if (isset($this->context_team))
		{
			$suggested_title = $this->GetContextTeam()->GetName();
			if (strpos(strtolower($suggested_title), 'tournament') === false) $suggested_title .= ' tournament';
		}
		if ($suggested_title == "To be confirmed tournament") $suggested_title = "";
		if ($suggested_title == "To be confirmed v To be confirmed") $suggested_title = "";

		$title = new TextBox($this->GetNamingPrefix() . 'Title', $suggested_title, $this->IsValidSubmit());
		$title->SetMaxLength(200);
		$match_box->AddControl(new FormPart('Tournament name', $title));

        # Open or invite?
        require_once('xhtml/forms/radio-button.class.php');

        $qualify_set = new XhtmlElement('fieldset');
        $qualify_set->SetCssClass('formPart radioButtonList');
        $qualify_set->AddControl(new XhtmlElement('legend', 'Who can play?', 'formLabel'));

        $qualify_radios = new XhtmlElement('div', null, 'formControl');
        $qualify_set->AddControl($qualify_radios);

        $qualify_radios->AddControl(new RadioButton($this->GetNamingPrefix() . 'Open', $this->GetNamingPrefix() . 'Qualify', 'any team may enter', MatchQualification::OPEN_TOURNAMENT, ($match->GetQualificationType() === MatchQualification::OPEN_TOURNAMENT or !$match->GetId()), $this->IsValidSubmit()));
        $qualify_radios->AddControl(new RadioButton($this->GetNamingPrefix() . 'Qualify', $this->GetNamingPrefix() . 'Qualify', 'only invited or qualifying teams can enter', MatchQualification::CLOSED_TOURNAMENT, $match->GetQualificationType() === MatchQualification::CLOSED_TOURNAMENT, $this->IsValidSubmit()));
                
        $match_box->AddControl($qualify_set);

		# Player type
		$suggested_type = 2;
		if (isset($this->context_season)) $suggested_type = $this->context_season->GetCompetition()->GetPlayerType();
		elseif (isset($this->context_team)) $suggested_type = $this->context_team->GetPlayerType();

		if (!is_null($match->GetPlayerType())) $suggested_type = $match->GetPlayerType(); # Saved value overrides suggestion

		$player_set = new XhtmlElement('fieldset');
		$player_set->SetCssClass('formPart radioButtonList');
		$player_set->AddControl(new XhtmlElement('legend', 'Type of teams', 'formLabel'));

		$player_radios = new XhtmlElement('div', null, 'formControl');
		$player_set->AddControl($player_radios);

		$player_radios_1 = new XhtmlElement('div', null, 'column');
		$player_radios_2 = new XhtmlElement('div', null, 'column');
		$player_radios->AddControl($player_radios_1);
		$player_radios->AddControl($player_radios_2);

		$player_radios_1->AddControl(new RadioButton($this->GetNamingPrefix() . 'Ladies', $this->GetNamingPrefix() . 'PlayerType', 'Ladies', 2, $suggested_type === 2, $this->IsValidSubmit()));
		$player_radios_1->AddControl(new RadioButton($this->GetNamingPrefix() . 'Mixed', $this->GetNamingPrefix() . 'PlayerType', 'Mixed', 1, $suggested_type === 1, $this->IsValidSubmit()));
		$player_radios_2->AddControl(new RadioButton($this->GetNamingPrefix() . 'Girls', $this->GetNamingPrefix() . 'PlayerType', 'Junior girls', 5, $suggested_type === 5, $this->IsValidSubmit()));
		$player_radios_2->AddControl(new RadioButton($this->GetNamingPrefix() . 'Children', $this->GetNamingPrefix() . 'PlayerType', 'Junior mixed', 4, $suggested_type === 6, $this->IsValidSubmit()));

		$match_box->AddControl($player_set);

		# How many?
		$per_side_box = new XhtmlSelect($this->GetNamingPrefix() . "Players", null, $this->IsValid());
		$per_side_box->SetBlankFirst(true);
		for ($i = 6; $i <= 16; $i++) $per_side_box->AddControl(new XhtmlOption($i));
		if ($match->GetIsMaximumPlayersPerTeamKnown())
        {
            $per_side_box->SelectOption($match->GetMaximumPlayersPerTeam());
        }
        else if (!$match->GetId()) 
        {
            # Use eight as sensible default for new tournaments
            $per_side_box->SelectOption(8);
        }
		$players_per_team = new XhtmlElement("label", $per_side_box);
		$players_per_team->AddAttribute("for", $this->GetNamingPrefix() . "Players");
		$players_per_team->AddControl(" players per team");
		$players_part = new FormPart("How many players?", $players_per_team);
		$players_part->AddCssClass("playersPerTeam");
		$match_box->AddControl($players_part);

		# Overs
		$overs_box = new XhtmlSelect($this->GetNamingPrefix() . "Overs", null, $this->IsValid());
		$overs_box->SetBlankFirst(true);
		for ($i = 2; $i <= 8; $i++) $overs_box->AddControl(new XhtmlOption($i));
		if ($match->GetIsOversKnown()) $overs_box->SelectOption($match->GetOvers());
		$overs_label = new XhtmlElement("label", "Overs per innings");
		$overs_label->AddAttribute("for", $overs_box->GetXhtmlId());
		$overs_part = new FormPart($overs_label, new XhtmlElement("div", $overs_box));
		$overs_part->AddCssClass("overs");
		$match_box->AddControl($overs_part);

		# Start date and time
		if (!$match->GetStartTime())
		{
			# if no date set, use specified default
			if ($this->i_default_time)
			{
				$match->SetStartTime($this->i_default_time);
			}
			else
			{
				# if no date set and no default, default to today at 10.30am BST
				# NOTE that if this is a new tournament in an old season, this date won't be selected because the available
				# dates will be limited below and won't include today. It'll be the same day in the relevant year though.
				$i_now = gmdate('U');
				$match->SetStartTime(gmmktime(9, 30, 00, (int)gmdate('n', $i_now), (int)gmdate('d', $i_now), (int)gmdate('Y', $i_now)));
				$match->SetIsStartTimeKnown(true);
			}
		}

		$o_date = new DateControl($this->GetNamingPrefix() . 'Start', $match->GetStartTime(), $match->GetIsStartTimeKnown(), $this->IsValidSubmit());
		$o_date->SetShowTime(true);
		$o_date->SetRequireTime(false);
		$o_date->SetMinuteInterval(5);

		# if only one season to choose from, limit available dates to the length of that season
		if ($this->context_season instanceof Season)
		{
			if ($this->context_season->GetStartYear() == $this->context_season->GetEndYear())
			{
				$i_mid_season = gmmktime(0, 0, 0, 6, 30, $this->context_season->GetStartYear());
			}
			else
			{
				$i_mid_season = gmmktime(0, 0, 0, 12, 31, $this->context_season->GetStartYear());
			}
			$season_dates = Season::SeasonDates($i_mid_season);
			$season_start_month = gmdate('n', $season_dates[0]);
			$season_end_month = gmdate('n', $season_dates[1]);
			if ($season_start_month) $o_date->SetMonthStart($season_start_month);
			if ($season_end_month) $o_date->SetMonthEnd($season_end_month);

			$season_start_year = $this->context_season->GetStartYear();
			$season_end_year = $this->context_season->GetEndYear();
			if ($season_start_year) $o_date->SetYearStart($season_start_year);
			if ($season_end_year) $o_date->SetYearEnd($season_end_year);
		}

		$o_date_part = new FormPart('When?', $o_date);
		$o_date_part->SetIsFieldset(true);
		$match_box->AddControl($o_date_part);

		# Where?
		$o_ground_list = new XhtmlSelect($this->GetNamingPrefix() . 'Ground');
		$o_ground_list->AddControl(new XhtmlOption("Don't know", -1));
		$o_ground_list->AddControl(new XhtmlOption('Not listed (type the address in the notes field)', -2));

		# Promote the most likely grounds to the top of the list
		$likely_ground_ids = array();
        if ($match->GetGroundId())
        {
            $likely_ground_ids[] = $match->GetGroundId();
        } 
		foreach ($this->probable_teams as $o_team) 
		{
		    $likely_ground_ids[] = $o_team->GetGround()->GetId();
        }
		if (isset($this->context_season))
		{
			foreach ($this->context_season->GetTeams() as $o_team)	
			{
			    $likely_ground_ids[] = $o_team->GetGround()->GetId();
            }
		}
		if (isset($this->context_team) and is_object($this->context_team->GetGround()))
		{
			$likely_ground_ids[] = $this->context_team->GetGround()->GetId();
		}
		$likely_grounds = array();
		$a_other_grounds = array();

		/* @var $o_ground Ground */
		foreach ($this->grounds->GetItems() as $o_ground)
		{
			if (array_search($o_ground->GetId(), $likely_ground_ids) > -1)
			{
				$likely_grounds[] = $o_ground;
			}
			else
			{
				$a_other_grounds[] = $o_ground;
			}
		}

		# Add home grounds
		foreach ($likely_grounds as $o_ground)
		{
			$option = new XhtmlOption($o_ground->GetNameAndTown(), $o_ground->GetId());
			$option->SetGroupName('Likely grounds');
			$o_ground_list->AddControl($option);
		}

		# Add away grounds
		foreach ($a_other_grounds as $o_ground)
		{
			$option = new XhtmlOption($o_ground->GetNameAndTown(), $o_ground->GetId());
			$option->SetGroupName('Other grounds');
			$o_ground_list->AddControl($option);
		}

		# Select ground
		if ($match->GetGroundId()) 
		{
		    $o_ground_list->SelectOption($match->GetGroundId());
        }
		elseif (isset($this->context_team))
		{
			$o_ground_list->SelectOption($this->context_team->GetGround()->GetId());
		}

		$o_ground_part = new FormPart('Where?', $o_ground_list);
		$match_box->AddControl($o_ground_part);

		# Notes
		$o_notes = new TextBox($this->GetNamingPrefix() . 'Notes', $match->GetNotes());
		$o_notes->SetMode(TextBoxMode::MultiLine());
		$o_notes_part = new FormPart('Notes<br />(remember to include contact details)', $o_notes);
		$match_box->AddControl($o_notes_part);

		# Remember short URL
		$o_short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $match->GetShortUrl());
		$o_short_url->SetMode(TextBoxMode::Hidden());
		$match_box->AddControl($o_short_url);

        # Note the context team to be added to the tournament by default
        if (isset($this->context_team))
        {
            $context_team_box = new TextBox($this->GetNamingPrefix() . 'ContextTeam', $this->context_team->GetId());
            $context_team_box->SetMode(TextBoxMode::Hidden());
            $match_box->AddControl($context_team_box);
        }

		# Change Save button to "Next" button
		if ($this->show_step_number) {
		  $this->SetButtonText('Next &raquo;');
        }
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		
		$this->a_validators = array();
		$this->AddValidator(new RequiredFieldValidator($this->GetNamingPrefix() . 'Title', 'Please enter the tournament name', ValidatorMode::SingleField()));
		$this->AddValidator(new LengthValidator($this->GetNamingPrefix() . 'Title', 'Your tournament name should not be more than 200 characters long', 0, 200));
		$player_type_required = new RequiredFieldValidator($this->GetNamingPrefix() . 'PlayerType', 'Please specify who can play in the tournament');
		$player_type_required->SetValidIfNotFound(false);
		$this->AddValidator($player_type_required);
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'Qualify', 'A number should represent who can play'));
        $this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'PlayerType', 'A number should represent the type of team'));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'Players', "The number of players per team should be in digits, for example '8' not 'eight'"));
		$this->AddValidator(new RequiredFieldValidator(array($this->GetNamingPrefix() . 'Start_day', $this->GetNamingPrefix() . 'Start_month', $this->GetNamingPrefix() . 'Start_year'), 'Please select a date for the match', ValidatorMode::AllFields()));
		$this->AddValidator(new RequiredFieldValidator(array($this->GetNamingPrefix() . 'Start_hour', $this->GetNamingPrefix() . 'Start_minute', $this->GetNamingPrefix() . 'Start_ampm'), 'If you know the match time select the hour, minute and am or pm. If not, leave all three blank.', ValidatorMode::AllOrNothing()));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'Ground', 'The ground should be a number'));
		$this->AddValidator(new LengthValidator($this->GetNamingPrefix() . 'Notes', 'Your match notes should not be more than 5000 characters long', 0, 5000));
    }
}
?>