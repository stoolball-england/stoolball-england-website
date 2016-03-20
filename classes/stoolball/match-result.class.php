<?php
require_once 'stoolball/batting.class.php';
require_once 'stoolball/bowling.class.php';
require_once 'stoolball/statistics/over.class.php';

/**
 * A possible or actual result of a match (and static enum values for each type of result)
 *
 */
class MatchResult
{
    private $toss_won_by;
    private $i_id;
	private $b_home_batted_first;
	private $i_home_runs;
	private $i_away_runs;
	private $i_home_wickets;
	private $i_away_wickets;
	private $i_home_points;
	private $i_away_points;
	private $player_of_the_match;
	private $player_of_the_match_home;
	private $player_of_the_match_away;
	private $home_batting;
	private $home_overs;
	private $home_bowling_figures;
	private $away_batting;
	private $away_overs;
	private $away_bowling_figures;

	/**
	 * Instantiates a MatchResult
	 *
	 * @param int $i_result
	 */
	public function __construct($i_result=0)
	{
		$this->SetResultType($i_result);
		$this->home_batting = new Collection(null, "Batting");
		$this->home_overs = new Collection(null, "Over");
		$this->home_bowling_figures = new Collection(null, "Bowling");
		$this->away_batting = new Collection(null, "Batting");
		$this->away_overs = new Collection(null, "Over");
		$this->away_bowling_figures = new Collection(null, "Bowling");
	}
    
    /**
     * @return void
     * @param TeamRole $team_role
     * @desc Sets whether the home or away team won the toss
     */
    public function SetTossWonBy($team_role) { $this->toss_won_by = is_null($team_role) ? null : (int)$team_role; }

    /**
     * @return TeamRole
     * @desc Gets whether the home or away team won the toss
     */
    public function GetTossWonBy() { return $this->toss_won_by; }
    
	/**
	 * Sets the numeric identifer for the type of result
	 *
	 * @param int $i_id
	 */
	public function SetResultType($i_id) { $this->i_id = (int)$i_id; }

	/**
	 * Gets the numeric identifier for the type of result
	 *
	 * @return int
	 */
	public function GetResultType() { return $this->i_id; }

	/**
	 * Gets whether the match was won by the home team
	 *
	 * @return bool
	 */
	public function GetIsHomeWin()
	{
		if ($this->GetResultType() == MatchResult::HOME_WIN) return true;
		if ($this->GetResultType() == MatchResult::HOME_WIN_BY_FORFEIT) return true;
		return false;
	}

	/**
	 * Gets whether the match was won by the away team
	 *
	 * @return bool
	 */
	public function GetIsAwayWin()
	{
		if ($this->GetResultType() == MatchResult::AWAY_WIN) return true;
		if ($this->GetResultType() == MatchResult::AWAY_WIN_BY_FORFEIT) return true;
		return false;
	}

	/**
	 * Gets whether the match was won or tied
	 *
	 * @return bool
	 */
	public function GetIsEqualResult()
	{
		if ($this->GetResultType() == MatchResult::TIE) return true;
		return false;
	}

	/**
	 * Gets whether the match was cancelled or abandoned
	 *
	 * @return bool
	 */
	public function GetIsNoResult()
	{
		if ($this->GetResultType() == MatchResult::ABANDONED) return true;
		if ($this->GetResultType() == MatchResult::CANCELLED) return true;
		if ($this->GetResultType() == MatchResult::POSTPONED) return true;
		return false;
	}


	/**
	 * @return void
	 * @param bool $b_input
	 * @desc Sets whether the home team batted first
	 */
	public function SetHomeBattedFirst($b_input) { $this->b_home_batted_first = is_null($b_input) ? null : (bool)$b_input; }

	/**
	 * @return bool
	 * @desc Gets whether the home team batted first
	 */
	public function GetHomeBattedFirst() { return $this->b_home_batted_first; }

	/**
	 * Gets the individual performances in the home batting innings
	 * @return Collection
	 */
	public function HomeBatting() { return $this->home_batting; }

