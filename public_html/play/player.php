<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/statistics/statistics-manager.class.php');
require_once("stoolball/statistics/statistics-filter.class.php");
require_once("stoolball/statistics/statistics-filter-control.class.php");
require_once("stoolball/user-edit-panel.class.php");
require_once("xhtml/tables/xhtml-table.class.php");

class CurrentPage extends StoolballPage
{
	/**
	 * Player to view
	 * @var Player
	 */
	private $player;
	
		/**
	 * Player to view
	 * @var Player
	 */
	private $player_unfiltered;
	
	private $filter;
	/**
	 * @var StatisticsFilterControl
	 */
	private $filter_control;

	public function OnPageInit()
	{
		# Get team to display players for
		if (!isset($_GET['player']) or !is_numeric($_GET['player'])) $this->Redirect();
		$this->player = new Player($this->GetSettings());
		$this->player->SetId($_GET['player']);
	}

	public function OnLoadPageData()
	{
		# Always get the player's unfiltered profile, because it's needed for the page description
		require_once("stoolball/player-manager.class.php");
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$player_manager->ReadPlayerById($this->player->GetId());
		$this->player_unfiltered = $player_manager->GetFirst();

        # Update search engine
        if ($this->player_unfiltered->GetSearchUpdateRequired())
        {
            require_once("search/lucene-search.class.php");
            $search = new LuceneSearch();
            $search->DeleteDocumentById("player" . $this->player->GetId());
            $search->IndexPlayer($this->player_unfiltered);
            $search->CommitChanges();

            $player_manager->SearchUpdated($this->player->GetId());
        }
		     
		unset($player_manager);

		# Now get statistics for the player
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByPlayer(array($this->player->GetId()));

		# Apply filters common to all statistics
		$this->filter_control = new StatisticsFilterControl();

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

		$filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
		$this->filter_control->SupportBattingPositionFilter($filter_batting_position);
		$this->filter .= $filter_batting_position[2];

		# Now get the statistics for the player
		$data = $statistics_manager->ReadPlayerSummary();
		if (count($data))
		{
			$this->player = $data[0];
		}
		else if ($this->filter)
		{
			# If no matches matched the filter, ensure we have the player's name and team
			$this->player = $this->player_unfiltered;
		}
		else
		{
			$this->player = new Player($this->GetSettings()); # empty object just avoids errors during stats regeneration
		}

		$data = $statistics_manager->ReadBestBattingPerformance(false);
		foreach ($data as $performance)
		{
			$batting = new Batting($this->player, $performance["how_out"], null, null, $performance["runs_scored"]);
			$this->player->Batting()->Add($batting);
		}

		$data = $statistics_manager->ReadBestPlayerAggregate("catches");
		$this->player->SetCatches(count($data) ? $data[0]["statistic"] : 0);

		$data = $statistics_manager->ReadBestPlayerAggregate("run_outs");
		$this->player->SetRunOuts(count($data) ? $data[0]["statistic"] : 0);

		$data = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
		$this->player->SetTotalPlayerOfTheMatchNominations(count($data) ? $data[0]["statistic"] : 0);

		$data = $statistics_manager->ReadBestBowlingPerformance();
		foreach ($data as $performance)
		{
			$bowling = new Bowling($this->player);
			$bowling->SetOvers($performance["overs"]);
			$bowling->SetMaidens($performance["maidens"]);
			$bowling->SetRunsConceded($performance["runs_conceded"]);
			$bowling->SetWickets($performance["wickets"]);

			$this->player->Bowling()->Add($bowling);
		}
		unset($statistics_manager);
	}

