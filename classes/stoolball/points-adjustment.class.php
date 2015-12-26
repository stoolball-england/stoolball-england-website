<?php
require_once('stoolball/team.class.php');

/**
 * Points added to or deducted from a team's score in a particular season
 *
 */
class PointsAdjustment
{
	private $i_id;
	private $i_points;
	private $o_team;
	private $s_reason;
	private $i_date;

	/**
	 * Instantiate a PointsAdjustment
	 *
	 * @param int $i_id
	 * @param int $i_points
	 * @param Team $o_team
	 * @param string $s_reason
	 * @param int $i_date
	 */
	public function __construct($i_id, $i_points, Team $o_team, $s_reason, $i_date = null)
	{
		$this->i_id = $i_id;
		$this->i_points = $i_points;
		$this->o_team = $o_team;
		$this->s_reason = $s_reason;
		if ($i_date) $this->i_date = $i_date;
	}
	
	/**
	 * Gets the unique id of the adjustment
	 *
	 * @return int
	 */
	public function GetId()
	{
		return $this->i_id;
	}
	
	/**
	 * Gets the value of the points adjustment (positive or negative)
	 *
	 * @return int
	 */
	public function GetPoints()
	{
		return $this->i_points;
	}
	
	/**
	 * Gets the team whose score is affected by the adjustment
	 *
	 * @return Team
	 */
	public function GetTeam()
	{
		return $this->o_team;
	}
	
	/**
	 * Gets a description of the reason for the adjustment
	 *
	 * @return string
	 */
	public function GetReason()
	{
		return $this->s_reason;
	}
	
	/**
	 * Sets the date the adjustment was recorded as a UNIX timestamp
	 *
	 * @param int
	 * @return void
	 */
	public function SetDate($i_date)
	{
		$this->i_date = (int)$i_date;
	}

	/**
	 * Gets the date the adjustment was recorded as a UNIX timestamp
	 *
	 * @return int
	 */
	public function GetDate()
	{
		return $this->i_date;
	}
}
?>