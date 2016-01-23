<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# Check it is a valid statistic
if (!in_array(preg_replace("/[^a-z-]/", "", $_GET["statistic"]), array(
	"individual-scores", "most-runs", "batting-average", "batting-strike-rate",
	"bowling-performances", "most-wickets", "bowling-average", "economy-rate", "bowling-strike-rate",
	"most-catches", "most-catches-in-innings", "most-run-outs", "most-run-outs-in-innings", "most-wickets-by-bowler-and-catcher",
	"player-performances", "player-of-match", "most-player-of-match"
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
    private $statistic;

	function OnLoadPageData()
	{
		# Sanitise the name of the requested statistic
		$this->which_statistic = preg_replace("/[^a-z-]/", "", $_GET["statistic"]);

		# Set up statistics manager
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());

		# Configure the requested statistic
		switch ($this->which_statistic)
		{
			case "individual-scores":
				require_once("stoolball/statistics/individual-scores.class.php");
				$this->statistic = new IndividualScores($statistics_manager);
				break;

			case "most-runs":             
                require_once("stoolball/statistics/most-runs.class.php");
                $this->statistic = new MostRuns($statistics_manager);
				break;

			case "batting-average":
                require_once("stoolball/statistics/batting-average.class.php");
                $this->statistic = new BattingAverage($statistics_manager);
				break;

            case "batting-strike-rate":
                require_once("stoolball/statistics/batting-strike-rate.class.php");
                $this->statistic = new BattingStrikeRate($statistics_manager);
                break;

			case "bowling-performances":
                require_once("stoolball/statistics/bowling-performances.class.php");
                $this->statistic = new BowlingPerformances($statistics_manager);
				break;

			case "most-wickets":
                require_once("stoolball/statistics/most-wickets.class.php");
                $this->statistic = new MostWickets($statistics_manager);
				break;
                
            case "most-wickets-by-bowler-and-catcher":
                require_once("stoolball/statistics/most-wickets-by-bowler-and-catcher.class.php");
                $this->statistic = new MostWicketsForBowlerAndCatcher($statistics_manager);
                break;

			case "bowling-average":
                require_once("stoolball/statistics/bowling-average.class.php");
                $this->statistic = new BowlingAverage($statistics_manager);
				break;

			case "economy-rate":
                require_once("stoolball/statistics/economy-rate.class.php");
                $this->statistic = new EconomyRate($statistics_manager);
				break;

			case "bowling-strike-rate":
                require_once("stoolball/statistics/bowling-strike-rate.class.php");
                $this->statistic = new BowlingStrikeRate($statistics_manager);
				break;

			case "most-catches":
                require_once("stoolball/statistics/most-catches.class.php");
                $this->statistic = new MostCatches($statistics_manager);
				break;

            case "most-catches-in-innings":
                require_once("stoolball/statistics/most-catches-in-innings.class.php");
                $this->statistic = new MostCatchesInAnInnings($statistics_manager);
                break;

			case "most-run-outs":
                require_once("stoolball/statistics/most-run-outs.class.php");
                $this->statistic = new MostRunOuts($statistics_manager);
				break;
                
            case "most-run-outs-in-innings":
                require_once("stoolball/statistics/most-run-outs-in-innings.class.php");
                $this->statistic = new MostRunOutsInAnInnings($statistics_manager);
                break;

            case "player-performances":
                require_once("stoolball/statistics/player-performances.class.php");
                $this->statistic = new PlayerPerformances($statistics_manager);
                break;

            case "player-of-match":
                require_once("stoolball/statistics/player-of-match.class.php");
                $this->statistic = new PlayerOfTheMatch($statistics_manager);
                break;

			case "most-player-of-match":
                require_once("stoolball/statistics/most-player-of-match.class.php");
                $this->statistic = new MostPlayerOfTheMatch($statistics_manager);
				break;
		}

        # Is this a request for CSV data?
        $csv = (isset($_GET["format"]) and $_GET["format"] == "csv");
        if ($csv) {
            $statistics_manager->OutputAsCsv($this->statistic->ColumnHeaders());
        }                
                    
        # Apply player filter first because it can be used to limit the choices for other filters
        if ($this->statistic->SupportsFilterByPlayer())
        {
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

        if ($this->statistic->SupportsFilterByBattingPosition()) {
            $filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
            $this->filter_control->SupportBattingPositionFilter($filter_batting_position);
            $this->filter .= $filter_batting_position[2];
        }

        # Configure paging of results
        if (!$csv and $this->statistic->SupportsPagedResults()) {
            require_once('data/paged-results.class.php');
            $this->paging = new PagedResults();
            $this->paging->SetPageName($this->which_statistic);
            $this->paging->SetQueryString(preg_replace("/statistic=$this->which_statistic&?/", "", $this->paging->GetQueryString()));
            $this->paging->SetPageSize(50);
            $this->paging->SetResultsTextSingular($this->statistic->ItemTypeSingular());
            $this->paging->SetResultsTextPlural($this->statistic->ItemTypePlural());
            $statistics_manager->FilterByPage($this->paging->GetPageSize(), $this->paging->GetCurrentPage());
        }

        $this->data = $this->statistic->ReadStatistic();

        if (is_array($this->data))
        {
            if ($csv) {
                require_once("data/csv.class.php");
                CSV::PublishData($this->data);
            } else {
                $this->paging->SetTotalResults(array_shift($this->data));
            }
        }
        
        unset($statistics_manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle($this->statistic->Title() . " $this->filter");
		$this->SetPageDescription($this->statistic->Description());
        $this->SetContentConstraint(StoolballPage::ConstrainBox());
		$this->LoadClientScript("/scripts/lib/jquery-ui-1.8.11.custom.min.js");
		$this->LoadClientScript("/play/statistics/statistics-filter.js");
		?>
<link rel="stylesheet" href="/css/custom-theme/jquery-ui-1.8.11.custom.css" media="screen" />
		<?php
	}

	function OnPageLoad()
	{
		echo ("<h1>" . htmlentities($this->statistic->Title(), ENT_QUOTES, "UTF-8", false) . " " . htmlentities($this->filter, ENT_QUOTES, "UTF-8", false) . "</h1>");
		$this->GetTheData("large");
        
        if ($this->statistic->ShowDescription()) {
            require_once("markup/xhtml-markup.class.php");
            echo XhtmlMarkup::ApplyParagraphs($this->statistic->Description());
        }
        ?>

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

    case "most-catches-in-innings":
        require_once('stoolball/statistics/player-performance-table.class.php');
        echo new PlayerPerformanceTable("Number of catches, highest first", $this->data, $this->paging->GetFirstResultOnPage(), true);
        break;

    case "most-run-outs-in-innings":
        require_once('stoolball/statistics/player-performance-table.class.php');
        echo new PlayerPerformanceTable("Number of run-outs, highest first", $this->data, $this->paging->GetFirstResultOnPage(), true);
        break;

    case "most-wickets-by-bowler-and-catcher":
        require_once('stoolball/statistics/bowler-catcher-table.class.php');
        echo new BowlerCatcherTable($this->data, $this->paging->GetFirstResultOnPage());
        break;
    
    case "player-performances":
    case "player-of-match":
        require_once('stoolball/statistics/player-performance-table.class.php');
        echo new PlayerPerformanceTable("Player performances, most recent first", $this->data, $this->paging->GetFirstResultOnPage(), false);
        break;

	default:
		require_once('stoolball/statistics/player-statistics-table.class.php');
        $headers = $this->statistic->ColumnHeaders();
		echo new PlayerStatisticsTable($this->statistic->Title(), $headers[0], $this->data, true, $this->paging->GetFirstResultOnPage());
		break;
}

if ($this->paging->GetTotalResults()) echo $this->paging->GetNavigationBar();
?>
</div>
<?php
    $this->GetTheData("small");

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