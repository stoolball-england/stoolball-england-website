<?php
require_once('stoolball/statistics/player-statistics-table.class.php');
if ($has_best_batting or $has_most_runs or $has_batting_average)
{
	# Show top batters
	echo '<h2>Batting statistics</h2>';

	if ($has_best_batting)
	{
		require_once('stoolball/statistics/batting-innings-table.class.php');
		$best_batting = new BattingInningsTable($this->statistics["best_batting"], true, 1, 10);
		echo $best_batting;

		if ($has_best_batting >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/individual-scores' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Individual scores &#8211; view all and filter</a></p>';
	}

	if ($has_most_runs)
	{
		echo new PlayerStatisticsTable("Most runs", "Runs", $this->statistics["most_runs"]);
		if ($has_most_runs >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-runs' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Most runs &#8211; view all and filter</a></p>';
	}

	if ($has_batting_average)
	{
		echo new PlayerStatisticsTable("Best batting average", "Average", $this->statistics["batting_average"]);
		if ($has_batting_average >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/batting-average' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Batting averages &#8211; view all and filter</a></p>';
	}

    echo '<p class="statsViewAll"><a href="/play/statistics/batting-strike-rate' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Batting strike rate &#8211; view all and filter</a></p>';
}

if ($has_best_bowling or $has_most_wickets or $has_bowling_average or $has_bowling_strike_rate)
{
	# Show top bowlers
	echo '<h2>Bowling statistics</h2>';

	if ($has_best_bowling)
	{
		require_once('stoolball/statistics/bowling-performance-table.class.php');
		$best_bowling = new BowlingPerformanceTable($this->statistics["best_bowling"], true, 1, 10);
		$best_bowling->SetCssClass("stats");
		echo $best_bowling;

		if ($has_best_bowling >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/bowling-performances' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Bowling performances &#8211; view all and filter</a></p>';
	}

	if ($has_most_wickets)
	{
		echo new PlayerStatisticsTable("Most wickets", "Wickets", $this->statistics["most_wickets"]);
		if ($has_most_wickets >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-wickets' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Most wickets &#8211; view all and filter</a></p>';
	}

	if ($has_bowling_average)
	{
		echo new PlayerStatisticsTable("Best bowling average", "Average", $this->statistics["bowling_average"]);
		if ($has_bowling_average >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/bowling-average' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Bowling averages &#8211; view all and filter</a></p>';
	}

	if ($has_bowling_economy)
	{
		echo new PlayerStatisticsTable("Best economy rate", "Economy rate", $this->statistics["bowling_economy"]);
		if ($has_bowling_economy >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/economy-rate' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Economy rates &#8211; view all and filter</a></p>';
	}

	if ($has_bowling_strike_rate)
	{
		echo new PlayerStatisticsTable("Best bowling strike rate", "Strike rate", $this->statistics["bowling_strike_rate"]);
		if ($has_bowling_strike_rate >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/bowling-strike-rate' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Bowling strike rates &#8211; view all and filter</a></p>';
	}
}

if ($has_catch_stats or $has_run_outs)
{
	echo '<h2>Fielding statistics</h2>';

	if ($has_catch_stats)
	{
		echo new PlayerStatisticsTable("Most catches", "Catches", $this->statistics["most_catches"]);
		if ($has_catch_stats >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-catches' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Most catches &#8211; view all and filter</a></p>';
        echo '<p class="statsViewAll"><a href="/play/statistics/most-catches-in-innings' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Most catches in an innings &#8211; view all and filter</a></p>';
	}

	if ($has_run_outs)
	{
		echo new PlayerStatisticsTable("Most run-outs", "Run-outs", $this->statistics["most_run_outs"]);
		if ($has_run_outs >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-run-outs' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Most run-outs &#8211; view all and filter</a></p>';
        echo '<p class="statsViewAll"><a href="/play/statistics/most-run-outs-in-innings' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Most run-outs in an innings &#8211; view all and filter</a></p>';
	}
}

    echo '<h2>All-round performance statistics</h2>';
if ($has_player_of_match_stats)
{

	echo new PlayerStatisticsTable("Most player of the match nominations", "Nominations", $this->statistics["most_player_of_match"]);
    echo '<p class="statsViewAll"><a href="/play/statistics/player-performances' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Player performances &#8211; view all and filter</a></p>';
	echo '<p class="statsViewAll"><a href="/play/statistics/player-of-match' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Player of the match nominations &#8211; view all and filter</a></p>';
	if ($has_player_of_match_stats >= 10) echo '<p class="statsViewAll"><a href="/play/statistics/most-player-of-match' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Most player of the match nominations &#8211; view all and filter</a></p>';
}
else {
	echo '<p><a href="/play/statistics/player-performances' . htmlentities($this->statistics["querystring"], ENT_QUOTES, "UTF-8", false) . '">Player performances &#8211; view all and filter</a></p>';
    
}
?>