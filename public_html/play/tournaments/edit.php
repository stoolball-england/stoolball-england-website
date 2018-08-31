<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/tournaments/tournament-edit-control.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * Tournament to add/edit
	 *
	 * @var Match
	 */
	private $tournament;

	/**
	 * Editor for the match
	 *
	 * @var TournamentEditControl
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

	function OnPageInit()
	{
    	# new data managers
		$this->match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# new edit control
		$this->editor = new TournamentEditControl($this->GetSettings());
		$this->editor->SetCssClass('panel');
		$this->RegisterControlForValidation($this->editor);

		# check permissions
		$this->b_user_is_match_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);

		# run template method
		parent::OnPageInit();
	}

	function OnPostback()
	{
		if ($this->editor->GetDataObjectId())
		{

			# Because this is a new request we need to reverify that this is the match owner before letting anything happen.
			# Can't trust that info from a postback so MUST go to the database to check it.
			$this->match_manager->ReadByMatchId(array($this->editor->GetDataObjectId()));
			$check_match = $this->match_manager->GetFirst();
			$this->b_user_is_match_owner = ($check_match instanceof Match and AuthenticationManager::GetUser()->GetId() == $check_match->GetAddedBy()->GetId());

			# This page is only for tournaments, so check that against the db too
			$this->b_is_tournament = ($check_match->GetMatchType() == MatchType::TOURNAMENT);
		}
		else
		{
			# Not an entirely correct error for there being no match id on postback,
			# but no match = not the owner, and most importantly it will prevent the
			# match being saved or the edit form being shown.
			$this->b_user_is_match_owner = false;
		}

		# get object
		$this->tournament = $this->editor->GetDataObject();

		# Check whether cancel was clicked
		if ($this->editor->CancelClicked())
		{
			# Show the tournament
			$this->match_manager->ExpandMatchUrl($this->tournament);
			$this->Redirect($this->tournament->GetNavigateUrl());
		}

		# save data if valid
		if($this->IsValid())
		{
			# Confirm match is being saved as a tournament
			$this->b_is_tournament = $this->b_is_tournament and ($this->tournament->GetMatchType() == MatchType::TOURNAMENT);

			# Check that the requester has permission to update this match
			if (($this->b_user_is_match_admin or $this->b_user_is_match_owner) and $this->b_is_tournament)
			{
				# Save match
				$this->match_manager->SaveFixture($this->tournament);
                $this->match_manager->NotifyMatchModerator($this->tournament->GetId());
                http_response_code(303);
                $this->Redirect($this->tournament->GetNavigateUrl());
			}
        }
	}

	function OnLoadPageData()
	{
		/* @var $match_manager MatchManager */
		/* @var $editor TournamentEditControl */

		# get id of Match
		$i_id = $this->editor->GetDataObjectId();
        
       if (!$i_id) return ;
    
		# no need to read if redisplaying invalid form
		if ($this->IsValid())
		{
			$this->match_manager->ReadByMatchId(array($i_id));
			$this->tournament = $this->match_manager->GetFirst();
			if ($this->tournament instanceof Match)
			{
				$this->b_user_is_match_owner = (AuthenticationManager::GetUser()->GetId() == $this->tournament->GetAddedBy()->GetId());
				$this->b_is_tournament = ($this->tournament->GetMatchType() == MatchType::TOURNAMENT);
			} else {
               return ;
			}
		}


		if ($this->b_user_is_match_admin or $this->b_user_is_match_owner)
		{
			# get all grounds
			require_once('stoolball/ground-manager.class.php');
			$o_ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
			$o_ground_manager->ReadAll();
			$this->editor->Grounds()->SetItems($o_ground_manager->GetItems());
			unset($o_ground_manager);

			# get teams in seasons, in order to promote home grounds of teams
			$season_ids = array();
			foreach ($this->tournament->Seasons() as $season) $season_ids[] = $season->GetId();
			require_once('stoolball/team-manager.class.php');
			$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
			if (count($season_ids))
			{
				$team_manager->ReadBySeasonId($season_ids);
				$this->editor->ProbableTeams()->SetItems($team_manager->GetItems());
			}
			unset($team_manager);
		}
	}

	function OnPrePageLoad()
	{
		/* @var $match Match */

		# set page title
		if ($this->tournament instanceof Match)
		{
			$this->SetPageTitle('Edit ' . $this->tournament->GetTitle() . ', ' . Date::BritishDate($this->tournament->GetStartTime()));
		}
		else
		{
			$this->SetPageTitle('Page not found');
		}

		$this->SetContentCssClass('matchEdit');
		$this->SetContentConstraint(StoolballPage::ConstrainText());
		$this->LoadClientScript('/scripts/tournament-edit-control-3.js');
  	}

	function OnPageLoad()
	{
		if (!$this->tournament instanceof Match or !$this->b_is_tournament)
		{
           http_response_code(404);
           require_once($_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/stoolball/section-404.php");
			return ;
		}

        echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

		# check permission
		if (!$this->b_user_is_match_admin and !$this->b_user_is_match_owner)
		{
			?>
<p>Sorry, you can't edit a tournament unless you added it.</p>
<p><a href="<?php echo Html::Encode($this->tournament->GetNavigateUrl()) ?>">Go back
to tournament</a></p>
			<?php
			return;
		}

		# OK to edit the match
		$o_match = (is_object($this->tournament)) ? $this->tournament : new Match($this->GetSettings());
		$this->editor->SetDataObject($o_match);
		echo $this->editor;

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::EDIT_MATCH, false);
?>