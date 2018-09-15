<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/tournaments/tournament-teams-control.class.php');

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
    private $adding = false;

	function OnPageInit()
	{
        $this->adding = (isset($_GET['action']) and $_GET['action'] === "add");

    	# new data managers
		$this->match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# new edit control
		$this->editor = new TournamentTeamsControl($this->GetSettings());
		$this->editor->SetCssClass('panel');
        $this->editor->SetShowStepNumber($this->adding);
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
			$this->b_user_is_match_owner = ($check_match instanceof Match and $check_match->GetAddedBy() instanceof User and AuthenticationManager::GetUser()->GetId() == $check_match->GetAddedBy()->GetId());

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
            $this->match_manager->ExpandMatchUrl($this->tournament);
		    if ($this->adding) {
		        http_response_code(303);
                $this->Redirect($this->tournament->GetDeleteNavigateUrl());
		    } else {
                # Show the tournament
                $this->Redirect($this->tournament->GetNavigateUrl());
		    }
		}

		# save data if valid
		if($this->IsValid())
		{
			# Confirm match is being saved as a tournament
			$this->b_is_tournament = $this->b_is_tournament and ($this->tournament->GetMatchType() == MatchType::TOURNAMENT);

			# Check that the requester has permission to update this match
			if (($this->b_user_is_match_admin or $this->b_user_is_match_owner) and $this->b_is_tournament)
			{
                # Save the teams in the tournament
                $this->match_manager->SaveTeams($this->tournament);
                $this->match_manager->NotifyMatchModerator($this->tournament->GetId());
                $this->match_manager->ExpandMatchUrl($this->tournament);
                http_response_code(303);
                
                if ($this->adding) {
                    $this->Redirect($this->tournament->AddTournamentCompetitionsUrl());
                } else {
                    $this->Redirect($this->tournament->GetNavigateUrl());
                }
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
				$this->b_user_is_match_owner = ($this->tournament->GetAddedBy() instanceof User and AuthenticationManager::GetUser()->GetId() == $this->tournament->GetAddedBy()->GetId());
				$this->b_is_tournament = ($this->tournament->GetMatchType() == MatchType::TOURNAMENT);
			} else {
               return ;
			}
		}
	}

	function OnPrePageLoad()
	{
		/* @var $match Match */

		# set page title
		if ($this->tournament instanceof Match)
		{
            $action = $this->adding ? "Add " : "Edit ";
        	$this->SetPageTitle($action . $this->tournament->GetTitle() . ', ' . Date::BritishDate($this->tournament->GetStartTime()));
		}
		else
		{
			$this->SetPageTitle('Page not found');
		}

		$this->SetContentCssClass('matchEdit');
		$this->SetContentConstraint(StoolballPage::ConstrainText());
        
        $except = array();
        if ($this->tournament instanceof Match)
        {
            foreach ($this->tournament->GetAwayTeams() as $team) 
            {
                $except[] = $team->GetId();
            }
        } 

        $this->LoadClientScript("/scripts/lib/jquery-ui-1.8.11.custom.min.js");
        $this->LoadClientScript("/play/teams/suggest-teams.js.php?except=" . trim(implode(",", $except),","));
        ?>
        <link rel="stylesheet" type="text/css" href="/css/custom-theme/jquery-ui-1.8.11.custom.css" media="all" />
        <?php
	}

	function OnPageLoad()
	{
		if (!$this->tournament instanceof Match or !$this->b_is_tournament)
		{
           http_response_code(404);
           require_once($_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/stoolball/section-404.php");
			return ;
		}

        echo new XhtmlElement('h1', htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false));

		# check permission
		if (!$this->b_user_is_match_admin and !$this->b_user_is_match_owner)
		{
			?>
<p>Sorry, you can't edit a tournament unless you added it.</p>
<p><a href="<?php echo htmlentities($this->tournament->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) ?>">Go back
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