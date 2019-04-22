<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/season-manager.class.php');
require_once('stoolball/season-edit-control.class.php');
require_once('stoolball/team-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * Data source for seasons
	 *
	 * @var SeasonManager
	 */
	private $season_manager;

	/**
	 * Data source for competitions
	 *
	 * @var CompetitionManager
	 */
	private $competition_manager;

	/**
	 * Teams data source
	 *
	 * @var TeamManager
	 */
	private $team_manager;

	/**
	 * The season to edit
	 *
	 * @var Season
	 */
	private $season;
	/**
	 * Editing control for season
	 *
	 * @var SeasonEditControl
	 */
	private $edit;

	private $i_seasons_in_competition;

	function OnPageInit()
	{
		# new data managers
		$this->season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
		$this->team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());

		# data edit control
		$this->edit = new SeasonEditControl($this->GetSettings(), $this->GetCsrfToken());
		$this->RegisterControlForValidation($this->edit);

		parent::OnPageInit();
	}

	function OnPostback()
	{
		$this->season = $this->edit->GetDataObject();

		if (!$this->season->GetId())
		{
			$existing_season_id = $this->season_manager->CheckIfSeasonExists($this->season->GetCompetition()->GetId(), $this->season->GetStartYear(), $this->season->GetEndYear());

			if ($existing_season_id)
			{
				require_once('data/validation/required-field-validator.class.php');
				$season = new Season($this->GetSettings());
				$season->SetId($existing_season_id);

				$validator = new RequiredFieldValidator('This_Validator_Will_Fail', "The season you're adding already exists &#8211; <a href=\"" . $season->GetNavigateUrl() . "\">edit season</a>");
				$validator->SetValidIfNotFound(false);
				$this->edit->AddValidator($validator);
			}
		}


        # Get the competition. This is used to build the page title and, when saving, the short URL.
        # It is also re-indexed in search below.
        $this->competition_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
        $this->competition_manager->ReadById(array($this->season->GetCompetition()->GetId()));
        $this->season->SetCompetition($this->competition_manager->GetFirst());
        unset($this->competition_manager);

		# save data if valid
		if($this->IsValid())
		{
			$b_saved_new = !(bool)$this->season->GetId();

			$id = $this->season_manager->SaveSeason($this->season);
			$this->season->SetId($id);

            # Add the competition to the search  engine. Re-request so we have team names as well as IDs.
            require_once ("search/competition-search-adapter.class.php");
            $this->SearchIndexer()->DeleteFromIndexById("competition" . $this->season->GetCompetition()->GetId());
            $adapter = new CompetitionSearchAdapter($this->season->GetCompetition());
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
            $this->SearchIndexer()->CommitChanges();

			# If just saved a new season, redirect to load this page from scratch
			# (When just loading data on this page, didn't load correctly into aggregated editors)
			if ($b_saved_new) $this->Redirect($this->season->GetEditSeasonUrl());
			
			$this->Redirect($this->season->GetNavigateUrl());
		}
	}

	function OnLoadPageData()
	{
		# get id of season
		$i_id = $this->edit->GetDataObjectId($this->season);

		# read season data when first asked to edit a season
		if (($i_id and !$this->IsPostback()))
		{
			$this->season_manager->ReadById(array($i_id));
			$this->season = $this->season_manager->GetFirst();
		}

		# get all teams
		$this->team_manager->SetGroupInactiveLast(false);
		$this->team_manager->ReadAll();
		$this->edit->SetTeams($this->team_manager->GetItems());

		# tidy up
		unset($this->season_manager);
		unset($this->team_manager);
	}

	function OnPrePageLoad()
	{
		/* @var $season Season */

		$this->SetPageTitle((is_object($this->season) and $this->season->GetId()) ? "Edit " . $this->season->GetCompetitionName() : 'New season');
	}

	function OnPageLoad()
	{
		/* @var $season Season */

		echo new XhtmlElement('h1', htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false));

		# display the season
		if (!is_object($this->season))
		{
			$this->season = new Season($this->GetSettings());
			if (isset($_GET['competition']))
			{
				$comp = new Competition($this->GetSettings());
				$comp->SetId($_GET['competition']);
				$this->season->SetCompetition($comp);
			}

			/* Create instruction panel */
			$panel = new XhtmlElement('div');
			$panel->SetCssClass('panel instructionPanel');

			$title_inner1 = new XhtmlElement('div', 'Tips for adding seasons');
			$title = new XhtmlElement('h2', $title_inner1);
			$panel->AddControl($title);

			$tab_tip = new XhtmlElement('ul');
			$tab_tip->AddControl(new XhtmlElement('li', "You'll be able to edit more details after you click 'Save'"));
			$panel->AddControl($tab_tip);
			echo $panel;
		}
		$this->edit->SetDataObject($this->season);
		echo $this->edit;

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_COMPETITIONS, false);
?>