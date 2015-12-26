<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# Check it is a valid statistic
if (!in_array(preg_replace("/[^a-z-]/", "", $_GET["statistic"]), array(
	"individual-scores", "most-runs", "batting-average",
	"bowling-performances", "most-wickets", "bowling-average", "economy-rate", "bowling-strike-rate",
	"most-catches", "most-run-outs",
	"most-player-of-match"
)))
{
	require_once("../../wp-content/themes/stoolball/404.php");
	exit();
}

# include required functions
require_once('page/stoolball-page.class.php');
require_once('stoolball/statistics/statistics-manager.class.php');
require_once("stoolball/statistics/statistics-filter.class.php");
require_once("stoolball/statistics/statistics-filter-control.class.php");
require_once('data/paged-results.class.php');

# create page
class CurrentPage extends StoolballPage
{
	private $data;
	private $paging;
	private $filter;
	/**
	 * @var StatisticsFilterControl
	 */
	private $filter_control;
	private $which_statistic;
	private $statistic_title;
	private $statistic_column;
	private $statistic_intro;
	private $statistic_description;

	function OnLoadPageData()
	{
		# Sanitise the name of the requested statistic
		$this->which_statistic = preg_replace("/[^a-z-]/", "", $_GET["statistic"]);

		# Set up statistics manager
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());

		# Is this a request for CSV data?
		$csv = (isset($_GET["format"]) and $_GET["format"] == "csv");
		if (!$csv)
		{
			$this->paging = new PagedResults();
			$this->paging->SetPageName($this->which_statistic);
			$this->paging->SetQueryString(preg_replace("/statistic=$this->which_statistic&?/", "", $this->paging->GetQueryString()));
			$this->paging->SetPageSize(50);
			$this->paging->SetResultsTextSingular("player");
			$this->paging->SetResultsTextPlural("players");
			$statistics_manager->FilterByPage($this->paging->GetPageSize(), $this->paging->GetCurrentPage());
		}

		# Apply player filter first because it can be used to limit the choices for other filters
		switch ($this->which_statistic)
		{
			case "individual-scores":
			case "bowling-performances":
				$this->filter .= StatisticsFilter::ApplyPlayerFilter($this->GetSettings(), $this->GetDataConnection(), $statistics_manager);
		}

		# Apply filters common to all statistics
		$this->filter_control = new StatisticsFilterControl();

		# If player filter applied, no point filtering by team. Check for player filter is stricter than this
		# but this is good enough. If this applies, but the player filter isn't applied, it's because someone
		# is playing with the query string, so that's tough.
		if (!isset($_GET['player']))
		{
			$filter_team = StatisticsFilter::SupportTeamFilter($statistics_manager);
			$this->filter_control->SupportTeamFilter($filter_team);
			$this->filter .= $filter_team[2];
		}

		$filter_opposition = StatisticsFilter::SupportOppositionFilter($statistics_manager);
		$this->filter_control->SupportOppositionFilter($filter_opposition);
		$this->filter .= $filter_opposition[2];

		$filter_competition = StatisticsFilter::SupportCompetitionFilter($statistics_manager);
		$this->filter_control->SupportCompetitionFilter($filter_competition);
		$this->filter .= $filter_competition[2];

		$this->filter .= StatisticsFilter::ApplySeasonFilter($this->GetSettings(), $this->GetDataConnection(), $statistics_manager);

		$filter_ground = StatisticsFilter::SupportGroundFilter($statistics_manager);
		$this->filter_control->SupportGroundFilter($filter_ground);
		$this->filter .= $filter_ground[2];

		$filter_date = StatisticsFilter::SupportDateFilter($statistics_manager);
		if (!is_null($filter_date[0])) $this->filter_control->SupportAfterDateFilter($filter_date[0]);
		if (!is_null($filter_date[1])) $this->filter_control->SupportBeforeDateFilter($filter_date[1]);
		$this->filter .= $filter_date[2];

        $this->filter .= StatisticsFilter::ApplyTournamentFilter($this->GetSettings(), $this->GetDataConnection(), $statistics_manager);

