<?php
/**
 * An individual bowling performance
 * @author Rick
 *
 */
class Bowling
{
	private $player;
	private $overs;
	private $maidens;
	private $runs_conceded;
	private $wickets;
	private $match;
	private $opposition;

	/**
	 * Creates an instance of an individual bowling performance
	 * @param Player $player
	 * @return void
	 */
	public function __construct(Player $player)
	{
		$this->player = $player;
	}

	/**
	 * Gets the bowler
	 * @return Player
	 */
	public function GetPlayer() { return $this->player; }

	/**
	 * Sets the number of overs bowled
	 * @param float $overs
	 * @return void
	 */
	public function SetOvers($overs)
	{
		# need to support null so we can detect when this info wasn't recorded
		if (is_null($overs))
		{
			$this->overs = $overs;
		}
		else
		{
			// max value of db field is 999.9;
			$this->overs = ($overs >= 1000) ? 999.9 : (float)$overs;
		}
	}

	/**
	 * Gets the number of overs bowled
	 * @return float
	 */
	public function GetOvers() { return $this->overs; }

	/**
	 * Sets the number of maidens bowled
	 * @param int $maidens
	 * @return void
	 */
	public function SetMaidens($maidens)
	{
		# need to support null so we can detect when this info wasn't recorded
		$this->maidens = is_null($maidens) ? null : (int)$maidens;
	}

	/**
	 * Gets the number of maidens bowled
	 * @return int
	 */
	public function GetMaidens() { return $this->maidens; }

	/**
	 * Sets the number of runs conceded in the overall bowling performance
	 * @param unknown_type $runs
	 * @return unknown_type
	 */
	public function SetRunsConceded($runs)
	{
		# need to support null so we can detect when this info wasn't recorded
		$this->runs_conceded = is_null($runs) ? null : (int)$runs;
	}

	/**
	 * Gets the number of runs conceded in the overall bowling performance
	 * @return int
	 */
	public function GetRunsConceded() { return $this->runs_conceded; }

	/**
	 * Sets the number of wickets taken in the overall bowling performance
	 * @param int $wickets
	 * @return void
	 */
	public function SetWickets($wickets) { $this->wickets = (int)$wickets; }

	/**
	 * Gets the number of wickets taken in the overall bowling performance
	 * @return int
	 */
	public function GetWickets() { return $this->wickets; }

	/**
	 * Sets the match in which this performance took place
	 * @param Match $match
	 * @return void
	 */
	public function SetMatch(Match $match) { $this->match = $match; }

	/**
	 * Gets the match in which this performance took place
	 * @return Match
	 */
	public function GetMatch() { return $this->match; }

	/**
	 * Sets the opposition team
	 * @param Team $team
	 * @return void
	 */
	public function SetOppositionTeam(Team $team) { $this->opposition = $team; }

	/**
	 * Gets the opposition team
	 * @return Team
	 */
	public function GetOppositionTeam() { return $this->opposition; }
}
?>