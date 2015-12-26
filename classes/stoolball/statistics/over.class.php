<?php
/**
 * An over bowled in a match
 * @author Rick
 *
 */
class Over
{
	private $player;
	private $over_number;
	private $balls;
	private $wides;
	private $no_balls;
	private $runs_in_over;

	/**
	 * Creates an instance of an over bowled in a match
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
	 * Gets the position of this over in the innings
	 * @param $over_number
	 * @return int
	 */
	public function SetOverNumber($over_number) { $this->over_number = (int)$over_number; }

	/**
	 * Gets the position of this over in the innings
	 * @return int
	 */
	public function GetOverNumber() { return $this->over_number; }

	/**
	 * Sets the number of balls bowled in a single over
	 * @param int $balls
	 * @return void
	 */
	public function SetBalls($balls) { $this->balls = is_null($balls) ? null : (int)$balls; }

	/**
	 * Gets the number of balls bowled in a single over
	 * @return int
	 */
	public function GetBalls() { return $this->balls; }

	/**
	 * Sets the number of wides conceded in a single over
	 * @param int $wides
	 * @return void
	 */
	public function SetWides($wides) { $this->wides = is_null($wides) ? null : (int)$wides; }

	/**
	 * Gets the number of wides conceded in a single over
	 * @return int
	 */
	public function GetWides() { return $this->wides; }

	/**
	 * Sets the number of no balls conceded in a single over
	 * @param int $no_balls
	 * @return void
	 */
	public function SetNoBalls($no_balls) { $this->no_balls = is_null($no_balls) ? null : (int)$no_balls; }

	/**
	 * Gets the number of no balls conceded in a single over
	 * @return int
	 */
	public function GetNoBalls() { return $this->no_balls; }

	/**
	 * Sets the number of runs conceded in a single over
	 * @param int $runs
	 * @return void
	 */
	public function SetRunsInOver($runs) { $this->runs_in_over = is_null($runs) ? null : (int)$runs; }

	/**
	 * Gets the number of runs conceded in a single over
	 * @return int
	 */
	public function GetRunsInOver() { return $this->runs_in_over; }
}
?>