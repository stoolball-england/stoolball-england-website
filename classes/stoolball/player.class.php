<?php
require_once('http/short-url.class.php');
require_once("team.class.php");
require_once 'stoolball/statistics/stoolball-statistics.class.php';

/**
 * A player in a stoolball match
 * @author Rick
 *
 */
class Player implements IHasShortUrl
{
	/**
	 * An ordinary player rather than a type of extra
	 * @var int
	 */
	const PLAYER = 0;

	/**
	 * No balls is a special player representing extras bowled
	 * @var int
	 */
	const NO_BALLS = 1;

	/**
	 * Wides is a special player representing extras bowled
	 * @var int
	 */
	const WIDES = 2;

	/**
	 * Byes is a special player representing extras conceded
	 * @var int
	 */
	const BYES = 3;

	/**
	 * Bonus runs is a special player representing any bonus or penalty runs
	 * @var int
	 */
	const BONUS_RUNS = 4;

	/**
	 * Settings
	 * @var SiteSettings
	 */
	private $settings;
	private $id;
	private $name;
	private $comparable_name;
	private $total_matches;
	private $short_url;
    private $update_search;
	/**
	 * @var Team
	 */
	private $team;
	private $first_played;
	private $last_played;
	private $role = Player::PLAYER;
	private $user;
	private $total_player_of_match;

	# Batting stats
	private $batting;
	private $total_innings;
	private $not_outs;
	private $all_scores;
    private $scores_out;
    private $scores_not_out;
    private $scores_with_balls_faced;
    private $balls_faced;
	private $batting_best;
    private $batting_50;
    private $batting_100;
	private $batting_average;
    private $batting_strike_rate;
	private $how_out;
	private $score_spread;

	# Fielding stats
	private $catches;
	private $run_outs;

	# Bowling stats
	private $wickets;
	private $bowling;
	private $bowling_innings;
	private $balls;
	private $overs;
	private $maidens;
	private $runs_against;
	private $wickets_taken;
	private $wickets_taken_for_average;
	private $wickets_taken_for_strike_rate;
	private $bowling_average;
	private $bowling_strike_rate;
	private $how_wickets_taken;
	private $bowling_best;
	private $bowling_5;
	private $bowling_economy;

	/**
	 * Creates a player
	 * @param SiteSettings $settings
	 * @return void
	 */
	public function __construct(SiteSettings $settings)
	{
		$this->settings = $settings;
		$this->team = new Team($settings);
		$this->batting = new Collection();
		$this->wickets = new Collection();
		$this->bowling = new Collection();
	}

	/**
	 * Sets the player's id
	 * @param int $id
	 * @return void
	 */
	public function SetId($id) { $this->id = (int)$id; }

	/**
	 * Gets the player's id
	 * @return int
	 */
	public function GetId() { return $this->id; }

	/**
	 * Sets the player's full name
	 * @param string $name
	 * @return void
	 */
	public function SetName($name)
	{
		$this->name = (string)$name;
		$this->comparable_name = null;
	}

	/**
	 * Gets the player's full name
	 * @return string
	 */
	public function GetName() { return $this->name; }

	/**
	 * Gets the version of the player's name used to match them for updates
	 * @return string
	 */
	public function GetComparableName()
	{
		if (is_null($this->comparable_name))
		{
			$this->comparable_name = preg_replace('/[^a-z0-9]/i', '', strtolower($this->name));
		}
		return $this->comparable_name;
	}

	/**
	 * Sets the account this player has registered on the site with
	 * @param User $user
	 * @return void
	 */
	public function SetUser(User $user) { $this->user = $user; }

	/**
	 * Gets the account this player has registered on the site with
	 * @return User
	 */
	public function GetUser() { return $this->user; }

	/**
	 * Sets the total number of matches played and recorded
	 * @param $total
	 * @return void
	 */
	public function SetTotalMatches($total) { $this->total_matches = (int)$total; }

	/**
	 * Gets the total number of matches played and recorded
	 * @return int
	 */
	public function GetTotalMatches() { return $this->total_matches; }

