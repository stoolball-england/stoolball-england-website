<?php
/**
 * Provides statistics based on match data
 *
 */
class StatisticsCalculator
{
	private $home_wins = array();
	private $home_losses = array();
	private $home_equal_results = array();
	private $home_no_results = array();
	private $home_runs_scored = array();
	private $home_runs_conceded = array();
	private $away_wins = array();
	private $away_losses = array();
	private $away_equal_results = array();
	private $away_no_results = array();
	private $away_runs_scored = array();
	private $away_runs_conceded = array();
	private $matches_with_run_data = array();
	private $opponents = array();
	private $seasons = array();

	/**
	 * Analyses supplied match data and makes relevant statistics available
	 *
	 * @param Match[] $data
	 * @param Team $context_team
	 */
	public function AnalyseMatchData($data, Team $context_team)
	{
		foreach ($data as $match)
		{
			/* @var $match Match */
			$season_dates = Season::SeasonDates($match->GetStartTime());
			$season_key = date('Y', $season_dates[0]);
			if ($season_key == date('Y', $season_dates[1]))
			{
				# For summer stats, use the ground the match is played at rather than the team
				# role to decide whether it's a home match.
				# In this case we're interested in home advantage, which applies whether you're
				# the team paying for the pitch or not.
				$at_home = ($context_team->GetGround()->GetId() == $match->GetGroundId());
				$swap_home_away_role = ($context_team->GetId() == $match->GetHomeTeamId()) != $at_home;
			}
			else
			{
				# Winter seasons span two years, so add second year to key
				$season_key .= "/" . date('y', $season_dates[1]);

				# For winter it's simpler - just use the team role. Teams are rarely down to play
				# at their home ground because they all
				# play "away" at a leisure centre. Using the rules above ends up with teams
				# getting the stats which belong to their opponents.
				$at_home = ($context_team->GetId() == $match->GetHomeTeamId());
				$swap_home_away_role = false;
			}
			//$at_home = ($context_team->GetId() == $match->GetHomeTeamId());
			//$swap_home_away_role = false;

			if (!in_array($season_key, $this->seasons))
				$this->seasons[] = $season_key;

			if (($match->Result()->GetIsHomeWin() and !$swap_home_away_role) or ($match->Result()->GetIsAwayWin() and $swap_home_away_role))
			{
				if ($at_home)
				{
					if (!array_key_exists($season_key, $this->home_wins))
						$this->home_wins[$season_key] = array();
					$this->home_wins[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetHomeTeam() : $match->GetAwayTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['wins']++;
				}
				else
				{
					if (!array_key_exists($season_key, $this->away_losses))
						$this->away_losses[$season_key] = array();
					$this->away_losses[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetAwayTeam() : $match->GetHomeTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['losses']++;
				}

			}
			else if (($match->Result()->GetIsAwayWin() and !$swap_home_away_role) or ($match->Result()->GetIsHomeWin() and $swap_home_away_role))
			{
				if ($at_home)
				{
					if (!array_key_exists($season_key, $this->home_losses))
						$this->home_losses[$season_key] = array();
					$this->home_losses[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetHomeTeam() : $match->GetAwayTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['losses']++;
				}
				else
				{
					if (!array_key_exists($season_key, $this->away_wins))
						$this->away_wins[$season_key] = array();
					$this->away_wins[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetAwayTeam() : $match->GetHomeTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['wins']++;
				}
			}
			else if ($match->Result()->GetIsEqualResult())
			{
				if ($at_home)
				{
					if (!array_key_exists($season_key, $this->home_equal_results))
						$this->home_equal_results[$season_key] = array();
					$this->home_equal_results[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetHomeTeam() : $match->GetAwayTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['equal']++;
				}
				else
				{
					if (!array_key_exists($season_key, $this->away_equal_results))
						$this->away_equal_results[$season_key] = array();
					$this->away_equal_results[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetAwayTeam() : $match->GetHomeTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['equal']++;
				}
			}
			else if ($match->Result()->GetIsNoResult() and !$match->Result()->GetResultType() == MatchResult::POSTPONED)
			{
				if ($at_home)
				{
					if (!array_key_exists($season_key, $this->home_no_results))
						$this->home_no_results[$season_key] = array();
					$this->home_no_results[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetHomeTeam() : $match->GetAwayTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['cancelled']++;
				}
				else
				{
					if (!array_key_exists($season_key, $this->away_no_results))
						$this->away_no_results[$season_key] = array();
					$this->away_no_results[$season_key][] = $match;

					# Note the opponents played, and the results achieved against them
					$opponent = $swap_home_away_role ? $match->GetAwayTeam() : $match->GetHomeTeam();
					$this->EnsureTeamArray($season_key, $opponent);
					$this->opponents[$season_key][$opponent->GetId()]['matches']++;
					$this->opponents[$season_key][$opponent->GetId()]['cancelled']++;
				}
			}

			# Now gather runs scored and conceded
			$run_data_for_match = false;
			if ($at_home)
			{
				if (!array_key_exists($season_key, $this->home_runs_scored))
					$this->home_runs_scored[$season_key] = array();
				if (!array_key_exists($season_key, $this->home_runs_conceded))
					$this->home_runs_conceded[$season_key] = array();

				$context_team_runs = $swap_home_away_role ? $match->Result()->GetAwayRuns() : $match->Result()->GetHomeRuns();
				$opposition_runs = $swap_home_away_role ? $match->Result()->GetHomeRuns() : $match->Result()->GetAwayRuns();

				if ($context_team_runs)
				{
					$this->home_runs_scored[$season_key][] = $context_team_runs;
					$run_data_for_match = true;
				}
				if ($opposition_runs)
				{
					$this->home_runs_conceded[$season_key][] = $opposition_runs;
					$run_data_for_match = true;
				}
			}
			else
			{
				if (!array_key_exists($season_key, $this->away_runs_scored))
					$this->away_runs_scored[$season_key] = array();
				if (!array_key_exists($season_key, $this->away_runs_conceded))
					$this->away_runs_conceded[$season_key] = array();

				$context_team_runs = $swap_home_away_role ? $match->Result()->GetHomeRuns() : $match->Result()->GetAwayRuns();
				$opposition_runs = $swap_home_away_role ? $match->Result()->GetAwayRuns() : $match->Result()->GetHomeRuns();

				if ($context_team_runs)
				{
					$this->away_runs_scored[$season_key][] = $context_team_runs;
					$run_data_for_match = true;
				}
				if ($opposition_runs)
				{
					$this->away_runs_conceded[$season_key][] = $opposition_runs;
					$run_data_for_match = true;
				}
			}

			if ($run_data_for_match)
			{
				if (!array_key_exists($season_key, $this->matches_with_run_data))
					$this->matches_with_run_data[$season_key] = 0;
				$this->matches_with_run_data[$season_key]++;
			}
		}
		rsort($this->seasons);
	}

	/**
	 * Ensures that an entry exists for the given team in the internal opponents
	 * array
	 *
	 * @param string $season_key
	 * @param Team $team
	 */
	private function EnsureTeamArray($season_key, Team $team)
	{
		if (!array_key_exists($season_key, $this->opponents))
			$this->opponents[$season_key] = array();
		if (!array_key_exists($team->GetId(), $this->opponents[$season_key]))
		{
			$this->opponents[$season_key][$team->GetId()] = array();
			$this->opponents[$season_key][$team->GetId()]['team'] = $team;
			$this->opponents[$season_key][$team->GetId()]['matches'] = 0;
			$this->opponents[$season_key][$team->GetId()]['wins'] = 0;
			$this->opponents[$season_key][$team->GetId()]['losses'] = 0;
			$this->opponents[$season_key][$team->GetId()]['equal'] = 0;
			$this->opponents[$season_key][$team->GetId()]['cancelled'] = 0;
		}
	}

	/**
	 * Counts how many times a particular result was achieved
	 *
	 * @param Match[] $match_data
	 * @param string $season_years
	 * @return int
	 */
	private function CountResults($match_data, $season_years = null)
	{
		$result_count = 0;
		if (is_null($season_years))
		{
			foreach ($match_data as $key => $data)
				$result_count += count($match_data[$key]);
		}
		else
		{
			if (array_key_exists($season_years, $match_data))
				$result_count += count($match_data[$season_years]);
		}
		return $result_count;
	}

	/**
	 * Counts how many matches the team identified in the call to AnalyseMatchData
	 * played at home
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HomeMatches($season_years = null)
	{
		return $this->HomeWins($season_years) + $this->HomeLosses($season_years) + $this->HomeEqualResults($season_years) + $this->HomeNoResults($season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData won
	 * at home
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HomeWins($season_years = null)
	{
		return $this->CountResults($this->home_wins, $season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData lost
	 * at home
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HomeLosses($season_years = null)
	{
		return $this->CountResults($this->home_losses, $season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData tied
	 * at home
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HomeEqualResults($season_years = null)
	{
		return $this->CountResults($this->home_equal_results, $season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData had
	 * to cancel or abandon a match at home
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HomeNoResults($season_years = null)
	{
		return $this->CountResults($this->home_no_results, $season_years);
	}

	/**
	 * Counts how many matches the team identified in the call to AnalyseMatchData
	 * played away
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function AwayMatches($season_years = null)
	{
		return $this->AwayWins($season_years) + $this->AwayLosses($season_years) + $this->AwayEqualResults($season_years) + $this->AwayNoResults($season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData won
	 * away
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function AwayWins($season_years = null)
	{
		return $this->CountResults($this->away_wins, $season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData lost
	 * away
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function AwayLosses($season_years = null)
	{
		return $this->CountResults($this->away_losses, $season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData tied
	 * away
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function AwayEqualResults($season_years = null)
	{
		return $this->CountResults($this->away_equal_results, $season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData had
	 * to cancel or abandon an away match
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function AwayNoResults($season_years = null)
	{
		return $this->CountResults($this->away_no_results, $season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData won
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function Wins($season_years = null)
	{
		return $this->HomeWins($season_years) + $this->AwayWins($season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData lost
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function Losses($season_years = null)
	{
		return $this->HomeLosses($season_years) + $this->AwayLosses($season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData tied
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function EqualResults($season_years = null)
	{
		return $this->HomeEqualResults($season_years) + $this->AwayEqualResults($season_years);
	}

	/**
	 * Counts how many times the team identified in the call to AnalyseMatchData had
	 * to cancel or abandon a match
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function NoResults($season_years = null)
	{
		return $this->HomeNoResults($season_years) + $this->AwayNoResults($season_years);
	}

	/**
	 * Counts how many runs were recorded in a given category
	 *
	 * @param string[int[]] $run_data
	 * @param string $season_years
	 * @return int
	 */
	private function CountRuns($run_data, $season_years = null)
	{
		$run_count = 0;
		if (is_null($season_years))
		{
			foreach ($run_data as $key => $data)
			{
				if (count($run_data[$key]))
					$run_count += array_sum($run_data[$key]);
			}
		}
		else
		{
			if (array_key_exists($season_years, $run_data) and count($run_data[$season_years]))
				$run_count += array_sum($run_data[$season_years]);
		}
		return $run_count;
	}

	/**
	 * Counts how many runs the team identified in the call to AnalyseMatchData
	 * scored at home
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HomeRunsScored($season_years = null)
	{
		return $this->CountRuns($this->home_runs_scored, $season_years);
	}

	/**
	 * Counts how many runs the team identified in the call to AnalyseMatchData
	 * conceded at home
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HomeRunsConceded($season_years = null)
	{
		return $this->CountRuns($this->home_runs_conceded, $season_years);
	}

	/**
	 * Counts how many runs the team identified in the call to AnalyseMatchData
	 * scored away
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function AwayRunsScored($season_years = null)
	{
		return $this->CountRuns($this->away_runs_scored, $season_years);
	}

	/**
	 * Counts how many runs the team identified in the call to AnalyseMatchData
	 * conceded away
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function AwayRunsConceded($season_years = null)
	{
		return $this->CountRuns($this->away_runs_conceded, $season_years);
	}

	/**
	 * Counts how many runs the team identified in the call to AnalyseMatchData
	 * scored
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function RunsScored($season_years = null)
	{
		return $this->HomeRunsScored($season_years) + $this->AwayRunsScored($season_years);
	}

	/**
	 * Counts how many runs the team identified in the call to AnalyseMatchData
	 * conceded
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function RunsConceded($season_years = null)
	{
		return $this->HomeRunsConceded($season_years) + $this->AwayRunsConceded($season_years);
	}

	/**
	 * Runs a calculation on the runs scored by the team identified in the call to
	 * AnalyseMatchData
	 *
	 * @param string[int[]] $run_data
	 * @param string $season_years
	 * @param string $function
	 * @return int
	 */
	private function ExtremeInnings($run_data, $season_years = null, $function)
	{
		if (is_null($season_years))
		{
			$combined_run_data = array();
			foreach ($run_data as $key => $data)
			{
				$combined_run_data = array_merge($combined_run_data, $run_data[$key]);
			}
			if (count($combined_run_data))
				return $function($combined_run_data);
		}
		else
		{
			if (array_key_exists($season_years, $run_data) and count($run_data[$season_years]))
				return $function($run_data[$season_years]);
		}
		return null;
	}

	/**
	 * Calculates the highest team score at home by the team identified in the call
	 * to AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HighestHomeInnings($season_years = null)
	{
		return $this->ExtremeInnings($this->home_runs_scored, $season_years, 'max');
	}

	/**
	 * Calculates the highest team score away by the team identified in the call to
	 * AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HighestAwayInnings($season_years = null)
	{
		return $this->ExtremeInnings($this->away_runs_scored, $season_years, 'max');
	}

	/**
	 * Calculates the lowest team score at home by the team identified in the call to
	 * AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function LowestHomeInnings($season_years = null)
	{
		return $this->ExtremeInnings($this->home_runs_scored, $season_years, 'min');
	}

	/**
	 * Calculates the lowest team score away by the team identified in the call to
	 * AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function LowestAwayInnings($season_years = null)
	{
		return $this->ExtremeInnings($this->away_runs_scored, $season_years, 'min');
	}

	/**
	 * Calculates the average runs scored by the team identified in the call to
	 * AnalyseMatchData
	 *
	 * @param string[int[]] $run_data
	 * @param string $season_years
	 * @return int
	 */
	private function CalculateAverageInnings($run_data, $season_years = null)
	{
		if (is_null($season_years))
		{
			$combined_run_data = array();
			foreach ($run_data as $key => $data)
			{
				$combined_run_data = array_merge($combined_run_data, $run_data[$key]);
			}
			if (count($combined_run_data))
				return $this->CalculateAverage($combined_run_data);
		}
		else
		{
			if (array_key_exists($season_years, $run_data) and count($run_data[$season_years]))
				return $this->CalculateAverage($run_data[$season_years]);
		}
		return null;
	}

	/**
	 * Works out the average value of an array of integers
	 *
	 * @param int[] $array
	 * @return float
	 */
	private function CalculateAverage($array)
	{
		$count = count($array);
		if (!$count)
			return 0;
		return array_sum($array) / $count;
	}

	/**
	 * Calculates the average team score at home by the team identified in the call
	 * to AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return float
	 */
	public function AverageHomeInnings($season_years = null)
	{
		return $this->CalculateAverageInnings($this->home_runs_scored, $season_years);
	}

	/**
	 * Calculates the average team score away by the team identified in the call to
	 * AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return float
	 */
	public function AverageAwayInnings($season_years = null)
	{
		return $this->CalculateAverageInnings($this->away_runs_scored, $season_years);
	}

	/**
	 * Calculates the highest team score by the team identified in the call to
	 * AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function HighestInnings($season_years = null)
	{
		$home = $this->HighestHomeInnings($season_years);
		$away = $this->HighestAwayInnings($season_years);

		if (is_null($home))
			return $away;
		else if (is_null($away))
			return $home;
		else
			return ($home > $away) ? $home : $away;
	}

	/**
	 * Calculates the lowest team score by the team identified in the call to
	 * AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function LowestInnings($season_years = null)
	{
		$home = $this->LowestHomeInnings($season_years);
		$away = $this->LowestAwayInnings($season_years);

		if (is_null($home))
			return $away;
		else if (is_null($away))
			return $home;
		else
			return ($home < $away) ? $home : $away;
	}

	/**
	 * Calculates the average team score by the team identified in the call to
	 * AnalyseMatchData conceded
	 *
	 * @param string $season_years
	 * @return float
	 */
	public function AverageInnings($season_years = null)
	{
		$home = $this->AverageHomeInnings($season_years);
		$away = $this->AverageAwayInnings($season_years);

		if (is_null($home))
			return $away;
		else if (is_null($away))
			return $home;
		else
			return ($home < $away) ? $home : $away;
	}

	/**
	 * Calculates how many matches of those passed in the call to AnalyseMatchData
	 * have at least partial run data
	 *
	 * @param string $season_years
	 * @return int
	 */
	public function TotalMatchesWithRunData($season_years = null)
	{
		$match_count = 0;
		if (is_null($season_years))
		{
			foreach ($this->matches_with_run_data as $key => $data)
				$match_count += $this->matches_with_run_data[$key];
		}
		else
		{
			if (array_key_exists($season_years, $this->matches_with_run_data))
				$match_count = $this->matches_with_run_data[$season_years];

		}
		return $match_count;
	}

	/**
	 * Gets the opponents the team identified in the call to AnalyseMatchData has
	 * played
	 *
	 * @param string $season_years
	 * @return [Team,int,int]
	 */
	public function Opponents($season_years = null)
	{
		$opponents = array();

		if (is_null($season_years))
		{
			foreach ($this->opponents as $season_years => $teams_array)
			{
				foreach ($teams_array as $team_id => $team_data)
				{
					if (!array_key_exists($team_id, $opponents))
						$opponents[$team_id] = $team_data;
					else
					{
						$opponents[$team_id]['matches'] = $opponents[$team_id]['matches'] + $team_data['matches'];
						$opponents[$team_id]['wins'] = $opponents[$team_id]['wins'] + $team_data['wins'];
						$opponents[$team_id]['losses'] = $opponents[$team_id]['losses'] + $team_data['losses'];
						$opponents[$team_id]['equal'] = $opponents[$team_id]['equal'] + $team_data['equal'];
						$opponents[$team_id]['cancelled'] = $opponents[$team_id]['cancelled'] + $team_data['cancelled'];
					}
				}
			}
		}
        elseif (array_key_exists($season_years, $this->opponents)) 
		{
			$opponents = $this->opponents[$season_years];
		}
		return $opponents;
	}

	/**
	 * Gets the years of seasons which have data available
	 *
	 * @return string[]
	 */
	public function SeasonYears()
	{
		return $this->seasons;
	}

	/**
	 * Gets whether there is any data available for the team identified in the call
	 * to AnalyseMatchData
	 *
	 * @param $season_years string
	 * @return bool
	 */
	public function EnoughDataForStats($season_years = null)
	{
		if ($this->TotalMatchesWithRunData($season_years))
			return true;
		if (is_null($season_years))
		{
			if (count($this->home_wins))
			{
				return true;
			}
			if (count($this->home_losses))
			{
				return true;
			}
			if (count($this->home_equal_results))
			{
				return true;
			}
			if (count($this->home_no_results))
			{
				return true;
			}
			if (count($this->home_runs_scored))
			{
				return true;
			}
			if (count($this->home_runs_conceded))
			{
				return true;
			}
			if (count($this->away_wins))
			{
				return true;
			}
			if (count($this->away_losses))
			{
				return true;
			}
			if (count($this->away_equal_results))
			{
				return true;
			}
			if (count($this->away_no_results))
			{
				return true;
			}
			if (count($this->away_runs_scored))
			{
				return true;
			}
			if (count($this->away_runs_conceded))
			{
				return true;
			}
		}
		else
		{
			if (array_key_exists($season_years, $this->home_wins))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->home_losses))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->home_equal_results))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->home_no_results))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->home_runs_scored))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->home_runs_conceded))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->away_wins))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->away_losses))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->away_equal_results))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->away_no_results))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->away_runs_scored))
			{
				return true;
			}
			if (array_key_exists($season_years, $this->away_runs_conceded))
			{
				return true;
			}
		}
		return false;
	}

}
?>