<?php
set_time_limit(0);
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/season-manager.class.php');

class CurrentPage extends StoolballPage
{
	private $competition;
	/**
	 * The season to display
	 *
	 * @var Season
	 */
	private $season;
	private $statistics = array();

	function OnLoadPageData()
	{
		/* @var $competition Competition */

		# check parameter
		if (isset($_GET['season']) and is_numeric($_GET['season']))
		{
			$comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
			$comp_manager->ReadById(null, array($_GET['season']));
			$this->competition = $comp_manager->GetFirst();
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

		# Get the season
		$this->season = $this->competition->GetWorkingSeason();
		if (is_object($this->season))
		{
			# Get stats highlights
			$this->statistics["querystring"] = "?season=" . $this->season->GetId();
			require_once('stoolball/statistics/statistics-manager.class.php');
			$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
			$statistics_manager->FilterBySeason(array($this->season->GetId()));
			require_once("_summary-data-query.php");
			unset($statistics_manager);

			# Get other seasons
			$a_comp_ids = array($this->competition->GetId());
			$season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
			$season_manager->ReadByCompetitionId($a_comp_ids);
			$a_other_seasons = $season_manager->GetItems();
			$this->competition->SetSeasons($a_other_seasons);
			$this->competition->AddSeason($this->season, true);
			unset($season_manager);
		}
		else
		{
			# Must have a season
			$this->Redirect("/competitions/");
		}

	}

	function OnPrePageLoad()
	{
		# set up page
		$this->SetPageTitle("Statistics for " . $this->season->GetCompetitionName());
		$this->SetContentConstraint(StoolballPage::ConstrainBox());
	}

	function OnPageLoad()
	{
		echo "<h1>Statistics for " . htmlentities($this->season->GetCompetitionName(), ENT_QUOTES, "UTF-8", false). "</h1>";

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
			echo "<p>There aren't any statistics for the " . htmlentities($this->season->GetCompetitionName(), ENT_QUOTES, "UTF-8", false) . ' yet.</p>
			<p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results - why you should add yours</a>.</p>' .
			"<p>You can also view the <a href=\"" . htmlentities($this->season->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) . "\">" . htmlentities($this->season->GetCompetitionName(), ENT_QUOTES, "UTF-8", false) . " page</a>.</p>";
		}
		else
		{
			require_once("_summary-controls.php");
		}

		# Check for other seasons. Check is >2 becuase current season is there twice - added above
		if (count($this->competition->GetSeasons()) > 2)
		{
			require_once("stoolball/season-list-control.class.php");
			echo new XhtmlElement('h2', Html::Encode('More statistics for the ' . $this->competition->GetName()), "screen");
			$season_list = new SeasonListControl($this->competition->GetSeasons());
			$season_list->SetExcludedSeasons(array($this->season));
			$season_list->SetUrlMethod('GetStatisticsUrl');
			$season_list->AddCssClass("screen");

			# Override XHTML to add link to competition stats
			$season_list_xhtml = $season_list->__toString();
			$season_list_xhtml = str_replace("<ul>", "<ul><li><a href=\"" . htmlentities($this->competition->GetStatisticsUrl(), ENT_QUOTES, "UTF-8", false) . "\">All seasons</a></li>", (string)$season_list_xhtml);
			echo $season_list_xhtml;
		}

        ?>
        </div>
        </div>
        <?php 
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>