	/**
	 * Gets the team the player is in
	 * @return Team
	 */
	public function Team() { return $this->team; }


	/**
	 * Sets whether this player is a special player representing a specific role, defined by constants of the Player class
	 * @param int $role
	 * @return void
	 */
	public function SetPlayerRole($role)
	{
		$role = (int)$role;
		switch ($role)
		{
			case Player::PLAYER:
				$this->role = $role;
				break;
			case Player::BYES:
				$this->SetName("Byes");
				$this->role = $role;
				break;
			case Player::WIDES:
				$this->SetName("Wides");
				$this->role = $role;
				break;
			case Player::NO_BALLS:
				$this->SetName("No balls");
				$this->role = $role;
				break;
			case Player::BONUS_RUNS:
				$this->SetName("Bonus runs");
				$this->role = $role;
				break;
		}
	}

	/**
	 * Gets whether this player is a special player representing a specific role, defined by constants of the Player class
	 * @return int
	 */
	public function GetPlayerRole() { return $this->role; }
	/**
	 * Sets the date of the first match this player was recorded as playing in
	 * @param int $timestamp
	 * @return void
	 */
	public function SetFirstPlayedDate($timestamp) { $this->first_played = (int)$timestamp; }

	/**
	 * Gets the date of the first match this player was recorded as playing in
	 * @return int
	 */
	public function GetFirstPlayedDate() { return $this->first_played; }

	/**
	 * Sets the date of the last match this player was recorded as playing in
	 * @param int $timestamp
	 * @return void
	 */
	public function SetLastPlayedDate($timestamp) { $this->last_played = (int)$timestamp; }

	/**
	 * Gets the date of the last match this player was recorded as playing in
	 * @return int
	 */
	public function GetLastPlayedDate() { return $this->last_played; }

	/**
	 * Gets the range of years when the player has been recorded
	 */
	public function GetPlayingYears() 
	{
		$years = "";
		if ($this->GetFirstPlayedDate())
		{
			$first_year = gmdate("Y", $this->GetFirstPlayedDate());
			$last_year = gmdate("Y", $this->GetLastPlayedDate());
			if ($first_year == $last_year)
			{
				$years = " in " . $first_year;
			}
			else
			{
				$years = (" from " . $first_year . " to " . $last_year);
			}
		}
		return $years;
	}
	
	/**
	 * Gets a short description of the player and their record
	 */
	public function GetPlayerDescription() 
	{
		$team_name = $this->Team()->GetName();
		$match_or_matches = ($this->GetTotalMatches() == 1) ? " match " : " matches ";
		$years = $this->GetPlayingYears();
		if ($this->GetPlayerRole() == Player::PLAYER)
		{
			return $this->GetName() . " played " . $this->GetTotalMatches() . $match_or_matches . "for ${team_name}${years}.";
		}
		else
		{
			return $team_name . " recorded " . strtolower($this->GetName()) . " in " . $this->GetTotalMatches() . "${match_or_matches}${years}.";
		}	
	}

    /**
     * Notes that the object may have changed since the last time it was indexed for search
     */
    public function SetSearchUpdateRequired() 
    {
        $this->update_search = true;
    }
    
    /**
     * Gets whether the object has changed since last time it was indexed for search
     */
    public function GetSearchUpdateRequired() 
    {
        return (bool)$this->update_search;
    }

	/**
	 * Sets the catches taken by this player
	 * @param int $catches
	 */
	public function SetCatches($catches) { $this->catches = (int)$catches; }

	/**
	 * Gets the catches taken by this player
	 * @return int
	 */
	public function GetCatches() { return $this->catches; }

	/**
	 * Sets the run-outs effected by this player
	 * @param int $run_outs
	 */
	public function SetRunOuts($run_outs) { $this->run_outs = (int)$run_outs; }

	/**
	 * Gets the run-outs effected by this player
	 * @return int
	 */
	public function GetRunOuts() { return $this->run_outs; }

	/**
	 * Gets this player's batting records
	 * @return Collection
	 */
	public function Batting() { return $this->batting; }

