<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/ground-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The ground to display
	 *
	 * @var Ground
	 */
	private $ground;
	private $season;
	private $seasons_with_statistics;
	private $statistics = array();

	public function OnPageInit()
	{
		if (isset($_GET['params']) and is_numeric(str_replace("-", "", $_GET['params'])))
		{
			$length = strlen($_GET['params']);
			if ($length == 4 or $length == 7)
			{
				$this->season = str_replace("-", "/", $_GET['params']);
			}
		}
	}

	function OnLoadPageData()
	{
		/* @var Ground $ground */

		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->Redirect();

		# get ground
		$ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$ground_manager->ReadById(array($_GET['item']));
		$this->ground = $ground_manager->GetFirst();
		unset($ground_manager);

		# must have found a ground
		if (!$this->ground instanceof Ground)	$this->Redirect('/play/');

		# Get some stats on the best players
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByGround(array($this->ground->GetId()));
		$this->seasons_with_statistics = $statistics_manager->ReadSeasonsWithPlayerStatistics();

		$this->statistics["querystring"] = "?ground=" . $this->ground->GetId();
		if ($this->season)
		{
			# use midpoint of season to get season dates for filter
			$start_year = substr($this->season, 0, 4);
			$end_year = (strlen($this->season) == 7) ? $start_year + 1 : $start_year;
			if ($start_year == $end_year)
			{
				$season_dates = Season::SeasonDates(mktime(0, 0, 0, 7, 1, $start_year));
			}
			else
			{
				$season_dates = Season::SeasonDates(mktime(0, 0, 0, 12, 31, $start_year));
			}
			$statistics_manager->FilterAfterDate($season_dates[0]);
			$statistics_manager->FilterBeforeDate($season_dates[1]);

			$this->statistics["querystring"] .= "&amp;from=" . $season_dates[0] . "&amp;to=" . $season_dates[1];
		}
		require_once("_summary-data-query.php");
		unset($statistics_manager);
	}

	function OnPrePageLoad()
	{
		$title = "Statistics at " . $this->ground->GetNameAndTown() . ' stoolball ground';
		if ($this->season) $title .= " in the $this->season season";
		$this->SetPageTitle($title);
		$this->SetContentConstraint(StoolballPage::ConstrainBox());
		$this->SetContentCssClass('stats');
	}

	function OnPageLoad()
	{
		$title = "Statistics at " . $this->ground->GetNameAndTown();
        if ($this->season) $title .= " in the $this->season season";
        echo "<h1>" . htmlentities($title, ENT_QUOTES, "UTF-8", false) . "</h1>";

		# See what stats we've got available
		require_once("_summary-data-found.php");
 
        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => $this->ground->GetNavigateUrl(), 'Matches' => $this->ground->GetMatchesUrl(), 'Statistics' => '');       
        echo new Tabs($tabs);
 
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php
		if (!$has_player_stats)
		{
			$yet = ($this->season) ? "in the $this->season season yet" : "yet";
			echo "<p>There aren't any statistics for " . htmlentities($this->ground->GetName() . ' ' . $yet, ENT_QUOTES, "UTF-8", false) . '.</p>
			<p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results - why you should add yours</a>.</p>';
		}
		else
		{
			require_once("_summary-controls.php");

			# Link to other seasons
			if (count($this->seasons_with_statistics) > 1)
			{
				echo new XhtmlElement('h2', 'More statistics for ' . Html::Encode($this->ground->GetName()), "screen");
				echo '<ul class="season-list screen">';
				if ($this->season) echo "<li><a href=\"" . htmlentities($this->ground->GetStatsNavigateUrl(), ENT_QUOTES, "UTF-8", false) . "\">All seasons</a></li>";
				foreach ($this->seasons_with_statistics as $season)
				{
					$season_url = $season->GetStartYear();
					if ($season->GetStartYear() != $season->GetEndYear())
					{
						$season_url .= "-" . substr($season->GetEndYear(), 2, 2);
					}

					# Link to season if it's not the current season. Important to use !== because
					# we're comparing numeric strings and with != 2009-10 is equal to 2009				
					if (!isset($_GET['params']) or (string)$season_url !== (string)$_GET["params"])
					{
						echo "<li><a href=\"" . htmlentities($this->ground->GetStatsNavigateUrl() . "/" . $season_url, ENT_QUOTES, "UTF-8", false) . "\">" . htmlentities($season->GetYears(), ENT_QUOTES, "UTF-8", false) . " season</a></li>";
					}
				}
				echo "</ul>";
			}
		}
        ?>
        </div>
        </div>
        <?php 
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>