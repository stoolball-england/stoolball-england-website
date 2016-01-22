<?php
/**
 * An individual performance on a batting scorecard
 * @author Rick
 *
 */
class Batting
{
	private $player;
	private $how_out;
	private $dismissed_by;
	private $bowler;
	private $runs;
    private $balls_faced;
	private $match;
	private $opposition;

	/**
	 * Creates an instance of an individual performance on a batting scorecard
	 * @param Player $player
	 * @param int $how_out
	 * @param Player $dismissed_by
	 * @param Player $bowler
	 * @param int $runs
     * @param int $balls_faced
	 * @return void
	 */
	public function __construct(Player $player, $how_out, $dismissed_by, $bowler, $runs, $balls_faced=null)
	{
		$this->player = $player;
		$this->how_out = $how_out;
		$this->dismissed_by = $dismissed_by;
		$this->bowler = $bowler;
		$this->runs = $runs;
        $this->balls_faced = $balls_faced;
	}

	/**
	 * Gets the player whose batting performance this represents
	 * @return Player
	 */
	public function GetPlayer() { return $this->player; }

	/**
	 * Gets how the player was out (if at all)
	 * @return int
	 */
	public function GetHowOut() { return $this->how_out; }

	/**
	 * Gets the player who caught or ran-out the batter
	 * @return Player
	 */
	public function GetDismissedBy() { return $this->dismissed_by; }

	/**
	 * Gets the player who was bowling when the batter was dismissed
	 * @return Player
	 */
	public function GetBowler() { return $this->bowler; }

	/**
	 * Gets the number of runs scored
	 * @return int
	 */
	public function GetRuns() { return $this->runs; }

    /**
     * Gets the number of balls faced
     * @return int
     */
    public function GetBallsFaced() { return $this->balls_faced; }
    
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

	/**
	 * Gets a description of a dismissal method
	 * @param int $type
	 * @return string
	 */
	public static function Text($type)
	{
		switch ($type)
		{
			case Batting::DID_NOT_BAT: return 'did not bat';
			case Batting::NOT_OUT: return 'not out';
			case Batting::CAUGHT: return 'caught';
			case Batting::BOWLED: return 'bowled';
			case Batting::CAUGHT_AND_BOWLED: return 'caught and bowled';
			case Batting::RUN_OUT: return 'run-out';
			case Batting::BODY_BEFORE_WICKET: return 'body before wicket';
			case Batting::HIT_BALL_TWICE: return 'hit ball twice';
			case Batting::TIMED_OUT: return 'timed out';
			case Batting::RETIRED: return 'retired';
			case Batting::RETIRED_HURT: return 'retired hurt';
			case Batting::UNKNOWN_DISMISSAL: return 'not known';
		}
		return '';
	}


	/**
	 * Player took part in the match but did not bat
	 * @var int
	 */
	const DID_NOT_BAT = 0;

	/**
	 * Player batted and was undefeated at the end of the team's innings
	 * @var int
	 */
	const NOT_OUT = 1;

	/**
	 * Player was out, caught
	 * @var int
	 */
	const CAUGHT = 9;

	/**
	 * Player was out, bowled
	 * @var int
	 */
	const BOWLED = 10;

	/**
	 * Player was out, caught by the bowler
	 * @var int
	 */
	const CAUGHT_AND_BOWLED = 4;

	/**
	 * Player was out, run-out
	 * @var int
	 */
	const RUN_OUT = 5;

	/**
	 * Player was out, body before wicket
	 * @var unknown_type
	 */
	const BODY_BEFORE_WICKET = 6;

	/**
	 * Player was out having hit the ball twice deliberately
	 * @var int
	 */
	const HIT_BALL_TWICE = 7;

	/**
	 * Player was out, timed out
	 * @var int
	 */
	const TIMED_OUT = 8;

	/**
	 * Player retired for a reason other than an injury obtained in the match
	 * @var int
	 */
	const RETIRED = 2;

	/**
	 * Player retired due to an injury obtained in the match
	 * @var int
	 */
	const RETIRED_HURT = 3;

	/**
	 * Player is assumed to be out even though it was not recorded how
	 * @var int
	 */
	const UNKNOWN_DISMISSAL = 11;
}
?>