	/**
	 * Gets the wickets taken by this bowler
	 * @return Collection
	 */
	public function Wickets() { return $this->wickets; }

	/**
	 * Gets this player's bowling records
	 * @return Collection
	 */
	public function Bowling() { return $this->bowling; }

	/**
	 * If recently-added batting/bowling records are not reflected in statistics, run this method
	 * @return void
	 */
	public function RecalculateStatistics()
	{
		# Gather batting stats
		$this->total_innings = 0;
		$this->all_scores = array();
        $this->scores_out = array();
        $this->scores_not_out = array();
        $this->scores_with_balls_faced = array();
        $this->balls_faced = array();
		$this->batting_best = null;
		$this->batting_average = null;
        $this->batting_strike_rate = null;
		$this->batting_50 = 0;
		$this->batting_100 = 0;
		$this->how_out = array();

		foreach ($this->batting as $batting)
		{
			/* @var $batting Batting */
			if ($batting->GetHowOut() != Batting::DID_NOT_BAT) $this->total_innings++;

			if (!array_key_exists($batting->GetHowOut(), $this->how_out)) $this->how_out[$batting->GetHowOut()] = 0;
			$this->how_out[$batting->GetHowOut()]++;

			if (!is_null($batting->GetRuns()))
			{
				$this->all_scores[] = $batting->GetRuns();
                if ($this->IsNotOut($batting->GetHowOut())) 
                {
                    $this->scores_not_out[] = $batting->GetRuns();
                }
                else 
                {
                    $this->scores_out[] = $batting->GetRuns();
                }
                
				if ($batting->GetRuns() >= 50) $this->batting_50++;
				if ($batting->GetRuns() >= 100) $this->batting_100++;

				if (is_null($this->batting_best) 
				    or $batting->GetRuns() > intval($this->batting_best) 
				    or ($batting->GetRuns() == intval($this->batting_best) && $this->IsNotOut($batting->GetHowOut()))
                    )
				{
					$this->batting_best = $batting->GetRuns();
					if ($this->IsNotOut($batting->GetHowOut()))  {
					    $this->batting_best .= "*";
                    }
				}
                
                if (!is_null($batting->GetBallsFaced())) {
                    $this->scores_with_balls_faced[] = $batting->GetRuns();
                    $this->balls_faced[] = $batting->GetBallsFaced(); 
                }
			}
		}
		$this->not_outs = array_key_exists(Batting::NOT_OUT, $this->how_out) ? $this->how_out[Batting::NOT_OUT] : 0;
		if (count($this->all_scores))
		{
			# retired is not out for purpose of average
			$retired = array_key_exists(Batting::RETIRED, $this->how_out) ? $this->how_out[Batting::RETIRED] : 0;
			$retired_hurt = array_key_exists(Batting::RETIRED_HURT, $this->how_out) ? $this->how_out[Batting::RETIRED_HURT] : 0;

			$total_innings_for_average = ($this->total_innings - $this->not_outs - $retired - $retired_hurt);
			if ($total_innings_for_average > 0) $this->batting_average = round((array_sum($this->all_scores) / $total_innings_for_average), 2);

			# Work out the ranges scores are in
            $this->WorkOutScoreSpread();
		}
        
        if (count($this->scores_with_balls_faced)) {
            $total_balls_faced = array_sum($this->balls_faced);
            if ($total_balls_faced) {
                $this->batting_strike_rate = round((array_sum($this->scores_with_balls_faced)/$total_balls_faced)*100,2);
            }
        }

		# Gather bowling stats
		$this->bowling_innings = 0;
		$this->balls = null;
		$this->overs = null;
		$this->maidens = null;
		$this->runs_against = null;
		$this->wickets_taken = 0;
		$this->wickets_taken_for_analysis = 0;
		$this->bowling_economy = null;
		$this->bowling_average = null;
		$this->bowling_strike_rate = null;
		$this->bowling_best = null;
		$best_wickets = 0;
		$best_runs = 0;
		$this->bowling_5 = 0;
		foreach ($this->bowling as $bowling)
		{
			/* @var $bowling Bowling */
			$this->bowling_innings += 1;
			if (!is_null($bowling->GetOvers()))
			{
				# Initialise balls, since we now know there are some recorded
				if (is_null($this->balls)) $this->balls = 0;

				# Work out how many balls were bowled - have to assume 8 ball overs, even though on rare occasions that may not be the case
				$this->balls += StoolballStatistics::OversToBalls($bowling->GetOvers());
			}
			if (!is_null($bowling->GetMaidens()))
			{
				# Initialise maidens now we know the stat has been recorded
				if (is_null($this->maidens)) $this->maidens = 0;

				# Add these maidens to total bowled
				$this->maidens += $bowling->GetMaidens();
			}
			if (!is_null($bowling->GetRunsConceded()))
			{
				# Initialise runs, since we now know there are some recorded
				if (is_null($this->runs_against)) $this->runs_against = 0;

				# Add these runs to the total
				$this->runs_against += $bowling->GetRunsConceded();
			}
			if (!is_null($bowling->GetWickets()))
			{
				$this->wickets_taken += $bowling->GetWickets();
				if ($bowling->GetWickets() >= 5) $this->bowling_5++;

				# Record wickets separately for average and strike rate, because otherwise wickets recorded on batting card
				# with no overs recorded on bowling card distort those statistics.
				if (!is_null($bowling->GetRunsConceded())) $this->wickets_taken_for_average += $bowling->GetWickets();
				if (!is_null($bowling->GetOvers())) $this->wickets_taken_for_strike_rate += $bowling->GetWickets();
			}

			if (!is_null($bowling->GetWickets()))
			{
				if ($bowling->GetWickets() > $best_wickets or # if more wickets taken, it's always better
				($bowling->GetWickets() == $best_wickets and # if wickets equal, we need to check runs too
				(!is_null($bowling->GetRunsConceded()) and # if runs are null, it'll never beat another performance with the same number of wickets
				(is_null($best_runs) or $bowling->GetRunsConceded() < $best_runs) # $best_runs could be null if wickets were higher; if so, it always loses cos we know from previous line that this performance is not null
				)))
				{
					$best_wickets = $bowling->GetWickets();
					$best_runs = $bowling->GetRunsConceded();
				}
			}

		}
		if (!is_null($this->balls) and $this->balls > 0 and !is_null($this->runs_against))
		{
			$this->overs = StoolballStatistics::BallsToOvers($this->balls);
			$this->bowling_economy = StoolballStatistics::BowlingEconomy($this->overs, $this->runs_against);
		}
		if ($this->wickets_taken > 0)
		{
			$this->bowling_best = "$best_wickets/$best_runs";
		}
		if ($this->wickets_taken_for_average > 0 and !is_null($this->runs_against))
		{
			$this->bowling_average = StoolballStatistics::BowlingAverage($this->runs_against, $this->wickets_taken_for_average);
		}
		if ($this->wickets_taken_for_strike_rate > 0 and $this->balls > 0)
		{
			$this->bowling_strike_rate = StoolballStatistics::BowlingStrikeRate($this->overs, $this->wickets_taken_for_strike_rate);
		}
	}

