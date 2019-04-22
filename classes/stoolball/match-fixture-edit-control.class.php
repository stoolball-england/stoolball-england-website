<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');
require_once('stoolball/match-type.enum.php');
require_once('data/date.class.php');
require_once('xhtml/forms/date-control.class.php');

/**
 * Editor for the who, what, where and when details of a stoolball match
 *
 */
class MatchFixtureEditControl extends DataEditControl
{
	private $i_match_type;

	/**
	 * The tournament of which the match is a part, if any
	 *
	 * @var Match
	 */
	private $tournament;
	/**
	 * Team for which the match is being added - could be home, away or unknown
	 *
	 * @var Team
	 */
	private $context_team;
	private $b_user_is_admin = false;

	/**
	 * Creates a MatchFixtureEditControl
	 *
	 * @param SiteSettings $o_settings
	 * @param string $csrf_token
	 * @param Match $o_match
	 * @param bool $b_entire_form
	 */
	public function __construct(SiteSettings $o_settings, $csrf_token, Match $o_match=null, $b_entire_form=true)
	{
		$this->SetDataObjectClass('Match');
		if (!is_null($o_match)) $this->SetDataObject($o_match);
		parent::__construct($o_settings, $csrf_token, $b_entire_form);
		$this->a_teams = array();
		$this->a_grounds = array();
		$this->SetButtonText('Save match');
		$this->i_match_type = MatchType::FRIENDLY;
		$this->seasons = new Collection();

		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES)) $this->b_user_is_admin = true;
	}

	/**
	 * Possible teams
	 *
	 * @var Team[][]
	 */
	private $a_teams;

	/**
	 * Sets the teams that might play, grouped into arrays
	 *
	 * @param Team[][] $a_teams
	 */
	function SetTeams($a_teams)
	{
		if (is_array($a_teams))
		{
			$this->a_teams = $a_teams;
			$this->EnsureTeamNames();
		}
	}

	/**
	 * Gets the teams that might play
	 *
	 * @return Team[][]
	 */
	function GetTeams()
	{
		return $this->a_teams;
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
	 * Sets the type of match to add/edit
	 *
	 * @param MatchType $i_type
	 */
	public function SetMatchType($i_type)
	{
		$this->i_match_type = (int)$i_type;
	}

	/**
	 * Gets the type of match to add/edit
	 *
	 * @return MatchType
	 */
	public function GetMatchType()
	{
		return $this->i_match_type;
	}

	/**
	 * Possible grounds
	 *
	 * @var Ground[]
	 */
	private $a_grounds;

	/**
	 * Sets the grounds where the match might be played
	 *
	 * @param Ground[] $a_grounds
	 */
	function SetGrounds($a_grounds)
	{
		if (is_array($a_grounds)) $this->a_grounds = $a_grounds;
	}

	/**
	 * Gets the grounds where the match might be played
	 *
	 * @return Ground[]
	 */
	function GetGrounds()
	{
		return $this->a_grounds;
	}

	/**
	 * The heading, where {0} becomes the match type
	 *
	 * @var string
	 */
	private $s_heading = 'Add your {0}';

	/**
	 * Sets the heading, where {0} becomes the match type
	 *
	 * @param string $s_heading
	 * @return void
	 */
	public function SetHeading($s_heading)
	{
		$this->s_heading = (string)$s_heading;
	}

	/**
	 * Gets the heading
	 *
	 * @return string
	 */
	public function GetHeading()
	{
		return $this->s_heading;
	}

	/**
	 * the default time for the match to start
	 *
	 * @var int
	 */
	private $i_default_time;

	/**
	 * Sets the the default time for the match to start
	 *
	 * @param int $i_default_time
	 * @return void
	 */
	public function SetDefaultTime($i_default_time)
	{
		$this->i_default_time = (int)$i_default_time;
	}

	/**
	 * Gets the the default time for the match to start
	 *
	 * @return int
	 */
	public function GetDefaultTime()
	{
		return $this->i_default_time;
	}


	/**
	 * the season the match is in
	 *
	 * @var Collection
	 */
	private $seasons;

	/**
	 * Sets the the seasons the match is in
	 *
	 * @return Collection
	 */
	public function Seasons()
	{
		return $this->seasons;
	}

	/**
	 * whether to show the control heading
	 *
	 * @var bool
	 */
	private $b_show_heading = true;

	/**
	 * Sets the whether to show the control heading
	 *
	 * @param bool $b_show_heading
	 * @return void
	 */
	public function SetShowHeading($b_show_heading)
	{
		$this->b_show_heading = (bool)$b_show_heading;
	}

	/**
	 * Gets the whether to show the control heading
	 *
	 * @return bool
	 */
	public function GetShowHeading()
	{
		return $this->b_show_heading;
	}

	/**
	 * Sets the tournament of which the match is a part, if any
	 *
	 * @param Match $tournament
	 */
	public function SetTournament(Match $tournament) { $this->tournament = $tournament; }

	/**
	 * Gets the tournament of which the match is a part, if any
	 *
	 * @return Match
	 */
	public function GetTournament() { return $this->tournament; }

	/**
	 * Builds a match object containing the result information posted by the control
	 *
	 */
	public function BuildPostedDataObject()
	{
		$o_match = new Match($this->GetSettings());

		# Get match id
		$s_key = $this->GetNamingPrefix() . 'item';
		if (isset($_POST[$s_key]))
		{
			$s_id = $_POST[$s_key];
			if (strlen($s_id))
			{
				$o_match->SetId($s_id);
			}
		}

		# Get the short URL
		$s_key = $this->GetNamingPrefix() . 'ShortUrl';
		if (isset($_POST[$s_key])) $o_match->SetShortUrl($_POST[$s_key]);

		# Get the start date
		$s_key = $this->GetNamingPrefix() . 'Start';
		$o_match->SetStartTime(DateControl::GetPostedTimestampUtc($s_key));
		$o_match->SetIsStartTimeKnown(DateControl::GetIsTimePosted($s_key));

		# Get the home team
		# Test for (int)$_POST[$s_key] deliberately excludes "Not known" value, which is 0
		$o_home = new Team($this->GetSettings());
		$s_key = $this->GetNamingPrefix() . 'Home';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]) and (int)$_POST[$s_key])
		{
			$o_home->SetId($_POST[$s_key]);
			$o_match->SetHomeTeam($o_home);
		}

		# Get the away team
		# Test for (int)$_POST[$s_key] deliberately excludes "Not known" value, which is 0
		$o_away = new Team($this->GetSettings());
		$s_key = $this->GetNamingPrefix() . 'Away';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]) and (int)$_POST[$s_key])
		{
			$o_away->SetId($_POST[$s_key]);
			$o_match->SetAwayTeam($o_away);
		}

		# Get the ground
		$s_key = $this->GetNamingPrefix() . 'Ground';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]))
		{
			$o_ground = new Ground($this->GetSettings());
			$o_ground->SetId($_POST[$s_key]);
			$o_match->SetGround($o_ground);
		}

		# Get the notes
		$s_key = $this->GetNamingPrefix() . 'Notes';
		if (isset($_POST[$s_key]))
		{
			$o_match->SetNotes($_POST[$s_key]);
		}

		# Get the match type
		$s_key = $this->GetNamingPrefix() . 'MatchType';
		if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$o_match->SetMatchType($_POST[$s_key]);
		}

		# Get the tournament
		if ($o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH)
		{
			$s_key = $this->GetNamingPrefix() . 'Tournament';
			if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
			{
				$tournament = new Match($this->GetSettings());
				$tournament->SetMatchType(MatchType::TOURNAMENT);
				$tournament->SetId($_POST[$s_key]);
				$o_match->SetTournament($tournament);
			}
		}

		# Get the season
		$s_key = $this->GetNamingPrefix() . 'Season';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]))
		{
			$o_season = new Season($this->GetSettings());
			$o_season->SetId($_POST[$s_key]);
			$o_match->Seasons()->Add($o_season);
		}

		$this->SetDataObject($o_match);
	}

	/**
	 * Ensure that teams in Match object have team names - can be important in building error messages etc
	 * @return void
	 */
	private function EnsureTeamNames()
	{
		$match =$this->GetDataObject();
		if ($match instanceof Match and count($this->a_teams))
		{
			if ($match->GetHomeTeamId() and !$match->GetHomeTeam()->GetName())
			{
				foreach($this->a_teams as $group_name => $teams)
				{
					foreach ($teams as $team)
					{
						if ($team->GetId() == $match->GetHomeTeamId())
						{
							$match->GetHomeTeam()->SetName($team->GetName());
						}
					}
				}
			}
			if ($match->GetAwayTeamId() and !$match->GetAwayTeam()->GetName())
			{
				foreach($this->a_teams as $group_name => $teams)
				{
					foreach ($teams as $team)
					{
						if ($team->GetId() == $match->GetAwayTeamId())
						{
							$match->GetAwayTeam()->SetName($team->GetName());
						}
					}
				}
			}
		}
	}

	protected function CreateControls()
	{
		$o_match = $this->GetDataObject();
		if (is_null($o_match)) $o_match = new Match($this->GetSettings());

		/* @var $o_match Match */
		/* @var $o_team Team */

		$b_got_home = !is_null($o_match->GetHomeTeam());
		$b_got_away = !is_null($o_match->GetAwayTeam());
		$b_is_new_match = !(bool)$o_match->GetId();
		$b_is_tournament_match = false;
		if ($this->i_match_type == MatchType::TOURNAMENT_MATCH) $b_is_tournament_match = $this->tournament instanceof Match; # new tournament match
		else if ($o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH and $o_match->GetTournament() instanceof Match) # edit tournament match
		{
			$this->SetTournament($o_match->GetTournament());
			$this->SetMatchType($o_match->GetMatchType());
			$b_is_tournament_match = true;
		}

		$o_match_outer_1 = new XhtmlElement('div');
		$o_match_outer_1->SetCssClass('MatchFixtureEdit');
		$o_match_outer_1->AddCssClass($this->GetCssClass());
		$this->SetCssClass('');
		$o_match_outer_1->SetXhtmlId($this->GetNamingPrefix());

		$o_match_outer_2 = new XhtmlElement('div');
		$o_match_box = new XhtmlElement('div');
		$this->AddControl($o_match_outer_1);
		$o_match_outer_1->AddControl($o_match_outer_2);
		$o_match_outer_2->AddControl($o_match_box);

		if ($this->GetShowHeading())
		{
			$s_heading = str_replace('{0}', MatchType::Text($this->i_match_type), $this->GetHeading()); # Add match type if required

			$o_title_inner_1 = new XhtmlElement('span', htmlentities($s_heading, ENT_QUOTES, "UTF-8", false));
			$o_title_inner_2 = new XhtmlElement('span', $o_title_inner_1);
			$o_title_inner_3 = new XhtmlElement('span', $o_title_inner_2);
			$o_match_box->AddControl(new XhtmlElement('h2', $o_title_inner_3, "medium large"));
		}

		# Offer choice of season if appropriate
		$season_count = $this->seasons->GetCount();
		if ($season_count == 1 and $this->i_match_type != MatchType::PRACTICE)
		{
			$o_season_id = new TextBox($this->GetNamingPrefix() . 'Season', $this->seasons->GetFirst()->GetId());
			$o_season_id->SetMode(TextBoxMode::Hidden());
			$o_match_box->AddControl($o_season_id);
		}
		elseif ($season_count > 1 and $this->i_match_type != MatchType::PRACTICE)
		{
			$o_season_id = new XhtmlSelect($this->GetNamingPrefix() . 'Season', '', $this->IsValidSubmit());
			foreach ($this->Seasons()->GetItems() as $season)
			{
				$o_season_id->AddControl(new XhtmlOption($season->GetCompetitionName(), $season->GetId()));
			}
			$o_match_box->AddControl(new FormPart('Competition', $o_season_id));
		}

		# Start date and time
		$match_time_known = (bool)$o_match->GetStartTime();
		if (!$match_time_known)
		{
			# if no date set, use specified default
			if ($this->i_default_time)
			{
				$o_match->SetStartTime($this->i_default_time);
				if ($b_is_tournament_match) $o_match->SetIsStartTimeKnown(false);
			}
			else
			{
				# if no date set and no default, default to today at 6.30pm BST
				$i_now = gmdate('U');
				$o_match->SetStartTime(gmmktime(17, 30, 00, (int)gmdate('n', $i_now), (int)gmdate('d', $i_now), (int)gmdate('Y', $i_now)));
			}
		}

		$o_date = new DateControl($this->GetNamingPrefix() . 'Start', $o_match->GetStartTime(), $o_match->GetIsStartTimeKnown(), $this->IsValidSubmit());
		$o_date->SetShowTime(true);
		$o_date->SetRequireTime(false);
		$o_date->SetMinuteInterval(5);

		# if no date set and only one season to choose from, limit available dates to the length of that season
		if (!$match_time_known and $season_count == 1)
		{
			if ($this->Seasons()->GetFirst()->GetStartYear() == $this->Seasons()->GetFirst()->GetEndYear())
			{
				$i_mid_season = gmmktime(0, 0, 0, 6, 30, $this->Seasons()->GetFirst()->GetStartYear());
			}
			else
			{
				$i_mid_season = gmmktime(0, 0, 0, 12, 31, $this->Seasons()->GetFirst()->GetStartYear());
			}
			$season_dates = Season::SeasonDates($i_mid_season);
			$season_start_month = gmdate('n', $season_dates[0]);
			$season_end_month = gmdate('n', $season_dates[1]); 
			if ($season_start_month) $o_date->SetMonthStart($season_start_month);
			if ($season_end_month) $o_date->SetMonthEnd($season_end_month+1);// TODO: need a better way to handle this, allowing overlap. Shirley has indoor matches until early April.

			$season_start_year = $this->Seasons()->GetFirst()->GetStartYear();
			$season_end_year = $this->Seasons()->GetFirst()->GetEndYear();
			if ($season_start_year) $o_date->SetYearStart($season_start_year);
			if ($season_end_year) $o_date->SetYearEnd($season_end_year);
		}


		if ($b_is_tournament_match)
		{
			$o_date->SetShowDate(false);
			$o_date->SetShowTime(false);
			$o_match_box->AddControl($o_date);
		}
		else
		{
			$o_date_part = new FormPart('When?', $o_date);
			$o_date_part->SetIsFieldset(true);
			$o_match_box->AddControl($o_date_part);
		}

		# Who's playing?
		if ($this->i_match_type == MatchType::PRACTICE and isset($this->context_team))
		{
			$home_id = new TextBox($this->GetNamingPrefix() . 'Home', $this->context_team->GetId());
			$home_id->SetMode(TextBoxMode::Hidden());
			$away_id = new TextBox($this->GetNamingPrefix() . 'Away', $this->context_team->GetId());
			$away_id->SetMode(TextBoxMode::Hidden());
			$o_match_box->AddControl($home_id);
			$o_match_box->AddControl($away_id);
		}
		else
		{
			$o_home_list = new XhtmlSelect($this->GetNamingPrefix() . 'Home');
			$o_away_list = new XhtmlSelect($this->GetNamingPrefix() . 'Away');

			$first_real_team_index = 0;
			if ($this->b_user_is_admin)
			{
				# Option of not specifying teams is currently admin-only
				# Value of 0 is important because PHP sees it as boolean negative, but it can be used as the indexer of an array in JavaScript
				$o_home_list->AddControl(new XhtmlOption("Don't know yet", '0'));
				$o_away_list->AddControl(new XhtmlOption("Don't know yet", '0'));
				$first_real_team_index = 1;
			}

			foreach($this->a_teams as $group_name => $teams)
			{
				foreach ($teams as $o_team)
				{
					$home_option = new XhtmlOption($o_team->GetName(), $o_team->GetId());
					if (is_string($group_name) and $group_name) $home_option->SetGroupName($group_name);
					$o_home_list->AddControl($home_option);

					$away_option = new XhtmlOption($o_team->GetName(), $o_team->GetId());
					if (is_string($group_name) and $group_name) $away_option->SetGroupName($group_name);
					$o_away_list->AddControl($away_option);
				}
			}

			$o_home_part = new FormPart('Home team', $o_home_list);
			$o_away_part = new FormPart('Away team', $o_away_list);

			$o_match_box->AddControl($o_home_part);
			$o_match_box->AddControl($o_away_part);

			if ($b_got_home) $o_home_list->SelectOption($o_match->GetHomeTeamId());

			if (!$b_got_home and $b_is_new_match)
			{
				// if no home team data, select the first team by default
				// unless editing a match, in which case it may be correct to have no teams (eg cup final)
				$o_home_list->SelectIndex($first_real_team_index);
			}

			if (!$b_got_away and $b_is_new_match)
			{
				// if no away team data, select the second team as the away team so that it's not the same as the first
				// unless editing a match, in which case it may be correct to have no teams (eg cup final).
				$o_away_list->SelectIndex($first_real_team_index+1);

				// if there was a home team but not an away team, make sure we don't select the home team against itself
				if ($b_got_home and $o_away_list->GetSelectedValue() == (string)$o_match->GetHomeTeamId())
				{
					$o_away_list->SelectIndex($first_real_team_index);
				}
			}
			else
			{
				if ($b_got_away) $o_away_list->SelectOption($o_match->GetAwayTeamId());

				if (!$b_is_new_match)
				{
					# Note which away team was previously saved, even if it's "not known" - this is for JavaScript to know it shouldn't auto-change the away team
					$away_saved = new TextBox($this->GetNamingPrefix() . 'SavedAway', $o_match->GetAwayTeamId());
					$away_saved->SetMode(TextBoxMode::Hidden());
					$o_match_box->AddControl($away_saved);
					unset($away_saved);
				}
			}
		}

		# Where?
		# If tournament match, assume same ground as tournament. Otherwise ask the user for ground.
		if ($b_is_tournament_match)
		{
			$ground = new TextBox($this->GetNamingPrefix() . 'Ground', $this->tournament->GetGroundId() ? $this->tournament->GetGroundId() : $o_match->GetGroundId());
			$ground->SetMode(TextBoxMode::Hidden());
			$o_match_box->AddControl($ground);
		}
		else
		{
			$o_ground_list = new XhtmlSelect($this->GetNamingPrefix() . 'Ground');
			$o_ground_list->AddControl(new XhtmlOption("Don't know", -1));
			$o_ground_list->AddControl(new XhtmlOption('Not listed (type the address in the notes field)', -2));

			# Promote home grounds for this season to the top of the list
			$a_home_ground_ids = array();
			foreach ($this->a_teams as $teams)
			{
				foreach ($teams as $o_team)
				{
					$a_home_ground_ids[$o_team->GetId()] = $o_team->GetGround()->GetId();
				}
			}
			$a_home_grounds = array();
			$a_other_grounds = array();

			/* @var $o_ground Ground */
			foreach ($this->a_grounds as $o_ground)
			{
				if (array_search($o_ground->GetId(), $a_home_ground_ids) > -1)
				{
					$a_home_grounds[] = $o_ground;
				}
				else
				{
					$a_other_grounds[] = $o_ground;
				}
			}

			# Add home grounds
			foreach ($a_home_grounds as $o_ground)
			{
				$option = new XhtmlOption($o_ground->GetNameAndTown(), $o_ground->GetId());
				$option->SetGroupName('Home grounds');
				$o_ground_list->AddControl($option);
			}

			# Add away grounds
			foreach ($a_other_grounds as $o_ground)
			{
				$option = new XhtmlOption($o_ground->GetNameAndTown(), $o_ground->GetId());
				$option->SetGroupName('Away grounds');
				$o_ground_list->AddControl($option);
			}

			# Select ground
			if ($o_match->GetGroundId()) $o_ground_list->SelectOption($o_match->GetGroundId());
			elseif ($this->i_match_type == MatchType::PRACTICE and isset($this->context_team))
			{
				$o_ground_list->SelectOption($this->context_team->GetGround()->GetId());
			}

			$o_ground_part = new FormPart('Where?', $o_ground_list);
			$o_match_box->AddControl($o_ground_part);

			# Note which grounds belong to which teams, for use by match-fixture-edit-control.js to select a ground when the home team is changed
			# Format is 1,2;2,3;4,5
			# where ; separates each team, and for each team the first number identifies the team and the second is the ground
			$s_team_ground = '';
			foreach ($a_home_ground_ids as $i_team => $i_ground)
			{
				if ($s_team_ground) $s_team_ground .= ';';
				$s_team_ground .= $i_team . ',' . $i_ground;
			}
			$o_hidden = new TextBox($this->GetNamingPrefix() . 'TeamGround', $s_team_ground);
			$o_hidden->SetMode(TextBoxMode::Hidden());
			$o_match_box->AddControl($o_hidden);
			unset($o_hidden);

			# Note which ground was previously saved - this is for JavaScript to know it shouldn't auto-change the ground
			if (!$b_is_new_match)
			{
				$o_hidden = new TextBox($this->GetNamingPrefix() . 'SavedGround', $o_match->GetGroundId());
				$o_hidden->SetMode(TextBoxMode::Hidden());
				$o_match_box->AddControl($o_hidden);
				unset($o_hidden);
			}
		}

		# Notes
		$o_notes = new TextBox($this->GetNamingPrefix() . 'Notes', $o_match->GetNotes());
		$o_notes->SetMode(TextBoxMode::MultiLine());
		$o_notes_part = new FormPart('Notes', $o_notes);
		$o_match_box->AddControl($o_notes_part);

		# Remember match type, tournament and short URL
		$o_type = new TextBox($this->GetNamingPrefix() . 'MatchType', $this->GetMatchType());
		$o_type->SetMode(TextBoxMode::Hidden());
		$o_match_box->AddControl($o_type);

		if ($b_is_tournament_match)
		{
			$tourn_box = new TextBox($this->GetNamingPrefix() . 'Tournament', $this->tournament->GetId());
			$tourn_box->SetMode(TextBoxMode::Hidden());
			$o_match_box->AddControl($tourn_box);
		}

		$o_short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $o_match->GetShortUrl());
		$o_short_url->SetMode(TextBoxMode::Hidden());
		$o_match_box->AddControl($o_short_url);

		# Note the context team - to be picked up by JavaScript to enable auto-changing of away team if
		# context team is not selected as home team
		if (isset($this->context_team))
		{
			$context_team_box = new TextBox($this->GetNamingPrefix() . 'ContextTeam', $this->context_team->GetId());
			$context_team_box->SetMode(TextBoxMode::Hidden());
			$o_match_box->AddControl($context_team_box);
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
		$this->a_validators[] = new RequiredFieldValidator(array($this->GetNamingPrefix() . 'Start_day', $this->GetNamingPrefix() . 'Start_month', $this->GetNamingPrefix() . 'Start_year'), 'Please select a date for the match', ValidatorMode::AllFields());
		$this->a_validators[] = new RequiredFieldValidator(array($this->GetNamingPrefix() . 'Start_hour', $this->GetNamingPrefix() . 'Start_minute', $this->GetNamingPrefix() . 'Start_ampm'), 'If you know the match time select the hour, minute and am or pm. If not, leave all three blank.', ValidatorMode::AllOrNothing());
		if (!$this->b_user_is_admin) $this->a_validators[] = new RequiredFieldValidator($this->GetNamingPrefix() . 'Home', 'Please select a home team');
		$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Home', 'The home team should be a number');
		if (!$this->b_user_is_admin) $this->a_validators[] = new RequiredFieldValidator($this->GetNamingPrefix() . 'Away', 'Please select an away team');
		$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Away', 'The away team should be a number');
		$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Ground', 'The ground should be a number');
		$this->a_validators[] = new LengthValidator($this->GetNamingPrefix() . 'Notes', 'Your match notes should not be more than 5000 characters long', 0, 5000);
	}
}
?>