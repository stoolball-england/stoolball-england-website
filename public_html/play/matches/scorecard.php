<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/matches/scorecard-edit-control.class.php');

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
	 * @var ScorecardEditControl
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
		$this->editor = new ScorecardEditControl($this->GetSettings());
        if (!$this->IsPostback()) {
            $this->editor->SetCurrentPage(ScorecardEditControl::FIRST_INNINGS);
        }
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

        # Because this is a new request, we need to reverify whether this is the match owner before letting anything happen. 
        # Can't trust that info from a postback so MUST go to the database to check it.
		$this->match_manager->ReadByMatchId(array($this->editor->GetDataObjectId()));
		$check_match = $this->match_manager->GetFirst();
		$this->b_user_is_match_owner = ($check_match instanceof Match and AuthenticationManager::GetUser()->GetId() == $check_match->GetAddedBy()->GetId());

        # Don't wan't to edit tournaments on this page, so even as admin make sure we're not trying to save one.
        # Can't change the match type, so find out what that is from the db too. 
        $this->b_is_tournament = ($check_match->GetMatchType() == MatchType::TOURNAMENT);

		# Check whether cancel was clicked
		if ($this->editor->CancelClicked())
		{
			# Match may have been updated so send an email
			$this->match_manager->NotifyMatchModerator($this->match->GetId());

			# If so, get the match's short URL and redirect
			$this->match_manager->ExpandMatchUrl($this->match);
			$this->Redirect($this->match->GetNavigateUrl());
		}

		# save data if valid
		if($this->IsValid() and !$this->b_is_tournament)
		{
			switch ($this->editor->GetCurrentPage())
			{
				case ScorecardEditControl::FIRST_INNINGS:

					$this->match_manager->SaveScorecard($this->match, true, $this->SearchIndexer());
					$this->editor->SetCurrentPage(ScorecardEditControl::SECOND_INNINGS);

					break;

				case ScorecardEditControl::SECOND_INNINGS:

					$this->match_manager->SaveScorecard($this->match, false, $this->SearchIndexer());
                    $this->match_manager->ExpandMatchUrl($this->match);
					http_response_code(303);
					$this->Redirect($this->match->EditHighlightsUrl());
					break;
			}
		}
	}

	function OnLoadPageData()
	{

		/* @var $match_manager MatchManager */

		# get id of Match
		$i_id = $this->match_manager->GetItemId();

		# Get details of match but, if invalid, don't replace submitted details with saved ones
		if ($i_id and $this->IsValid())
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
        
        # Tournament or match in the future or not played is page not found
        $editable_results = array(MatchResult::UNKNOWN, MatchResult::HOME_WIN, MatchResult::AWAY_WIN, MatchResult::TIE, MatchResult::ABANDONED);
        if (!$this->match instanceof Match or 
            $this->b_is_tournament or 
            $this->match->GetStartTime() > gmdate('U') 
            or !in_array($this->match->Result()->GetResultType(), $editable_results))
        {
            http_response_code(404);
            $this->page_not_found  = true;
        }
	}

	public function OnPrePageLoad()
	{
		/* @var $match Match */
		$this->SetContentConstraint(StoolballPage::ConstrainText());
        $this->SetContentCssClass('scorecardPage');
        $this->SetContentCssClass('scorecardPage');

        if ($this->page_not_found)
        {
            $this->SetPageTitle('Page not found');
            return; # Don't load any JS
        }

		# Set page title
		$edit_or_update = ($this->b_user_is_match_admin or $this->b_user_is_match_owner) ? "Edit" : "Update";
		$step = ", step " . $this->editor->GetCurrentPage() . " of 4";
		$this->SetPageTitle("$edit_or_update " . $this->match->GetTitle() . ', ' . Date::BritishDate($this->match->GetStartTime()) . $step);

		# Load JavaScript
		$autocomplete_team_ids = array();
		if ($this->match->GetHomeTeamId()) $autocomplete_team_ids[] = $this->match->GetHomeTeamId();
		if ($this->match->GetAwayTeamId()) $autocomplete_team_ids[] = $this->match->GetAwayTeamId();
		if (count($autocomplete_team_ids))
		{
			$this->LoadClientScript("/scripts/lib/jquery-ui-1.8.11.custom.min.js");
			$this->LoadClientScript("/play/playersuggest.js.php?v=2&amp;team=" . implode(",", $autocomplete_team_ids));// . "&amp;time=" . time());
			?>
<link rel="stylesheet" type="text/css" href="/css/custom-theme/jquery-ui-1.8.11.custom.css" media="all" />
			<?php
		}

        $this->LoadClientScript('matchedit-3.js',true);
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
		$step = " &#8211; step " . $this->editor->GetCurrentPage() . " of 4";
		echo new XhtmlElement('h1', "$edit_or_update " . htmlentities($this->match->GetTitle(), ENT_QUOTES, "UTF-8", false) . $step);

		if ($this->IsValid())
		{
			/* Create instruction panel */
			$panel_inner2 = new XhtmlElement('div');
			$panel_inner1 = new XhtmlElement('div', $panel_inner2);
			$panel = new XhtmlElement('div', $panel_inner1);
			$panel->SetCssClass('panel instructionPanel');

			$title_inner3 = new XhtmlElement('span', 'Fill in scorecards quickly:');
			$title_inner2 = new XhtmlElement('span', $title_inner3);
			$title_inner1 = new XhtmlElement('span', $title_inner2);
			$title = new XhtmlElement('h2', $title_inner1, "large");
			$panel_inner2->AddControl($title);

			$tab_tip = new XhtmlElement('ul');
			$tab_tip->AddControl(new XhtmlElement('li', 'Use the <span class="tab">tab</span> and up and down keys to move through the form', "large"));
			$tab_tip->AddControl(new XhtmlElement('li', 'Use the <span class="tab">tab</span> key to select a player\'s name from the suggestions', "large"));
            $tab_tip->AddControl(new XhtmlElement('li', "List everyone on the batting card, even if they didn't bat, so we know who played."));
			$tab_tip->AddControl(new XhtmlElement('li', 'Don\'t worry if you don\'t know &#8211; fill in what you can and leave the rest blank.'));
			$panel_inner2->AddControl($tab_tip);
			echo $panel;
		}

		# OK to edit the match
		$this->editor->SetDataObject($this->match);
		echo $this->editor;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::EDIT_MATCH, false);
?>