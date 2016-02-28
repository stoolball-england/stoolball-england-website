<?php
require_once('stoolball/statistics/player-statistics-table.class.php');
$statistics_query = Html::Encode($this->statistics["querystring"]);
if ($has_best_batting or $has_most_runs or $has_batting_average)
{
	# Show top batters
	echo '<h2>Batting statistics</h2>';

	if ($has_best_batting)
	{
		require_once('stoolball/statistics/batting-innings-table.class.php');
		$best_batting = new BattingInningsTable($this->statistics["best_batting"], true, 1, 10);
		echo $best_batting;

		if ($has_best_batting >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/individual-scores' . $statistics_query . '">Individual scores &#8211; view all and filter</a></p>';
        
        if (preg_match("/^\/lewes[0-9]*\/statistics/", $_SERVER["REQUEST_URI"]) === 1) {
            echo '<p class="statsViewAll"><a href="/play/statistics/most-scores-of-40' . $statistics_query . '">Most scores of 40 or more</a></p>';
        } else {
            echo '<p class="statsViewAll"><a href="/play/statistics/most-scores-of-100' . $statistics_query . '">Most scores of 100 or more</a></p>';
            echo '<p class="statsViewAll"><a href="/play/statistics/most-scores-of-50' . $statistics_query . '">Most scores of 50 or more</a></p>';
        }
	}

	if ($has_most_runs)
	{
		echo new PlayerStatisticsTable("Most runs", "Runs", $this->statistics["most_runs"]);
		if ($has_most_runs >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-runs' . $statistics_query . '">Most runs &#8211; view all and filter</a></p>';
	}

	if ($has_batting_average)
	{
		echo new PlayerStatisticsTable("Best batting average", "Average", $this->statistics["batting_average"]);
		if ($has_batting_average >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/batting-average' . $statistics_query . '">Batting averages &#8211; view all and filter</a></p>';
	}

    echo '<p class="statsViewAll"><a href="/play/statistics/batting-strike-rate' . $statistics_query . '">Batting strike rate</a></p>';
}

if ($has_best_bowling or $has_most_wickets or $has_bowling_average or $has_bowling_strike_rate)
{
	# Show top bowlers
	echo '<h2>Bowling statistics</h2>';

	if ($has_best_bowling)
	{
		require_once('stoolball/statistics/bowling-performance-table.class.php');
		$best_bowling = new BowlingPerformanceTable($this->statistics["best_bowling"], true, 1, 10);
		$best_bowling->SetCssClass("stats bowling");
		echo $best_bowling;

		if ($has_best_bowling >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/bowling-performances' . $statistics_query . '">Bowling performances &#8211; view all and filter</a></p>';
	}

	if ($has_most_wickets)
	{
		$table = new PlayerStatisticsTable("Most wickets", "Wickets", $this->statistics["most_wickets"]);
		$table->SetCssClass("bowling");
        echo $table;
		if ($has_most_wickets >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-wickets' . $statistics_query . '">Most wickets &#8211; view all and filter</a></p>';
        echo '<p class="statsViewAll"><a href="/play/statistics/most-5-wickets' . $statistics_query . '">Most times taking 5 wickets in an innings</a></p>';
        echo '<p class="statsViewAll"><a href="/play/statistics/most-wickets-by-bowler-and-catcher' . $statistics_query . '">Most wickets by a bowling and catching combination</a></p>';
	}

	if ($has_bowling_average)
	{
		$table = new PlayerStatisticsTable("Best bowling average", "Average", $this->statistics["bowling_average"]);
        $table->SetCssClass("bowling");
        echo $table;
		if ($has_bowling_average >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/bowling-average' . $statistics_query . '">Bowling averages &#8211; view all and filter</a></p>';
	}

	if ($has_bowling_economy)
	{
		$table = new PlayerStatisticsTable("Best economy rate", "Economy rate", $this->statistics["bowling_economy"]);
        $table->SetCssClass("bowling");
        echo $table;
				if ($has_bowling_economy >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/economy-rate' . $statistics_query . '">Economy rates &#8211; view all and filter</a></p>';
	}

	if ($has_bowling_strike_rate)
	{
		$table = new PlayerStatisticsTable("Best bowling strike rate", "Strike rate", $this->statistics["bowling_strike_rate"]);
        $table->SetCssClass("bowling");
        echo $table;
		if ($has_bowling_strike_rate >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/bowling-strike-rate' . $statistics_query . '">Bowling strike rates &#8211; view all and filter</a></p>';
	}
}

if ($has_catch_stats or $has_run_outs)
{
	echo '<h2>Fielding statistics</h2>';

	if ($has_catch_stats)
	{
		$table = new PlayerStatisticsTable("Most catches", "Catches", $this->statistics["most_catches"]);
        $table->SetCssClass("bowling");
        echo $table;
		if ($has_catch_stats >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-catches' . $statistics_query . '">Most catches &#8211; view all and filter</a></p>';
        echo '<p class="statsViewAll"><a href="/play/statistics/most-catches-in-innings' . $statistics_query . '">Most catches in an innings</a></p>';
	}

	if ($has_run_outs)
	{
		$table = new PlayerStatisticsTable("Most run-outs", "Run-outs", $this->statistics["most_run_outs"]);
        $table->SetCssClass("bowling");
        echo $table;
		if ($has_run_outs >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-run-outs' . $statistics_query . '">Most run-outs &#8211; view all and filter</a></p>';
        echo '<p class="statsViewAll"><a href="/play/statistics/most-run-outs-in-innings' . $statistics_query . '">Most run-outs in an innings</a></p>';
	}
}

    echo '<h2>All-round performance statistics</h2>';
if ($has_player_of_match_stats)
{

	echo new PlayerStatisticsTable("Most player of the match nominations", "Nominations", $this->statistics["most_player_of_match"]);
	if ($has_player_of_match_stats >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-player-of-match' . $statistics_query . '">Most player of the match nominations &#8211; view all and filter</a></p>';
    echo '<p class="statsViewAll"><a href="/play/statistics/player-of-match' . $statistics_query . '">Player of the match nominations</a></p>';
    echo '<p class="statsViewAll"><a href="/play/statistics/player-performances' . $statistics_query . '">Player performances</a></p>';
}
else {
	echo '<p><a href="/play/statistics/player-performances' . $statistics_query . '">Player performances</a></p>';
    
}
?>