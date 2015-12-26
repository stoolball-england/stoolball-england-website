<?php
class StoolballStatistics
{
	/**
	 * Converts a number of 8-ball overs into a number of balls bowled
	 * @param float $overs
	 * @return int
	 */
	public static function OversToBalls($overs)
	{
		# Work out how many balls were bowled - have to assume 8 ball overs, even though on rare occasions that may not be the case
		$completed_overs = floor($overs);
		$extra_balls = ($overs - $completed_overs)*10; // *10 changes 0.6 overs into 6 balls, for example
		return (($completed_overs*8)+$extra_balls);
	}

	/**
	 * Converts a number of balls bowling into 8-ball overs
	 * @param int $balls
	 * @return float
	 */
	public static function BallsToOvers($balls)
	{
		$overs = floor($balls/8);
		$extra_balls = $balls % 8;
		if ($extra_balls) $overs = round($overs . "." . $extra_balls, 1);
		return $overs;
	}

	/**
	 * Gets the number of balls bowled per wicket
	 * @param float $overs
	 * @param int $wickets
	 * @return float
	 */
	public static function BowlingStrikeRate($overs, $wickets)
	{
		if ($overs > 0 and $wickets > 0)
		{
			return round(StoolballStatistics::OversToBalls($overs)/$wickets, 2);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Gets the number of runs conceded per wicket taken for a bowling performance
	 * @param int $runs
	 * @param int $wickets
	 * @return float
	 */
	public static function BowlingAverage($runs, $wickets)
	{
		if ($wickets > 0 and !is_null($runs))
		{
			return round($runs/$wickets, 2);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Gets the average number of runs conceded per over
	 * @param int $overs
	 * @param int $runs
	 * @return float
	 */
	public static function BowlingEconomy($overs, $runs)
	{
		if ($overs > 0 and !is_null($runs))
		{
			$overs_in_decimal = StoolballStatistics::OversToBalls($overs)/8;
			return round($runs/$overs_in_decimal, 2);
		}
		else return null;
	}
}