    private function IsNotOut($how_out) 
    {
        return ($how_out == Batting::NOT_OUT or $how_out == Batting::RETIRED or $how_out == Batting::RETIRED_HURT);
    }
    
    private function WorkOutScoreSpread() 
    {
        if (count($this->all_scores))
        {
            # Work out the ranges scores are in
            sort($this->all_scores, SORT_NUMERIC);
            $score_spread = array(0=>array("not-out"=>0,"out"=>0), 
                                 10=>array("not-out"=>0,"out"=>0),
                                 20=>array("not-out"=>0,"out"=>0),
                                 30=>array("not-out"=>0,"out"=>0),
                                 40=>array("not-out"=>0,"out"=>0),
                                 50=>array("not-out"=>0,"out"=>0),
                                 60=>array("not-out"=>0,"out"=>0),
                                 70=>array("not-out"=>0,"out"=>0),
                                 80=>array("not-out"=>0,"out"=>0),
                                 90=>array("not-out"=>0,"out"=>0)
                                 );
            foreach ($this->all_scores as $score)
            {
                $key = intval(floor($score/10))*10;

                // if the score is higher than our default ranges, add all ranges up to and including this
                if (!array_key_exists($key, $score_spread))
                {
                    $biggest_key = count($score_spread) ? max(array_keys($score_spread)) : -10;
                    $new_key = $biggest_key+10;
                    while($new_key <= $key)
                    {
                        $score_spread[$new_key] = array("not-out"=>0,"out"=>0);
                        $new_key = $new_key+10;
                    }
                }
            }

            foreach ($this->scores_not_out as $score)
            {
                $key = intval(floor($score/10))*10;
                $score_spread[$key]["not-out"]++;
            }
            
            foreach ($this->scores_out as $score)
            {
                $key = intval(floor($score/10))*10;
                $score_spread[$key]["out"]++;
            }
            
            # Create real score_spread array with keys as ranges
            $this->score_spread = array();
            foreach ($score_spread as $key=>$value)  {
                $this->score_spread[$key . "-" . ($key+9)] = $value;
            }
        }
    }

