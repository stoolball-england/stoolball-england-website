<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# Offload editing of tournaments to a separate page to prevent code on this one getting too complex.
require_once('stoolball/match-type.enum.php');
if (isset($_GET['type']) and $_GET['type'] == MatchType::TOURNAMENT and isset($_GET['item']))
{
	require_once($_SERVER['DOCUMENT_ROOT'] . '/play/tournaments/edit.php');
	exit();
}

# include required functions
require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/matches/match-edit-control.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The match to edit
	 *
	 * @var Match
	 */
	private $match;

	/**
	 * Editor for the match
	 *
	 * @var MatchEditControl
	 */
	private $editor;

	/**
	 * Data manager for the match
	 *
	 * @var MatchManager
	 */
	private $match_manager;

	private $b_user_is_match_admin = false;
	private $b_user_is_match_owner = false;
	private $b_is_tournament = false;
    private $page_not_found = false;

	public function OnPageInit()
	{
		# new data managers
		$this->match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# new edit control
		$this->editor = new MatchEditControl($this->GetSettings());
		$this->RegisterControlForValidation($this->editor);

		# check permissions
		$this->b_user_is_match_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);
	}

	public function OnPostback()
	{
		# If there's no id, ensure no match object is created. Page will then display a "match not found" message.
		# There's a separate page for adding matches, even for admins.
		if (!$this->editor->GetDataObjectId()) return;

		# Get the submitted match
		$this->match = $this->editor->GetDataObject();
		$check_match = $this->match;

		# Because this is a new request, if the user isn't admin we need to reverify whether this is the match owner
		# before letting anything happen. Can't trust that info from a postback so MUST go to the database to check it.
		if (!$this->b_user_is_match_admin)
		{
			$this->match_manager->ReadByMatchId(array($this->editor->GetDataObjectId()));
			$check_match = $this->match_manager->GetFirst();
			$this->b_user_is_match_owner = ($check_match instanceof Match and AuthenticationManager::GetUser()->GetId() == $check_match->GetAddedBy()->GetId());
			if ($this->b_user_is_match_owner)
			{
				# Set the owner of the match. This means the edit control knows who the owner is and therefore
				# whether to display the fixture editor on an invalid postback
				$this->match->SetAddedBy(AuthenticationManager::GetUser());
			}
            else {
                # If user is neither admin nor owner, they won't have the team info. Get it from the $check_match so
                # that the match title can be updated correctly with a changed result.
                $this->match->SetHomeTeam($check_match->GetHomeTeam());
                $this->match->SetAwayTeam($check_match->GetAwayTeam());
            }
		}

		# Don't wan't to edit tournaments on this page, so even as admin make sure we're not trying to save one.
		# If user's not admin, can't change the match type, so find out what that is from the db too. For admin,
		# $check_match is the submitted one as the match type might've been changed.
		$this->b_is_tournament = ($check_match->GetMatchType() == MatchType::TOURNAMENT);

		# Check whether cancel was clicked
		if ($this->editor->CancelClicked())
		{
			# If so, get the match's short URL and redirect
			$this->match_manager->ExpandMatchUrl($this->match);
			$this->Redirect($this->match->GetNavigateUrl());
		}

		# save data if valid
		if($this->IsValid() and !$this->b_is_tournament)
		{
			# Check whether the user has permission to update the fixture as well as the result
			if ($this->b_user_is_match_admin or $this->b_user_is_match_owner)
			{
				# Get the ground name from the database. This is used when compiling an email about the updated match result.
				if ($this->match->GetGround() instanceof Ground and $this->match->GetGround()->GetId() and !$this->match->GetGround()->GetName())
				{
					require_once('stoolball/ground-manager.class.php');
					$ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
					$ground_manager->ReadById(array($this->match->GetGround()->GetId()));
					if ($ground_manager->GetCount()) $this->match->SetGround($ground_manager->GetFirst());
					unset($ground_manager);
				}

				$this->match_manager->SaveFixture($this->match);
				if ($this->b_user_is_match_admin) $this->match_manager->SaveSeasons($this->match, false);
				$this->editor->SetNavigateUrl($this->match->GetEditNavigateUrl()); # because edit URL may have changed
			}

			# Save the result
			$this->match_manager->SaveIfPlayed($this->match);
            $this->match_manager->SaveWhoWonTheToss($this->match);
			$this->match_manager->SaveWhoBattedFirst($this->match);
               
			# If match didn't happen or the teams aren't known yet, save and finish, otherwise go to next page
			$result = $this->match->Result()->GetResultType();
			if ($result == MatchResult::HOME_WIN_BY_FORFEIT
			or $result == MatchResult::AWAY_WIN_BY_FORFEIT
			or $result == MatchResult::CANCELLED
			or $result == MatchResult::POSTPONED
			or $check_match->GetStartTime() > gmdate('U')
            or ($this->b_user_is_match_admin and (!$this->match->GetHomeTeamId() or !$this->match->GetAwayTeamId())))
			{
				# Match may have been updated so, first, send an email
				$this->match_manager->NotifyMatchModerator($this->match->GetId());

                http_response_code(303);
				$this->Redirect($this->match->GetNavigateUrl());
			}
			else
			{
			    http_response_code(303);
				$this->Redirect($this->match->EditScorecardUrl());
			}
		}
	}

	function OnLoadPageData()
	{

		/* @var $match_manager MatchManager */
		/* @var $editor MatchEditControl */

		# get id of Match
		$i_id = $this->match_manager->GetItemId();

		if ($i_id) 
        {
    		# Get details of match but, if invalid, don't replace submitted details with saved ones
    		if ($this->IsValid())
    		{
    			$this->match_manager->ReadByMatchId(array($i_id));
                $this->match_manager->ExpandMatchScorecards();
    			$this->match = $this->match_manager->GetFirst();
    			if ($this->match instanceof Match)
    			{
    				$this->b_user_is_match_owner = (AuthenticationManager::GetUser()->GetId() == $this->match->GetAddedBy()->GetId());
    				$this->b_is_tournament = ($this->match->GetMatchType() == MatchType::TOURNAMENT);
    			}
    		}
    		unset($this->match_manager);
    
			# get all competitions if user has permission to change the season
			if ($this->b_user_is_match_admin)
			{
				require_once('stoolball/competition-manager.class.php');
				$o_comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
				$o_comp_manager->ReadAllSummaries();
				$this->editor->SetSeasons(CompetitionManager::GetSeasonsFromCompetitions($o_comp_manager->GetItems()));
				unset($o_comp_manager);

			}

			if ($this->b_user_is_match_admin or $this->b_user_is_match_owner)
			{
				# get all teams
				$season_ids = array();
				if ($this->match instanceof Match) foreach ($this->match->Seasons() as $season) $season_ids[] = $season->GetId();
				require_once('stoolball/team-manager.class.php');
				$o_team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());

                if ($this->match instanceof Match and $this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH)
                {
                    $o_team_manager->FilterByTournament(array($this->match->GetTournament()->GetId()));
                    $o_team_manager->FilterByTeamType(array()); # override default to allow all team types
                    $o_team_manager->ReadTeamSummaries();
                } 
                else if ($this->b_user_is_match_admin or !count($season_ids) or $this->match->GetMatchType() == MatchType::FRIENDLY)
				{
					$o_team_manager->ReadById();# we need full data on the teams to get the seasons they are playing in;
				}
				else
				{
					# If the user can't change the season, why let them select a team that's not in the season?
					$o_team_manager->ReadBySeasonId($season_ids);
				}
				$this->editor->SetTeams(array($o_team_manager->GetItems()));
				unset($o_team_manager);

				# get all grounds
				require_once('stoolball/ground-manager.class.php');
				$o_ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
				$o_ground_manager->ReadAll();
				$this->editor->SetGrounds($o_ground_manager->GetItems());
				unset($o_ground_manager);
			}
   		}

      
        # Tournament or match not found is page not found
        if (!$this->match instanceof Match or 
            $this->b_is_tournament 
            )
        {
            http_response_code(404);
            $this->page_not_found  = true;
        }
	}

	public function OnPrePageLoad()
	{
		/* @var $match Match */
		$this->SetContentConstraint(StoolballPage::ConstrainText());

        if ($this->page_not_found)
        {
            $this->SetPageTitle('Page not found');
            return; # Don't load any JS
        }

		# Set page title
		$edit_or_update = ($this->b_user_is_match_admin or $this->b_user_is_match_owner) ? "Edit" : "Update";
		if ($this->match->GetStartTime() > gmdate('U') and !$this->b_user_is_match_admin and !$this->b_user_is_match_owner)
		{
			$step = ""; # definitely only this step because match in future and can't change date
		}
		else
		{
			$step = ", step 1 of 4";
		}
		$this->SetPageTitle("$edit_or_update " . $this->match->GetTitle() . ', ' . Date::BritishDate($this->match->GetStartTime()) . $step);

		# Load JavaScript
		if ($this->b_user_is_match_admin or $this->b_user_is_match_owner) $this->LoadClientScript('/scripts/match-fixture-edit-control-5.js');
		if ($this->b_user_is_match_admin) $this->LoadClientScript('matchedit-admin-3.js', true);
        $this->LoadClientScript('matchedit.js',true);
	}

	public function OnPageLoad()
	{
       # Matches this page shouldn't edit are page not found
        if ($this->page_not_found)
        {
           require_once($_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/stoolball/section-404.php");
           return;
        }

		$edit_or_update = ($this->b_user_is_match_admin or $this->b_user_is_match_owner) ? "Edit" : "Update";
		if ($this->match->GetStartTime() > gmdate('U') and !$this->b_user_is_match_admin and !$this->b_user_is_match_owner)
		{
			$step = ""; # definitely only this step because match in future and can't change date
		}
		else
		{
			$step = " &#8211; step 1 of 4";
		}

		echo new XhtmlElement('h1', "$edit_or_update " . htmlentities($this->match->GetTitle(), ENT_QUOTES, "UTF-8", false) . $step);

		# If result only there's room for a little help
		if (!$this->b_user_is_match_admin and !$this->b_user_is_match_owner)
		{
			/* Create instruction panel */
			$o_panel = new XhtmlElement('div');
			$o_panel->SetCssClass('panel instructionPanel');

			$o_title_inner1 = new XhtmlElement('div', 'Add your matches quickly:');
			$o_title = new XhtmlElement('h2', $o_title_inner1);
			$o_panel->AddControl($o_title);

			$o_tab_tip = new XhtmlElement('ul');
			$o_tab_tip->AddControl(new XhtmlElement('li', 'You can add runs, wickets and the winning team on the next few pages'));
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Don\'t worry if you don\'t know &#8211; fill in what you can and leave the rest blank.'));
			$o_panel->AddControl($o_tab_tip);
			echo $o_panel;
		}

		# OK to edit the match
		$this->editor->SetDataObject($this->match);
		echo $this->editor;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::EDIT_MATCH, false);
?>