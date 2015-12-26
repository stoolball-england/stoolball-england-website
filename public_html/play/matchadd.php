<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('data/data-edit-repeater.class.php');
require_once('stoolball/match-fixture-edit-control.class.php');
require_once('stoolball/match-list-control.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/season-manager.class.php');
require_once('stoolball/ground-manager.class.php');

class CurrentPage extends StoolballPage
{
	private $i_season_id;
	private $b_saved;
	private $i_match_type;

	/**
	 * The season to which a match is being added
	 *
	 * @var Season
	 */
	private $season;

	/**
	 * Saved match
	 *
	 * @var Match
	 */
	private $match;

	/**
	 * Editing control for the match details
	 *
	 * @var MatchFixtureEditControl
	 */
	private $edit;

	/**
	 * Team for which a match is being added
	 *
	 * @var Team
	 */
	private $team;

	function OnSiteInit()
	{
		parent::OnSiteInit();

		# check parameter
		if (isset($_GET['season']) and is_numeric($_GET['season']))
		{
			$this->i_season_id = (int)$_GET['season'];
		}
		else if (isset($_POST['Season']) and is_numeric($_POST['Season']))
		{
			$this->i_season_id = (int)$_POST['Season'];
		}


		if (isset($_GET['type']) and is_numeric($_GET['type']))
		{
			$this->i_match_type = (int)$_GET['type'];
		}
		else if (isset($_POST['MatchType']) and is_numeric($_POST['MatchType']))
		{
			$this->i_match_type = (int)$_POST['MatchType'];
		}
		else
		{
			$this->i_match_type = MatchType::FRIENDLY;
		}

		if (isset($_GET['team']) and is_numeric($_GET['team']))
		{
			$this->team = new Team($this->GetSettings());
			$this->team->SetId($_GET['team']);
		}
		else if (isset($_POST['team']) and is_numeric($_POST['team']))
		{
			$this->team = new Team($this->GetSettings());
			$this->team->SetId($_POST['team']);
		}

		if (!isset($this->i_season_id) and !$this->team instanceof Team) $this->Abort();
	}

	public function OnPageInit()
	{
		# create edit control
		if ($this->team instanceof Team)
		{
			$match = new Match($this->GetSettings());
			$match->SetHomeTeam($this->team); # Createa match specifying requested team as the home team, so that it gets selected by default
			$this->edit = new MatchFixtureEditControl($this->GetSettings(), $match);
		}
		else
		{
			$this->edit = new MatchFixtureEditControl($this->GetSettings());
		}
		$this->edit->SetMatchType($this->i_match_type);
		$this->RegisterControlForValidation($this->edit);
	}