		# Configure and read the requested statistic
		switch ($this->which_statistic)
		{
			case "individual-scores":
				$filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
				$this->filter_control->SupportBattingPositionFilter($filter_batting_position);
				$this->filter .= $filter_batting_position[2];

				$this->statistic_title = $this->filter ? "Individual scores" : "All individual scores";
				$this->statistic_description = "See the highest scores by individuals in a single stoolball innings.";
				if ($csv)
				{
					$statistics_manager->OutputAsCsv(array());
				}
				else
				{
					$this->paging->SetResultsTextSingular("innings");
					$this->paging->SetResultsTextPlural("innings");
				}
				$this->data = $statistics_manager->ReadBestBattingPerformance();
				break;

			case "most-runs":
				$filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
				$this->filter_control->SupportBattingPositionFilter($filter_batting_position);
				$this->filter .= $filter_batting_position[2];

				$this->statistic_title = "Most runs";
				$this->statistic_column = "Runs";
				$this->statistic_description = "Find out who has scored the most runs overall in all stoolball matches.";
				if ($csv) $statistics_manager->OutputAsCsv(array("Runs"));
				$this->data = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
				break;

			case "batting-average":
				$filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
				$this->filter_control->SupportBattingPositionFilter($filter_batting_position);
				$this->filter .= $filter_batting_position[2];

				$this->statistic_title = "Batting averages";
				$this->statistic_column = "Average";
				$this->statistic_intro = <<<INTRO
<p>A batsman's average measures how many runs he or she typically scores before getting out.
We only include people who have batted at least three times, and got out at least once.</p>
INTRO;
				if ($csv) $statistics_manager->OutputAsCsv(array("Batting average"));
				$this->data = $statistics_manager->ReadBestPlayerAverage("runs_scored", "dismissed", true, "runs_scored", 3);
				break;

			case "bowling-performances":
				$this->statistic_title = $this->filter ? "Bowling performances" : "All bowling performances";
				$this->statistic_description = "See the best wicket-taking performances in all stoolball matches.";
				if ($csv)
				{
					$statistics_manager->OutputAsCsv(array());
				}
				else
				{
					$this->paging->SetResultsTextSingular("innings");
					$this->paging->SetResultsTextPlural("innings");
				}
				$this->data = $statistics_manager->ReadBestBowlingPerformance();
				break;

			case "most-wickets":
				$this->statistic_title = "Most wickets";
				$this->statistic_column = "Wickets";
				$this->statistic_intro = <<<INTRO
<p>If a player is out caught, caught and bowled, bowled, body before wicket or for hitting the ball twice the wicket is credited to the bowler and included here.
Players run-out or timed out are not counted.</p>
INTRO;
				if ($csv) $statistics_manager->OutputAsCsv(array("Wickets"));
				$this->data = $statistics_manager->ReadBestPlayerAggregate("wickets");
				break;

			case "bowling-average":
				$this->statistic_title = "Bowling averages";
				$this->statistic_column = "Average";
				$this->statistic_intro = <<<INTRO
<p>A bowler's average measures how many runs he or she typically concedes before taking a wicket. Lower numbers are better.</p>
<p>We only include people who have bowled in at least three matches, and we ignore wickets in innings where the bowling card wasn't filled in.</p>
INTRO;
				if ($csv) $statistics_manager->OutputAsCsv(array("Bowling average"));
				$this->data = $statistics_manager->ReadBestPlayerAverage("runs_conceded", "wickets_with_bowling", false, "runs_conceded", 3);
				break;

			case "economy-rate":
				$this->statistic_title = "Economy rates";
				$this->statistic_column = "Economy rate";
				$this->statistic_intro = <<<INTRO
<p>A bowler's economy rate measures how many runs he or she typically concedes in each over. Lower numbers are better.
We only include people who have bowled in at least three matches.</p>
INTRO;
				if ($csv) $statistics_manager->OutputAsCsv(array("Economy rate"));
				$this->data = $statistics_manager->ReadBestPlayerAverage("runs_conceded", "overs_decimal", false, "runs_conceded", 3);
				break;

			case "bowling-strike-rate":
				$this->statistic_title = "Bowling strike rates";
				$this->statistic_column = "Strike rate";
				$this->statistic_intro = <<<INTRO
<p>A bowler's strike rate measures how many deliveries he or she typically bowls before taking a wicket. Lower numbers are better.</p>
<p>We only include people who have bowled in at least three matches, and we ignore wickets in innings where the bowling card wasn't filled in.</p>
INTRO;
				if ($csv) $statistics_manager->OutputAsCsv(array("Bowling strike rate"));
				$this->data = $statistics_manager->ReadBestPlayerAverage("balls_bowled", "wickets_with_bowling", false, "balls_bowled", 3);
				break;


			case "most-catches":
				$this->statistic_title = "Most catches";
				$this->statistic_column = "Catches";
				$this->statistic_intro = <<<INTRO
<p>This measures the number of catches taken by a fielder, not how often a batsman has been caught out.</p>
INTRO;
				if ($csv) $statistics_manager->OutputAsCsv(array("Catches"));
				$this->data = $statistics_manager->ReadBestPlayerAggregate("catches");
				break;

			case "most-run-outs":
				$this->statistic_title = "Most run-outs";
				$this->statistic_column = "Run-outs";
				$this->statistic_intro = <<<INTRO
<p>This measures the number of run-outs completed by a fielder, not how often a batsman has been run-out.</p>
INTRO;
				if ($csv) $statistics_manager->OutputAsCsv(array("Run-outs"));
				$this->data = $statistics_manager->ReadBestPlayerAggregate("run_outs");
				break;

			case "most-player-of-match":
				$filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
				$this->filter_control->SupportBattingPositionFilter($filter_batting_position);
				$this->filter .= $filter_batting_position[2];

				$this->statistic_title = "Most player of the match nominations";
				$this->statistic_column = "Nominations";
				$this->statistic_description = "Find out who has won the most player of the match awards for their outstanding performances on the pitch.";
				if ($csv) $statistics_manager->OutputAsCsv(array("Player of the match nominations"));
				$this->data = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
				break;
		}