	public function OnPrePageLoad()
	{
		if ($this->player->GetId())
		{
			$title = ($this->player->GetPlayerRole() == Player::PLAYER) ? ", a player for " : " conceded by ";
			$title = $this->player->GetName() . $title . $this->player->Team()->GetName() . " stoolball team";

			if ($this->player->GetPlayerRole() == Player::PLAYER)
			{
				$this->SetOpenGraphTitle($this->player->GetName() . ", " . $this->player->Team()->GetName() . " stoolball team");
			}
			if ($this->filter)
			{
				$this->filter = ", " . $this->filter;
				$title .= $this->filter;
			}
			
			$this->SetPageDescription($this->player_unfiltered->GetPlayerDescription());
		}
		else
		{
			$url = $_SERVER['REQUEST_URI'];
			$len = strlen($url);
			if (substr($url, $len-9,9) == "/no-balls" or substr($url, $len-6, 6) == "/wides" or substr($url, $len-5,5) == "/byes" or substr($url, $len-11, 11) == "/bonus-runs")
			{
				$title = "Add statistics for this team";
			}
			else
			{
				$title = "Please come back later";
			}
		}

		$this->SetPageTitle($title);
		$this->SetOpenGraphType("athlete");
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		$this->SetContentCssClass("playerStats");

		$this->LoadClientScript("/scripts/lib/jquery-ui-1.8.11.custom.min.js");
		$this->LoadClientScript("/play/statistics/statistics-filter.js");
        $this->LoadClientScript("/scripts/lib/chart.min.js");
        $this->LoadClientScript("/scripts/chart.js");
        $this->LoadClientScript("/scripts/lib/Chart.StackedBar.js");
        $this->LoadClientScript("/play/statistics/player.js");
		?>
<link rel="stylesheet" href="/css/custom-theme/jquery-ui-1.8.11.custom.css" media="screen" />
<!--[if lte IE 8]><script src="/scripts/lib/excanvas.compiled.js"></script><![endif]-->
		<?php
	}

