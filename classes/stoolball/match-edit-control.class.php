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
		switch($this->GetCurrentPage())
		{
			case MatchEditControl::FIXTURE:

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
				break;

			case MatchEditControl::FIRST_INNINGS:
				break;

			case MatchEditControl::SECOND_INNINGS:
				break;
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
		switch($this->GetCurrentPage())
		{
			case MatchEditControl::FIXTURE:
				$match = $this->BuildPostedFixture();
				break;

			case MatchEditControl::FIRST_INNINGS:
				$match = $this->BuildPostedScorecard();
				break;

			case MatchEditControl::SECOND_INNINGS:
				$match = $this->BuildPostedScorecard();
				break;
		}


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
		
		# Get who batted first
		$s_key = $this->GetNamingPrefix() . 'BatFirst';
		if (isset($_POST[$s_key]))
		{
			$s_batted = $_POST[$s_key];
			if ($s_batted == 'home')
			{
				$match->Result()->SetHomeBattedFirst(true);
			}
			else if ($s_batted == 'away')
			{
				$match->Result()->SetHomeBattedFirst(false);
			}
		}

		# Get the result
		$s_key = $this->GetNamingPrefix() . 'Result';
		if (isset($_POST[$s_key]))
		{
			$s_result = $_POST[$s_key];
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
		$s_key = $this->GetNamingPrefix() . 'ShortUrl';
		if (isset($_POST[$s_key])) $match->SetShortUrl($_POST[$s_key]);

		return $match;
	}

	/**
	 * Builds data posted on pages 2/3 back into a match object
	 * @return Match
	 */
	private function BuildPostedScorecard()
	{
		$match = new Match($this->GetSettings());
		$match->SetId($this->GetDataObjectId());

		# Must have data on which team is which, otherwise none of the rest makes sense
		# Team ids are essential for saving data, while team names and match title are
		# purely so they can be redisplayed if the page is invalid
		$key = "teams";
		if (!isset($_POST[$key]) or (strpos($_POST[$key], MatchEditControl::DATA_SEPARATOR) === false)) return $match;

		$teams = explode(MatchEditControl::DATA_SEPARATOR,$_POST[$key],6);
		if (count($teams) != 6) return $match;

		switch ($teams[0])
		{
			case "0":
				$match->Result()->SetHomeBattedFirst(false);
				$home_batting = ($this->GetCurrentPage() == 3);
				break;
			case "1":
				$match->Result()->SetHomeBattedFirst(true);
				$home_batting = ($this->GetCurrentPage() == 2);
				break;
			default:
				$home_batting = ($this->GetCurrentPage() == 2);
		}

		$home_team = new Team($this->GetSettings());
		$home_team->SetId($teams[1]);
		$home_team->SetName($teams[2]);
		$match->SetHomeTeam($home_team);

		$away_team = new Team($this->GetSettings());
		$away_team->SetId($teams[3]);
		$away_team->SetName($teams[4]);
		$match->SetAwayTeam($away_team);

		$match->SetTitle($teams[5]);

		# Read posted batting data
		$i = 1;
		$key = "batName$i";
		while (isset($_POST[$key]))
		{
			# This will increment up to the number previously posted, which means the same number of boxes
			# will be redisplayed on an invalid postback
			$match->SetMaximumPlayersPerTeam($i);

			# The row exists - has it been filled in?
			if (trim($_POST[$key]))
			{
				# Read the batter data in this row
				$player = new Player($this->GetSettings());
				$player->SetName($_POST[$key]);
				$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

				$key = "batHowOut$i";
				$how_out = (isset($_POST[$key]) and is_numeric($_POST[$key])) ? (int)$_POST[$key] : null;

				$key = "batOutBy$i";
				$dismissed_by = null;
				if (isset($_POST[$key]) and trim($_POST[$key]))
				{
					$dismissed_by = new Player($this->GetSettings());
					$dismissed_by->SetName($_POST[$key]);
					$dismissed_by->Team()->SetId($home_batting ? $away_team->GetId() : $home_team->GetId());
				}

				$key = "batBowledBy$i";
				$bowler = null;
				if (isset($_POST[$key]) and trim($_POST[$key]))
				{
					$bowler = new Player($this->GetSettings());
					$bowler->SetName($_POST[$key]);
					$bowler->Team()->SetId($home_batting ? $away_team->GetId() : $home_team->GetId());
				}

				# Correct caught and bowled if marked as caught
				if ($how_out == Batting::CAUGHT and !is_null($dismissed_by) and !is_null($bowler) and trim($dismissed_by->GetName()) == trim($bowler->GetName()))
				{
					$how_out = Batting::CAUGHT_AND_BOWLED;
					$dismissed_by = null;
				}

				$key = "batRuns$i";
				$runs = (isset($_POST[$key]) and is_numeric($_POST[$key])) ? (int)$_POST[$key] : null;

				# Add that batting performance to the match result
				$batting = new Batting($player, $how_out, $dismissed_by, $bowler, $runs);
				if ($home_batting)
				{
					$match->Result()->HomeBatting()->Add($batting);
				}
				else
				{
					$match->Result()->AwayBatting()->Add($batting);
				}
			}

			# Get ready to check for another row
			$i++;
			$key = "batName$i";
		}

		$key = "batByes";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::BYES);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batWides";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::WIDES);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batNoBalls";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::NO_BALLS);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batBonus";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::BONUS_RUNS);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batTotal";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			if ($home_batting)
			{
				$match->Result()->SetHomeRuns($_POST[$key]);
			}
			else
			{
				$match->Result()->SetAwayRuns($_POST[$key]);
			}
		}

		$key = "batWickets";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			if ($home_batting)
			{
				$match->Result()->SetHomeWickets($_POST[$key]);
			}
			else
			{
				$match->Result()->SetAwayWickets($_POST[$key]);
			}
		}

		# Read posted bowling data
		$i = 1;
		$key = "bowlerName$i";
		while (isset($_POST[$key]))
		{
			# This will increment up to the number previously posted, which means the same number of boxes
			# will be redisplayed on an invalid postback
			$match->SetOvers($i);

			# The row exists - has it been filled in?
			if (trim($_POST[$key]))
			{
				# Read the bowler data in this row
				# strlen test allows 0 but not empty string, because is_numeric allows empty string
				$player = new Player($this->GetSettings());
				$player->SetName($_POST[$key]);
				$player->Team()->SetId($home_batting ? $away_team->GetId() : $home_team->GetId());

				$key = "bowlerBalls$i";
				$balls = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				$key = "bowlerNoBalls$i";
				$no_balls = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				$key = "bowlerWides$i";
				$wides = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				$key = "bowlerRuns$i";
				$runs = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				# Add that over to the match result
				$bowling = new Over($player);
				$bowling->SetBalls($balls);
				$bowling->SetNoBalls($no_balls);
				$bowling->SetWides($wides);
				$bowling->SetRunsInOver($runs);
				if ($home_batting)
				{
					$match->Result()->AwayOvers()->Add($bowling);
				}
				else
				{
					$match->Result()->HomeOvers()->Add($bowling);
				}
			}

			# Get ready to check for another row
			$i++;
			$key = "bowlerName$i";
		}

		return $match;
	}

	function CreateControls()
	{
		$match = $this->GetDataObject();
		/* @var $match Match */
		$this->b_user_is_match_owner = ($match->GetAddedBy() instanceof User and AuthenticationManager::GetUser()->GetId() == $match->GetAddedBy()->GetId());

		switch($this->GetCurrentPage())
		{
			case MatchEditControl::FIXTURE:
				$this->CreateFixtureControls($match);
				break;

			case MatchEditControl::FIRST_INNINGS:
				if ($match->Result()->GetHomeBattedFirst() === false)
				{
					$this->CreateScorecardControls($match, $match->GetAwayTeam(), $match->Result()->AwayBatting(), $match->GetHomeTeam(), $match->Result()->HomeOvers(), $match->Result()->GetAwayRuns(), $match->Result()->GetAwayWickets());
				}
				else
				{
					$this->CreateScorecardControls($match, $match->GetHomeTeam(), $match->Result()->HomeBatting(), $match->GetAwayTeam(), $match->Result()->AwayOvers(), $match->Result()->GetHomeRuns(), $match->Result()->GetHomeWickets());
				}
				break;

			case MatchEditControl::SECOND_INNINGS:
				if ($match->Result()->GetHomeBattedFirst() === false)
				{
					$this->CreateScorecardControls($match, $match->GetHomeTeam(), $match->Result()->HomeBatting(), $match->GetAwayTeam(), $match->Result()->AwayOvers(), $match->Result()->GetHomeRuns(), $match->Result()->GetHomeWickets());
				}
				else
				{
					$this->CreateScorecardControls($match, $match->GetAwayTeam(), $match->Result()->AwayBatting(), $match->GetHomeTeam(), $match->Result()->HomeOvers(), $match->Result()->GetAwayRuns(), $match->Result()->GetAwayWickets());
				}
				break;
		}
	}

	/**
	 * Sets up controls for page 1 of the wizard
	 * @param Match $match
	 * @return void
	 */
	private function CreateFixtureControls(Match $match)
	{
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

			$o_bat_first = new XhtmlSelect($this->GetNamingPrefix() . 'BatFirst');
			$o_bat_first->AddControl(new XhtmlOption("Don't know", ''));
			$o_bat_first->AddControl(new XhtmlOption($got_home_team ? $match->GetHomeTeam()->GetName() : 'Home team', 'home'));
			$o_bat_first->AddControl(new XhtmlOption($got_away_team ? $match->GetAwayTeam()->GetName() : 'Away team', 'away'));

			if (!is_null($match->Result()->GetHomeBattedFirst()))
			{
				if ($match->Result()->GetHomeBattedFirst())
				{
					$o_bat_first->SelectOption('home');
				}
				else
				{
					$o_bat_first->SelectOption('away');
				}
			}
			$o_bat_part = new FormPart('Who batted first?', $o_bat_first);
			$match_box->AddControl($o_bat_part);
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
	 * Sets up controls for pages 2/3 of the wizard
	 * @param Match $match
	 * @param Team $batting_team
	 * @param Collection $batting_data
	 * @param Team $bowling_team
	 * @param Collection $bowling_data
	 * @param int $total
	 * @param int $wickets_taken
	 * @return void
	 */
	private function CreateScorecardControls(Match $match, Team $batting_team, Collection $batting_data, Team $bowling_team, Collection $bowling_data, $total, $wickets_taken)
	{
		require_once("xhtml/tables/xhtml-table.class.php");

		$batting_table = new XhtmlTable();
		$batting_table->SetCaption($batting_team->GetName() . "'s batting");
		$batting_table->SetCssClass("scorecard scorecardEditor batting");

		$score_header = new XhtmlCell(true, "Runs");
		$score_header->SetCssClass("numeric");
		$out_by_header = new XhtmlCell(true, '<span class="small">Fielder</span><span class="large">Caught/run-out by</span>');
		$out_by_header->SetCssClass("dismissedBy");
		$batting_headings = new XhtmlRow(array("Batsman", "How out", $out_by_header, "Bowler", $score_header));
		$batting_headings->SetIsHeader(true);
		$batting_table->AddRow($batting_headings);

		$batting_data->ResetCounter();
		$byes = null;
		$wides = null;
		$no_balls = null;
		$bonus = null;

		# Loop = max players + 4, because if you have a full scorecard you have to keep looping to get the 4 extras players
		for ($i=1; $i <= $match->GetMaximumPlayersPerTeam()+4; $i++)
		{
			$batting = ($batting_data->MoveNext()) ? $batting_data->GetItem() : null;
			/* @var $batting Batting */

			# Grab the scores for extras players to use later
			if (!is_null($batting))
			{
				switch($batting->GetPlayer()->GetPlayerRole())
				{
					case Player::BYES:
						$byes = $batting->GetRuns();
						break;
					case Player::WIDES:
						$wides = $batting->GetRuns();
						break;
					case Player::NO_BALLS:
						$no_balls = $batting->GetRuns();
						break;
					case Player::BONUS_RUNS:
						$bonus = $batting->GetRuns();
						break;
				}
			}

			# Don't write a table row for the last four loops, we'll do that next because they're different
			if ($i <= $match->GetMaximumPlayersPerTeam())
			{
				$player = new TextBox("batName$i", (is_null($batting) or !$batting->GetPlayer()->GetPlayerRole() == Player::PLAYER) ? "" : $batting->GetPlayer()->GetName(), $this->IsValidSubmit());
				$player->SetMaxLength(100);
				$player->AddAttribute("autocomplete", "off");
				$player->AddCssClass("player team" . $batting_team->GetId());

				$how = new XhtmlSelect("batHowOut$i", null, $this->IsValidSubmit());
				$how->SetCssClass("howOut");
				$how->AddOptions(array(Batting::DID_NOT_BAT => Batting::Text(Batting::DID_NOT_BAT),
				Batting::NOT_OUT => Batting::Text(Batting::NOT_OUT),
				Batting::CAUGHT => Batting::Text(Batting::CAUGHT),
				Batting::BOWLED => Batting::Text(Batting::BOWLED),
				Batting::CAUGHT_AND_BOWLED => str_replace(" and ", "/", Batting::Text(Batting::CAUGHT_AND_BOWLED)), # use shorter version to minimise width of dropdown and allow more room for names
				Batting::RUN_OUT => Batting::Text(Batting::RUN_OUT),
				Batting::BODY_BEFORE_WICKET => "bbw", # use shorter version to minimise width of dropdown and allow more room for names
				Batting::HIT_BALL_TWICE => Batting::Text(Batting::HIT_BALL_TWICE),
				Batting::TIMED_OUT => Batting::Text(Batting::TIMED_OUT),
				Batting::RETIRED_HURT => Batting::Text(Batting::RETIRED_HURT),
				Batting::RETIRED => Batting::Text(Batting::RETIRED),
				Batting::UNKNOWN_DISMISSAL => Batting::Text(Batting::UNKNOWN_DISMISSAL)) , null);
				if (!is_null($batting) and $batting->GetPlayer()->GetPlayerRole() == Player::PLAYER and $this->IsValidSubmit()) $how->SelectOption($batting->GetHowOut());

				$out_by = new TextBox("batOutBy$i", (is_null($batting) or is_null($batting->GetDismissedBy()) or !$batting->GetDismissedBy()->GetPlayerRole() == Player::PLAYER) ? "" : $batting->GetDismissedBy()->GetName(), $this->IsValidSubmit());
				$out_by->SetMaxLength(100);
				$out_by->AddAttribute("autocomplete", "off");
				$out_by->AddCssClass("player team" . $bowling_team->GetId());

				$bowled_by = new TextBox("batBowledBy$i", (is_null($batting) or is_null($batting->GetBowler()) or !$batting->GetBowler()->GetPlayerRole() == Player::PLAYER) ? "" : $batting->GetBowler()->GetName(), $this->IsValidSubmit());
				$bowled_by->SetMaxLength(100);
				$bowled_by->AddAttribute("autocomplete", "off");
				$bowled_by->AddCssClass("player team" . $bowling_team->GetId());

				$runs = new TextBox("batRuns$i", (is_null($batting) or $batting->GetPlayer()->GetPlayerRole() != Player::PLAYER) ? "" : $batting->GetRuns(), $this->IsValidSubmit());
				$runs->SetCssClass("numeric runs");
                $runs->AddAttribute("type", "number");
                $runs->AddAttribute("min", "0");
				$runs->AddAttribute("autocomplete", "off");

				$batting_row = new XhtmlRow(array($player, $how, $out_by, $bowled_by, $runs));
                $batting_row->GetFirstCell()->SetCssClass("batsman");
                $batting_table->AddRow($batting_row);
			}
		}

		$batting_table->AddRow($this->CreateExtrasRow("batByes", "Byes", "extras", "numeric runs", $byes));
		$batting_table->AddRow($this->CreateExtrasRow("batWides", "Wides", "extras", "numeric runs", $wides));
		$batting_table->AddRow($this->CreateExtrasRow("batNoBalls", "No balls", "extras", "numeric runs", $no_balls));
		$batting_table->AddRow($this->CreateExtrasRow("batBonus", "Bonus or penalty runs", "extras", "numeric runs", $bonus));
		$batting_table->AddRow($this->CreateExtrasRow("batTotal", "Total", "totals", "numeric", $total));

		$wickets_header = new XhtmlCell(true, "Wickets");
		$wickets_header->SetColumnSpan(4);
		$wickets = new XhtmlSelect("batWickets", null, $this->IsValidSubmit());
		$wickets->SetBlankFirst(true);

		$max_wickets = $match->GetMaximumPlayersPerTeam()-2;
		$season_dates = Season::SeasonDates($match->GetStartTime()); # working with GMT
		if (Date::Year($season_dates[0]) != Date::Year($season_dates[1]))
		{
			# outdoor needs maximum-2, but indoor needs maximum-1 cos last batter can play on.
			# if there's any chance it's indoor use maximum-1
			$max_wickets = $match->GetMaximumPlayersPerTeam()-1;
		}
		for ($i = 0; $i <= $max_wickets; $i++) $wickets->AddControl(new XhtmlOption($i));

		$wickets->AddControl(new XhtmlOption('all out', -1));
		if ($this->IsValidSubmit() and !is_null($wickets_taken)) $wickets->SelectOption($wickets_taken);

		$wickets_row = new XhtmlRow(array($wickets_header, $wickets));
		$wickets_row->SetCssClass("totals");
		$batting_table->AddRow($wickets_row);

		$this->AddControl($batting_table);

		$bowling_table = new XhtmlTable();
		$bowling_table->SetCaption($bowling_team->GetName() . "'s bowling, over-by-over");
		$bowling_table->SetCssClass("scorecard scorecardEditor bowling");

		$over_header = new XhtmlCell(true, 'Balls bowled <span class="qualifier">(excluding extras)</span>');
		$over_header->SetCssClass("numeric balls");
        $wides_header = new XhtmlCell(true, "Wides");
        $wides_header->SetCssClass("numeric");
		$no_balls_header = new XhtmlCell(true, "No balls");
		$no_balls_header->SetCssClass("numeric");
		$runs_header = new XhtmlCell(true, "Over total");
		$runs_header->SetCssClass("numeric");
		$bowling_headings = new XhtmlRow(array("Bowler", $over_header, $wides_header, $no_balls_header, $runs_header));
		$bowling_headings->SetIsHeader(true);
		$bowling_table->AddRow($bowling_headings);

		$bowling_data->ResetCounter();
		for ($i=1; $i <= $match->GetOvers(); $i++)
		{
			$bowling = ($bowling_data->MoveNext()) ? $bowling_data->GetItem() : null;
			/* @var $bowling Over */

			$blank_row = (is_null($bowling) or is_null($bowling->GetOverNumber())); // don't list records generated from batting card to record wickets taken

			$player = new TextBox("bowlerName$i", $blank_row ? "" : $bowling->GetPlayer()->GetName(), $this->IsValidSubmit());
			$player->SetMaxLength(100);
			$player->AddAttribute("autocomplete", "off");
			$player->AddCssClass("player team" . $bowling_team->GetId());

			$balls = new TextBox("bowlerBalls$i", $blank_row ? "" : $bowling->GetBalls(), $this->IsValidSubmit());
			$balls->AddAttribute("autocomplete", "off");
			$balls->AddAttribute("type", "number");
            $balls->AddAttribute("min", "0");
            $balls->AddAttribute("max", "10");
            $balls->SetCssClass("numeric balls");

            $wides = new TextBox("bowlerWides$i", $blank_row ? "" : $bowling->GetWides(), $this->IsValidSubmit());
            $wides->AddAttribute("autocomplete", "off");
            $wides->AddAttribute("type", "number");
            $wides->AddAttribute("min", "0");
            $wides->SetCssClass("numeric wides");

			$no_balls = new TextBox("bowlerNoBalls$i", $blank_row ? "" : $bowling->GetNoBalls(), $this->IsValidSubmit());
			$no_balls->AddAttribute("autocomplete", "off");
            $no_balls->AddAttribute("type", "number");
            $no_balls->AddAttribute("min", "0");
            $no_balls->SetCssClass("numeric no-balls");

			$runs = new TextBox("bowlerRuns$i", $blank_row ? "" : $bowling->GetRunsInOver(), $this->IsValidSubmit());
			$runs->AddAttribute("autocomplete", "off");
            $runs->AddAttribute("type", "number");
            $runs->AddAttribute("min", "0");
            $runs->SetCssClass("numeric runs");

			$bowling_row = new XhtmlRow(array($player, $balls, $wides, $no_balls, $runs));
			$bowling_row->GetFirstCell()->SetCssClass("bowler");
			$bowling_table->AddRow($bowling_row);
		}

		$this->AddControl($bowling_table);

        		
        if ($match->GetLastAudit() != null)
        {
            require_once("data/audit-control.class.php");
            $this->AddControl(new AuditControl($match->GetLastAudit(), "match"));
        }

		$home_batted_first = "";
		if (!is_null($match->Result()->GetHomeBattedFirst())) $home_batted_first = (int)$match->Result()->GetHomeBattedFirst();

		$teams = new TextBox("teams", $home_batted_first . MatchEditControl::DATA_SEPARATOR .
		$match->GetHomeTeamId() . MatchEditControl::DATA_SEPARATOR .
		$match->GetHomeTeam()->GetName() . MatchEditControl::DATA_SEPARATOR .
		$match->GetAwayTeamId() . MatchEditControl::DATA_SEPARATOR .
		$match->GetAwayTeam()->GetName() . MatchEditControl::DATA_SEPARATOR .
		$match->GetTitle());
		$teams->SetMode(TextBoxMode::Hidden());
		$this->AddControl($teams);
	}

	/**
	 * Creates an extras/totals row at the bottom of the batting card
	 * @param string $id
	 * @param string $label
	 * @param string $row_class
	 * @param string $box_class
	 * @param int $value
	 * @return void
	 */
	private function CreateExtrasRow($id, $label, $row_class, $box_class, $value)
	{
		$extras_header = new XhtmlCell(true, $label);
		$extras_header->SetColumnSpan(4);
		$extras = new TextBox($id, $value, $this->IsValidSubmit());
		$extras->AddAttribute("autocomplete", "off");
		$extras->SetCssClass($box_class);
        $extras->AddAttribute("type", "number");
		$extras_row = new XhtmlRow(array($extras_header, $extras));
		$extras_row->SetCssClass($row_class);
		return $extras_row;
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/length-validator.class.php');

		switch($this->GetCurrentPage())
		{
			case MatchEditControl::FIXTURE:

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
				break;

			case MatchEditControl::FIRST_INNINGS:
			case MatchEditControl::SECOND_INNINGS:
				require_once("data/validation/requires-other-fields-validator.class.php");
				require_once('data/validation/numeric-range-validator.class.php');

				# Add per-row batting validators
				$i = 1;
				$key = "batName$i";
				while (isset($_POST[$key]))
				{
					switch ($i % 10)
					{
						case 1:
							$ordinal = $i . "st";
							break;
						case 2:
							$ordinal = $i . "nd";
							break;
						case 3:
							$ordinal = $i . "rd";
							break;
						default:
							$ordinal = $i . "th";
					}
					$this->AddValidator(new LengthValidator("batName$i", "The $ordinal batsman's name must be 100 characters or fewer", 0, 100));
					$this->AddValidator(new LengthValidator("batOutBy$i", "Who caught/ran-out the $ordinal batsman must be 100 characters or fewer", 0, 100));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("batOutBy$i", "batName$i"),"You've said who caught/ran out the $ordinal batsman. Please name the batsman."));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("batOutBy$i", "batHowOut$i"),"You've said who caught/ran out the $ordinal batsman, but they weren't caught or run-out.", array(array(Batting::CAUGHT, Batting::CAUGHT_AND_BOWLED, Batting::RUN_OUT))));
					$this->AddValidator(new LengthValidator("batBowledBy$i", "Who bowled the $ordinal batsman must be 100 characters or fewer", 0, 100));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("batBowledBy$i", "batName$i"),"You've said who bowled to the $ordinal batsman. Please name the batsman."));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("batBowledBy$i", "batHowOut$i"),"You've said who bowled to the $ordinal batsman, but they were 'not out' or didn't bat.", array(array(Batting::CAUGHT, Batting::BOWLED, Batting::CAUGHT_AND_BOWLED, Batting::RUN_OUT, Batting::BODY_BEFORE_WICKET, Batting::HIT_BALL_TWICE, Batting::RETIRED, Batting::RETIRED_HURT))));
					$this->AddValidator(new NumericValidator("batHowOut$i", "How the $ordinal batsman was out should be a number. For example: '3' for 'bowled'."));
					$this->AddValidator(new NumericValidator("batRuns$i", "The $ordinal batsman's runs should be in figures. For example: '50', not 'fifty'."));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("batRuns$i", "batName$i"),"You've added runs for the $ordinal batsman. Please name the batsman."));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("batBowledBy$i", "batHowOut$i"),"You've added runs for the $ordinal batsman, but they were 'not out' or didn't bat.", array(array(Batting::CAUGHT, Batting::BOWLED, Batting::CAUGHT_AND_BOWLED, Batting::RUN_OUT, Batting::BODY_BEFORE_WICKET, Batting::HIT_BALL_TWICE, Batting::RETIRED, Batting::RETIRED_HURT))));

					# Get ready to check for another row
					$i++;
					$key = "batName$i";
				}

				# Add remaining batting validators
				$this->AddValidator(new NumericValidator('batByes', 'Byes should be in figures. For example: \'5\', not \'five\'.'));
				$this->AddValidator(new NumericValidator('batWides', 'Wides should be in figures. For example: \'5\', not \'five\'.'));
				$this->AddValidator(new NumericValidator('batNoBalls', 'No balls should be in figures. For example: \'5\', not \'five\'.'));
				$this->AddValidator(new NumericValidator('batBonus', 'Bonus runs should be in figures. For example: \'5\', not \'five\'.'));
				$this->AddValidator(new NumericValidator('batTotal', 'The total score should be in figures. For example: \'5\', not \'five\'.'));
				$this->AddValidator(new NumericValidator('batWickets', 'Wickets for the innings should be in figures. For example: \'5\', not \'five\'.'));

				# Add per-row bowling validators
				$i = 1;
				$key = "bowlerName$i";
				while (isset($_POST[$key]))
				{
					switch ($i % 10)
					{
						case 1:
							$ordinal = ($i == 11) ? $i . "th" : $i . "st";
							break;
						case 2:
							$ordinal = ($i == 12) ? $i . "th" : $i . "nd";
							break;
						case 3:
							$ordinal = ($i == 13) ? $i . "th" : $i . "rd";
							break;
						default:
							$ordinal = $i . "th";
					}
					$this->AddValidator(new LengthValidator("bowlerName$i", "The $ordinal bowler's name must be 100 characters or fewer", 0, 100));
					$this->AddValidator(new NumericValidator("bowlerBalls$i", "The $ordinal bowler's balls bowled should be in figures. For example: '5', not 'five'."));
					$this->AddValidator(new NumericRangeValidator("bowlerBalls$i", "The $ordinal bowler's balls bowled should be between 1 and 10", 1, 10));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerBalls$i", "bowlerName$i"),"You've added balls bowled for the $ordinal bowler. Please name the bowler."));
					$this->AddValidator(new NumericValidator("bowlerNoBalls$i", "The $ordinal bowler's no balls should be in figures. For example: '5', not 'five'."));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerNoBalls$i", "bowlerName$i"),"You've added no balls for the $ordinal bowler. Please name the bowler."));
					$this->AddValidator(new NumericValidator("bowlerWides$i", "The $ordinal bowler's wides should be in figures. For example: '5', not 'five'."));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerWides$i", "bowlerName$i"),"You've added wides for the $ordinal bowler. Please name the bowler."));
					$this->AddValidator(new NumericValidator("bowlerRuns$i", "The $ordinal bowler's runs should be in figures. For example: '50', not 'fifty'."));
					$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerRuns$i", "bowlerName$i"),"You've added runs for the $ordinal bowler. Please name the bowler."));

					# Get ready to check for another row
					$i++;
					$key = "bowlerName$i";
				}

				break;
		}
	}

	/**
	 * Fixture and result view
	 * @var int
	 */
	const FIXTURE = 1;

	/**
	 * Scorecard for first innings
	 * @var int
	 */
	const FIRST_INNINGS = 2;

	/**
	 * Scorecard for second innings
	 * @var int
	 */
	const SECOND_INNINGS = 3;
}
?>