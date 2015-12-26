<?php
$has_best_batting = count($this->statistics["best_batting"]);
$has_most_runs = count($this->statistics["most_runs"]);
$has_batting_average = count($this->statistics["batting_average"]);
$has_best_bowling = count($this->statistics["best_bowling"]);
$has_most_wickets = count($this->statistics["most_wickets"]);
$has_bowling_average = count($this->statistics["bowling_average"]);
$has_bowling_economy = count($this->statistics["bowling_economy"]);
$has_bowling_strike_rate = count($this->statistics["bowling_strike_rate"]);
$has_catch_stats = count($this->statistics["most_catches"]);
$has_run_outs = count($this->statistics["most_run_outs"]);
$has_player_of_match_stats = count($this->statistics["most_player_of_match"]);

$has_player_stats = (
$has_best_batting or $has_most_runs or $has_batting_average
or $has_best_bowling or $has_most_wickets or $has_bowling_average or $has_bowling_economy or $has_bowling_strike_rate
or $has_catch_stats or $has_run_outs
or $has_player_of_match_stats
);
?>