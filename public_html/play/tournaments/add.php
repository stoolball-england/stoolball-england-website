<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('data/data-edit-repeater.class.php');
require_once('stoolball/tournaments/tournament-edit-control.class.php');
require_once('stoolball/match-list-control.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/season-manager.class.php');
require_once('stoolball/ground-manager.class.php');

class CurrentPage extends StoolballPage
{
	private $b_saved;

	/**
	 * The season to which a match is being added
	 *
	 * @var Season
	 */
	private $season;

	/**
	 * Editing control for the match details
	 *
	 * @var TournamentEditControl
	 */
	private $editor;

	/**
	 * Tournament to add/edit
	 *
	 * @var Match
	 */
	private $tournament;

	/**
	 * Team for which a match is being added
	 *
	 * @var Team
	 */
	private $team;

	private $destination_url_if_cancelled;

	function OnSiteInit()
	{
		parent::OnSiteInit();

		# check parameter
		if (isset($_GET['season']) and is_numeric($_GET['season']))
		{
			$this->season = new Season($this->GetSettings());
			$this->season->SetId((int)$_GET['season']);
		}

		if (isset($_GET['team']) and is_numeric($_GET['team']))
		{
			$this->team = new Team($this->GetSettings());
			$this->team->SetId($_GET['team']);
		}
	}

	public function OnPageInit()
	{
		# create edit control
		$this->editor = new TournamentEditControl($this->GetSettings());
        $this->editor->SetShowStepNumber(true);
		$this->RegisterControlForValidation($this->editor);
	}

	function OnLoadPageData()
	{
		/* @var $o_last_match Match */
		/* @var $season Season */
		/* @var $team Team */

		# First best guess at where user came from is the page field posted back,
		# second best is tournaments page. Either can be tampered with, so there will be
		# a check later before they're used for redirection. If there's a context
		# season or team its URL will be read from the db and overwrite this later.
		if (isset($_POST['page']))
		{
			$this->destination_url_if_cancelled = $_POST['page'];
		}
		else 
		{
			$this->destination_url_if_cancelled = "/tournaments";
		}

		# new data manager
		$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
		$match_manager->FilterByMatchType(array(MatchType::TOURNAMENT));

		# Check whether cancel was clicked
		if ($this->IsPostback() and $this->editor->CancelClicked())
		{
			# new tournament, nothing saved yet, so just send user back where they came from
			$this->Cancel();
		}

		# Save match
		if ($this->IsPostback() and $this->IsValid())
		{
			# Get posted match
			$this->tournament = $this->editor->GetDataObject();

			# Save match
			$match_manager->SaveFixture($this->tournament);
            if (count($this->tournament->GetAwayTeams())) {
                $match_manager->SaveTeams($this->tournament);
            }
            if ($this->season instanceof Season) {
                $this->tournament->Seasons()->Add($this->season); 
                $match_manager->SaveSeasons($this->tournament, true);
            }
            http_response_code(303);
            $this->Redirect($this->tournament->AddTournamentTeamsUrl());
		}

		if (isset($this->season))
		{
			$season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
			$season_manager->ReadById(array($this->season->GetId()));
			$this->season = $season_manager->GetFirst();
			$this->editor->SetContextSeason($this->season);
			$this->destination_url_if_cancelled = $this->season->GetNavigateUrl();
			unset($season_manager);

			# If we're adding a match to a season, get last game in season for its date
			$match_manager->ReadLastInSeason($this->season->GetId());
		}

		if (isset($this->team))
		{
			# Get more information about the team itself
			require_once('stoolball/team-manager.class.php');
			$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
			$team_manager->ReadById(array($this->team->GetId()));
			$this->team = $team_manager->GetFirst();
			$this->editor->SetContextTeam($this->team);
			$this->destination_url_if_cancelled = $this->team->GetNavigateUrl();

			# Get the last game already scheduled for the team to use its date
			$match_manager->ReadLastForTeam($this->team->GetId());

			# Read teams played in the last year in order to get their grounds, which will be put at the top of the select list
			$team_manager->ReadRecentOpponents(array($this->team->GetId()), 12);
			$this->editor->ProbableTeams()->SetItems($team_manager->GetItems());
			unset($team_manager);
		}

		# Use the date of the most recent tournament if it was this year, otherwise just use the default of today
		$o_last_match = $match_manager->GetFirst();
		if (is_object($o_last_match) and gmdate('Y', $o_last_match->GetStartTime()) == gmdate('Y'))
		{
			if ($o_last_match->GetIsStartTimeKnown())
			{
				# If the last match has a time, use it
				$this->editor->SetDefaultTime($o_last_match->GetStartTime());
			}
			else
			{
				# If the last match has no time, use 11am BST
				$this->editor->SetDefaultTime(gmmktime(10, 0, 0, gmdate('m', $o_last_match->GetStartTime()), gmdate('d', $o_last_match->GetStartTime()), gmdate('Y', $o_last_match->GetStartTime())));
			}
		}
		unset($match_manager);

    	# Get grounds
		$o_ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$o_ground_manager->ReadAll();
		$a_grounds = $o_ground_manager->GetItems();
		$this->editor->Grounds()->SetItems($a_grounds);
		unset($o_ground_manager);    
	}

	private function Cancel()
	{
        http_response_code(303);
            
		# redirect to the page the user came from, so long as the data value has not been tampered with
		# to point to another website.
		if (strpos($this->destination_url_if_cancelled, 'https://' . $this->GetSettings()->GetDomain()) === 0 or strpos($this->destination_url_if_cancelled, '/') === 0)
		{
			$this->Redirect($this->destination_url_if_cancelled);
		}

		$this->Redirect();
	}

	function OnPrePageLoad()
	{
		if($this->team instanceof Team)
		{
			$this->SetPageTitle('New tournament for ' . $this->team->GetName());
		}
		else
		{
			$this->SetPageTitle('New stoolball tournament');
		}

		$this->SetContentCssClass('matchEdit');
		$this->SetContentConstraint(StoolballPage::ConstrainText());
		$this->LoadClientScript('/scripts/tournament-edit-control-3.js');
	}

	function OnPageLoad()
	{
		echo '<h1>' . Html::Encode($this->GetPageTitle()) . '</h1>';

		if (!is_object($this->tournament))
		{
			/* Create instruction panel */
			$o_panel = new XhtmlElement('div');
			$o_panel->SetCssClass('panel instructionPanel large');

			$o_title_inner1 = new XhtmlElement('div', 'Add your tournament quickly:');
			$o_title = new XhtmlElement('h2', $o_title_inner1);
			$o_panel->AddControl($o_title);

			$o_tab_tip = new XhtmlElement('ul');
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Use the <span class="tab">tab</span> key on your keyboard to move through the form'));
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Type the first letter or number to select from a dropdown list'));
			$o_tab_tip->AddControl(new XhtmlElement('li', 'If you\'re not sure of any details, leave them blank'));
			$o_panel->AddControl($o_tab_tip);
			echo $o_panel;
		
		}

        # Configure edit control
        $this->editor->SetCssClass('panel');

		# remember the page the user came from, in case they click cancel
		require_once('xhtml/forms/textbox.class.php');
		$cancel_url = new TextBox('page', $this->destination_url_if_cancelled);
		$cancel_url->SetMode(TextBoxMode::Hidden());
		$this->editor->AddControl($cancel_url);

		# display the match to edit
		echo $this->editor;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ADD_MATCH, false);
?>