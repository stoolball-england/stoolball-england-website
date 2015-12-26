<?php
		$statistics_manager->FilterMaxResults(10);
		$this->statistics["best_batting"] = $statistics_manager->ReadBestBattingPerformance();
		$this->statistics["most_runs"] = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
		$this->statistics["batting_average"] = $statistics_manager->ReadBestPlayerAverage("runs_scored", "dismissed", true, "runs_scored", 3);
		$this->statistics["best_bowling"] = $statistics_manager->ReadBestBowlingPerformance();
		$this->statistics["most_wickets"] = $statistics_manager->ReadBestPlayerAggregate("wickets");
		$this->statistics["bowling_average"] = $statistics_manager->ReadBestPlayerAverage("runs_conceded", "wickets_with_bowling", false, "runs_conceded", 3);
		$this->statistics["bowling_economy"] = $statistics_manager->ReadBestPlayerAverage("runs_conceded", "overs_decimal", false, "runs_conceded", 3);
		$this->statistics["bowling_strike_rate"] = $statistics_manager->ReadBestPlayerAverage("balls_bowled", "wickets_with_bowling", false, "balls_bowled", 3);
		$this->statistics["most_catches"] = $statistics_manager->ReadBestPlayerAggregate("catches");
		$this->statistics["most_run_outs"] = $statistics_manager->ReadBestPlayerAggregate("run_outs");
		$this->statistics["most_player_of_match"] = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
?>