<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/team-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The team to display
	 *
	 * @var Team
	 */
	private $team;
	private $stats;
	private $most_runs;
	private $most_wickets;
	private $most_catches;
	private $most_run_outs;
	private $most_player_of_match;
	private $season;
	private $statistics_query;

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
		/* @var Team $team */

		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->Redirect();

		# get team
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $team_manager->FilterByTeamType(array());
		$team_manager->ReadById(array($_GET['item']));
		$this->team = $team_manager->GetFirst();
		unset($team_manager);

		# must have found a team
		if (!$this->team instanceof Team)	$this->Redirect('/teams/');

		# get match stats
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByTeam(array($this->team->GetId()));
		$statistics_manager->ReadMatchStatistics();
		$this->stats = $statistics_manager->GetItems();

		# Get some stats on the best players
		$this->statistics_query = "?team=" . $this->team->GetId();
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

			$this->statistics_query .= "&amp;from=" . $season_dates[0] . "&amp;to=" . $season_dates[1];
		}
		$statistics_manager->FilterMaxResults(10);
		$this->most_runs = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
		$this->most_wickets = $statistics_manager->ReadBestPlayerAggregate("wickets");
		$this->most_catches = $statistics_manager->ReadBestPlayerAggregate("catches");
		$this->most_run_outs = $statistics_manager->ReadBestPlayerAggregate("run_outs");
		$this->most_player_of_match = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
		unset($statistics_manager);    
    }

	function OnPrePageLoad()
	{
		$title = "Team statistics for " . $this->team->GetNameAndType() . ' stoolball team';
		if ($this->season) $title .= " in the $this->season season";
		$this->SetPageTitle($title);
		$this->SetContentConstraint(StoolballPage::ConstrainBox());
		$this->SetContentCssClass('stats');
        
        
        $this->LoadClientScript("/scripts/lib/chart.min.js");
        $this->LoadClientScript("/scripts/chart.js?v=2");
        $this->LoadClientScript("/scripts/lib/Chart.StackedBar.js");
        $this->LoadClientScript("team.js", true);
        ?><!--[if lte IE 8]><script src="/scripts/lib/excanvas.compiled.js"></script><![endif]--><?php
    }

	function OnPageLoad()
	{
		require_once('stoolball/statistics-calculator.class.php');
		$calculator = new StatisticsCalculator();
		$calculator->AnalyseMatchData($this->stats, $this->team);

		$title = "Team statistics for " . $this->team->GetNameAndType();
		if ($this->season) $title .= " in the $this->season season";
		echo "<h1>" . htmlentities($title, ENT_QUOTES, "UTF-8", false) . "</h1>";

		# See what stats we've got available
		$has_team_stats = $calculator->EnoughDataForStats($this->season);

		$has_most_runs = count($this->most_runs);
		$has_most_wickets = count($this->most_wickets);
		$has_catch_stats = count($this->most_catches);
		$has_run_outs = count($this->most_run_outs);
		$has_player_of_match_stats = count($this->most_player_of_match);
		$has_player_stats = ($has_most_runs or $has_most_wickets or $has_catch_stats or $has_run_outs or $has_player_of_match_stats);

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => $this->team->GetNavigateUrl());       

        if ($has_player_stats) {
            $tabs['Players'] = $this->team->GetPlayersNavigateUrl();
        }
        $tabs['Statistics'] = '';
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php
        
		if (!$has_team_stats and !$has_player_stats)
		{
		    $scope = $this->team->GetNameAndType();
            if ($this->season) $scope .= " in the $this->season season";
            $scope = htmlentities($scope, ENT_QUOTES, "UTF-8", false);
        
			echo "<p>There aren't any statistics for $scope yet.</p>" . 
			'<p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results - why you should add yours</a>.</p>';
		}
		else
		{
			require_once('stoolball/team-runs-table.class.php');
			require_once('stoolball/statistics/player-statistics-table.class.php');

			echo '<div class="statsGroup">';

			if ($has_team_stats or $has_most_runs)
			{
				echo '<div class="statsColumns">
				<h2>Batting statistics</h2>
				<div class="statsColumn">';

				if ($has_most_runs)
				{
					echo new PlayerStatisticsTable("Most runs", "Runs", $this->most_runs, false);
					if ($has_most_runs >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-runs' . $this->statistics_query . '">Most runs &#8211; view all and filter</a></p>';
				}

				echo "</div><div class=\"statsColumn\">";

				if ($has_team_stats)
				{
					echo new TeamRunsTable($calculator->TotalMatchesWithRunData($this->season), $calculator->RunsScored($this->season), $calculator->RunsConceded($this->season), $calculator->HighestInnings($this->season), $calculator->LowestInnings($this->season), $calculator->AverageInnings($this->season));
				}

				if ($has_most_runs)
				{
					echo '<p class="statsViewAll"><a href="/play/statistics/individual-scores' . $this->statistics_query . '">Individual scores</a></p>';
					echo '<p class="statsViewAll"><a href="/play/statistics/batting-average' . $this->statistics_query . '">Batting averages</a></p>';
                    echo '<p class="statsViewAll"><a href="/play/statistics/batting-strike-rate' . $this->statistics_query . '">Batting strike rates</a></p>';
				}

				echo "</div></div>";
			}

			if ($has_most_wickets or $has_team_stats)
			{
				echo '<div class="statsColumns">
					<h2>Bowling statistics and match results</h2>
					  <div class="statsColumn">';

				if ($has_most_wickets)
				{
					# Show top bowlers
					echo new PlayerStatisticsTable("Most wickets", "Wickets", $this->most_wickets, false);

					if ($has_most_wickets >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-wickets' . $this->statistics_query . '">Most wickets &#8211; view all and filter</a></p>';
                    echo '<p class="statsViewAll"><a href="/play/statistics/most-wickets-by-bowler-and-catcher' . $this->statistics_query . '">Most wickets by a bowling and catching combination</a></p>';
					echo '<p class="statsViewAll"><a href="/play/statistics/bowling-performances' . $this->statistics_query . '">Bowling performances</a></p>';
					echo '<p class="statsViewAll"><a href="/play/statistics/bowling-average' . $this->statistics_query . '">Bowling averages</a></p>';
					echo '<p class="statsViewAll"><a href="/play/statistics/economy-rate' . $this->statistics_query . '">Economy rates</a></p>';
					echo '<p class="statsViewAll"><a href="/play/statistics/bowling-strike-rate' . $this->statistics_query . '">Bowling strike rates</a></p>';				}

                    ?>
					</div><div class="statsColumn">
                        <span class="chart-js-template" id="all-results-chart"></span>
                        <span class="chart-js-template" id="home-results-chart"></span>
                        <span class="chart-js-template" id="away-results-chart"></span>
					</div></div>
                    <?php               
			}

            ?><span class="chart-js-template" id="opponents-chart"></span><?php 

			if ($has_catch_stats or $has_run_outs)
			{
				echo '<div class="statsColumns">
				<h2>Fielding statistics</h2>
				<div class="statsColumn">';

				if ($has_catch_stats)
				{
					# Show top catchers
					echo new PlayerStatisticsTable("Most catches", "Catches", $this->most_catches, false);
					if ($has_catch_stats >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-catches' . $this->statistics_query . '">Most catches &#8211; view all and filter</a></p>';
                    echo '<p class="statsViewAll"><a href="/play/statistics/most-catches-in-innings' . $this->statistics_query . '">Most catches in an innings &#8211; view all and filter</a></p>';
				}

				echo "</div><div class=\"statsColumn\">";

				if ($has_run_outs)
				{
					echo new PlayerStatisticsTable("Most run-outs", "Run-outs", $this->most_run_outs, false);
					if ($has_run_outs >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-run-outs' . $this->statistics_query . '">Most run-outs &#8211; view all and filter</a></p>';
                    echo '<p class="statsViewAll"><a href="/play/statistics/most-run-outs-in-innings' . $this->statistics_query . '">Most run-outs in an innings &#8211; view all and filter</a></p>';
				}

				echo "</div></div>";
			}

            echo '<h2>All-round performance statistics</h2>';
			if ($has_player_of_match_stats)
			{
				echo new PlayerStatisticsTable("Most player of the match nominations", "Nominations", $this->most_player_of_match, false);
                echo '<p class="statsViewAll"><a href="/play/statistics/player-performances' . $this->statistics_query . '">Player performances &#8211; view all and filter</a></p>';
			    echo '<p class="statsViewAll"><a href="/play/statistics/player-of-match' . $this->statistics_query . '">Player of the match nominations &#8211; view all and filter</a></p>';
            	if ($has_player_of_match_stats >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-player-of-match' . $this->statistics_query . '">Most player of the match nominations &#8211; view all and filter</a></p>';
			}
            else {
                echo '<p><a href="/play/statistics/player-performances' . $this->statistics_query . '">Player performances &#8211; view all and filter</a></p>';
            }

			echo "</div>"; # close statsGroup

			# Link to other seasons
			if (count($calculator->SeasonYears()) > 1)
			{
				echo new XhtmlElement('h2', 'More statistics for ' . htmlentities($this->team->GetName(), ENT_QUOTES, "UTF-8", false));
				echo '<div class="season-list"><ul>';
				if ($this->season) echo "<li><a href=\"" . htmlentities($this->team->GetStatsNavigateUrl(), ENT_QUOTES, "UTF-8", false) . "\">All seasons</a></li>";
				foreach ($calculator->SeasonYears() as $season_years)
				{
					$season_url = str_replace("/", "-", $season_years);

					# Link to season if it's not the current season. Important to use !== because
					# we're comparing numeric strings and with != 2009-10 is equal to 2009
					if (!isset($_GET['params']) or $season_url !== $_GET["params"])
					{
						echo "<li><a href=\"" . htmlentities($this->team->GetStatsNavigateUrl() . "/" . $season_url, ENT_QUOTES, "UTF-8", false) . "\">" . htmlentities($season_years, ENT_QUOTES, "UTF-8", false) . " season</a></li>";
					}
				}
				echo "</ul></div>";
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