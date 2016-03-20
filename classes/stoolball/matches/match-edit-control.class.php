<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');

class MatchEditControl extends DataEditControl
{
	/**
	 * Aggregated editor for the fixture date
	 *
	 * @var MatchFixtureEditControl
	 */
	private $fixture_editor;

	/**
	 * Aggregated editor for selecting seasons
	 *
	 * @var SeasonIdEditor
	 */
	private $season_editor;

	private $b_user_is_match_admin = false;
	private $b_user_is_match_owner = false;
	const DATA_SEPARATOR = "|";

	public function __construct(SiteSettings $settings)
	{
		# set up element
		$this->SetDataObjectClass('Match');
		parent::__construct($settings);
		$this->SetAllowCancel(true);
		$this->SetButtonText('Next &raquo;');

		# check permissions
		$this->b_user_is_match_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);

	}

	/**
	 * Lazy load of aggregated editors
	 * @return void
	 */
	private function EnsureAggregatedEditors()
	{
		if (!is_object($this->fixture_editor))
		{
			require_once('stoolball/match-fixture-edit-control.class.php');
			$this->fixture_editor = new MatchFixtureEditControl($this->GetSettings(), null, false);
			$this->fixture_editor->SetNamingPrefix(get_class($this->fixture_editor)); # Ensure prefix is unique
			$this->fixture_editor->SetShowValidationErrors(false);
			$this->fixture_editor->SetShowHeading(true);
			$this->fixture_editor->SetHeading('About this match');
			$this->fixture_editor->SetCssClass('panel');
		}

		if ($this->b_user_is_match_admin and !is_object($this->season_editor))
		{
			require_once('stoolball/season-id-editor.class.php');
			$this->season_editor = new SeasonIdEditor($this->GetSettings(), $this, 'Season');
		}
	}

	public function SetTeams($a_teams)
	{
		$this->EnsureAggregatedEditors();
		$this->fixture_editor->SetTeams($a_teams);
	}

	public function GetTeams()
	{
		$this->EnsureAggregatedEditors();
		return $this->fixture_editor->GetTeams();
	}

	public function SetGrounds($a_grounds)
	{
		$this->EnsureAggregatedEditors();
		$this->fixture_editor->SetGrounds($a_grounds);
	}

	public function GetGrounds()
	{
		$this->EnsureAggregatedEditors();
		return $this->fixture_editor->GetGrounds();
	}

	/**
	 * Sets the seasons which might be related
	 *
	 * @param Season[] $a_seasons
	 */
	public function SetSeasons($a_seasons)
	{
		$this->EnsureAggregatedEditors();
		$this->season_editor->SetPossibleDataObjects($a_seasons);
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$match = $this->BuildPostedFixture();
		$this->SetDataObject($match);
	}

	/**
	 * Builds data posted on page 1 back into a match object
	 * @return Match
	 */
	private function BuildPostedFixture()
	{

		/* @var $match Match */
		$this->EnsureAggregatedEditors();
		$match = $this->fixture_editor->GetDataObject(); # use fixture anyway even if empty because it's easier than checking at this stage if user is match owner
		$match->SetId($this->GetDataObjectId()); # use id from result because it's always there
		
        # Get who won the toss
        $key = $this->GetNamingPrefix() . 'Toss';
        if (isset($_POST[$key]))
        {
            $toss = $_POST[$key];
            if ($toss == TeamRole::Home() or $toss == TeamRole::Away())
            {
                $match->Result()->SetTossWonBy($toss);
            }
        }
		
		# Get who batted first
		$key = $this->GetNamingPrefix() . 'BatFirst';
		if (isset($_POST[$key]))
		{
			$batted = $_POST[$key];
			if ($batted == TeamRole::Home() or $batted == TeamRole::Away())
			{
				$match->Result()->SetHomeBattedFirst($batted == TeamRole::Home());
			}
		}

		# Get the result
		$key = $this->GetNamingPrefix() . 'Result';
		if (isset($_POST[$key]))
		{
			$s_result = $_POST[$key];
			if (strlen($s_result))
			{
				# First option, "match went ahead", is a negative value of the current result, so if we get a negative value
				# that option was chosen. But it means "match went ahead", so any current result that indicates a cancellation
				# must be overwritten with UNKNOWN.
				$result_type = intval($s_result);
				if ($result_type < 0)
				{
					$s_result = str_replace("-", "", $s_result);
					$result_type = intval($s_result);

					switch ($result_type)
					{
						case MatchResult::CANCELLED:
						case MatchResult::POSTPONED:
						case MatchResult::HOME_WIN_BY_FORFEIT:
						case MatchResult::AWAY_WIN_BY_FORFEIT:
						case MatchResult::ABANDONED:
							$result_type = MatchResult::UNKNOWN;
					}
				}
				$match->Result()->SetResultType($result_type);
			}
		}

		if ($this->b_user_is_match_admin)
		{
			# Match title
			$match->SetUseCustomTitle(!isset($_POST['defaultTitle']));
			if ($match->GetUseCustomTitle() and isset($_POST['title']))
			{
				$match->SetTitle($_POST['title']);
			}

			if (isset($_POST['type'])) $match->SetMatchType($_POST['type']);

			# Get seasons from aggregated editor
			foreach ($this->season_editor->DataObjects() as $season) $match->Seasons()->Add($season);

			# Has short URL been explicitly set?
			$match->SetUseCustomShortUrl(!isset($_POST[$this->GetNamingPrefix() . 'RegenerateUrl']));
		}

		# Get the short URL
		$key = $this->GetNamingPrefix() . 'ShortUrl';
		if (isset($_POST[$key])) $match->SetShortUrl($_POST[$key]);

		return $match;
	}

	function CreateControls()
	{
		$match = $this->GetDataObject();
		/* @var $match Match */
		$this->b_user_is_match_owner = ($match->GetAddedBy() instanceof User and AuthenticationManager::GetUser()->GetId() == $match->GetAddedBy()->GetId());

        $this->AddCssClass('legacy-form');
    
    	if ($this->b_user_is_match_admin)
		{
			# Add match title editing
			$b_new = (!$match->GetId());
			$title = new TextBox('title', ($b_new) ? '' : $match->GetTitle());
			$title->SetMaxLength(100);
			$this->AddControl(new FormPart('Match title', $title));

			$o_generate = new CheckBox('defaultTitle', 'Use default title', 1);
			if ($b_new)
			{
				$o_generate->SetChecked(true);
			}
			else
			{
				$o_generate->SetChecked(!$match->GetUseCustomTitle());
			}
			$this->AddControl($o_generate);

			# Add match type
			if ($match->GetMatchType() != MatchType::TOURNAMENT_MATCH)
			{
				$o_type_list = new XhtmlSelect('type');
				$o_type_list->AddControl(new XhtmlOption(MatchType::Text(MatchType::LEAGUE), MatchType::LEAGUE));
				$o_type_list->AddControl(new XhtmlOption(MatchType::Text(MatchType::PRACTICE), MatchType::PRACTICE));
				$o_type_list->AddControl(new XhtmlOption(MatchType::Text(MatchType::FRIENDLY), MatchType::FRIENDLY));
				$o_type_list->AddControl(new XhtmlOption(MatchType::Text(MatchType::CUP), MatchType::CUP));
				$o_type_list->SelectOption($match->GetMatchType());
				$this->AddControl(new FormPart('Type of match', $o_type_list));
			}
		}

		if ($this->b_user_is_match_owner or $this->b_user_is_match_admin)
		{
			$this->EnsureAggregatedEditors();

			$this->fixture_editor->SetDataObject($match);
			$this->AddControl($this->fixture_editor);
		}

		if ($this->b_user_is_match_admin)
		{
			# add season
			if (!$this->IsPostback()) $this->season_editor->DataObjects()->SetItems($match->Seasons()->GetItems());
			$this->AddControl($this->season_editor);

			# Hidden data on teams, for use by match-fixture-edit-control.js to re-sort teams when the season is changed
			# Format is 1,2,3,4,5;2,3,4,5,6
			# where ; separates each team, and for each team the first number identifies the team and subsequent numbers identify the season
			/* @var $o_team Team */
			/* @var $o_comp Competition */
			$s_team_season = '';

			# Build a list of all seasons, so that the "Not known yet" option can be added to all seasons
			$all_seasons = array();

			foreach($this->fixture_editor->GetTeams() as $group)
			{
				foreach ($group as $o_team)
				{
					if (!($o_team instanceof Team)) continue;

					# Add team id
					if ($s_team_season) $s_team_season .= ';';
					$s_team_season .= $o_team->GetId();

					# add team seasons
					foreach ($o_team->Seasons() as $team_in_season)
					{
                        $s_team_season .= ',' . $team_in_season->GetSeasonId();

						$all_seasons[] = $team_in_season->GetSeasonId();
					}
				}
			}

			# Add the "Don't know yet" option with all seasons
			$all_seasons = array_unique($all_seasons);
			$s_team_season = "0," . implode(",",$all_seasons) . ";$s_team_season";

			# Put it in a hidden field accessible to JavaScript.
			$o_ts = new TextBox($this->GetNamingPrefix() . 'TeamSeason', $s_team_season);
			$o_ts->SetMode(TextBoxMode::Hidden());
			$this->AddControl($o_ts);
		}

		$got_home_team = !is_null($match->GetHomeTeam());
        $got_away_team = !is_null($match->GetAwayTeam());
        
		$b_got_teams = ($got_home_team and $got_away_team);
		$b_future = ($match->GetId() and $match->GetStartTime() > gmdate('U'));

		// Move CSS class to div element
		$match_outer_1 = new XhtmlElement('div');
		$match_outer_1->SetCssClass('matchResultEdit panel');

		$match_outer_2 = new XhtmlElement('div');
		$match_box = new XhtmlElement('div');
		$this->AddControl($match_outer_1);
		$match_outer_1->AddControl($match_outer_2);
		$match_outer_2->AddControl($match_box);

		$o_title_inner_1 = new XhtmlElement('span',  "Result of this match");
		$o_title_inner_2 = new XhtmlElement('span', $o_title_inner_1);
		$o_title_inner_3 = new XhtmlElement('span', $o_title_inner_2);
		$o_heading = new XhtmlElement('h2', $o_title_inner_3);
		$match_box->AddControl($o_heading);

		# Who batted first?
		if (!$b_future)
		{
			$match_box->AddControl('<h3>If the match went ahead:</h3>');

            $toss = $this->SelectOneOfTwoTeams($this->GetNamingPrefix() . 'Toss', $match, $match->Result()->GetTossWonBy() === TeamRole::Home(), $match->Result()->GetTossWonBy() === TeamRole::Away());
            $toss_part = new FormPart('Who won the toss?', $toss);
            $match_box->AddControl($toss_part);

			$bat_first = $this->SelectOneOfTwoTeams($this->GetNamingPrefix() . 'BatFirst', $match, $match->Result()->GetHomeBattedFirst() === true, $match->Result()->GetHomeBattedFirst() === false);
			$bat_part = new FormPart('Who batted first?', $bat_first);
			$match_box->AddControl($bat_part);
		}

		# Who won?
		$o_winner = new XhtmlSelect($this->GetNamingPrefix() . 'Result');
		if ($b_future)
		{
			$o_winner->AddControl(new XhtmlOption('The match will go ahead', ''));
		}
		else
		{
			# This option means "no change", therefore it has to have the current value for the match result even though that's
			# a different value from match to match. If it's not there the submitted match doesn't have result info, so when admin
			# saves fixture it regenerates its title as "Team A v Team B", which doesn't match the existing title so gets saved to db.
			# Can't be exactly the current value though, because otherwise a cancelled match has two options with the same value,
			# so re-selecting the option doesn't work. Instead change it to a negative number, which can be converted back when submitted.
			$o_winner->AddControl(new XhtmlOption('Not applicable &#8211; match went ahead', $match->Result()->GetResultType()*-1));
		}

		$result_types = array(MatchResult::HOME_WIN_BY_FORFEIT, MatchResult::AWAY_WIN_BY_FORFEIT, MatchResult::POSTPONED, MatchResult::CANCELLED, MatchResult::ABANDONED);
		foreach ($result_types as $result_type)
		{
			if (!$b_future or MatchResult::PossibleInAdvance($result_type))
			{
				if ($b_got_teams)
				{
					$o_winner->AddControl(new XhtmlOption($this->NameTeams(MatchResult::Text($result_type), $match->GetHomeTeam(), $match->GetAwayTeam()), $result_type));
				}
				else
				{
					$o_winner->AddControl(new XhtmlOption(MatchResult::Text($result_type), $result_type));
				}
			}
		}
		$o_winner->SelectOption($match->Result()->GetResultType());
		if (!$b_future) $match_box->AddControl('<h3 class="ifNotPlayed">Or, if the match was not played:</h3>');
		$o_win_part = new FormPart($b_future ? 'Will it happen?' : 'Why not?', $o_winner);
		$match_box->AddControl($o_win_part);

		# Show audit data
		if ($match->GetLastAudit() != null)
		{
	        require_once("data/audit-control.class.php");
            $match_box->AddControl(new AuditControl($match->GetLastAudit(), "match"));
    	}

		# Remember short URL
		$o_short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $match->GetShortUrl());
		if ($this->b_user_is_match_admin)
		{
			$o_url_part = new FormPart('Short URL', $o_short_url);
			$this->AddControl($o_url_part);

			$this->AddControl(new CheckBox($this->GetNamingPrefix() . 'RegenerateUrl', 'Regenerate short URL', 1, !$match->GetUseCustomShortUrl(), $this->IsValidSubmit()));
		}
		else
		{
			$o_short_url->SetMode(TextBoxMode::Hidden());
			$this->AddControl($o_short_url);
		}

		if ($b_future and !$this->b_user_is_match_admin and !$this->b_user_is_match_owner)
		{
			$this->SetButtonText("Save result");
		}

	}

    /**
     * Creates a dropdown list to select either "don't know" or one of the two playing teams
     */
    private function SelectOneOfTwoTeams($list_id, Match $match, $select_home_if, $select_away_if) 
    {
        $list = new XhtmlSelect($list_id);
        $list->AddControl(new XhtmlOption("Don't know", ''));
        $list->AddControl(new XhtmlOption(!is_null($match->GetHomeTeam()) ? $match->GetHomeTeam()->GetName() : 'Home team', TeamRole::Home(), $select_home_if));
        $list->AddControl(new XhtmlOption(!is_null($match->GetAwayTeam()) ? $match->GetAwayTeam()->GetName() : 'Away team', TeamRole::Away(), $select_away_if));
        return $list;
    }

	/**
	 * Replace the words "home" and "away" with the names of the relevant teams
	 *
	 * @param string $s_text
	 * @param Team $o_home
	 * @param Team $o_away
	 * @return string
	 */
	private function NameTeams($s_text, Team $o_home, Team $o_away)
	{
		$s_text = str_ireplace('Home', $o_home->GetName(), $s_text);
		$s_text = str_ireplace('Away', $o_away->GetName(), $s_text);
		return $s_text;
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/length-validator.class.php');

		if ($this->b_user_is_match_admin)
		{
			require_once('data/validation/required-field-validator.class.php');
			$this->AddValidator(new LengthValidator('title', 'Please make the match title shorter', 0, 100));
			$this->AddValidator(new RequiredFieldValidator(array('type'), 'Please select a match type'));
			$this->AddValidator(new NumericValidator('type', 'The match type identifier should be a number'));
		}

		$this->EnsureAggregatedEditors();

		if ($this->b_user_is_match_owner or $this->b_user_is_match_admin)
		{
			$this->a_validators = array_merge($this->a_validators, $this->fixture_editor->GetValidators());
		}

		if ($this->b_user_is_match_admin)
		{
			$this->AddValidator(new NumericValidator('season', 'The season identifier should be a number'));
			$this->a_validators = array_merge($this->a_validators, $this->season_editor->GetValidators());
		}

		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'Result', 'The result identifier should be a number'));

		require_once('data/validation/mutually-exclusive-validator.class.php');
		$not_cancelled = array(0,-1,-2,-3,-4,-5,-6,-7,-8,-9,5);
		$this->AddValidator(new MutuallyExclusiveValidator(array($this->GetNamingPrefix() . 'BatFirst', $this->GetNamingPrefix() . 'Result'), "Select who batted first or why the match wasn't played, not both", array("", $not_cancelled)));
	}
}
?>