	/**
	 * Gets the individual performances in the away batting innings
	 * @return Collection
	 */
	public function AwayBatting() { return $this->away_batting; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the number of runs scored by the home team
	 */
	public function SetHomeRuns($i_input) { $this->i_home_runs = (is_numeric($i_input)) ? (int)$i_input : null; }

	/**
	 * @return int
	 * @desc Gets the number of runs scored by the home team
	 */
	public function GetHomeRuns() { return $this->i_home_runs; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the number of runs scored by the away team
	 */
	public function SetAwayRuns($i_input) { $this->i_away_runs = (is_numeric($i_input)) ? (int)$i_input : null; }

	/**
	 * @return int
	 * @desc Gets the number of runs scored by the away team
	 */
	public function GetAwayRuns() { return $this->i_away_runs; }

	/**
	 * Gets the overs bowled by the home team
	 * @return Collection
	 */
	public function HomeOvers() { return $this->home_overs; }

	/**
	 * Gets the overs bowled by the away team
	 * @return Collection
	 */
	public function AwayOvers() { return $this->away_overs; }

	/**
	 * Gets the individual bowling performances by the home team
	 * @return Collection
	 */
	public function HomeBowling() { return $this->home_bowling_figures; }

	/**
	 * Gets the individual bowling performances by the away team
	 * @return Collection
	 */
	public function AwayBowling() { return $this->away_bowling_figures; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the number of wickets lost by the home team
	 */
	public function SetHomeWickets($i_input) { $this->i_home_wickets = is_null($i_input) ? null : (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the number of wickets lost by the home team
	 */
	public function GetHomeWickets() { return $this->i_home_wickets; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the number of wickets lost by the away team
	 */
	public function SetAwayWickets($i_input) { $this->i_away_wickets = is_null($i_input) ? null : (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the number of wickets lost by the away team
	 */
	public function GetAwayWickets() { return $this->i_away_wickets; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the number of points awarded to the home team
	 */
	public function SetHomePoints($i_input) { $this->i_home_points = (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the number of points awarded to the home team
	 */
	public function GetHomePoints() { return $this->i_home_points; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the number of points awarded to the away team
	 */
	public function SetAwayPoints($i_input) { $this->i_away_points = (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the number of points awarded to the away team
	 */
	public function GetAwayPoints() { return $this->i_away_points; }


	/**
	 * Sets the overall player of the match
	 *
	 * @param Player name $s_player
	 */
	public function SetPlayerOfTheMatch(Player $player)
	{
		$this->player_of_the_match = $player;
	}

	/**
	 * Gets the overall player of the match
	 *
	 * @return Player
	 */
	public function GetPlayerOfTheMatch()
	{
		return $this->player_of_the_match;
	}

	/**
	 * Sets the  home team player of the match
	 *
	 * @param Player name $s_player
	 */
	public function SetPlayerOfTheMatchHome(Player $player)
	{
		$this->player_of_the_match_home = $player;
	}

	/**
	 * Gets the home team player of the match
	 *
	 * @return Player
	 */
	public function GetPlayerOfTheMatchHome()
	{
		return $this->player_of_the_match_home;
	}

	/**
	 * Sets the away team player of the match
	 *
	 * @param Player name $s_player
	 */
	public function SetPlayerOfTheMatchAway(Player $player)
	{
		$this->player_of_the_match_away = $player;
	}

	/**
	 * Gets the away team player of the match
	 *
	 * @return Player
	 */
	public function GetPlayerOfTheMatchAway()
	{
		return $this->player_of_the_match_away;
	}


	/**
	 * Gets whether it's possible to know a type of result before the match
	 * @param int ResultType
	 * @return bool
	 */
	public static function PossibleInAdvance($type)
	{
		switch ($type)
		{
			case MatchResult::HOME_WIN_BY_FORFEIT:
			case MatchResult::AWAY_WIN_BY_FORFEIT:
			case MatchResult::CANCELLED:
			case MatchResult::POSTPONED:
				return true;
			default:
				return false;
		}
	}

	/**
	 * Gets a description of a type of match result
	 * @param $type
	 * @return string
	 */
	public static function Text($type)
	{
		switch ($type)
		{
			case MatchResult::HOME_WIN: return "Home win";
			case MatchResult::AWAY_WIN: return "Away win";
			case MatchResult::HOME_WIN_BY_FORFEIT: return "Home win by forfeit";
			case MatchResult::AWAY_WIN_BY_FORFEIT: return "Away win by forfeit";
			case MatchResult::TIE: return "Tie";
			case MatchResult::POSTPONED: return "Postponed";
			case MatchResult::CANCELLED: return "Cancelled";
			case MatchResult::ABANDONED: return "Abandoned during play";
		}
	}

	/**
	 * @return int
	 * @desc Match result is unknown or match has yet to take place
	 */
	const UNKNOWN = 0;

	/**
	 * @return int
	 * @desc Home team won the game
	 */
	const HOME_WIN = 1;

	/**
	 * @return int
	 * @desc Away team won the game
	 */
	const AWAY_WIN = 2;

	/**
	 * @return int
	 * @desc Both teams achieved the same score
	 */
	const TIE = 3;

	/**
	 * @return int
	 * @desc Match was called off at the beginning of or during play
	 */
	const ABANDONED = 5;

	/**
	 * @return int
	 * @desc Home team won due to forfeit by away team
	 */
	const HOME_WIN_BY_FORFEIT = 6;

	/**
	 * @return int
	 * @desc Away team won due to forfeit by home team
	 */
	const AWAY_WIN_BY_FORFEIT = 7;

	/**
	 * @return int
	 * @desc Match was cancelled before play
	 */
	const CANCELLED = 8;

	/**
	 * @return int
	 * @desc Match will be played at a different time
	 */
	const POSTPONED = 9;
}
?>
