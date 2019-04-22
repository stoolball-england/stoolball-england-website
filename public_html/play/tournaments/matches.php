<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/tournaments/tournament-matches-control.class.php');

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
	 * @var TournamentMatchControl
	 */
	private $editor;

	/**
	 * Data manager for the match
	 *
	 * @var MatchManager
	 */
	private $match_manager;

	private $b_is_tournament = false;

	function OnPageInit()
	{
    	# new data managers
		$this->match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# new edit control
		$this->editor = new TournamentMatchesControl($this->GetSettings(), $this->GetCsrfToken());
		$this->editor->SetCssClass('panel');
		$this->RegisterControlForValidation($this->editor);
        
		# run template method
		parent::OnPageInit();
	}

	/**
	 * For postbacks, allow session writes beyond the usual point so that SaveFixture() can assign permissions to the current user
	 */
	protected function SessionWriteClosing()
	{
		return !$this->IsPostback();
	}

	function OnPostback()
	{
        # get object
        $this->tournament = $this->editor->GetDataObject();

		if ($this->editor->GetDataObjectId())
		{
			# Because this is a new request we need to reverify security details before letting anything happen.
			# Can't trust that info from a postback so MUST go to the database to check it.
			$this->match_manager->ReadByMatchId(array($this->editor->GetDataObjectId()));
			$check_match = $this->match_manager->GetFirst();
			
			if ($check_match instanceof Match) {
			    
                # This page is only for tournaments, so check that against the db 
                $this->b_is_tournament = ($check_match->GetMatchType() == MatchType::TOURNAMENT);
    
                /* The editor requires the teams in the tournament to populate the dropdown and to come up with a
                 * match title, and it doesn't have them when recreating the tournament from the postback data. 
                 * Since we have $check_match available from the database anyway, get the teams from there. */
                foreach ($check_match->GetAwayTeams() as $team) {
                    $this->tournament->AddAwayTeam($team);
                }
			}
		}
		else
		{
			# Error for there being no match id on postback,
			$this->b_is_tournament = false;
		}

		# Check whether cancel was clicked
		if ($this->editor->CancelClicked())
		{
            $this->match_manager->ExpandMatchUrl($this->tournament);
            $this->Redirect($this->tournament->GetNavigateUrl());
		}

		# save data if valid
		if($this->IsValid())
		{
			# Confirm match is being saved as a tournament
			$this->b_is_tournament = $this->b_is_tournament and ($this->tournament->GetMatchType() == MatchType::TOURNAMENT);

			# Check that the requester has permission to update this match
			if ($this->b_is_tournament)
			{   
                # Save the matches in the tournament
                $this->match_manager->SaveMatchesInTournament($this->tournament, $this->GetAuthenticationManager());
                $this->match_manager->NotifyMatchModerator($this->tournament->GetId());
                $this->match_manager->ExpandMatchUrl($this->tournament);
				
				http_response_code(303);
                $this->Redirect($this->tournament->GetNavigateUrl());
			}
		}
		
		# Safe to end session writes now
		session_write_close();
	}

	function OnLoadPageData()
	{
		/* @var $match_manager MatchManager */
		/* @var $editor TournamentMatchesControl */

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
            $action = "Edit ";
        	$this->SetPageTitle($action . $this->tournament->GetTitle() . ', ' . Date::BritishDate($this->tournament->GetStartTime()));
		}
		else
		{
			$this->SetPageTitle('Page not found');
		}

		$this->SetContentCssClass('matchEdit');
		$this->SetContentConstraint(StoolballPage::ConstrainText());
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
		$this->editor->SetDataObject($this->tournament);
		echo $this->editor;

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::EDIT_MATCH, false);
?>