	/*
	 * Gets how many times this player has batted
	 */
	public function TotalBattingInnings()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->total_innings;
	}

	/**
	 * Gets the player's best batting score
	 * @return string
	 */
	public function BestBatting()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->batting_best;
	}

	/**
	 * Gets how many times this player has been not out at the end of an innings
	 * @return int
	 */
	public function NotOuts()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();

		# Include retired too, as for the purpose of total not outs it's the same
		$retired = array_key_exists(Batting::RETIRED, $this->how_out) ? $this->how_out[Batting::RETIRED] : 0;
		$retired_hurt = array_key_exists(Batting::RETIRED_HURT, $this->how_out) ? $this->how_out[Batting::RETIRED_HURT] : 0;

		return $this->not_outs + $retired + $retired_hurt;
	}

	/**
	 * Gets the total runs scored
	 * @return int
	 */
	public function TotalRuns()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return array_sum($this->all_scores);
	}

	/**
	 * Gets the player's batting average
	 * @return float
	 */
	public function BattingAverage()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->batting_average;
	}

    /**
     * Gets the player's batting strike rate
     * @return float
     */
    public function BattingStrikeRate()
    {
        if (is_null($this->total_innings)) $this->RecalculateStatistics();
        return $this->batting_strike_rate;
    }

	/**
	 * Gets the number of scores of 50 or above
	 * @return int
	 */
	public function Fifties()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->batting_50;
	}

	/**
	 * Gets the number of scores of 100 or above
	 * @return int
	 */
	public function Centuries()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->batting_100;
	}

	/**
	 * Gets the range of scores the batter has made with the array key as the multiple of 10, or null if no scores recorded
	 * @return int[]
	 */
	public function ScoreSpread()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->score_spread;
	}

	/**
	 * Gets the ways the player got out, in an array indexed by dismissal method
	 * @return int[int]
	 */
	public function HowOut()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->how_out;
	}

    /**
     * Sets the ways the player got others out, in an array indexed by dismissal method
     * @return int[int]
     */
    public function SetHowWicketsTaken(array $how_wickets_taken)
    {
        $this->how_wickets_taken = $how_wickets_taken;
    }

	/**
	 * Gets the ways the player got others out, in an array indexed by dismissal method
	 * @return int[int]
	 */
	public function GetHowWicketsTaken()
	{
		return $this->how_wickets_taken;
	}

	/**
	 * Gets the number of innings in which the player bowled
	 * @return int
	 */
	public function BowlingInnings()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->bowling_innings;
	}

	/**
	 * Gets the number of balls bowled
	 * @return int
	 */
	public function Balls()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->balls;
	}

	/**
	 * Gets the number of overs bowled
	 * @return float
	 */
	public function Overs()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->overs;
	}

	/**
	 * Gets the number of maiden overs bowled
	 * @return int
	 */
	public function MaidenOvers()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->maidens;
	}

	/**
	 * Gets the number of runs scored against this player's bowling
	 * @return int
	 */
	public function RunsAgainst()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->runs_against;
	}

	/**
	 * Gets the number of wickets taken, according to bowling stats
	 * @return int
	 */
	public function WicketsTaken()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->wickets_taken;
	}

	/**
	 * Gets the player's best bowling figures
	 * @return string
	 */
	public function BestBowling()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->bowling_best;
	}

	/**
	 * Gets the average runs conceded for each wicket taken
	 * @return float
	 */
	public function BowlingAverage()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->bowling_average;
	}

	/**
	 * Gets the average runs conceded for each over
	 * @return float
	 */
	public function BowlingEconomy()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->bowling_economy;
	}

	/**
	 * Gets the average balls bowled for each wicket taken
	 * @return float
	 */
	public function BowlingStrikeRate()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->bowling_strike_rate;
	}

	/**
	 * Gets the number of 5 wickets hauls as the bowler
	 * @return int
	 */
	public function FiveWicketHauls()
	{
		if (is_null($this->total_innings)) $this->RecalculateStatistics();
		return $this->bowling_5;
	}

	/**
	 * Sets the number matches in which this player was nominated Player of the Match
	 * @param int $total
	 * @return void
	 */
	public function SetTotalPlayerOfTheMatchNominations($total) { $this->total_player_of_match = (int)$total; }

	/**
	 * Gets the number matches in which this player was nominated Player of the Match
	 * @return int
	 */
	public function GetTotalPlayerOfTheMatchNominations() { return $this->total_player_of_match; }

	/**
	 * Sets the short URL for a player
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->short_url = trim($s_url); }

	/**
	 * Gets the short URL for a player
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->short_url; }

	/**
	 * Gets the URL of the page to view the player's details
	 * @return string
	 */
	public function GetPlayerUrl()
	{
		return $this->settings->GetClientRoot() . $this->GetShortUrl();
	}

	/**
	 * Gets the URL to edit the player
	 * @return string
	 */
	public function GetEditUrl() { return $this->GetPlayerUrl() . "/edit"; }

	/**
	 * Gets the URL to delete the player
	 * @return string
	 */
	public function GetDeleteUrl() { return $this->GetPlayerUrl() . "/delete"; }

	/**
	 * Gets the format to use for a player's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType()
	{
		return new ShortUrlFormat("nsa_player", 'short_url', array('player_id'), array('GetId'),
		array(
		'{0}' => '/play/statistics/player-batting.php?player={0}',
        '{0}/bowling' => '/play/statistics/player-bowling.php?player={0}',
		'{0}/edit' => '/play/statistics/playeredit.php?item={0}',
		'{0}/delete' => '/play/statistics/playerdelete.php?item={0}',
        '{0}/batting.json' => "/play/statistics/player-batting.js.php?player={0}",
		'{0}/bowling.json' => "/play/statistics/player-bowling.js.php?player={0}"
		));
	}

	/**
	 * Gets the format to use for a player's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Player::GetShortUrlFormatForType();
	}

	/**
	 * Suggests a short URL to use to view the player
	 *
	 * @param int $i_preference
	 * @return string
	 */
	public function SuggestShortUrl($i_preference=1)
	{
		$s_url = strtolower(html_entity_decode($this->GetName()));

		# Remove punctuation, add dashes
		$s_url = preg_replace('/[^a-z0-9- ]/i', '', $s_url);
		$s_url = str_replace(' ', '-', $s_url);
        $s_url = rtrim($s_url, "-");

		# Add team as prefix
		if ($this->team->GetShortUrl())
		{
			$s_url = $this->team->GetShortUrl() . "/" . $s_url;
		}

		# Apply preference
		if ($i_preference > 1)
		{
			$s_url = ShortUrl::SuggestShortUrl($s_url, $i_preference, 1, "-player", true);
		}

		return $s_url;
	}

	/**
	 * Gets the URI which uniquely identifies this player
	 */
	public function GetLinkedDataUri()
	{
		return "https://www.stoolball.org.uk/id/player/" . $this->GetShortUrl();
	}
}
?>