		unset($statistics_manager);

		$this->paging->SetTotalResults(array_shift($this->data));
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle("$this->statistic_title $this->filter");
		$this->SetPageDescription($this->statistic_intro ? strip_tags($this->statistic_intro) : $this->statistic_description);
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		$this->LoadClientScript("jquery-ui-1.8.11.custom.min.js");
		$this->LoadClientScript("/play/statistics/statistics-filter.js");
		?>
<link rel="stylesheet" href="/css/custom-theme/jquery-ui-1.8.11.custom.css" media="screen" />
		<?php
	}

	function OnPageLoad()
	{
		echo ("<h1>" . htmlentities($this->statistic_title, ENT_QUOTES, "UTF-8", false) . " " . htmlentities($this->filter, ENT_QUOTES, "UTF-8", false) . "</h1>");
		$this->GetTheData("large");
		echo $this->statistic_intro; ?>

<p>
	The statistics below are based on scorecards added to this website. If you know someone who's played better, add the scorecard for that match &#8211; see <a
		href="/play/manage/website/how-to-add-match-results/">How to add match results</a>.
</p>

		<?php echo $this->filter_control; ?>

<div class="pagedTable">
<?php

echo $this->paging->GetNavigationBar();

switch ($this->which_statistic)
{
	case "individual-scores":
		require_once('stoolball/statistics/batting-innings-table.class.php');
		echo new BattingInningsTable($this->data, true, $this->paging->GetFirstResultOnPage());
		break;

	case "bowling-performances":
		require_once('stoolball/statistics/bowling-performance-table.class.php');
		echo new BowlingPerformanceTable($this->data, true, $this->paging->GetFirstResultOnPage());
		break;

	default:
		require_once('stoolball/statistics/player-statistics-table.class.php');
		echo new PlayerStatisticsTable($this->statistic_title, $this->statistic_column, $this->data, true, $this->paging->GetFirstResultOnPage());
		break;
}

if ($this->paging->GetTotalResults()) echo $this->paging->GetNavigationBar();
?>
</div>
<?php
    $this->GetTheData("small");

	$this->AddSeparator();
	$this->BuySomething();

	}

	private function GetTheData($class)
	{
		$verbs = array("Sort", "Filter", "Sift", "Mix", "Slice", "Mash", "Cut", "Crunch", "Chart", "See");
		$verb = $verbs[array_rand($verbs, 1)];
		$csv_url = $_SERVER["REQUEST_URI"];
		$csv_url .= (strpos($csv_url, "?") === false) ? "?format=csv" : "&amp;format=csv";
		?>
<div class="box get-the-data <?php echo $class ?> screen">
	<div class="box-content">
		<div class="csv">
			<h2>
				<a href="<?php echo htmlentities($csv_url, ENT_QUOTES, "UTF-8", false); ?>" rel="nofollow">Get the data</a>
			</h2>
			<p>
			<?php echo $verb; ?>
				it your way with a CSV spreadsheet
			</p>
		</div>
	</div>
</div>
						<?php
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>