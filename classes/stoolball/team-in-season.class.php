<?php
require_once('team.class.php');
require_once('season.class.php');

/**
 * Registration of a team playing in a stoolball season
 *
 */
class TeamInSeason
{
	/**
	 * The team
	 *
	 * @var Team
	 */
	private $team;
    
    /**
     * The season
     * @var Season
     */
    private $season;
	private $b_withdrawn_from_league;

	/**
	 * Creates details of a team's registration in a stoolball season
	 *
	 * @param Team $team
     * @param Season $season
	 * @param bool $b_withdrawn_from_league
	 */
	public function __construct($team, $season, $b_withdrawn_from_league)
	{
		if ($team instanceof Team) $this->SetTeam($team);
        if ($season instanceof Season) $this->SetSeason($season);
		$this->SetWithdrawnFromLeague($b_withdrawn_from_league);
	}

	/**
	 * Sets the team
	 *
	 * @param Team $team
	 */
	public function SetTeam(Team $team) { $this->team = $team; }

	/**
	 * Gets the team
	 *
	 * @return Team
	 */
	public function GetTeam() { return $this->team; }

	/**
	 * Gets the unique id of the team
	 *
	 * @return int
	 */
	public function GetTeamId()
	{
		return ($this->team instanceof Team) ? $this->team->GetId() : null;
	}

	/**
	 * Gets the name of the team
	 *
	 * @return string
	 */
	public function GetTeamName()
	{
		return ($this->team instanceof Team) ? $this->team->GetName() : null;
	}

    /**
     * Sets the season
     *
     * @param Season $season
     */
    public function SetSeason(Season $season) { $this->season = $season; }

    /**
     * Gets the season
     *
     * @return Season
     */
    public function GetSeason() { return $this->season; }

    /**
     * Gets the unique id of the season
     *
     * @return int
     */
    public function GetSeasonId()
    {
        return ($this->season instanceof Season) ? $this->season->GetId() : null;
    }

    /**
     * Gets the name of the season
     *
     * @return string
     */
    public function GetSeasonName()
    {
        return ($this->season instanceof Season) ? $this->season->GetCompetitionName() : null;
    }


	/**
	 * Sets whether the team has withdrawn from the league
	 *
	 * @param bool $b_withdrawn
	 */
	public function SetWithdrawnFromLeague($b_withdrawn) { $this->b_withdrawn_from_league = (bool)$b_withdrawn; }

	/**
	 * Gets whether the team has withdrawn from the league
	 *
	 * @return bool
	 */
	public function GetWithdrawnFromLeague() { return $this->b_withdrawn_from_league; }
}
?>