	public function OnPageLoad()
	{
		if (!$this->player->GetId())
		{
			$url = $_SERVER['REQUEST_URI'];
			$len = strlen($url);
			$no_balls = (substr($url, $len-9,9) == "/no-balls");
			$wides = (substr($url, $len-6, 6) == "/wides");
			$byes = (substr($url, $len-5,5) == "/byes");
			$bonus = (substr($url, $len-11, 11) == "/bonus-runs");
			if ($no_balls or $wides or $byes or $bonus)
			{
				if ($no_balls) $extras = "no balls";
				if ($wides) $extras = "wides";
				if ($byes) $extras = "byes";
				if ($bonus) $extras = "bonus runs";
				?>
<h1>Add statistics for this team</h1>
<p>
	There aren't any
	<?php echo $extras ?>
	recorded yet for this team.
</p>
<p>
	To find out how to add statistics, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results &#8211; why you should add yours</a>.
</p>
	<?php
			}
			else
			{
				?>
<h1>Please come back later</h1>
<p>We're working on something new, and the statistics for this player aren't ready yet.</p>
<p>Please come back later and see whether you can spot what we've done!</p>
				<?php
			}
			return;
		}

		# Container element for structured data
		echo '<div typeof="schema:Person" about="' . htmlentities($this->player->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '">';

		$by = ($this->player->GetPlayerRole() == Player::PLAYER) ? ", " : " conceded by ";
		echo '<h1><span property="schema:name">' . htmlentities($this->player->GetName(), ENT_QUOTES, "UTF-8", false) . '</span>' . htmlentities($by . ' ' . $this->player->Team()->GetName() . $this->filter, ENT_QUOTES, "UTF-8", false) . "</h1>";

		# When has this player played?
		$match_or_matches = ($this->player->GetTotalMatches() == 1) ? " match " : " matches ";

		$filtered = "";
		if ($this->filter)
		{
			# If first played date is missing, the player came from PlayerManager because there were no statistics found
			if ($this->player->GetFirstPlayedDate())
			{
				$filtered = " matching this filter";
			}
			else
			{
				$filtered = ", but none match this filter";
			}
		}

		$years = $this->player->GetPlayingYears();

		$team_name = '<span rel="schema:memberOf"><span about="' . htmlentities($this->player->Team()->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '" typeof="schema:SportsTeam"><a property="schema:name" rel="schema:url" href="' . htmlentities($this->player->Team()->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) . "\">" . htmlentities($this->player->Team()->GetName(), ENT_QUOTES, "UTF-8", false) . "</a></span></span>";

		if ($this->player->GetPlayerRole() == Player::PLAYER)
		{
			echo "<p>" . htmlentities($this->player->GetName() . " played " . $this->player->GetTotalMatches() . $match_or_matches, ENT_QUOTES, "UTF-8", false) . "for ${team_name}${filtered}${years}.</p>";
}
else
{
	echo "<p>$team_name recorded " . htmlentities($this->player->TotalRuns() . " " . strtolower($this->player->GetName()) . " in " . $this->player->GetTotalMatches() . $match_or_matches . "${filtered}${years}.", ENT_QUOTES, "UTF-8", false) . "</p>";
 }
 # Player of match
 if ($this->player->GetTotalPlayerOfTheMatchNominations() > 0)
 {
 	$match_or_matches = ($this->player->GetTotalPlayerOfTheMatchNominations() == 1) ? " match." : " matches.";
 	echo "<p>Nominated player of the match in " . $this->player->GetTotalPlayerOfTheMatchNominations() . $match_or_matches . "</p>";
 }

 # Filter control
 echo $this->filter_control;

 $querystring = 'player=' . $this->player->GetId();
 if ($_SERVER["QUERY_STRING"]) $querystring = htmlspecialchars($_SERVER["QUERY_STRING"]);

 # Batting stats
 $catches = $this->player->GetCatches();
 $run_outs = $this->player->GetRunOuts();
 if ($this->player->TotalBattingInnings() or $catches or $run_outs)
 {
 	//echo "<h2>Batting</h2>";

 	# Overview table
 	$batting_table = new XhtmlTable();
 	$batting_table->SetCssClass("numeric");
 	$batting_table->SetCaption("Batting and fielding");

 	$batting_heading_row = new XhtmlRow(array('<abbr title="Innings" class="small">Inn</abbr><span class="large">Innings</span>', 
 	                                      "Not out", 
 	                                      "Runs", 
 	                                      "Best", 
 	                                      '<abbr title="Average" class="small">Avg</abbr><span class="large">Average</span>',
 	                                      "50s", 
 	                                      "100s", 
 	                                      '<abbr title="Catches" class="small">Ct</abbr><span class="large">Catches</span>',
 	                                      "Run&#8202;-outs"));
 	$batting_heading_row->SetIsHeader(true);
 	$batting_table->AddRow($batting_heading_row);

 	$batting_table->AddRow(new XhtmlRow(array(
 	$this->player->TotalBattingInnings(),
 	$this->player->NotOuts(),
 	$this->player->TotalRuns(),
 	$this->player->BestBatting(),
 	is_null($this->player->BattingAverage()) ? "&#8211;" : $this->player->BattingAverage(),
 	$this->player->Fifties(),
 	$this->player->Centuries(),
 	$catches,
 	$run_outs)));

 	echo $batting_table;

 	if ($this->player->TotalBattingInnings())
 	{
 		echo '<p class="statsViewAll"><a href="/play/statistics/individual-scores?' . $querystring . '">Individual scores &#8211; view all and filter</a></p>';
 	}
    ?>
    <span class="chart-js-template" id="score-spread-chart"></span>
    <span class="chart-js-template" id="dismissals-chart"></span>
    <?php
     }

 if ($this->player->Bowling()->GetCount())
 {
 	//			echo "<h2>Bowling</h2>";

 	# Overview table
 	$bowling_table = new XhtmlTable();
 	$bowling_table->SetCssClass("numeric");
 	$bowling_table->SetCaption("Bowling");

 	$bowling_heading_row = new XhtmlRow(array('<abbr title="Innings" class="small">Inn</abbr><span class="large">Innings</span>', 
 	                                      '<abbr title="Overs" class="small">Ov</abbr><span class="large">Overs</span>', 
 	                                      '<abbr title="Maiden overs" class="small">Md</abbr><abbr title="Maiden overs" class="large">Mdns</abbr>', 
 	                                      "Runs", 
 	                                      '<abbr title="Wickets" class="small">Wk</abbr><abbr title="Wickets" class="large">Wkts</abbr>',
 	                                      "Best", 
 	                                      '<abbr title="Economy" class="small">Econ</abbr><span class="large">Economy</span>', 
 	                                      '<abbr title="Average" class="small">Avg</abbr><span class="large">Average</span>', 
 	                                      '<abbr title="Strike rate" class="small">S/R</abbr><span class="large">Strike rate</span>', 
 	                                      '<abbr title="5 wickets" class="small">5 wk</abbr><abbr title="5 wickets" class="large">5 wkts</abbr>'));
 	$bowling_heading_row->SetIsHeader(true);
 	$bowling_table->AddRow($bowling_heading_row);

 	$bowling_table->AddRow(new XhtmlRow(array(
 	$this->player->BowlingInnings(),
 	$this->player->Overs(),
 	$this->player->MaidenOvers(),
 	$this->player->RunsAgainst(),
 	$this->player->WicketsTaken(),
 	is_null($this->player->BestBowling()) ? "&#8211;" : $this->player->BestBowling(),
 	is_null($this->player->BowlingEconomy()) ? "&#8211;" : $this->player->BowlingEconomy(),
 	is_null($this->player->BowlingAverage()) ? "&#8211;" : $this->player->BowlingAverage(),
 	is_null($this->player->BowlingStrikeRate()) ? "&#8211;" : $this->player->BowlingStrikeRate(),
 	$this->player->FiveWicketHauls()
 	)));

 	echo $bowling_table;

 	echo '<p class="statsViewAll"><a href="/play/statistics/bowling-performances?' . $querystring . '">Bowling performances &#8211; view all and filter</a></p>';
 }
/*
 if ($this->player->WicketsTaken())
 {
 	# Wickets - breakdown by dismissal method
 	$how_wickets_taken = $this->player->HowWicketsTaken();
    
 	$wickets_data = array();
 	if (array_key_exists(Batting::CAUGHT, $how_wickets_taken)) $wickets_data[ucfirst(html_entity_decode(Batting::Text(Batting::CAUGHT))) . " (" . $how_wickets_taken[Batting::CAUGHT] . ")"] = $how_wickets_taken[Batting::CAUGHT];
 	if (array_key_exists(Batting::CAUGHT_AND_BOWLED, $how_wickets_taken)) $wickets_data[ucfirst(html_entity_decode(Batting::Text(Batting::CAUGHT_AND_BOWLED))) . " (" . $how_wickets_taken[Batting::CAUGHT_AND_BOWLED] . ")"] = $how_wickets_taken[Batting::CAUGHT_AND_BOWLED];
 	if (array_key_exists(Batting::BOWLED, $how_wickets_taken)) $wickets_data[ucfirst(html_entity_decode(Batting::Text(Batting::BOWLED))) . " (" . $how_wickets_taken[Batting::BOWLED] . ")"] = $how_wickets_taken[Batting::BOWLED];
 	if (array_key_exists(Batting::BODY_BEFORE_WICKET, $how_wickets_taken)) $wickets_data[ucfirst(html_entity_decode(Batting::Text(Batting::BODY_BEFORE_WICKET))) . " (" . $how_wickets_taken[Batting::BODY_BEFORE_WICKET] . ")"] = $how_wickets_taken[Batting::BODY_BEFORE_WICKET];
 	if (array_key_exists(Batting::HIT_BALL_TWICE, $how_wickets_taken)) $wickets_data[ucfirst(html_entity_decode(Batting::Text(Batting::HIT_BALL_TWICE))) . " (" . $how_wickets_taken[Batting::HIT_BALL_TWICE] . ")"] = $how_wickets_taken[Batting::HIT_BALL_TWICE];

 	$wickets_chart = new PieChart(400,147,"How this player takes wickets");
 	$wickets_chart->SetChartData($wickets_data);
 	echo $wickets_chart;
 }
*/
 echo '<p>View <a href="' . htmlentities($this->player->Team()->GetPlayersNavigateUrl(), ENT_QUOTES, "UTF-8", false) . '">player statistics for ' . htmlentities($this->player->Team()->GetName(), ENT_QUOTES, "UTF-8", false) . '</a>.</p>';

 # End container for structured data
 echo "</div>";

        $this->ShowSocial();

 $has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_PLAYERS));
 if ($has_permission)
 {
 	$this->AddSeparator();
 	$panel = new UserEditPanel($this->GetSettings());
 	$panel->AddLink("edit this player", $this->player->GetEditUrl());
 	$panel->AddLink("delete this player", $this->player->GetDeleteUrl());
 	echo $panel;
 }
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>