	function OnLoadPageData()
	{
		/* @var $o_last_match Match */
		/* @var $season Season */
		/* @var $team Team */

		# new data manager
		$o_match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# Collect season to add this match to, starting with the URL

		# get season and teams (was at this stage because editor needed teams to build its
		# posted data object, but that's no longer the case so probably could be later if needed)
		if (isset($this->i_season_id))
		{
            $season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
            $season_manager->ReadById(array($this->i_season_id));
            $this->season = $season_manager->GetFirst();
            unset($season_manager);
                
			$this->edit->Seasons()->Add($this->season);

			# If there are at least 2 teams in the season, show only those teams, otherwise show all teams of the relevant player type
			if (count($this->season->GetTeams()) > 1)
			{
				$this->edit->SetTeams(array($this->season->GetTeams()));
			}
			else
			{
				require_once('stoolball/team-manager.class.php');
				$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
				$team_manager->FilterByPlayerType(array($this->season->GetCompetition()->GetPlayerType()));
				$team_manager->ReadTeamSummaries();
				$this->edit->SetTeams(array($team_manager->GetItems()));
				unset($team_manager);
			}
		}

		# Not elseif, because when you've added a match there's a season, but we still need this to run to populate
		# the choices for the next match to be added
		if ($this->team instanceof Team)
		{
			# Otherwise it should be a team.

			# Get more information about the team itself
			require_once('stoolball/team-manager.class.php');
			$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
			$team_manager->ReadById(array($this->team->GetId()));
			$this->team = $team_manager->GetFirst();

			if (!is_null($this->team))
			{
				$this->edit->SetContextTeam($this->team);

				$season_ids = array();
				$team_groups = array();
				$a_exclude_team_ids = array();
				$team_manager->FilterByActive(true);

				# Add the home team first
				$team_groups[] = array($this->team);
				$a_exclude_team_ids[] = $this->team->GetId();

				# Get the seasons this team is in...
				$season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
				if ($this->i_match_type == MatchType::FRIENDLY)
				{
					# For a friendly, any group of teams they play with is fine
					$season_manager->ReadCurrentSeasonsByTeamId(array($this->team->GetId()));
				}
				else
				{
					# For anything else, get the seasons *for this type of match*
					$season_manager->ReadCurrentSeasonsByTeamId(array($this->team->GetId()), array($this->i_match_type));
				}
				$seasons = $season_manager->GetItems();
				unset($season_manager);

				$this->edit->Seasons()->Clear(); # on postback, the season just added is already there, so clear to prevent a duplicate
				foreach ($seasons as $season)
				{
					$this->edit->Seasons()->Add($season);
					$season_ids[] = $season->GetId();
				}

				#... and their opponent teams in those seasons
				if (count($season_ids))
				{
					$team_manager->FilterExceptTeams($a_exclude_team_ids);
					$team_manager->ReadBySeasonId($season_ids);
					$season_teams = $team_manager->GetItems();
					if (count($season_teams)) $team_groups['This season\'s teams'] = $season_teams;
					foreach ($season_teams as $team) $a_exclude_team_ids[] = $team->GetId();
				}

				# ...and if this is a friendly it could be any other team
				if ($this->i_match_type == MatchType::FRIENDLY)
				{
					# get any other teams they played in the last 2 years, and combine with existing results
					$team_manager->FilterExceptTeams($a_exclude_team_ids);
					$team_manager->ReadRecentOpponents(array($this->team->GetId()), 24);
					$recent_opponents = $team_manager->GetItems();
					if (count($recent_opponents)) $team_groups['Recent opponents'] = $recent_opponents;
					foreach ($recent_opponents as $team) $a_exclude_team_ids[] = $team->GetId();

					# get any other teams they might play, and combine with existing results
					$team_manager->FilterExceptTeams($a_exclude_team_ids);
					$team_manager->ReadAll();
					$team_groups['Other teams'] = $team_manager->GetItems();
				}

				# What if there are still no opponents to choose from? In that case select all teams.
				if (count($team_groups) == 1)
				{
					$team_manager->FilterExceptTeams($a_exclude_team_ids);
					$team_manager->ReadAll();
					$team_groups[] = $team_manager->GetItems();
				}

				# Offer those teams to select from
				if ($total_groups = count($team_groups))
				{
					# If only two groups (home team + 1 group), don't group teams. Remove the only key from the array.
					if ($total_groups == 2)
					{
						$keys = array_keys($team_groups);
						$team_groups = array($team_groups[$keys[0]], $team_groups[$keys[1]]);
					}
					$this->edit->SetTeams($team_groups);
				}
			}

			unset($team_manager);
		}

		# Save match
		if ($this->IsPostback() and $this->IsValid())
		{
			# Get posted match
			$this->match = $this->edit->GetDataObject();

			if (!$this->IsRefresh())
			{
				# Save match
				$o_match_manager->SaveFixture($this->match);
				$o_match_manager->SaveSeasons($this->match, true);
				$o_match_manager->NotifyMatchModerator($this->match->GetId());

				# Update 'next 5 matches'
				$o_match_manager->ReadNext();
				$this->a_next_matches = $o_match_manager->GetItems();
            }

			# Reset control for new match
			$this->edit->SetDataObject(new Match($this->GetSettings()));
		}

		$o_match_manager->FilterByMatchType(array($this->i_match_type));

		if (isset($this->i_season_id))
		{
			# If we're adding a match to a season, get last game in season for its date
			$o_match_manager->ReadLastInSeason($this->season->GetId());
		}
		else if ($this->team instanceof Team)
		{
			# Get the last game already scheduled for the team to use its date
			$o_match_manager->ReadLastForTeam($this->team->GetId());
		}

		$o_last_match = $o_match_manager->GetFirst();
		if (is_object($o_last_match))
		{
			$current_season = Season::SeasonDates();
			if (gmdate('Y', $o_last_match->GetStartTime()) < gmdate('Y', $current_season[0]))
			{
				# If the last match this team played was last season, use the time but not the date
				$this->edit->SetDefaultTime(gmmktime(gmdate('H', $o_last_match->GetStartTime()), gmdate('i', $o_last_match->GetStartTime()), 0, gmdate('m'), gmdate('d'), gmdate('Y')));
			}
			else
			{
				# If the last match was this season and has a time, use it
				if ($o_last_match->GetIsStartTimeKnown())
				{
					$this->edit->SetDefaultTime($o_last_match->GetStartTime());
				}
				else
				{
					# If the last match has no time, use 6.30pm BST
					$this->edit->SetDefaultTime(gmmktime(17, 30, 0, gmdate('m', $o_last_match->GetStartTime()), gmdate('d', $o_last_match->GetStartTime()), gmdate('Y', $o_last_match->GetStartTime())));
				}
			}
		}
		unset($o_match_manager);

		# Get grounds
		$o_ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$o_ground_manager->ReadAll();
		$a_grounds = $o_ground_manager->GetItems();
		$this->edit->SetGrounds($a_grounds);
		unset($o_ground_manager);
	}

