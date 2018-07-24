<?php
set_time_limit(0);
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# include required functions
require_once('page/stoolball-page.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/season-manager.class.php');

# create page
class CurrentPage extends StoolballPage
{
	private $competition;
    private $season;
	private $statistics = array();

	function OnLoadPageData()
	{
		/* @var $competition Competition */

		# check parameter
		if (isset($_GET['competition']) and is_numeric($_GET['competition']))
		{
			$comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
			$comp_manager->ReadById(array($_GET['competition']), null);
			$this->competition = $comp_manager->GetFirst();
            $this->season = $this->competition->GetWorkingSeason();
			unset($comp_manager);
		}
		else
		{
			$this->Redirect("/competitions/");
		}

		# must have found a competition
		if (!$this->competition instanceof Competition)
		{
			$this->Redirect("/competitions/");
		}

		# Get seasons in the competition
		$a_comp_ids = array($this->competition->GetId());
		$o_season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
		$o_season_manager->ReadByCompetitionId($a_comp_ids);
		$a_seasons = $o_season_manager->GetItems();
		$this->competition->SetSeasons($a_seasons);
		unset($o_season_manager);
        
		# Get stats highlights
		$this->statistics["querystring"] = "?competition=" . $this->competition->GetId();
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByCompetition(array($this->competition->GetId()));
		require_once("_summary-data-query.php");
		unset($statistics_manager);
	}

	function OnPrePageLoad()
	{
		/* @var $competition Competition */
		# set up page
		$this->SetPageTitle("Statistics for " . $this->competition->GetName() . ", All seasons");
		$this->SetContentConstraint(StoolballPage::ConstrainBox());
	}

	function OnPageLoad()
	{
 
		echo "<h1>Statistics for " . Html::Encode($this->competition->GetName()) . ", All seasons</h1>";

		require_once("_summary-data-found.php");
        require_once('xhtml/navigation/tabs.class.php');
        
        $tabs = array('Summary' => $this->season->GetNavigateUrl());
        if ($this->season->MatchTypes()->Contains(MatchType::LEAGUE))
        {
            $tabs['Table'] = $this->season->GetTableUrl();
        }
        if (count($this->season->GetTeams()))
        {
            $tabs['Map'] = $this->season->GetMapUrl();
        }
        $tabs['Statistics'] = '';
        
        echo new Tabs($tabs);
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php 

		if (!$has_player_stats)
		{
			echo "<p>There aren't any statistics for the " . htmlentities($this->competition->GetName(), ENT_QUOTES, "UTF-8", false) . ' yet.</p>
			<p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results - why you should add yours</a>.</p>';
		}
		else
		{
            require_once("_summary-controls.php");
		}

		# Check for other seasons. Check is >2 becuase current season is there twice - added above
		if (count($this->competition->GetSeasons()) > 2)
		{
			require_once("stoolball/season-list-control.class.php");
			echo new XhtmlElement('h2', 'Statistics by season', "screen");
			$season_list = new SeasonListControl($this->competition->GetSeasons());
			$season_list->SetUrlMethod('GetStatisticsUrl');
			$season_list->AddCssClass("screen");
			echo $season_list;
		}

        ?>
        </div>
        </div>
        <?php 
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>