	function Abort()
	{
		header('Location: /matches/');
		exit();
	}

	function OnPrePageLoad()
	{
        if (isset($this->i_season_id))
		{
			$this->SetPageTitle('New ' . MatchType::Text($this->i_match_type) . ' in ' . $this->season->GetCompetitionName());
		}
		else if($this->team instanceof Team)
		{
			$this->SetPageTitle('New '. MatchType::Text($this->i_match_type) . ' for ' . $this->team->GetName());
		}

		$this->SetContentConstraint(StoolballPage::ConstrainText());
		$this->SetContentCssClass('matchEdit');
		$this->LoadClientScript('/scripts/match-fixture-edit-control-5.js');
	}

	function OnPageLoad()
	{
		echo '<h1>' . htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false) . '</h1>';

		if (is_object($this->match))
		{
			echo "<p>Thank you. You have added the following match:</p>";
			echo new MatchListControl(array($this->match));
			echo "<p>Don't worry if you made a mistake, just <a href=\"" . htmlentities($this->match->GetEditNavigateUrl(), ENT_QUOTES, "UTF-8", false) . '">edit this match</a> 
			      or <a href="' . htmlentities($this->match->GetDeleteNavigateUrl(), ENT_QUOTES, "UTF-8", false) . '">delete this match</a>.</p>';
			$this->edit->SetHeading('Add another {0}');

			# Configure edit control
			$this->edit->SetCssClass('panel addAnotherPanel');
		}
		else
		{
            /* Create instruction panel */
			$o_panel_inner2 = new XhtmlElement('div');
			$o_panel_inner1 = new XhtmlElement('div', $o_panel_inner2);
			$o_panel = new XhtmlElement('div', $o_panel_inner1);
			$o_panel->SetCssClass('panel instructionPanel large');

			$o_title_inner3 = new XhtmlElement('span', 'Add your matches quickly:');
			$o_title_inner2 = new XhtmlElement('span', $o_title_inner3);
			$o_title_inner1 = new XhtmlElement('span', $o_title_inner2);
			$o_title = new XhtmlElement('h2', $o_title_inner1);
			$o_panel_inner2->AddControl($o_title);

			$o_tab_tip = new XhtmlElement('ul');
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Use the <span class="tab">tab</span> key on your keyboard to move through the form'));
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Type the first letter or number to select from a dropdown list'));
			if ($this->i_match_type != MatchType::TOURNAMENT_MATCH)
			{
				$o_tab_tip->AddControl(new XhtmlElement('li', 'If you\'re not sure when the match starts, leave the time blank'));
			}
			$o_panel_inner2->AddControl($o_tab_tip);
			echo $o_panel;
		
			# Configure edit control
			$this->edit->SetCssClass('panel');
		}

		# display the match to edit
		echo $this->edit;

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ADD_MATCH, false);
?>