<?php
require_once('data/data-manager.class.php');
require_once("authentication/authentication-manager.class.php");
require_once('stoolball/match.class.php');
require_once('stoolball/player-type.enum.php');

class MatchManager extends DataManager
{
	/**
	 * @return MatchManager
	 * @param SiteSettings $o_settings
	 * @desc Read and write matches
	 */
	public function __construct($o_settings, $o_db)
	{
		parent::__construct($o_settings, $o_db);
		$this->s_item_class = 'Match';
		$this->filter_by_max_items = 0;
		$this->filter_by_start_date = 0;
		$this->filter_by_end_date = 0;
		$this->filter_by_match_types = array();
        $this->filter_by_player_types = array();
		$this->filter_by_teams = array();
		$this->filter_by_grounds = array();
		$this->filter_by_competitions = array();
		$this->filter_by_match_result = array();
	}
	
	private $filter_by_max_items;

	/**
	 * @return void
	 * @param int $maximum
	 * @desc Sets the maximum number of objects which should be returned by this data manager, if supported
	 */
	public function FilterByMaximumResults($maximum)
	{
		$this->filter_by_max_items = (int)$maximum;
	}

	private $filter_by_start_date;
	
	/**
	 * Sets the date from before which data should not be returned
	 *
	 * @param int $timestamp
	 */
	public function FilterByDateStart($timestamp)
	{
		$this->filter_by_start_date = (int)$timestamp;
	}

	private $filter_by_end_date;

	/**
	 * Sets the date after which data should not be returned
	 *
	 * @param int $timestamp
	 */
	public function FilterByDateEnd($timestamp)
	{
		$this->filter_by_end_date = (int)$timestamp;
	}

	private $filter_by_match_types;

	/**
	 * Sets the types of match to select
	 *
	 * @param int[] $a_types
	 */
	public function FilterByMatchType($a_types)
	{
		$this->ValidateNumericArray($a_types);
		$this->filter_by_match_types = $a_types;
	}
    
    private $filter_by_match_result;

    /**
     * Sets the permitted results of the matches to select
     *
     * @param int[] $results
     */
    public function FilterByMatchResult($results)
    {
        $this->ValidateNumericArray($results);
        $this->filter_by_match_result = $results;
    }

    private $filter_by_player_types;

    /**
     * Filter supporting queries to select only matches for the specified player types
     *
     * @var int[] $a_types
     */
    public function FilterByPlayerType($a_types)
    {
        $this->ValidateNumericArray($a_types);
        $this->filter_by_player_types = $a_types;
    }

    private $filter_by_teams;

    /**
     * Filter supporting queries to select only matches involving the specified teams
     *
     * @var int[] $team_ids
     */
    public function FilterByTeam($team_ids)
    {
        $this->ValidateNumericArray($team_ids);
        $this->filter_by_teams = $team_ids;
	}
	
	private $filter_by_grounds;

    /**
     * Filter supporting queries to select only matches at the specified grounds
     *
     * @var int[] $ground_ids
     */
    public function FilterByGround($ground_ids)
    {
        $this->ValidateNumericArray($ground_ids);
        $this->filter_by_grounds = $ground_ids;
    }

    private $filter_by_tournament_id;
    
    /**
     * Supporting queries will only read matches in the specified tournament
     * @param int $tournament_id
     */
    public function FilterByTournament($tournament_id) 
    {
        $this->filter_by_tournament_id = (int)$tournament_id;
    }

    private $filter_by_competitions;

    /**
     * Filter supporting queries to select only matches in the specified competition
     *
     * @var int[] $competition_ids
     */
    public function FilterByCompetition($competition_ids)
    {
        $this->ValidateNumericArray($competition_ids);
        $this->filter_by_competitions = $competition_ids;
    }
    
    private $sort_by;
    
    /**
     * Sets the sort order of supporting queries
     */
    public function SortBy($field) 
    {
        $this->sort_by = (string)$field;
    }

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the matches matching the supplied ids, or all matches
	 */
	public function ReadByMatchId($a_ids=null)
	{
		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_mt = $this->GetSettings()->GetTable('MatchTeam');
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');
		$s_comp = $this->GetSettings()->GetTable('Competition');
		$s_ground = $this->GetSettings()->GetTable('Ground');
		$players = $this->GetSettings()->GetTable("Player");
		$bowling = $this->GetSettings()->GetTable("Bowling");
		$batting = $this->GetSettings()->GetTable("Batting");
		$stats = $this->GetSettings()->GetTable("PlayerMatch");

		$s_sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.custom_title, $s_match.ground_id, $s_match.start_time, $s_match.start_time_known, $s_match.added_by, 
		$s_match.match_type, $s_match.player_type_id, $s_match.tournament_match_id, $s_match.max_tournament_teams, $s_match.short_url, $s_match.home_runs, $s_match.home_wickets, 
		$s_match.away_runs, $s_match.away_wickets, $s_match.match_result_id, $s_match.won_toss, $s_match.home_bat_first, $s_match.match_notes, $s_match.qualification, 
		$s_match.players_per_team, $s_match.overs AS overs_per_innings, $s_match.update_search, $s_match.date_changed, $s_match.modified_by_id, 
		home_team.team_id AS home_team_id, home_team.team_name AS home_team_name, home_team.short_url AS home_short_url,
		away_team.team_id AS away_team_id, away_team.team_name AS away_team_name, away_team.short_url AS away_short_url,
		$s_season.season_id, $s_season.season_name, $s_season.short_url AS season_short_url, " .
		$s_comp . '.competition_id, ' . $s_comp . '.competition_name, ' . $s_comp . '.notification_email, ' .
		"$s_ground.saon, $s_ground.paon, $s_ground.street_descriptor, $s_ground.locality, $s_ground.town, $s_ground.postcode, $s_ground.short_url AS ground_short_url, $s_ground.latitude, $s_ground.longitude, $s_ground.geo_precision, " .
		'tournament_match.match_id AS match_id_in_tournament, tournament_match.match_title AS tournament_match_title, tournament_match.short_url AS tournament_match_url, ' .
		'tournament_match.start_time AS tournament_match_time, tournament_match.start_time_known AS tournament_match_start_time_known, tournament_match.order_in_tournament, ' .
		'tournament.match_title AS tournament_title, tournament.short_url AS tournament_url, ' .
		"player_of_match.player_id AS player_of_match_id, player_of_match.player_name AS player_of_match, player_of_match.short_url AS player_of_match_url, player_of_match.team_id AS player_of_match_team_id,
		player_of_match_home.player_id AS player_of_match_home_id, player_of_match_home.player_name AS player_of_match_home, player_of_match_home.short_url AS player_of_match_home_url, player_of_match_home.team_id AS player_of_match_home_team_id,
		player_of_match_away.player_id AS player_of_match_away_id, player_of_match_away.player_name AS player_of_match_away, player_of_match_away.short_url AS player_of_match_away_url, player_of_match_away.team_id AS player_of_match_away_team_id, 
        user.known_as 
        FROM (((((((((((((" . $s_match . ' LEFT OUTER JOIN ' . $s_mt . ' AS home_link ON ' . $s_match . '.match_id = home_link.match_id AND home_link.team_role = ' . TeamRole::Home() . ') ' .
		'LEFT OUTER JOIN nsa_team AS home_team ON home_link.team_id = home_team.team_id AND home_link.team_role = ' . TeamRole::Home() . ")
		LEFT OUTER JOIN " . $s_season_match . ' ON ' . $s_match . '.match_id = ' . $s_season_match . '.match_id) ' .
		'LEFT OUTER JOIN ' . $s_season . ' ON ' . $s_season_match . '.season_id = ' . $s_season . '.season_id) ' .
		'LEFT OUTER JOIN ' . $s_comp . ' ON ' . $s_season . '.competition_id = ' . $s_comp . '.competition_id) ' .
		"LEFT OUTER JOIN $s_mt AS away_link ON $s_match.match_id = away_link.match_id AND away_link.team_role = " . TeamRole::Away() . ') ' .
		'LEFT OUTER JOIN nsa_team AS away_team ON away_link.team_id = away_team.team_id AND away_link.team_role = ' . TeamRole::Away() . ")
		LEFT OUTER JOIN " . $s_ground . ' ON ' . $s_match . '.ground_id = ' . $s_ground . '.ground_id) ' .
		'LEFT OUTER JOIN ' . $s_match . ' AS tournament_match ON ' . $s_match . '.match_id = tournament_match.tournament_match_id) ' .
		'LEFT OUTER JOIN ' . $s_match . ' AS tournament ON ' . $s_match . '.tournament_match_id = tournament.match_id) ' .
		"LEFT OUTER JOIN $players AS player_of_match ON $s_match.player_of_match_id = player_of_match.player_id)
		LEFT OUTER JOIN $players AS player_of_match_home ON $s_match.player_of_match_home_id = player_of_match_home.player_id)
		LEFT OUTER JOIN $players AS player_of_match_away ON $s_match.player_of_match_away_id = player_of_match_away.player_id) 
		LEFT OUTER JOIN nsa_user AS user ON $s_match.modified_by_id = user.user_id ";

		# limit to specific matches, if specified
		$where = "";
		
		if ($this->filter_by_start_date > 0)
		{
			$where = $this->SqlAddCondition($where, 'nsa_match.start_time >= ' . $this->filter_by_start_date, 1);
		}
		if ($this->filter_by_end_date > 0)
		{
			$where = $this->SqlAddCondition($where, 'nsa_match.start_time <= ' . $this->filter_by_end_date, 1);
		}

		if (is_array($a_ids))
        {
            $where = $this->SqlAddCondition($where, $s_match . '.match_id IN (' . join(', ', $a_ids) . ') ');
        }
        if (count($this->filter_by_match_types))
        {
            $where = $this->SqlAddCondition($where, $s_match . '.match_type IN (' . join(', ', $this->filter_by_match_types) . ') ', $this->SqlAnd());
        }
        $s_sql = $this->SqlAddWhereClause($s_sql, $where);

		# sort matches
		$s_sql .= 'ORDER BY ' . $s_match . '.start_time ASC, ' . $s_season . '.season_id ASC, tournament_match.start_time ASC';

		# run query
		$result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($result);

		# tidy up
		$result->closeCursor();
    }

    /**
     * Add full scorecard data to the currently selected matches
     */
    public function ExpandMatchScorecards()
    {
        $a_ids = array();
        foreach ($this->GetItems() as $match) {
            $a_ids[] = $match->GetId();
        }
        
		# Select batting and bowling separately because when selecting it all together they create so many duplicate rows that
		# it would significantly increase the amount of data sent across the wire
		$s_sql = "SELECT mt.match_id, mt.team_role,
		bat.player_id, bat.how_out, bat.dismissed_by_id, bat.bowler_id, bat.runs, bat.balls_faced,
		batter.player_name AS batter_name, batter.player_role AS batter_role, batter.short_url AS batter_url,
		dismissed_by.player_name AS dismissed_by_name, dismissed_by.player_role AS dismissed_by_role, dismissed_by.short_url AS dismissed_by_url,
		bowler.player_name AS bowler_name, bowler.player_role AS bowler_role, bowler.short_url AS bowler_url
		FROM nsa_match_team mt
		INNER JOIN nsa_batting bat ON mt.match_team_id = bat.match_team_id
		INNER JOIN nsa_player batter ON bat.player_id = batter.player_id
		LEFT JOIN nsa_player dismissed_by ON bat.dismissed_by_id = dismissed_by.player_id
		LEFT JOIN nsa_player bowler ON bat.bowler_id = bowler.player_id
		WHERE mt.team_role IN (" . TeamRole::Home() . "," . TeamRole::Away() . ") ";

		if (count($a_ids)) $s_sql .= "AND mt.match_id IN (" . join(', ', $a_ids) . ') ';

		$s_sql .= "ORDER BY mt.match_id, mt.match_team_id, bat.position";

		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$match = $this->GetItemByProperty('GetId', $row->match_id);
			if (!$match instanceof Match) continue; # shouldn't ever happen

			$batter = new Player($this->GetSettings());
			$batter->SetId($row->player_id);
			$batter->SetName($row->batter_name);
			$batter->SetPlayerRole($row->batter_role);
			$batter->SetShortUrl($row->batter_url);

			if (!is_null($row->dismissed_by_id))
			{
				$dismissed_by = new Player($this->GetSettings());
				$dismissed_by->SetId($row->dismissed_by_id);
				$dismissed_by->SetName($row->dismissed_by_name);
				$dismissed_by->SetPlayerRole($row->dismissed_by_role);
				$dismissed_by->SetShortUrl($row->dismissed_by_url);
			}
			else $dismissed_by = null;

			if (!is_null($row->bowler_id))
			{
				$bowler = new Player($this->GetSettings());
				$bowler->SetId($row->bowler_id);
				$bowler->SetName($row->bowler_name);
				$bowler->SetPlayerRole($row->bowler_role);
				$bowler->SetShortUrl($row->bowler_url);
			}
			else $bowler = null;

			$batting = new Batting($batter, $row->how_out, $dismissed_by, $bowler, $row->runs, $row->balls_faced);

			if (intval($row->team_role) == TeamRole::Home())
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else if (intval($row->team_role) == TeamRole::Away())
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$result->closeCursor();

		# select over-by-over bowling figures
		$s_sql = "SELECT mt.match_id, mt.team_role,
		p.player_name, p.player_role, p.short_url,
		b.player_id, b.position, b.balls_bowled, b.no_balls, b.wides, b.runs_in_over
		FROM nsa_match_team mt
		INNER JOIN nsa_bowling b ON mt.match_team_id = b.match_team_id
		INNER JOIN nsa_player p ON p.player_id = b.player_id
		WHERE mt.team_role IN (" . TeamRole::Home() . "," . TeamRole::Away() . ") ";

		if (count($a_ids)) $s_sql .= "AND mt.match_id IN (" . join(', ', $a_ids) . ') ';

		$s_sql .= "ORDER BY mt.match_id, mt.match_team_id, ISNULL(b.position), b.position"; # null positions sorted last

		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$match = $this->GetItemByProperty('GetId', $row->match_id);
			if (!$match instanceof Match) continue; # shouldn't ever happen

			$bowler = new Player($this->GetSettings());
			$bowler->SetId($row->player_id);
			$bowler->SetName($row->player_name);
			$bowler->SetPlayerRole($row->player_role);
			$bowler->SetShortUrl($row->short_url);

			$bowling = new Over($bowler);
			if (!is_null($row->position)) $bowling->SetOverNumber($row->position);
			if (!is_null($row->balls_bowled)) $bowling->SetBalls($row->balls_bowled);
			if (!is_null($row->no_balls)) $bowling->SetNoBalls($row->no_balls);
			if (!is_null($row->wides)) $bowling->SetWides($row->wides);
			if (!is_null($row->runs_in_over)) $bowling->SetRunsInOver($row->runs_in_over);

			if (intval($row->team_role) == TeamRole::Home())
			{
				$match->Result()->HomeOvers()->Add($bowling);
			}
			else if (intval($row->team_role) == TeamRole::Away())
			{
				$match->Result()->AwayOvers()->Add($bowling);
			}
		}

		$result->closeCursor();
		unset($result);

		# select overall bowling figures for each bowler
		$s_sql = "SELECT mt.match_id, mt.team_role,
		p.player_name, p.player_role, p.short_url,
		b.player_id, b.overs, b.maidens, b.runs_conceded, b.wickets
		FROM nsa_match_team mt
		INNER JOIN nsa_player_match b ON mt.match_team_id = b.match_team_id
		INNER JOIN nsa_player p ON p.player_id = b.player_id
		WHERE mt.team_role IN (" . TeamRole::Home() . "," . TeamRole::Away() . ")
		AND b.wickets IS NOT NULL ";

		if (count($a_ids)) $s_sql .= "AND mt.match_id IN (" . join(', ', $a_ids) . ') ';

		$s_sql .= "ORDER BY mt.match_id, mt.match_team_id, b.first_over";

		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$match = $this->GetItemByProperty('GetId', $row->match_id);
			if (!$match instanceof Match) continue; # shouldn't ever happen

			$bowler = new Player($this->GetSettings());
			$bowler->SetId($row->player_id);
			$bowler->SetName($row->player_name);
			$bowler->SetPlayerRole($row->player_role);
			$bowler->SetShortUrl($row->short_url);

			$figures = new Bowling($bowler);
			if (!is_null($row->overs)) $figures->SetOvers($row->overs);
			if (!is_null($row->maidens)) $figures->SetMaidens($row->maidens);
			if (!is_null($row->runs_conceded)) $figures->SetRunsConceded($row->runs_conceded);
			if (!is_null($row->wickets)) $figures->SetWickets($row->wickets);

			if (intval($row->team_role) == TeamRole::Home())
			{
				$match->Result()->HomeBowling()->Add($figures);
			}
			else if (intval($row->team_role) == TeamRole::Away())
			{
				$match->Result()->AwayBowling()->Add($figures);
			}
		}

		$result->closeCursor();
		unset($result);
	}

	/**
	 * @access public
	 * @return void
	 * @param int $i_how_many
	 * @desc Read from the db the next matches due to be played
	 */
	function ReadNext($i_how_many=5)
	{
		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$i_time = gmdate('U') - (60*120);

		$s_sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.match_type, $s_match.start_time, $s_match.start_time_known, $s_match.short_url, $s_match.tournament_spaces " .
		'FROM ' . $s_match . ' ' .
		'WHERE ' . $s_match . '.match_type != ' . MatchType::TOURNAMENT_MATCH . ' ' .
		'AND ' . $s_match . '.start_time >= ' . $i_time . ' ' .
		'AND (' . $s_match . '.match_result_id IS NULL OR ' . $s_match . '.match_result_id = 0) ' . # forfeit can be decided in advance
		'ORDER BY ' . $s_match . '.start_time ASC LIMIT 0,' . (int)$i_how_many;

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @param int $i_how_many
	 * @desc Read from the db latest matches in the given season
	 */
	public function ReadLastInSeason($i_season_id, $i_how_many=1)
	{
		if (!is_numeric($i_season_id)) throw new Exception('$i_season_id must be an integer');
		if (!is_numeric($i_how_many)) throw new Exception('$i_how_many must be an integer');

		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');

		$s_sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.match_type, $s_match.start_time, $s_match.start_time_known " .
		"FROM $s_match INNER JOIN $s_season_match ON $s_match.match_id = $s_season_match.match_id " .
		'WHERE ' . $s_season_match . '.season_id = ' . Sql::ProtectNumeric($i_season_id) . ' ';

		if (count($this->filter_by_match_types))
		{
			$s_sql = $this->SqlAddCondition($s_sql, $s_match . '.match_type IN (' . join(', ', $this->filter_by_match_types) . ') ', $this->SqlAnd());
		}
		else
		{
			# default if no match types specified
			$s_sql = $this->SqlAddCondition($s_sql, $s_match . '.match_type NOT IN (' . MatchType::TOURNAMENT . ', ' . MatchType::TOURNAMENT_MATCH . ') ', $this->SqlAnd());
		}

		$s_sql .= 'ORDER BY ' . $s_match . '.start_time DESC LIMIT 0,' . (int)$i_how_many;

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @param int $i_how_many
	 * @desc Read from the db latest matches for a given team
	 */
	public function ReadLastForTeam($i_team_id, $i_how_many=1)
	{
		if (!is_numeric($i_team_id)) throw new Exception('$i_team_id must be an integer');
		if (!is_numeric($i_how_many)) throw new Exception('$i_how_many must be an integer');

		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_match_team = $this->GetSettings()->GetTable('MatchTeam');

		$s_sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.match_type, $s_match.start_time, $s_match.start_time_known " .
		"FROM $s_match INNER JOIN $s_match_team ON $s_match.match_id = $s_match_team.match_id " .
		'WHERE ' . $s_match_team . '.team_id = ' . Sql::ProtectNumeric($i_team_id) . ' ';

		if (count($this->filter_by_match_types))
		{
			$s_sql = $this->SqlAddCondition($s_sql, $s_match . '.match_type IN (' . join(', ', $this->filter_by_match_types) . ') ', $this->SqlAnd());
		}
		else
		{
			# default if no match types specified
			$s_sql = $this->SqlAddCondition($s_sql, $s_match . '.match_type NOT IN (' . MatchType::TOURNAMENT . ', ' . MatchType::TOURNAMENT_MATCH . ') ', $this->SqlAnd());
		}

		$s_sql .= 'ORDER BY ' . $s_match . '.start_time DESC LIMIT 0,' . (int)$i_how_many;

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @param MatchType[] $a_types
	 * @desc Read from the db the matches in the supplied match types
	 */
	function ReadByMatchType($a_types)
	{
		/* @var $o_settings SiteSettings */
		$this->ValidateNumericArray($a_types);

		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');

		$s_sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.match_type, $s_match.start_time, $s_match.start_time_known, $s_match.short_url, $s_match.match_result_id, " .
		$s_season_match . '.season_id ' .
		"FROM $s_match INNER JOIN $s_season_match ON $s_match.match_id = $s_season_match.match_id " .
		'WHERE ' . $s_match . '.match_type IN (' .  join(', ', $a_types) . ') ' .
		'ORDER BY ' . $s_match . '.start_time DESC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @param $b_with_team_names
	 * @desc Read from the db the matches in the supplied seasons
	 */
	public function ReadBySeasonId($a_ids, $b_with_team_names=false)
	{
		/* @var $o_settings SiteSettings */
		$this->ValidateNumericArray($a_ids);

		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_mt = $this->GetSettings()->GetTable('MatchTeam');
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');
		$s_season_rule = $this->GetSettings()->GetTable('SeasonRule');
		$s_comp = $this->o_settings->GetTable('Competition');
		$s_ground = $this->GetSettings()->GetTable('Ground');

		$s_sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.match_type, $s_match.start_time, $s_match.start_time_known, $s_match.short_url, 
		$s_match.players_per_team, $s_match.player_type_id, $s_match.qualification, $s_match.tournament_spaces, " .
		$s_match . '.home_runs, ' . $s_match . '.home_wickets, ' . $s_match . '.away_runs, ' . $s_match . '.away_wickets, ' . $s_match . '.match_result_id, ' . 
		$s_match . '.home_bat_first, ' . $s_match . ".date_changed, $s_match.modified_by_id, " . 
		$s_ground . '.ground_id, ' . $s_ground . '.saon, ' . $s_ground . '.paon, ' . $s_ground . '.town, ' .
		"$s_season.season_id, $s_season.season_name, " .
		$s_comp . '.competition_id, ' . $s_comp . '.competition_name, ' .
		'home_link.team_id AS home_team_id, ' .
		'away_link.team_id AS away_team_id, ';

		if ($b_with_team_names)
		{
			$s_sql .= 'home_team.team_name AS home_team_name, away_team.team_name AS away_team_name, ';
		}

		$s_sql .= $s_season_rule . '.home_points, ' . $s_season_rule . '.away_points, 
		user.known_as
		FROM ';

		if ($b_with_team_names)	$s_sql .= '((';

		$s_sql .= '((((((' . $s_match . ' INNER JOIN ' . $s_season_match . ' ON ' . $s_match . '.match_id = ' . $s_season_match . '.match_id) ' .
		'INNER JOIN ' . $s_season . ' ON ' . $s_season_match . '.season_id = ' . $s_season . '.season_id) ' .
		'INNER JOIN ' . $s_comp . ' ON ' . $s_comp . '.competition_id = ' . $s_season . '.competition_id) ' .
		'LEFT OUTER JOIN ' . $s_mt . ' AS home_link ON ' . $s_match . '.match_id = home_link.match_id AND home_link.team_role = ' . TeamRole::Home() . ') ' . # tournament or cup final may have no teams
		'LEFT OUTER JOIN ' . $s_mt . ' AS away_link ON ' . $s_match . '.match_id = away_link.match_id AND away_link.team_role = ' . TeamRole::Away() . ') ' . # tournament or cup final may have no teams
		'LEFT OUTER JOIN ' . $s_ground . ' ON ' . $s_match . '.ground_id = ' . $s_ground . '.ground_id) ';

		if ($b_with_team_names)
		{
			$s_sql .= 'LEFT OUTER JOIN nsa_team AS home_team ON home_link.team_id = home_team.team_id) ' .
			'LEFT OUTER JOIN nsa_team AS away_team ON away_link.team_id = away_team.team_id) ';
		}

		$s_sql .= 'LEFT OUTER JOIN ' . $s_season_rule . ' ON ' . $s_match . '.match_result_id = ' . $s_season_rule . '.match_result_id AND ' . $s_season . '.season_id = ' . $s_season_rule . '.season_id ' .
		"LEFT OUTER JOIN nsa_user AS user ON $s_match.modified_by_id = user.user_id " . 
		'WHERE ' . $s_match . '.match_type != ' .  MatchType::TOURNAMENT_MATCH . ' ' .
		'AND ' . $s_season . '.season_id IN (' . join(', ', $a_ids) . ') ';
        
        if ($this->filter_by_start_date)
        {
            $s_sql .= "AND start_time >= " . $this->filter_by_start_date . ' ';
        }
        
		$s_sql .= 'ORDER BY ' . $s_match . '.start_time ASC';
        
        if ($this->filter_by_max_items)
        {
            $s_sql .= " LIMIT 0," . $this->filter_by_max_items;
        }

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}


	/**
	 * @access public
	 * @return void
     * @desc Read from the db the matches matching the active filters
	 */
	public function ReadMatchSummaries()
	{
		/* @var $o_settings SiteSettings */
		$by_team = count($this->filter_by_teams);
        $include_teams = ($by_team or $this->filter_by_tournament_id);

        # build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_mt = $this->GetSettings()->GetTable('MatchTeam');
		$s_ground = $this->GetSettings()->GetTable('Ground');
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');
		$players = $this->GetSettings()->GetTable("Player");

		$s_sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.match_type, $s_match.player_type_id, $s_match.start_time, $s_match.start_time_known, 
		$s_match.short_url, $s_match.players_per_team,  $s_match.home_runs, $s_match.home_wickets, $s_match.away_runs, $s_match.away_wickets, $s_match.match_result_id,  
		$s_match.home_bat_first, $s_match.date_added, $s_match.date_changed, $s_match.modified_by_id, $s_match.qualification, $s_match.overs AS overs_per_innings, $s_match.tournament_spaces, $s_match.match_notes, 
		$s_ground.ground_id, $s_ground.saon, $s_ground.paon, $s_ground.town, $s_ground.latitude, $s_ground.longitude, $s_ground.geo_precision,
		$s_season.season_id, $s_season.season_name, 
		nsa_competition.competition_id, nsa_competition.competition_name, ";

		if ($include_teams)
		{
			$s_sql .= 'home_team.team_name AS home_team_name, home_team.short_url AS home_short_url, ' .
			'away_team.team_name AS away_team_name, away_team.short_url AS away_short_url, ';
		}

		$s_sql .= 'home_link.team_id AS home_team_id, ' .
		'away_link.team_id AS away_team_id, 
		user.known_as ' .
		'FROM (((((((' . $s_match . ' LEFT OUTER JOIN ' . $s_mt . ' AS home_link ON ' . $s_match . '.match_id = home_link.match_id AND home_link.team_role = ' . TeamRole::Home() . ') ' .
		'LEFT OUTER JOIN ' . $s_mt . ' AS away_link ON ' . $s_match . '.match_id = away_link.match_id AND away_link.team_role = ' . TeamRole::Away() . ') ' .
		'LEFT OUTER JOIN ' . $s_season_match . ' ON ' . $s_match . '.match_id = ' . $s_season_match . '.match_id) ' .
		'LEFT OUTER JOIN ' . $s_season . ' ON ' . $s_season_match . '.season_id = ' . $s_season . '.season_id) ' .
		'LEFT OUTER JOIN nsa_competition ON nsa_competition.competition_id = ' . $s_season . '.competition_id) ';

		if ($include_teams)
		{
			# Left outer to away team, not inner, because a tournament might only know a home team
			$s_sql .= 'LEFT OUTER JOIN nsa_team AS home_team ON home_link.team_id = home_team.team_id) ' .
			'LEFT OUTER JOIN nsa_team AS away_team ON away_link.team_id = away_team.team_id) ';
		}
		else
		{
			$s_sql .= ')) ';
		}

		$s_sql .= 'LEFT OUTER JOIN ' . $s_ground . ' ON ' . $s_match . '.ground_id = ' . $s_ground . '.ground_id ' .
		"LEFT OUTER JOIN nsa_user AS user ON $s_match.modified_by_id = user.user_id ";
        
        $where = "";
		
		if ($this->filter_by_start_date > 0)
		{
			$where = $this->SqlAddCondition($where, 'nsa_match.start_time >= ' . $this->filter_by_start_date, 1);
		}
		if ($this->filter_by_end_date > 0)
		{
			$where = $this->SqlAddCondition($where, 'nsa_match.start_time <= ' . $this->filter_by_end_date, 1);
		}

		if ($by_team)
		{
			$team_ids = join(', ', $this->filter_by_teams);
            if ($where)
            {
                $where .= 'AND ';
            }
			$where .= '(home_link.team_id IN (' . $team_ids . ') OR away_link.team_id IN (' . $team_ids . ')) ';
		}

		if (!is_null($this->filter_by_match_types) and count($this->filter_by_match_types))
		{
			$s_type_ids = join(', ', $this->filter_by_match_types);
            if ($where)
            {
                $where .= 'AND ';
            }
			$where .= $s_match . '.match_type IN (' . $s_type_ids . ') ';
		}

		if (!is_null($this->filter_by_grounds) and count($this->filter_by_grounds))
		{
			$ground_ids = join(', ', $this->filter_by_grounds);
            if ($where)
            {
                $where .= 'AND ';
            }
			$where .= $s_match . '.ground_id IN (' . $ground_ids . ') ';
		}

        # Filter out tournament matches unless selecting a tournament, to save remembering it every time
        if (is_null($this->filter_by_tournament_id))
        {
            if ($where)
            {
                $where .= 'AND ';
            }
            $where .= $s_match . '.match_type != ' .  MatchType::TOURNAMENT_MATCH . ' ';
        } 

        if (!is_null($this->filter_by_match_result) and count($this->filter_by_match_result))
        {
            $result_ids = join(', ', $this->filter_by_match_result);
           if ($where)
            {
                $where .= 'AND ';
           }
            $clause = $s_match . '.match_result_id IN (' . $result_ids . ') ';
            if (in_array(MatchResult::UNKNOWN, $this->filter_by_match_result)) {
                $clause = "($clause OR match_result_id IS NULL) ";
            }
           $where .= $clause;
        }

		if (!is_null($this->filter_by_player_types) and count($this->filter_by_player_types))
		{
			$s_player_ids = join(', ', $this->filter_by_player_types);
            if ($where)
            {
                $where .= 'AND ';
            }
			$where .= "$s_match.player_type_id IN (" . $s_player_ids . ') ';
		}
        
        if (!is_null($this->filter_by_tournament_id))
        {
            if ($where)
            {
                $where .= 'AND ';
            }
            $where .= "$s_match.tournament_match_id = " . Sql::ProtectNumeric($this->filter_by_tournament_id) . " ";
        } 

        if (!is_null($this->filter_by_competitions) and count($this->filter_by_competitions))
        {
            $competition_ids = join(', ', $this->filter_by_competitions);
            if ($where)
            {
                $where .= 'AND ';
            }
            $where .= "nsa_competition.competition_id IN ($competition_ids) ";
        } 
        
        if ($where)
        {
            $s_sql .= 'WHERE ' . $where;
        } 

        # Support default or custom sort order
        if (isset($this->sort_by) and $this->sort_by) 
        {
            $s_sql .= "ORDER BY $this->sort_by ";
        }
        else 
        {
            $s_sql .= 'ORDER BY ' . $s_match . '.start_time ASC ';   
        }

        if ($this->filter_by_max_items) 
        {
            $s_sql .= 'LIMIT 0, ' . $this->filter_by_max_items;
        }
        
        # run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}


	/**
	 * Adds short URL info to the supplied match
	 * @param Match to update
	 * @return void
	 */
	public function ExpandMatchUrl(Match $match)
	{
		# Must have id for the query
		if (!$match->GetId()) return;

		# Get short URL for the match and add it to the object
		$s_sql = "SELECT short_url FROM " . $this->GetSettings()->GetTable('Match') . " WHERE match_id = " . Sql::ProtectNumeric($match->GetId());
		$result = $this->GetDataConnection()->query($s_sql);
		if ($row = $result->fetch())
		{
			$match->SetShortUrl($row->short_url);
		}
		$result->closeCursor();
		unset($result);
	}

	/**
	 * Adds match season and competiiton information to the matches already in the collection
	 *
	 */
	public function ExpandMatchSeasons()
	{
		# Get ids of matches in internal array
		$a_season_ids = array();
		$a_matches = array();
		foreach ($this->GetItems() as $o_match)
		{
			/* @var $o_match Match */
			foreach ($o_match->Seasons() as $season) $a_season_ids[] = $season->GetId();
			$a_matches[$o_match->GetId()] = $o_match;
		}

		if (count($a_season_ids))
		{
			# Select season info where match_id in (match ids)
			$s_season = $this->GetSettings()->GetTable('Season');
			$s_comp = $this->GetSettings()->GetTable('Competition');

			$s_sql = "SELECT $s_season.season_id, $s_season.season_name, $s_season.start_year, $s_season.end_year, " .
			$s_comp . '.competition_id, ' . $s_comp . '.competition_name ' .
			"FROM $s_season INNER JOIN $s_comp ON $s_season.competition_id = $s_comp.competition_id " .
			'WHERE ' . $s_season . '.season_id IN (' . join(', ', $a_season_ids) . ') ';

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# Add data to objects
			while($o_row = $o_result->fetch())
			{
				foreach ($a_matches as $match)
				{
					$this->BuildSeason($match, $o_row);
				}
			}

			# tidy up
			$o_result->closeCursor();
			unset($o_result);

			# Update internal items
			$this->SetItems($a_matches);
		}
	}

	/**
	 * @access public
	 * @return array (UNIX timestamps of months as keys, number of matches as values)
	 * @desc Read from the db all months containing matches
	 */
	public function ReadMatchMonths()
	{
		# build query
		$s_match = $this->o_settings->GetTable('Match');

		$s_sql = "SELECT $s_match.match_id, $s_match.start_time, $s_match.start_time_known " .
		'FROM ' . $s_match . ' ' .
		'ORDER BY ' . $s_match . '.start_time DESC ';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# see which months have matches
		$a_months = array();
		if ($o_result instanceof MySqlRawData)
		{
			while ($o_row = $o_result->fetch())
			{
				# Get first day of month match takes place
				$i_month = gmmktime(0, 0, 0, gmdate('m', $o_row->start_time), 1, gmdate('Y', $o_row->start_time));

				# If not already in array, add it
				if (empty($a_months[$i_month])) $a_months[$i_month] = 1;

				# Otherwise increment count
				else $a_months[$i_month]++;
			}
		}

		# tidy up
		$o_result->closeCursor();
		unset($o_result);

		return $a_months;
	}

	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		$this->Clear();

		$o_match_builder = new CollectionBuilder();
		$o_team_builder = new CollectionBuilder();
		$o_tournament_match_builder = new CollectionBuilder();
		$season_builder = new CollectionBuilder();

		while($o_row = $o_result->fetch())
		{
			if (!$o_match_builder->IsDone($o_row->match_id))
			{
				if (isset($o_match))
				{
					$this->Add($o_match);
					$o_team_builder->Reset();
					$o_tournament_match_builder->Reset();
					$season_builder->Reset();
				}

				# create new
				$o_match = new Match($this->GetSettings());
				$this->BuildMatchSummary($o_match, $o_row);

				if ($o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH and isset($o_row->tournament_match_id))
				{
					$o_tourn = new Match($this->o_settings);
					$o_tourn->SetMatchType(MatchType::TOURNAMENT);
					$o_tourn->SetId($o_row->tournament_match_id);
					if (isset($o_row->tournament_title)) $o_tourn->SetTitle($o_row->tournament_title);
					if (isset($o_row->tournament_url)) $o_tourn->SetShortUrl($o_row->tournament_url);
					$o_match->SetTournament($o_tourn);
					unset($o_tourn);
				}

				if (isset($o_row->home_team_id) and !is_null($o_row->home_team_id))
				{
					$o_home = new Team($this->o_settings);
					$o_home->SetId($o_row->home_team_id);
					if (isset($o_row->home_team_name)) $o_home->SetName($o_row->home_team_name);
					if (isset($o_row->home_short_url)) $o_home->SetShortUrl($o_row->home_short_url);
					$o_match->SetHomeTeam($o_home);
					unset($o_home);
				}

				$this->BuildGround($o_match, $o_row);
			}

			# Add away teams
			if (isset($o_row->away_team_id) && !$o_team_builder->IsDone($o_row->away_team_id))
			{
				$o_away = new Team($this->o_settings);
				$o_away->SetId($o_row->away_team_id);
				if (isset($o_row->away_team_name)) $o_away->SetName($o_row->away_team_name);
				if (isset($o_row->away_short_url)) $o_away->SetShortUrl($o_row->away_short_url);
				$o_match->AddAwayTeam($o_away);
				unset($o_away);
			}

			# Add matches in tournament
			if (isset($o_row->match_id_in_tournament) and is_numeric($o_row->match_id_in_tournament) and !$o_tournament_match_builder->IsDone($o_row->match_id_in_tournament))
			{
				$o_tmatch = new Match($this->o_settings);
				$o_tmatch->SetMatchType(MatchType::TOURNAMENT_MATCH);
				$o_tmatch->SetId($o_row->match_id_in_tournament);
				$o_tmatch->SetTitle($o_row->tournament_match_title);
				$o_tmatch->SetShortUrl($o_row->tournament_match_url);
				$o_tmatch->SetStartTime($o_row->tournament_match_time);
				$o_tmatch->SetIsStartTimeKnown($o_row->tournament_match_start_time_known);
                $o_tmatch->SetOrderInTournament($o_row->order_in_tournament);

				$o_match->AddMatchInTournament($o_tmatch);
				unset($o_tmatch);
			}

			# Add seasons
			$this->BuildSeason($o_match, $o_row, $season_builder);

		}

		# Add final match
		if (isset($o_match)) $this->Add($o_match);

		return true;
	}

	/**
	 * Helper to build basic info about a match from raw data
	 *
	 * @param Match $match
	 * @param DataRow $row
	 */
	private function BuildMatchSummary(Match $match, $row)
	{
		$match->SetId($row->match_id);
		if (isset($row->start_time)) $match->SetStartTime($row->start_time);
		if (isset($row->start_time_known)) $match->SetIsStartTimeKnown($row->start_time_known);
		if (isset($row->match_type)) $match->SetMatchType($row->match_type);
		if (isset($row->player_type_id)) $match->SetPlayerType($row->player_type_id);
        if (isset($row->qualification)) $match->SetQualificationType($row->qualification);
		if (isset($row->players_per_team)) $match->SetMaximumPlayersPerTeam($row->players_per_team);
        if (isset($row->max_tournament_teams)) $match->SetMaximumTeamsInTournament($row->max_tournament_teams);
        if (isset($row->tournament_spaces)) $match->SetSpacesLeftInTournament($row->tournament_spaces);
		if (isset($row->overs_per_innings)) $match->SetOvers($row->overs_per_innings);
		if (isset($row->match_title)) $match->SetTitle($row->match_title);
		if (isset($row->custom_title)) $match->SetUseCustomTitle($row->custom_title);
		if (isset($row->home_bat_first) and !is_null($row->home_bat_first)) $match->Result()->SetHomeBattedFirst($row->home_bat_first);
        if (isset($row->won_toss) and !is_null($row->won_toss)) $match->Result()->SetTossWonBy($row->won_toss);
		if (isset($row->home_runs)) $match->Result()->SetHomeRuns($row->home_runs);
		if (isset($row->home_wickets)) $match->Result()->SetHomeWickets($row->home_wickets);
		if (isset($row->away_runs)) $match->Result()->SetAwayRuns($row->away_runs);
		if (isset($row->away_wickets)) $match->Result()->SetAwayWickets($row->away_wickets);
		if (isset($row->home_points)) $match->Result()->SetHomePoints($row->home_points);
		if (isset($row->away_points)) $match->Result()->SetAwayPoints($row->away_points);
		if (isset($row->player_of_match_id))
		{
			$player = new Player($this->GetSettings());
			$player->SetId($row->player_of_match_id);
			$player->SetName($row->player_of_match);
			$player->SetShortUrl($row->player_of_match_url);
			$player->Team()->SetId($row->player_of_match_team_id);
			$match->Result()->SetPlayerOfTheMatch($player);
		}
		if (isset($row->player_of_match_home_id))
		{
			$player = new Player($this->GetSettings());
			$player->SetId($row->player_of_match_home_id);
			$player->SetName($row->player_of_match_home);
			$player->SetShortUrl($row->player_of_match_home_url);
			$player->Team()->SetId($row->player_of_match_home_team_id);
			$match->Result()->SetPlayerOfTheMatchHome($player);
		}
		if (isset($row->player_of_match_away_id))
		{
			$player = new Player($this->GetSettings());
			$player->SetId($row->player_of_match_away_id);
			$player->SetName($row->player_of_match_away);
			$player->SetShortUrl($row->player_of_match_away_url);
			$player->Team()->SetId($row->player_of_match_away_team_id);
			$match->Result()->SetPlayerOfTheMatchAway($player);
		}
		if (isset($row->match_notes)) $match->SetNotes($row->match_notes);
		if (isset($row->short_url)) $match->SetShortUrl($row->short_url);
        if (isset($row->update_search) and $row->update_search == 1) $match->SetSearchUpdateRequired();
		if (isset($row->date_added) and is_numeric($row->date_added)) 
		{
		    $match->SetDateAdded($row->date_added);
        }
		if (isset($row->added_by) and is_numeric($row->added_by))
		{
			$match->SetAddedBy(new User($row->added_by));
		}
		if (isset($row->match_result_id)) $match->Result()->SetResultType($row->match_result_id);
        if (isset($row->date_changed))
        {
            $match->SetLastAudit(new AuditData($row->modified_by_id, $row->known_as, $row->date_changed));
        }
	}

	/**
	 * Helper to build season and competition for a match from raw data
	 *
	 * @param Match $o_match
	 * @param DataRow $o_row
	 * @param CollectionBuilder $season_builder
	 */
	private function BuildSeason(Match $o_match, $o_row, $season_builder=null)
	{
		if (isset($o_row->season_id) and (is_null($season_builder) or !$season_builder->IsDone($o_row->season_id)))
		{
			$o_season = $o_match->Seasons()->GetItemByProperty('GetId', (int)$o_row->season_id);
			$b_existing_season = is_object($o_season);
			if (!$b_existing_season)
			{
				$o_season = new Season($this->GetSettings());
			}
			$o_season->SetId($o_row->season_id);
			if (isset($o_row->season_name)) $o_season->SetName($o_row->season_name);
			if (isset($o_row->season_short_url)) $o_season->SetShortUrl($o_row->season_short_url);
			if (isset($o_row->start_year)) $o_season->SetStartYear($o_row->start_year);
			if (isset($o_row->end_year)) $o_season->SetEndYear($o_row->end_year);

			$o_comp = new Competition($this->GetSettings());
			if (isset($o_row->competition_id)) $o_comp->SetId($o_row->competition_id);
			if (isset($o_row->competition_name)) $o_comp->SetName($o_row->competition_name);
			if (isset($o_row->notification_email)) $o_comp->SetNotificationEmail($o_row->notification_email);
			$o_comp->Add($o_season);

			if (!$b_existing_season) $o_match->Seasons()->Add($o_season);
		}
	}

	/**
	 * Helper to build ground for a match from raw data
	 *
	 * @param Match $o_match
	 * @param DataRow $o_row
	 */
	private function BuildGround(Match $o_match, $o_row)
	{
		if (isset($o_row->ground_id))
		{
			$o_ground = new Ground($this->GetSettings());
			$o_ground->SetId($o_row->ground_id);
			if (isset($o_row->ground_short_url)) $o_ground->SetShortUrl($o_row->ground_short_url);

			if (isset($o_row->town))
			{
				$o_addr = $o_ground->GetAddress();
				$o_addr->SetSaon($o_row->saon);
				$o_addr->SetPaon($o_row->paon);
                if (isset($o_row->street_descriptor)) $o_addr->SetStreetDescriptor($o_row->street_descriptor);
                if (isset($o_row->locality)) $o_addr->SetLocality($o_row->locality);
				$o_addr->SetTown($o_row->town);
                if (isset($o_row->postcode)) $o_addr->SetPostcode($o_row->postcode);
			}

			if (isset($o_row->latitude))
			{
				$o_addr->SetGeoLocation($o_row->latitude, $o_row->longitude, $o_row->geo_precision);
			}

			$o_match->SetGround($o_ground);
			unset($o_ground);
		}
	}


	/**
	 * Checks whether the given match fixture data is already in the database; returns the duplicate match or null
	 *
	 * @param Match $match_to_compare
	 * @param bool $b_user_is_match_admin
	 * @return Match
	 */
	private function GetDuplicateFixture(Match $match_to_compare, $b_user_is_match_admin)
	{
		# Make sure it's not a duplicate. If it is, return the duplicate match.
		$is_duplicate = false;
		$is_new_match = !(bool)$match_to_compare->GetId();

		$id_of_duplicate = null;

		$s_match = $this->GetSettings()->GetTable('Match');
		$s_mt = $this->GetSettings()->GetTable('MatchTeam');
		$i_tournament = is_null($match_to_compare->GetTournament()) ? null : $match_to_compare->GetTournament()->GetId();
		$i_ground = ($match_to_compare->GetGroundId() > 0) ? $match_to_compare->GetGroundId() : null;

		$s_sql = "SELECT $s_match.match_id FROM $s_match INNER JOIN $s_mt ON $s_match.match_id = $s_mt.match_id AND $s_mt.team_role = " . TeamRole::Home();
		$s_where = $this->SqlAddCondition('', 'tournament_match_id' . Sql::ProtectNumeric($i_tournament, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'ground_id' . Sql::ProtectNumeric($i_ground, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'start_time = ' . Sql::ProtectNumeric($match_to_compare->GetStartTime()));
		$s_where = $this->SqlAddCondition($s_where, 'start_time_known = ' . Sql::ProtectBool($match_to_compare->GetIsStartTimeKnown()));
		$s_where = $this->SqlAddCondition($s_where, 'match_notes = ' . Sql::ProtectString($this->GetDataConnection(), $match_to_compare->GetNotes()));
		$s_where = $this->SqlAddCondition($s_where, "$s_mt.team_id" . Sql::ProtectNumeric($match_to_compare->GetHomeTeamId(), true, true));

		# If it's a new match we want to check that it's not a duplicate match type of one already added.
		# If it's an updated match only an admin can change that. If not an admin, data's not even available so don't compare.
		if ($is_new_match or $b_user_is_match_admin)
		{
			$s_where = $this->SqlAddCondition($s_where, 'match_type = ' . Sql::ProtectNumeric($match_to_compare->GetMatchType()));
		}

		# If it's a tournament the player type should be specified by the user so check whether it's changed.
		# For any other match it's inferred automatically from other metadata about the match as it's saved,
		# and we're already checking whether that other data has changed so don't check for player type.
		if ($match_to_compare->GetMatchType() == MatchType::TOURNAMENT)
		{
			$s_where = $this->SqlAddCondition($s_where, 'player_type_id' . Sql::ProtectNumeric($match_to_compare->GetPlayerType(), true, true));
		}

		# If there's an id we're comparing whether the existing match has changed.
		# If no id we're looking whether the match has already been added, probably by someone else.
		if (!$is_new_match)
		{
			$s_where = $this->SqlAddCondition($s_where, "$s_match.match_id" . Sql::ProtectNumeric($match_to_compare->GetId(), false, true));

			# Only compare title properties for an update to a match, not for a match being added. If a new match is being added
			# the same match may exist with a different title because a result has been filled in - we still want to treat that as
			# a duplicate and not add the new match. But if the match is being updated it can only come from the match editing screen,
			# where the existing title is visible. The title may have changed due to an updated result, but OK to treat this as a changed
			# fixture in that event - an email about the match update is going to be sent and the match marked as updated anyway, so no problem.
			$s_where = $this->SqlAddCondition($s_where, 'match_title = ' . Sql::ProtectString($this->GetDataConnection(), $match_to_compare->GetTitle(), false));
			$s_where = $this->SqlAddCondition($s_where, 'custom_title' . Sql::ProtectBool($match_to_compare->GetUseCustomTitle(), false, true));

			# A new match would have either no or a newly-generated short URL, which inevitably would be different from the existing match
			# had it already been entered. But if we're updating the match the short URL may have been changed deliberately and we want to
			# recognise that change, not throw it away as a duplicate.
			$s_where = $this->SqlAddCondition($s_where, 'short_url = ' . Sql::ProtectString($this->GetDataConnection(), $match_to_compare->GetShortUrl(), false));
		}

		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		$result = $this->GetDataConnection()->query($s_sql);
		if ($o_row = $result->fetch())
		{
            $id_of_duplicate = $o_row->match_id; # if it's a new match, this gives info on the match as it already exists
			$is_duplicate = true;
		}
		$result->closeCursor();

		# If basic match details appear to be a duplicate, does it have the same away team(s) too?
		if ($is_duplicate)
		{
			$s_sql = "SELECT team_id FROM $s_mt WHERE match_id =  " . Sql::ProtectNumeric($id_of_duplicate) . " AND team_role = " . TeamRole::Away();
			$result = $this->GetDataConnection()->query($s_sql);

            $existing_away_team_ids = array();
            while ($row = $result->fetch()) $existing_away_team_ids[] = (int)$row->team_id;
            $result->closeCursor();

			if (count($existing_away_team_ids) != count($match_to_compare->GetAwayTeams()))
			{
				# Different number of teams...
				$is_duplicate = false;
			}

            # Same number of away teams... now are those away teams the same ones as in the match we're comparing?
			if ($is_duplicate)
			{
				foreach ($match_to_compare->GetAwayTeams() as $team)
				{
					/* @var $team Team */
					if (!in_array($team->GetId(), $existing_away_team_ids, true))
					{
						$is_duplicate = false;
						break;
					}
				}
			}
		}

		# If basic match details and away teams appear to be a duplicate, does it have the same season(s) too?
		if ($is_duplicate)
		{
			# 1. If the user isn't admin and is updating match, don't check. They're not allowed to change season data so it's not even available.
			# 2. If the user isn't admin and it's a new match, is the existing match in the given season - don't care if it's in others too
			# 3. If the user is an admin, we want to look for an exact match
			if ($b_user_is_match_admin or $is_new_match) # this checks for condition #1
			{
				$s_sql = "SELECT season_id FROM " . $this->GetSettings()->GetTable('SeasonMatch') . " WHERE match_id =  " . Sql::ProtectNumeric($id_of_duplicate);
				$result = $this->GetDataConnection()->query($s_sql);

                $existing_season_ids = array();
                while ($row = $result->fetch()) $existing_season_ids[] = (int)$row->season_id;
                $result->closeCursor();

				if (count($existing_season_ids) == $match_to_compare->Seasons()->GetCount() or (!$b_user_is_match_admin and $is_new_match)) # second check allows condition #2 through, blocks condition #3
				{
					# Same number of seasons...
				}
				else
				{
					# Different number of seasons...
					$is_duplicate = false;
				}

				# Disposed of result, now are those seasons are the same ones as in the match we're comparing?
				if ($is_duplicate)
				{
					foreach ($match_to_compare->Seasons() as $season)
					{
						/* @var $season Season */
						if (!in_array($season->GetId(), $existing_season_ids, true))
						{
							$is_duplicate = false;
							break;
						}
					}
				}
			}
		}

		# Return the duplicate if one was found, otherwise return null
		if ($is_duplicate)
		{
			$match_to_compare->SetId($id_of_duplicate);
			return $match_to_compare;
		}
		else
		{
			return null;
		}
	}

    /**
	 * @return void
	 * @desc Save the fixture details of the supplied Match to the database, and return the id
	 */
	public function SaveFixture(Match $o_match, AuthenticationManager $authentication_manager)
	{
		/* @var $o_away_team Team */
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');

		# check permissions - only save limited fields if not admin
		$b_user_is_match_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);
		$b_is_new_match = !$o_match->GetId();

		# Ensure we know the match type because we'll make decisions based on it
		if (!$b_is_new_match and !$b_user_is_match_admin)
		{
			# Match type can't be changed on this request so MatchType property usually not populated.
			# Read from db, otherwise these variables can be wrong and the code can follow the wrong path.
			$s_sql = "SELECT match_type FROM $s_match WHERE match_id = " . Sql::ProtectNumeric($o_match->GetId());
			$result = $this->GetDataConnection()->query($s_sql);
			if ($row = $result->fetch())
			{
				$o_match->SetMatchType($row->match_type);
			}
			$result->closeCursor();
		}
		$is_friendly = ($o_match->GetMatchType() == MatchType::FRIENDLY);
		$is_tournament = ($o_match->GetMatchType() == MatchType::TOURNAMENT);
		$is_tournament_match = ($o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH and $o_match->GetTournament() instanceof Match);

		# Ensure all involved teams have their name, because that's essential to building a match title,
		# and short URL because that's used to build a non-tournament match URL
		$teams_without_data = array();
		if ($o_match->GetHomeTeamId() and (!$o_match->GetHomeTeam()->GetName() or !$o_match->GetHomeTeam()->GetShortUrl()))
		{
			$teams_without_data[] = $o_match->GetHomeTeamId();
		}
		foreach ($o_match->GetAwayTeams() as $team)
		{
			/* @var $team Team */
			if ($team->GetId() and (!$team->GetName() or !$team->GetShortUrl())) $teams_without_data[] = $team->GetId();
		}
		if (count($teams_without_data))
		{
			$result = $this->GetDataConnection()->query("SELECT team_id, team_name, short_url FROM nsa_team WHERE team_id IN (" . implode(",",$teams_without_data) . ")");
			while ($row = $result->fetch())
			{
				if ($row->team_id == $o_match->GetHomeTeamId())
				{
					$o_match->GetHomeTeam()->SetName($row->team_name);
					$o_match->GetHomeTeam()->SetShortUrl($row->short_url);
				}
				foreach ($o_match->GetAwayTeams() as $team)
				{
					/* @var $team Team */
					if ($row->team_id == $team->GetId())
					{
						$team->SetName($row->team_name);
						$team->SetShortUrl($row->short_url);
					}
				}
			}
			$result->closeCursor();
		}

		# Make sure it's not a duplicate. If it is, just return the existing match's id.
		$duplicate_match = $this->GetDuplicateFixture($o_match, $b_user_is_match_admin);
		if ($duplicate_match instanceof Match)
		{
			return $duplicate_match->GetId();
		}

		# build query
		# NOTE: dates are user-selected, therefore they don't need adjusting

		$o_tournament = $o_match->GetTournament();
		$i_tournament = is_null($o_tournament) ? null : $o_tournament->GetId();
		$i_ground = ($o_match->GetGroundId() > 0) ? $o_match->GetGroundId() : null;

		# if no id, it's a new match; otherwise update the match
        # All changes to master data from here are logged, because this method can be called from the public interface
        if (!$b_is_new_match)
		{
	        # Update the main match record
			$s_sql = 'UPDATE nsa_match SET ';

            $updated_type = "";
            $updated_title = "";
            
			if ($b_user_is_match_admin)
			{
                $updated_type = 'match_type = ' . Sql::ProtectNumeric($o_match->GetMatchType()) . ', ';
			    $updated_title = "match_title = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetTitle()) .  ", ";
				
				$s_sql .= $updated_type . $updated_title .
        				'custom_title' . Sql::ProtectBool($o_match->GetUseCustomTitle(), false, true) . ', ';
			}
			else
			{
				# Non-admin user not given chance to amend a custom title, so if there is a custom title
				# it must be left unchanged. But if title was generated, it can be regenerated based on
				# the user's changes.
				$o_result = $this->GetDataConnection()->query("SELECT custom_title FROM $s_match WHERE $s_match.match_id = " . Sql::ProtectNumeric($o_match->GetId()));
				$o_row = $o_result->fetch();
				if (!is_null($o_row))
				{
					$o_match->SetUseCustomTitle($o_row->custom_title);
				}
				if (!$o_match->GetUseCustomTitle()) {
				    $updated_title = "match_title = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetTitle()) . ", ";
                    $s_sql .= $updated_title;
                }
			}

            $player_type = Sql::ProtectNumeric($this->WorkOutPlayerType($o_match), true, false);
			$ground_id = Sql::ProtectNumeric($i_ground, true);
			$start_time = Sql::ProtectNumeric($o_match->GetStartTime());

			$s_sql .= 'player_type_id = ' . $player_type . ', 
			qualification = ' . Sql::ProtectNumeric($o_match->GetQualificationType(), false, false) . ', 
			tournament_match_id = ' . Sql::ProtectNumeric($i_tournament, true) . ', 
    		ground_id = ' . $ground_id . ', ' .
			'start_time = ' . $start_time . ', ' .
			'start_time_known = ' . Sql::ProtectBool($o_match->GetIsStartTimeKnown()) . ', ' .
			"match_notes = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetNotes()) . ", 
            update_search = 1,  
			date_changed = " . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ' ' . 
			'WHERE match_id = ' . Sql::ProtectNumeric($o_match->GetId());
			$this->LoggedQuery($s_sql);

			# Update fields which are cached in the player statistics table
			$sql = "UPDATE $statistics SET
			$updated_type
			match_player_type = $player_type,
			match_time = $start_time,
			$updated_title
			ground_id = $ground_id
			WHERE match_id = " . Sql::ProtectNumeric($o_match->GetId());
			$this->GetDataConnection()->query($sql);
		}
		else
		{
			# If it's not a duplicate, insert the match
			$s_sql = 'INSERT INTO ' . $s_match . ' SET ' .
			"match_title = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetTitle()) . ", " .
			'custom_title' . Sql::ProtectBool($o_match->GetUseCustomTitle(), false, true) . ', ' .
			'match_type = ' . Sql::ProtectNumeric($o_match->GetMatchType()) . ', ' .
			'player_type_id = ' . Sql::ProtectNumeric($this->WorkOutPlayerType($o_match), true, false) . ', 
			qualification = ' . Sql::ProtectNumeric($o_match->GetQualificationType(), false, false) . ', 
            tournament_match_id = ' . Sql::ProtectNumeric($i_tournament, true) . ', 
    		ground_id = ' . Sql::ProtectNumeric($i_ground, true) . ', ' .
			'start_time = ' . Sql::ProtectNumeric($o_match->GetStartTime()) . ', ' .
			'start_time_known = ' . Sql::ProtectBool($o_match->GetIsStartTimeKnown()) . ', ' .
			"match_notes = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetNotes()) . ", 
			update_search = 1,  
            added_by " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId(), false, true) . ', ' .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());

			$this->LoggedQuery($s_sql);

			# get autonumber
			$o_match->SetId($this->GetDataConnection()->insertID());
		}

		# Save teams, unless this is a tournament, in which case it happens separately
		if (!$is_tournament)
		{
			$this->SaveTeams($o_match, $authentication_manager);
		}

        if ($is_tournament) {
            $this->CopyTournamentDetailsToTournamentMatches($o_match);
        } else if ($is_tournament_match) {
            $this->CopyTournamentMatchDetailsFromTournament($o_match->GetTournament(), $o_match);
        }

		# Only save players per side/overs for tournaments and friendlies. For other match types it's based on the
		# seasons and updated during SaveSeasons()
		if ($is_tournament or $is_friendly)
		{
			$players_per_team = $o_match->GetIsMaximumPlayersPerTeamKnown() ? Sql::ProtectNumeric($o_match->GetMaximumPlayersPerTeam()) : "NULL";
			$overs = $o_match->GetIsOversKnown() ? Sql::ProtectNumeric($o_match->GetOvers()) : "NULL";

			$s_sql = "UPDATE $s_match SET players_per_team = $players_per_team, overs = $overs " .
			" WHERE match_id = " . Sql::ProtectNumeric($o_match->GetId()) .
			" OR tournament_match_id = " . Sql::ProtectNumeric($o_match->GetId());

			$this->LoggedQuery($s_sql);
		}

        $this->UpdateMatchUrl($o_match, $b_user_is_match_admin);

		# Match data has changed so notify moderator
		$this->QueueForNotification($o_match->GetId(), $b_is_new_match);
	}

	private function CopyTournamentDetailsToTournamentMatches(Match $tournament) {
	    
        # If it's a tournament, copy the date of the tournament to the tournament matches to ensure they're always on the same day
        $typical_match_duration_in_seconds = 45*60;

        $sql = "UPDATE nsa_match SET start_time = " . Sql::ProtectNumeric($tournament->GetStartTime()) . " + ($typical_match_duration_in_seconds*IFNULL(order_in_tournament-1,0)), 
                start_time_known = 0
                WHERE tournament_match_id = " . Sql::ProtectNumeric($tournament->GetId());

        $this->LoggedQuery($sql);        
	}
    
    private function CopyTournamentMatchDetailsFromTournament(Match $tournament, Match $tournament_match) {
            
        # For a tournament match copy from the tournament everything that should be the same for matches in the tournament
        $sql = "SELECT start_time, players_per_team, overs FROM nsa_match WHERE match_id = " . Sql::ProtectNumeric($tournament->GetId());
        $result = $this->GetDataConnection()->query($sql);
        if ($row = $result->fetch())
        {
            $typical_match_duration_in_seconds = 45*60;
                        
            $sql = "UPDATE nsa_match SET
                start_time = " . Sql::ProtectNumeric($row->start_time) . " + ($typical_match_duration_in_seconds*IFNULL(order_in_tournament-1,0)), 
                start_time_known = 0,
                players_per_team = " . Sql::ProtectNumeric($row->players_per_team,true) . ",
                overs = " . Sql::ProtectNumeric($row->overs,true) . "
                WHERE match_id = " . Sql::ProtectNumeric($tournament_match->GetId());
            $this->LoggedQuery($sql);
        }
    }

    private function UpdateMatchUrl(Match $match, $user_is_match_admin) {

        # Ensure we have the correct details to generate a short URL.
        # Particularly important for tournament matches where this may have been updated from the tournament.
        
        $sql = "SELECT start_time FROM nsa_match WHERE match_id = " . Sql::ProtectNumeric($match->GetId(), false);
        $result = $this->GetDataConnection()->query($sql);
        $row = $result->fetch();
        if ($row) {
            $match->SetStartTime($row->start_time);
        }

        # URL generation scenarios
        # 1. New match added by public - generate short URL
        # 2. Match updated by public - regenerate allowing current url to be kept
        # 3. Match added or updated by admin - if blank or generation requested, regenerate allowing current url to be kept, otherwise keep
        $new_short_url = null;
        if (
            (!$user_is_match_admin) or
            ($user_is_match_admin and (!$match->GetShortUrl() or !$match->GetUseCustomShortUrl()))
            )
        {
            # Set up short URL manager
            require_once('http/short-url-manager.class.php');
            $url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
            $new_short_url = $url_manager->EnsureShortUrl($match, true);
        }

        # Save the URL for the match, and copy to match statistics
        $sql = "UPDATE nsa_match SET
                short_url = " . Sql::ProtectString($this->GetDataConnection(), $match->GetShortUrl()) . "
                WHERE match_id = " . Sql::ProtectNumeric($match->GetId());
        $this->LoggedQuery($sql);

        $sql = "UPDATE nsa_player_match SET
                match_url = " . Sql::ProtectString($this->GetDataConnection(), $match->GetShortUrl()) . "
                WHERE match_id = " . Sql::ProtectNumeric($match->GetId());
        $this->GetDataConnection()->query($sql);

        # Regenerate short URLs, but check whether manager exists because might've been denied permission above
        # and in that same case we do NOT want to regenerate short URLs
        if (isset($url_manager))
        {
            if (is_object($new_short_url))
            {
                $new_short_url->SetParameterValuesFromObject($match);
                $url_manager->Save($new_short_url);
            }

            unset($url_manager);
        }
        
    }
	
	/**
	 * Saves the links between a match and the teams playing in it
	 * @param $match
	 * @return void
	 */
	public function SaveTeams(Match $match, AuthenticationManager $authentication_manager)
	{
		if (!$match->GetId()) return;
        $match_id = Sql::ProtectNumeric($match->GetId());

		$match_team_ids = array();

		if ($match->GetHomeTeamId())
		{
			$match_team_ids[] = $this->EnsureTeamInMatch($match, $match->GetHomeTeamId(), TeamRole::Home());
		}

        $is_tournament = ($match->GetMatchType() == MatchType::TOURNAMENT);
        $tournament = null;
        if ($is_tournament)
        { 
            require_once("stoolball/team-manager.class.php");
            $team_manager = new TeamManager($this->GetSettings(),$this->GetDataConnection());
            
            # For a tournament, attempt to save the maxiumum number of teams
            $sql = "UPDATE nsa_match SET max_tournament_teams = " . Sql::ProtectNumeric($match->GetMaximumTeamsInTournament(), true, false) . ",
                    tournament_spaces = " . Sql::ProtectNumeric($match->GetSpacesLeftInTournament(), true, false) . "
                    WHERE match_id = $match_id";
            $this->GetDataConnection()->query($sql);
        }
		$away_teams = $match->GetAwayTeams();
		foreach ($away_teams as $away_team)
		{
		    $away_team_id = $away_team->GetId();
            
		    # If this is a tournament we may be adding teams based on the name rather than the ID
		    if (!$away_team_id and $is_tournament)
            {
                if (is_null($tournament))
                {
                    # Make sure we have full details for the tournament, in order to build any tournament teams
                    $this->ReadByMatchId(array($match->GetId()));
                    $tournament = $this->GetFirst();
                } 
                $away_team_id = $team_manager->SaveOrMatchTournamentTeam($tournament, $away_team, $authentication_manager);
            } 
        
            # Ensure we now have an ID, or the following queries will be invalid
            if ($away_team_id)
            {
            	$match_team_ids[] = $this->EnsureTeamInMatch($match, $away_team_id, TeamRole::Away());
            }
		}
        
	   	# See if there are any other teams for this match, aside from those we've just confirmed or added.
	   	# Those will be teams just deleted.
		$match_table = $this->GetSettings()->GetTable('Match');
		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$stats = $this->GetSettings()->GetTable('PlayerMatch');

		$sql = "SELECT match_team_id, team.team_id, team.team_type 
		          FROM $mt INNER JOIN nsa_team AS team ON $mt.team_id = team.team_id
		          WHERE match_id = $match_id";
		if (count($match_team_ids)) $sql .= " AND match_team_id NOT IN (" . implode(",",$match_team_ids) . ")";
		$result = $this->GetDataConnection()->query($sql);

        $delete_match_team_ids = array();
        $delete_team_ids = array();
        while ($row = $result->fetch())
        {
            $delete_match_team_ids[] = $row->match_team_id;
            if ($is_tournament and $row->team_type == Team::ONCE)
            {
                $delete_team_ids[] = $row->team_id;
            } 
        }
		if (count($delete_match_team_ids))
		{
			# If there are, delete them and their dependent records
			$delete_match_team_ids = implode(",",$delete_match_team_ids);

			$batting = $this->GetSettings()->GetTable('Batting');
			$bowling = $this->GetSettings()->GetTable('Bowling');

			$this->LoggedQuery("DELETE FROM $batting WHERE match_team_id IN ($delete_match_team_ids)");
			$this->LoggedQuery("DELETE FROM $bowling WHERE match_team_id IN ($delete_match_team_ids)");
			$this->LoggedQuery("DELETE FROM $stats WHERE match_team_id IN ($delete_match_team_ids)");
			$this->LoggedQuery("DELETE FROM $mt WHERE match_team_id IN ($delete_match_team_ids)");
            $this->RemovePlayerOfTheMatchIfTeamRemoved($match_id, "player_of_match_id");            
            $this->RemovePlayerOfTheMatchIfTeamRemoved($match_id, "player_of_match_home_id");            
            $this->RemovePlayerOfTheMatchIfTeamRemoved($match_id, "player_of_match_away_id");            
		}

		# make sure the player statistics have the correct opposition
		$this->GetDataConnection()->query("UPDATE $stats SET opposition_id = NULL WHERE match_id = $match_id");

		foreach ($match_team_ids as $match_team_id)
		{
			$sql = "SELECT team_id
						FROM $mt INNER JOIN $match_table m ON $mt.match_id = m.match_id
						WHERE m.match_id = (SELECT match_id FROM $mt WHERE match_team_id = $match_team_id)
						AND $mt.match_team_id != $match_team_id
						AND team_role IN (" . TeamRole::Home() . ", " . TeamRole::Away() . ")";
			$data = $this->GetDataConnection()->query($sql);
			$data_row = $data->fetch();

			if ($data_row)
			{
				$this->GetDataConnection()->query("UPDATE $stats SET opposition_id = $data_row->team_id WHERE match_team_id = $match_team_id");
			}
		}
		
		# If we found any once-only tournament teams that were removed, delete them
        if ($is_tournament)
        { 
    		if (count($delete_team_ids))
            {
                $team_manager->DeleteTeams($delete_team_ids, $authentication_manager);
            }
            unset($team_manager);
        }
             
		
		$result->closeCursor();
	}
	
	/**
     * Removes a player of the match if their team has been removed from the match
     * @param int $match_id
     * @param string $fieldName Field in nsa_match to update
     * @return void 
     */
	private function RemovePlayerOfTheMatchIfTeamRemoved($match_id, $fieldName)
    {
        $match_id = Sql::ProtectNumeric($match_id);
   
        $sql ="SELECT $fieldName FROM nsa_match INNER JOIN nsa_player ON $fieldName = nsa_player.player_id 
             WHERE match_id = $match_id
             AND nsa_player.team_id NOT IN (SELECT team_id FROM nsa_match_team WHERE match_id = $match_id AND team_role IN (" . TeamRole::Home() . ", " . TeamRole::Away() . "))";
        $result = $this->GetDataConnection()->query($sql);
        if ($result->fetch()) {
            $this->LoggedQuery("UPDATE nsa_match SET $fieldName = NULL WHERE match_id = $match_id");
        }
    
    }

	/**
	 * Ensures a team is recorded as playing the specified role in the specified match
	 * @param $match
	 * @param $team_idthis is essentially
	 * @param $team_role
	 * @return int match_team_id
	 */
	private function EnsureTeamInMatch(Match $match, $team_id, $team_role)
	{
		# See whether this record is already in the database. Important to preserve the match_team_id if possible
		# because it's used elsewhere as a foreign key.
		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$sql = "SELECT match_team_id
					FROM $mt
					WHERE match_id = " . Sql::ProtectNumeric($match->GetId(), false) . "
					AND team_id = " . Sql::ProtectNumeric($team_id, false) . "
					AND team_role = " . Sql::ProtectNumeric($team_role, false);
		$result = $this->GetDataConnection()->query($sql);
		if ($row = $result->fetch())
		{
            $result->closeCursor();
			return $row->match_team_id;
		}
		else
		{
			$sql = "INSERT INTO $mt SET
					match_id = " . Sql::ProtectNumeric($match->GetId(), false) . ",
					team_id = " . Sql::ProtectNumeric($team_id, false) . ",
					team_role = " . Sql::ProtectNumeric($team_role, false) . ",
					date_added = " . gmdate('U') . ",
					date_changed = " . gmdate('U');
			$this->LoggedQuery($sql);
			return $this->GetDataConnection()->insertID();
		}
	}

	/**
	 * If not explicitly specified by the user, infer the player type from the teams involved
	 * @param Match $match
	 * @return int
	 */
	private function WorkOutPlayerType(Match $match)
	{
		require_once('stoolball/player-type.enum.php');

		/* @var $match Match */
		# If player type is already specified, leave it alone. That should be the case for a tournament.
		if (!is_null($match->GetPlayerType())) return $match->GetPlayerType();

		# Second strategy, look at the teams playing the match
		$teams = $match->GetAwayTeams();
		if ($match->GetHomeTeamId()) $teams[] = $match->GetHomeTeam();
		if (count($teams))
		{
			# Get the team ids...
			$team_ids = array();
			foreach ($teams as $team) $team_ids[] = $team->GetId();

			# And use them to get the team player types...
			$sql = "SELECT DISTINCT player_type_id FROM nsa_team WHERE team_id IN (" . implode(',', $team_ids) . ")";
			$result = $this->GetDataConnection()->query($sql);
			$player_types = array();
			while ($row = $result->fetch()) $player_types[] = $row->player_type_id;
			$result->closeCursor();

			return $this->WorkOutPlayerTypeHelper($player_types);
		}

		# Third strategy, if no teams it could be a cup match where the teams will be known later?
		# Look at the player types of the seasons it's involved in.
		if ($match->Seasons()->GetCount())
		{
			# Get the season ids...
			$season_ids = array();
			foreach ($match->Seasons() as $season) $season_ids[] = $season->GetId();

			# And use them to get the season player types...
			$season = $this->GetSettings()->GetTable('Season');
			$comp = $this->GetSettings()->GetTable('Competition');
			$sql = "SELECT DISTINCT player_type_id FROM $season INNER JOIN $comp ON $season.competition_id = $comp.competition_id " .
					"WHERE season_id IN (" . implode(',', $season_ids) . ")";
			$result = $this->GetDataConnection()->query($sql);
			$player_types = array();
			while ($row = $result->fetch()) $player_types[] = $row->player_type_id;
			$result->closeCursor();

			return $this->WorkOutPlayerTypeHelper($player_types);
		}

		# Seems a bit unlikely you could have a match without any seasons or any teams, but just in case...
		return null;
	}

	/**
	 * Helper for WorkOutPlayerType(). Works out the player type of a match from an array of the types involved.
	 * @param $player_types
	 * @return int
	 */
	private function WorkOutPlayerTypeHelper($player_types)
	{
		$num_types = count($player_types);

		# only one type of player, that's the one to choose
		if ($num_types == 1) return $player_types[0];

		# check which player types are involved
		$mixed =in_array(PlayerType::MIXED, $player_types);
		$ladies = in_array(PlayerType::LADIES, $player_types);
		$men = in_array(PlayerType::MEN, $player_types);
		$junior_mixed = in_array(PlayerType::JUNIOR_MIXED, $player_types);
		$girls = in_array(PlayerType::GIRLS, $player_types);
		$boys = in_array(PlayerType::BOYS, $player_types);

		# if there's a mixed team involved, it's got to be a mixed match
		if ($mixed) return PlayerType::MIXED;

		# if adult men and women, must be mixed
		if ($ladies and $men) return PlayerType::MIXED;

		# Any further matches must involve children, but maybe adults too!
		# Check first for single-sex matches involving adults
		if ($ladies and $girls) return PlayerType::LADIES;
		if ($men and $boys) return PlayerType::MEN;

		# Any further matches involving adults must be mixed-sex
		if ($ladies or $men) return PlayerType::MIXED;

		# Now it's children only. Anything that only involved one gender
		# would have been caught by the very first test, so it must be mixed
		return PlayerType::JUNIOR_MIXED;
	}

    /**
     * Saves the supplied match array as the matches in a tournament
     */
    public function SaveMatchesInTournament(Match $tournament, AuthenticationManager $authentication_manager) {

        $match_ids_to_keep = array();

        foreach ($tournament->GetMatchesInTournament() as $match) {
            /* @var $match Match */
            if (!$match instanceof Match or $match->GetMatchType() !== MatchType::TOURNAMENT_MATCH) continue;
            
            if ($match->GetId()) {
                $match_ids_to_keep[] = $match->GetId(); 
            }
        }
        
        # Delete unwanted matches
        $match_ids_to_delete = array();
        
        $sql = "SELECT match_id FROM nsa_match 
				WHERE tournament_match_id = " . Sql::ProtectNumeric($tournament->GetId(), false, false);
		if (count($match_ids_to_keep)) {
			$sql .= " AND match_id NOT IN (" . join(',',$match_ids_to_keep) . ") ";
		}
		$result = $this->GetDataConnection()->query($sql);
        
        while($row = $result->fetch()) {
            $match_ids_to_delete[] = $row->match_id;
        }
    
        if (count($match_ids_to_delete)) {    
            $this->DeleteMatch($match_ids_to_delete);
        }
        
        # Add and update matches
        foreach ($tournament->GetMatchesInTournament() as $match) {
            /* @var $match Match */
            if (!$match instanceof Match or $match->GetMatchType() !== MatchType::TOURNAMENT_MATCH) continue;

            if (!$match->GetId()) {
                $this->SaveFixture($match, $authentication_manager);
            }
            $this->SaveMatchOrderInTournament($match->GetId(), $match->GetOrderInTournament());
            $this->CopyTournamentMatchDetailsFromTournament($tournament, $match);
        }
    }

    /**
     * Updates the order of a match in a tournament
     */
    private function SaveMatchOrderInTournament($match_id, $order) {
        
        if (!$match_id or !is_integer($match_id)) {
            throw new Exception('$match_id must be an integer');            
        }
        
        $sql = "UPDATE nsa_match SET 
            order_in_tournament = " . Sql::ProtectNumeric($order, true, false) . ',
            date_changed = ' . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . '  
            WHERE match_id = ' . Sql::ProtectNumeric($match_id);
        $this->LoggedQuery($sql);
    }

	/**
	 * Saves the seasons a match is in
	 *
	 * @param Match $match
	 * @param bool $b_is_new_match
	 */
	public function SaveSeasons(Match $match, $b_is_new_match)
	{
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');
		$season_table = $this->GetSettings()->GetTable('Season');
		$comp_table = $this->GetSettings()->GetTable('Competition');

		# Get ids of the tournament matches as well as this match, because they must necessarily be in the same season as their tournament
		# so we'll update the tournament matches whenever we update the season
		$a_matches_in_seasons = array();

		# Check GetId() rather than $b_is_new_match because match is being added as a multi-part process. Even though it's
		# new, by this point in the process it has an id that we need to use.
		if ($match->GetId()) $a_matches_in_seasons[] = $match->GetId();

		if (!$b_is_new_match and $match->GetMatchType() == MatchType::TOURNAMENT)
		{
			$s_sql = "SELECT match_id FROM $s_match WHERE tournament_match_id = " . Sql::ProtectNumeric($match->GetId());
			$o_result = $this->GetDataConnection()->query($s_sql);
			while($row = $o_result->fetch())
			{
				$a_matches_in_seasons[] = $row->match_id;
			}
			$o_result->closeCursor();
		}

		# All changes to master data from here are logged, because this method can be called from the public interface
		
		# Clear out seasons for this match and its tournament matches, ready to re-insert
		if (count($a_matches_in_seasons))
		{
			$sql = "DELETE FROM $s_season_match WHERE match_id IN (" . join(', ', $a_matches_in_seasons) . ')';
			$this->LoggedQuery($sql);
		}

		# Add seasons again with new data
		foreach ($match->Seasons() as $season)
		{
			/* @var $season Season */

			foreach ($a_matches_in_seasons as $i_match_id)
			{
				$sql = "INSERT INTO $s_season_match (season_id, match_id) VALUES (" . Sql::ProtectNumeric($season->GetId()) . ', ' . Sql::ProtectNumeric($i_match_id) . ') ';
				$this->LoggedQuery($sql);
			}

			# If participation in this match implies the team is part of the whole season (ie not practice or tournament or friendly),
			# make sure the team is in the season
			if ($match->GetMatchType() == MatchType::CUP or $match->GetMatchType() == MatchType::LEAGUE)
			{
				require_once('stoolball/season-manager.class.php');
				$season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
				if ($match->GetHomeTeamId())
				{
					$season_manager->EnsureTeamIsInSeason($match->GetHomeTeamId(), $season->GetId());
				}

				$a_away = $match->GetAwayTeams();
				if (is_array($a_away) and count($a_away))
				{
					foreach ($a_away as $o_away_team)
					{
						if (is_null($o_away_team)) continue;
						$season_manager->EnsureTeamIsInSeason($o_away_team->GetId(), $season->GetId());
					}
				}
				unset($season_manager);
			}

		}

		# The number of players in the match is derived from the competitions it's in. It's never entered directly even
		# by admins or displayed. Done to save extra queries when rendering scorecard editing interfaces. If people want to
		# display the number of players per match they can enter the results with the players' names.

		# Get the max number of players who may play based on the competitions the match is in, so long as this isn't a tournament or friendly.
		# For a tournament we'll ask the user instead, so just ignore this code and keep the existing value. Even in a season different
		# tournaments may have different rules, so really can't automate. Friendlies are just as flexible so, again, can't predict.
		if ($match->GetId() and $match->GetMatchType() != MatchType::TOURNAMENT
		and $match->GetMatchType() != MatchType::TOURNAMENT_MATCH
		and $match->GetMatchType() != MatchType::FRIENDLY)
		{
			$season_ids = array();
			foreach ($match->Seasons() as $season) $season_ids[] = $season->GetId();
			if (count($season_ids))
			{
				$s_sql = "SELECT MAX($comp_table.players_per_team) AS players_per_team, MAX($comp_table.overs) AS overs
				FROM $season_table INNER JOIN $comp_table ON $season_table.competition_id = $comp_table.competition_id
				WHERE $season_table.season_id IN (" . implode(',', $season_ids) . ")";

				$result = $this->GetDataConnection()->query($s_sql);
				if (!$this->GetDataConnection()->isError())
				{
					$row = $result->fetch();
					$match->SetMaximumPlayersPerTeam($row->players_per_team);
					$match->SetOvers($row->overs);
				}
				$result->closeCursor();
			}

			# Update the match. Using the GetMaximumPlayersPerTeam property because it will give us the existing value
			# (possibly the default value if the code above didn't run because there were no seasons).
			$sql = "UPDATE $s_match SET
						players_per_team = " . Sql::ProtectNumeric($match->GetMaximumPlayersPerTeam()) . ",
						overs = " . Sql::ProtectNumeric($match->GetOvers()) . "
						WHERE match_id = " . Sql::ProtectNumeric($match->GetId());
			$this->LoggedQuery($sql);
		}

	    # This season is mentioned in search results for a match, so request an update, 
	    # and note for auditing that the match has been changed
	    $sql = "UPDATE nsa_match SET 
	    update_search = 1, 
	    date_changed = " . gmdate("U") . ", 
        modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . "
	    WHERE match_id = " . Sql::ProtectNumeric($match->GetId(), false);
        $this->LoggedQuery($sql);
  	
		# Match data has changed so notify moderator
		$this->QueueForNotification($match->GetId(), $b_is_new_match);
	}

	/**
	 * Note that a match has been added/updated and its moderator should be notified
	 * @param int $match_id
	 * @param bool $b_is_new_match
	 * @return void
	 */
	private function QueueForNotification($match_id, $b_is_new_match)
	{
		# Don't send email if match added by a trusted administrator
		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES)) return;

		require_once('data/queue-action.enum.php');
		$queue_action = $b_is_new_match ? QueueAction::MATCH_ADDED : QueueAction::MATCH_UPDATED;
		$queue = $this->GetSettings()->GetTable('Queue');
		$sql = "INSERT INTO $queue (data, action, user_id, date_added) VALUES ($match_id, $queue_action, " . AuthenticationManager::GetUser()->GetId() . ", " . gmdate('U') . ')';
		$this->GetDataConnection()->query($sql);
	}

	/**
	 * Notifies the moderator(s) for a match that it has been added or updated. If no arguments, send all outstanding notifications.
	 * @param int $match_id
	 * @return void
	 */
	public function NotifyMatchModerator($match_id=null)
	{
		# Check in the queue whether to actually send the email, because even if "Save" is clicked on a match it may not have
		# been updated if the data didn't change. If the data changed there should be an entry in the queue.
		require_once('data/queue-action.enum.php');
		$queue = $this->GetSettings()->GetTable('Queue');
		$user = $this->GetSettings()->GetTable('User');
		$this->Lock($queue);
		$sql = "SELECT DISTINCT $queue.data, $queue.action, $user.user_id, $user.known_as, $user.email
		FROM $queue LEFT JOIN $user ON $queue.user_id = $user.user_id
		WHERE action IN (" . QueueAction::MATCH_ADDED . "," . QueueAction::MATCH_UPDATED . ") ";

		$match_ids = array();
		if (is_null($match_id))
		{
			# Get any notifications from over an hour ago. That should only be ones where the user clicked away from an add/update wizard before the last page.
			# Want to avoid logging an update on p1 of a wizard and sending an email before they finish p4 and submit.
			$sql .= "AND $queue.date_added < " . (gmdate('U') - (60*60));
		}
		else
		{
			# Check in the queue for the specific match
			$sql .= "AND $queue.data " . Sql::ProtectNumeric($match_id, false, true);
		}
		$result = $this->GetDataConnection()->query($sql);
		while ($row = $result->fetch())
		{
			$user_who_updated_match = new User($row->user_id, $row->known_as);
			$user_who_updated_match->SetEmail($row->email);

			$match_ids[(int)$row->data] = array($user_who_updated_match, (((int)$row->action) == QueueAction::MATCH_ADDED));
		}
		$result->closeCursor();
		unset($result);

		if (count($match_ids))
		{
			# Now that we know what notification to send, remove those match(es) from the queue
			$sql = "DELETE FROM $queue WHERE action IN (" . QueueAction::MATCH_ADDED . "," . QueueAction::MATCH_UPDATED . ") AND data IN (" . join(', ', array_keys($match_ids)) . ") ";
			$this->GetDataConnection()->query($sql);
		}
		$this->Unlock();

		# Get info on the matches needed to send the email
		if (count($match_ids))
		{
			$s_match = $this->GetSettings()->GetTable('Match');
			$s_season = $this->GetSettings()->GetTable('Season');
			$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');
			$s_comp = $this->GetSettings()->GetTable('Competition');
			$s_ground = $this->GetSettings()->GetTable('Ground');
			$s_mt = $this->GetSettings()->GetTable('MatchTeam');
			
			$sql = "SELECT $s_match.match_id, $s_match.match_title, $s_match.start_time, $s_match.start_time_known, $s_match.match_type, $s_match.short_url,
			$s_match.home_bat_first, $s_match.match_notes, $s_match.home_runs, $s_match.home_wickets, $s_match.away_runs, $s_match.away_wickets,
			$s_season.season_id, $s_season.season_name, $s_season.start_year, $s_season.end_year, " .
			$s_comp . '.competition_id, ' . $s_comp . '.competition_name, ' . $s_comp . '.notification_email, ' .
			"$s_ground.ground_id, $s_ground.saon, $s_ground.paon, $s_ground.town,
			home_team.team_id AS home_team_id, home_team.team_name AS home_team_name,
			away_team.team_id AS away_team_id, away_team.team_name AS away_team_name
			FROM ((((((($s_match LEFT OUTER JOIN $s_season_match ON $s_match.match_id = $s_season_match.match_id) " .
			'LEFT OUTER JOIN ' . $s_season . ' ON ' . $s_season_match . '.season_id = ' . $s_season . '.season_id) ' .
			'LEFT OUTER JOIN ' . $s_comp . ' ON ' . $s_season . '.competition_id = ' . $s_comp . '.competition_id) ' .
			'LEFT OUTER JOIN ' . $s_ground . ' ON ' . $s_match . '.ground_id = ' . $s_ground . '.ground_id) ' .
			'LEFT OUTER JOIN ' . $s_mt . ' AS home_link ON ' . $s_match . '.match_id = home_link.match_id AND home_link.team_role = ' . TeamRole::Home() . ') ' .
			'LEFT OUTER JOIN nsa_team AS home_team ON home_link.team_id = home_team.team_id AND home_link.team_role = ' . TeamRole::Home() . ")
			LEFT OUTER JOIN $s_mt AS away_link ON $s_match.match_id = away_link.match_id AND away_link.team_role = " . TeamRole::Away() . ') ' .
			'LEFT OUTER JOIN nsa_team AS away_team ON away_link.team_id = away_team.team_id AND away_link.team_role = ' . TeamRole::Away() . "
			WHERE $s_match.match_id IN (" . join(', ', array_keys($match_ids)) . ") ";

			# run query
			$result = $this->GetDataConnection()->query($sql);
			$this->BuildItems($result);
			$result->closeCursor();
			unset($result);

			# send email
			require_once('data-change-notifier.class.php');
			$o_notify = new DataChangeNotifier($this->GetSettings());
			foreach ($this->GetItems() as $match)
			{
				$o_notify->MatchUpdated($match, $match_ids[$match->GetId()][0], $match_ids[$match->GetId()][1]);
			}
		}
	}

	/**
	 * @return void
	 * @param Match $o_match
	 * @desc Save the result and a summary of the scores of the supplied Match to the database
	 */
	public function SaveResult(Match $o_match)
	{
		# To add a result there must always already be a match to update
		if (!$o_match->GetId()) return;

		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$i_result = ($o_match->Result()->GetResultType() <= 0) ? null : $o_match->Result()->GetResultType();
		$i_home_runs = ($o_match->Result()->GetHomeRuns() == -1) ? null : $o_match->Result()->GetHomeRuns();
		$i_away_runs = ($o_match->Result()->GetAwayRuns() == -1) ? null : $o_match->Result()->GetAwayRuns();

		# Check whether anything's changed and don't re-save if not
		$s_sql = 'SELECT match_id FROM ' . $s_match . ' ';
		$s_where = $this->SqlAddCondition('', 'home_bat_first' . Sql::ProtectBool($o_match->Result()->GetHomeBattedFirst(), true, true));
		$s_where = $this->SqlAddCondition($s_where, 'home_runs' . Sql::ProtectNumeric($i_home_runs, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'home_wickets' . Sql::ProtectNumeric($o_match->Result()->GetHomeWickets(), true, true));
		$s_where = $this->SqlAddCondition($s_where, 'away_runs' . Sql::ProtectNumeric($i_away_runs, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'away_wickets' . Sql::ProtectNumeric($o_match->Result()->GetAwayWickets(), true, true));
		$s_where = $this->SqlAddCondition($s_where, 'match_result_id' . Sql::ProtectNumeric($i_result, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'match_id = ' . Sql::ProtectNumeric($o_match->GetId()));
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		$o_result = $this->GetDataConnection()->query($s_sql);

		if ($o_result->fetch())
		{
			return;
		}

		# Should the match_title be regenerated?
		$s_sql = "SELECT custom_title FROM $s_match WHERE $s_match.match_id = " . Sql::ProtectNumeric($o_match->GetId());
		$o_result = $this->GetDataConnection()->query($s_sql);
		$o_row = $o_result->fetch();
		if (!is_null($o_row))
		{
			$o_match->SetUseCustomTitle($o_row->custom_title);
		}

		# Update the main match record
        # All changes to master data are logged from here, because this method can be called from the public interface
        $sql = 'UPDATE ' . $this->o_settings->GetTable('Match') . ' SET ';
		if (!$o_match->GetUseCustomTitle()) {
		    $sql .= "match_title = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetTitle()) . ", ";
        }
		$sql .= 'home_runs = ' . Sql::ProtectNumeric($i_home_runs, true) . ', ' .
			'home_wickets = ' . Sql::ProtectNumeric($o_match->Result()->GetHomeWickets(), true) . ', ' .
			'away_runs = ' . Sql::ProtectNumeric($i_away_runs, true) . ', ' .
			'away_wickets = ' . Sql::ProtectNumeric($o_match->Result()->GetAwayWickets(), true) . ', ' .
			'match_result_id = ' . Sql::ProtectNumeric($i_result, true) . ', 
            update_search = 1,  
			date_changed = ' . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ' ' . 
			'WHERE match_id = ' . Sql::ProtectNumeric($o_match->GetId());

		$this->LoggedQuery($sql);
        
        $this->SaveWhoBattedFirst($o_match);
        
        # Copy updated data to statistics
        require_once('stoolball/statistics/statistics-manager.class.php');
        $statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
        $statistics_manager->UpdateMatchDataInStatistics($this, $o_match->GetId());
        unset($statistics_manager);

		# Match data has changed so notify moderator
		$this->QueueForNotification($o_match->GetId(), false);
	}

    /**
     * @return void
     * @param Match $match
     * @desc Saves who won the toss in the supplied Match to the database
     */
    public function SaveWhoWonTheToss(Match $match)
    {
        # To add a result there must always already be a match to update
        if (!$match->GetId()) return;

        # build query
        $match_id = Sql::ProtectNumeric($match->GetId());
        
        # Check whether anything's changed and don't re-save if not
        $sql = 'SELECT match_id FROM nsa_match ';
        $where = $this->SqlAddCondition('', 'won_toss' . Sql::ProtectNumeric($match->Result()->GetTossWonBy(), true, true));
        $where = $this->SqlAddCondition($where, 'match_id = ' . $match_id);
        $sql = $this->SqlAddWhereClause($sql, $where);

        $result = $this->GetDataConnection()->query($sql);

        if ($result->fetch())
        {
            return;
        }

        # All changes to master data from here are logged, because this method can be called from the public interface
        
        # Update the main match record
        $sql = 'UPDATE nsa_match SET ' .
            'won_toss = ' . Sql::ProtectNumeric($match->Result()->GetTossWonBy(), true);
            'date_changed = ' . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ' ' . 
            'WHERE match_id = ' . $match_id;

        $this->LoggedQuery($sql);
        
        # Match data has changed so notify moderator
        $this->QueueForNotification($match->GetId(), false);
    }

	/**
	 * @return void
	 * @param Match $match
	 * @desc Saves who batted first in the supplied Match to the database
	 */
	public function SaveWhoBattedFirst(Match $match)
	{
		# To add a result there must always already be a match to update
		if (!$match->GetId()) return;

		# build query
        $match_id = Sql::ProtectNumeric($match->GetId());
		
		# Check whether anything's changed and don't re-save if not
		$s_sql = 'SELECT match_id FROM nsa_match ';
		$s_where = $this->SqlAddCondition('', 'home_bat_first' . Sql::ProtectBool($match->Result()->GetHomeBattedFirst(), true, true));
		$s_where = $this->SqlAddCondition($s_where, 'match_id = ' . $match_id);
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		$o_result = $this->GetDataConnection()->query($s_sql);

		if ($o_result->fetch())
		{
			return;
		}

		# All changes to master data from here are logged, because this method can be called from the public interface
		
		# Update the main match record
		$sql = 'UPDATE nsa_match SET 
            home_bat_first = ' . Sql::ProtectBool($match->Result()->GetHomeBattedFirst(), true) . ', 
            date_changed = ' . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . '  
			WHERE match_id = ' . $match_id;

		$this->LoggedQuery($sql);
        
        # Copy updated value to statistics
        if (is_null($match->Result()->GetHomeBattedFirst())) 
        {
            $sql = "UPDATE nsa_player_match SET batting_first = NULL WHERE match_id = " . $match_id;
            $this->GetDataConnection()->query($sql);
        } 
        else if ($match->Result()->GetHomeBattedFirst() === true) 
        {
            if ($match->GetHomeTeamId()) {
                $sql = "UPDATE nsa_player_match SET batting_first = 1 WHERE match_id = " . $match_id  . " AND team_id = " . $match->GetHomeTeamId();
                $this->GetDataConnection()->query($sql);
            }
            if ($match->GetAwayTeamId()) {
                $sql = "UPDATE nsa_player_match SET batting_first = 0 WHERE match_id = " . $match_id  . " AND team_id = " . $match->GetAwayTeamId();
                $this->GetDataConnection()->query($sql);
            }
        } 
        else if ($match->Result()->GetHomeBattedFirst() === false) 
        {
            if ($match->GetHomeTeamId()) {
                $sql = "UPDATE nsa_player_match SET batting_first = 0 WHERE match_id = " . $match_id  . " AND team_id = " . $match->GetHomeTeamId();
                $this->GetDataConnection()->query($sql);
            }
            if ($match->GetAwayTeamId()) {
                $sql = "UPDATE nsa_player_match SET batting_first = 1 WHERE match_id = " . $match_id  . " AND team_id = " . $match->GetAwayTeamId();
                $this->GetDataConnection()->query($sql);
            }
        }
        

		# Match data has changed so notify moderator
		$this->QueueForNotification($match->GetId(), false);
	}

	/**
	 * @return void
	 * @param Match $o_match
	 * @desc Saves whether the match was played
	 */
	public function SaveIfPlayed(Match $o_match)
	{
		# To add a result there must always already be a match to update
		if (!$o_match->GetId()) return;

		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$i_result = ($o_match->Result()->GetResultType() <= 0) ? null : $o_match->Result()->GetResultType();

		# Check whether anything's changed and don't re-save if not
		$s_sql = 'SELECT match_id FROM ' . $s_match . ' ';
		$s_where = $this->SqlAddCondition("", 'match_result_id' . Sql::ProtectNumeric($i_result, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'match_id = ' . Sql::ProtectNumeric($o_match->GetId()));
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		$o_result = $this->GetDataConnection()->query($s_sql);

		if ($o_result->fetch())
		{
			$o_result->closeCursor();
			return;
		}

		# If user said match went ahead, we want to wipe out any kind of cancellation, but leave in place
		# any kind of info about the actual result
		if ($o_match->Result()->GetResultType() == MatchResult::UNKNOWN)
		{
			$s_sql = 'SELECT match_result_id FROM ' . $s_match . ' WHERE match_id = ' . Sql::ProtectNumeric($o_match->GetId());
			$o_result = $this->GetDataConnection()->query($s_sql);
			$row = $o_result->fetch();
			$match_result = (int)$row->match_result_id;
			$o_result->closeCursor();

			if ($match_result == MatchResult::HOME_WIN or
			$match_result == MatchResult::AWAY_WIN or
			$match_result == MatchResult::TIE)
			{
				return;
			}
		}

		# Should the match_title be regenerated?
		$s_sql = "SELECT custom_title FROM $s_match WHERE $s_match.match_id = " . Sql::ProtectNumeric($o_match->GetId());
		$o_result = $this->GetDataConnection()->query($s_sql);
		$o_row = $o_result->fetch();
		if (!is_null($o_row))
		{
			$o_match->SetUseCustomTitle($o_row->custom_title);
		}

		# Update the main match record
        # All changes to master data from here are logged, because this method can be called from the public interface
        $sql = 'UPDATE ' . $s_match . ' SET ';
		if (!$o_match->GetUseCustomTitle()) {
            $sql .= "match_title = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetTitle()) . ", ";
        }
		$sql .= 'match_result_id = ' . Sql::ProtectNumeric($i_result, true) . ', 
			update_search = 1,  
            date_changed = ' . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ' ' . 
			'WHERE match_id = ' . Sql::ProtectNumeric($o_match->GetId());

		$this->LoggedQuery($sql);
		
        # Copy updated data to statistics
        require_once('stoolball/statistics/statistics-manager.class.php');
        $statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
        $statistics_manager->UpdateMatchDataInStatistics($this, $o_match->GetId());
        unset($statistics_manager);
  
		# Match data has changed so notify moderator
		$this->QueueForNotification($o_match->GetId(), false);
	}


	/**
	 * @return void
	 * @param Match $match
	 * @param bool $is_first_innings
	 * @desc Saves the batting and bowling scorecards for one innings
	 */
	public function SaveScorecard(Match $match, $is_first_innings, ISearchIndexProvider $search)
	{
		# To add a scorecard there must always already be a match to update
		if (!$match->GetId()) return;

		# This isn't for tournaments
		if ($match->GetMatchType() == MatchType::TOURNAMENT) return;

		# Get tables
		$batting_table = $this->GetSettings()->GetTable("Batting");
		$bowling_table = $this->GetSettings()->GetTable("Bowling");
		$match_table = $this->GetSettings()->GetTable('Match');
		$mt = $this->GetSettings()->GetTable('MatchTeam');

		# Is this scorecard for the home or the away innings?
		$sql = "SELECT home_bat_first FROM $match_table WHERE match_id = " . Sql::ProtectNumeric($match->GetId());
		$result = $this->GetDataConnection()->query($sql);
		$row = $result->fetch();

		if (is_null($row->home_bat_first) or ((bool)$row->home_bat_first) === true)
		{
			$is_home_innings = $is_first_innings;
		}
		else
		{
			$is_home_innings = !$is_first_innings;
		}
		$result->closeCursor();


		# Prepare data for query
		if ($is_home_innings)
		{
			$bowling_team_id = $match->GetAwayTeamId();
			$batting_team_id = $match->GetHomeTeamId();
			$team_bowling = $match->Result()->AwayOvers();
			$team_batting = $match->Result()->HomeBatting();
		}
		else
		{
			$bowling_team_id = $match->GetHomeTeamId();
			$batting_team_id = $match->GetAwayTeamId();
			$team_bowling = $match->Result()->HomeOvers();
			$team_batting = $match->Result()->AwayBatting();
		}

		# Find the match_team_id for the bowling
		$sql = "SELECT match_team_id FROM $mt
				WHERE match_id " . Sql::ProtectNumeric($match->GetId(), false, true) . "
				AND team_id " . Sql::ProtectNumeric($bowling_team_id, false, true) . "
				AND team_role = " . ($is_home_innings ? TeamRole::Away() : TeamRole::Home());
		$result = $this->GetDataConnection()->query($sql);

		$row = $result->fetch();
		$bowling_match_team_id = $row->match_team_id;
		$result->closeCursor();

		# Find the match_team_id for the batting
		$sql = "SELECT match_team_id FROM $mt
				WHERE match_id " . Sql::ProtectNumeric($match->GetId(), false, true) . "
				AND team_id " . Sql::ProtectNumeric($batting_team_id, false, true) . "
				AND team_role = " . ($is_home_innings ? TeamRole::Home() : TeamRole::Away());
		$result = $this->GetDataConnection()->query($sql);

		$row = $result->fetch();
		$batting_match_team_id = $row->match_team_id;
		$result->closeCursor();

		$affected_players = $this->SaveBattingScorecard($match, $is_home_innings,$batting_match_team_id,$team_batting);
		$affected_bowlers = $this->SaveBowlingScorecard($match, $is_home_innings, $batting_match_team_id, $bowling_match_team_id, $team_bowling);
		$affected_players = array_merge($affected_players, $affected_bowlers);
		$affected_players = array_unique($affected_players);

		if (count($affected_players))
		{
			require_once('stoolball/statistics/statistics-manager.class.php');
			$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());

			# generate player statistics from the data entered
			$statistics_manager->UpdateBattingStatistics($affected_players, array($batting_match_team_id));
			$statistics_manager->UpdateFieldingStatistics($affected_players, array($bowling_match_team_id));
			$statistics_manager->UpdateBowlingStatistics($affected_players, array($bowling_match_team_id));
			$statistics_manager->DeleteObsoleteStatistics($match->GetId());

			# update overall stats for players
			$statistics_manager->UpdatePlayerStatistics($affected_players);
			unset($statistics_manager);


            # update search engine
            require_once "stoolball/player-manager.class.php";
            require_once "search/player-search-adapter.class.php";
            $player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
            foreach ($affected_players as $player_id) 
            {
                $player_manager->ReadPlayerById($player_id);
                $player = $player_manager->GetFirst();
                $search->DeleteFromIndexById("player" . $player_id);
                if ($player instanceof Player) {
                    $adapter = new PlayerSearchAdapter($player);
                    $search->Index($adapter->GetSearchableItem());
                }
            }
            $search->CommitChanges();
            unset($player_manager);
		}
	}


	/**
	 * @return array of IDs of players featured in records added, updated or deleted
	 * @param Match $match
	 * @param bool $is_home_innings
	 * @param int $batting_match_team_id
	 * @param Batting[] $team_batting
	 * @desc Saves the batting scorecard for one innings
	 */
	private function SaveBattingScorecard(Match $match, $is_home_innings, $batting_match_team_id, $team_batting)
	{
		require_once "stoolball/player-manager.class.php";
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());

		# Get tables
		$batting_table = $this->GetSettings()->GetTable("Batting");
		$match_table = $this->GetSettings()->GetTable('Match');

		# Check whether anything's changed and don't re-save if not
		$unchanged_batting = array();
		$new_batting = array();
		$batting_added = false;
		$batting_deleted = false;
		$totals_changed = false;
		$players_affected = array();

		# check if batting changed for this innings
		$position = 0;
		foreach ($team_batting as $batting)
		{
			$position++;

			/* @var $batting Batting */
			$sql = "SELECT batting_id FROM $batting_table WHERE
					match_team_id = $batting_match_team_id
					AND player_id = " . Sql::ProtectNumeric($player_manager->SaveOrMatchPlayer($batting->GetPlayer())) . "
					AND position = $position
					AND how_out " . Sql::ProtectNumeric($batting->GetHowOut(), false, true) . "
					AND dismissed_by_id " . Sql::ProtectNumeric($player_manager->SaveOrMatchPlayer($batting->GetDismissedBy()), true, true). "
					AND bowler_id " . Sql::ProtectNumeric($player_manager->SaveOrMatchPlayer($batting->GetBowler()),true, true) . "
					AND runs " . Sql::ProtectNumeric($batting->GetRuns(), true, true) . "
                    AND balls_faced " . Sql::ProtectNumeric($batting->GetBallsFaced(), true, true);
			$result = $this->GetDataConnection()->query($sql);

			if ($row = $result->fetch())
			{
                $unchanged_batting[] = $row->batting_id;
			}
			else
			{
				# Save IDs of players added or updated
				$player_id = $player_manager->SaveOrMatchPlayer($batting->GetPlayer());
				$dismissed_by_id = $player_manager->SaveOrMatchPlayer($batting->GetDismissedBy());
				$bowler_id = $player_manager->SaveOrMatchPlayer($batting->GetBowler());
				$players_affected[] = $player_id;
				if (!is_null($dismissed_by_id)) $players_affected[] = $dismissed_by_id;
				if (!is_null($bowler_id)) $players_affected[] = $bowler_id;

				# Prepare insert query
				$player_id = Sql::ProtectNumeric($player_id);
				$dismissed_by_id = Sql::ProtectNumeric($dismissed_by_id, true);
				$bowler_id = Sql::ProtectNumeric($bowler_id, true);

				$new_batting[] = "INSERT INTO $batting_table SET
					match_team_id = $batting_match_team_id,
					player_id = " . $player_id . ",
					position = $position,
					how_out = " . Sql::ProtectNumeric($batting->GetHowOut()) . ",
					dismissed_by_id = $dismissed_by_id,
					bowler_id = $bowler_id,
					runs = " . Sql::ProtectNumeric($batting->GetRuns(), true) . ",
                    balls_faced = " . Sql::ProtectNumeric($batting->GetBallsFaced(), true) . ",
					date_added = " . gmdate('U');
				$batting_added = true;
			}
		}

		# See whether any batting records have been changed or removed
		$sql = "SELECT batting_id, player_id, dismissed_by_id, bowler_id FROM $batting_table WHERE match_team_id = $batting_match_team_id"; # match_team_id is autonumber from db so can be trusted
		if (count ($unchanged_batting)) $sql .= " AND batting_id NOT IN(". implode(',', $unchanged_batting) . ")";
		$result = $this->GetDataConnection()->query($sql);

		while ($row = $result->fetch())
		{
			# Save IDs of players whose records will be updated or deleted
			$players_affected[] = $row->player_id;
			if (!is_null($row->dismissed_by_id)) $players_affected[] = $row->dismissed_by_id;
			if (!is_null($row->bowler_id)) $players_affected[] = $row->bowler_id;

			$batting_deleted = true;
		}

		# Final step for the batting is to update, if necessary, the total runs/wickets in the main match record
		if ($is_home_innings)
		{
			$runs_and_wickets = "home_runs = " . Sql::ProtectNumeric($match->Result()->GetHomeRuns(), true, false) . "
					AND home_wickets = " . Sql::ProtectNumeric($match->Result()->GetHomeWickets(), true, false);
		}
		else
		{
			$runs_and_wickets = "away_runs = " . Sql::ProtectNumeric($match->Result()->GetAwayRuns(), true, false) . "
					AND away_wickets = " . Sql::ProtectNumeric($match->Result()->GetAwayWickets(), true, false);
		}

		$sql = "SELECT match_id FROM $match_table WHERE $runs_and_wickets AND match_id " . Sql::ProtectNumeric($match->GetId(), false, true);
		$result = $this->GetDataConnection()->query($sql);
		$totals_changed	= ($result->fetch() === false);

		
		# All changes to master data from here are logged, because this method can be called from the public interface

		# Delete batting for this innings if changed or removed
		if ($batting_deleted)
		{
			$sql = "DELETE FROM $batting_table WHERE match_team_id = $batting_match_team_id"; # match_team_id is autonumber from db so can be trusted
			if (count ($unchanged_batting)) $sql .= " AND batting_id NOT IN(". implode(',', $unchanged_batting) . ")";
			$this->LoggedQuery($sql);
		}

		# Insert batting for this innings if changed or added
		if ($batting_added)
		{
			foreach ($new_batting as $sql)
			{
				$this->LoggedQuery($sql);
			}
		}

		# If run or wicket totals changed update that at the same time as recording match audit data
		$runs_and_wickets = "";
		if ($totals_changed)
		{
			if ($is_home_innings)
			{
				$runs_and_wickets = "home_runs = " . Sql::ProtectNumeric($match->Result()->GetHomeRuns(), true, false) . ",
					home_wickets = " . Sql::ProtectNumeric($match->Result()->GetHomeWickets(), true, false) . ", ";
			}
			else
			{
				$runs_and_wickets = "away_runs = " . Sql::ProtectNumeric($match->Result()->GetAwayRuns(), true, false) . ",
					away_wickets = " . Sql::ProtectNumeric($match->Result()->GetAwayWickets(), true, false) . ", ";
			}
		}

		# if match data has changed record that for audit, and notify moderator
        # We can also update the number of players per team so that the same number of boxes are shown next time. 
		if ($batting_added or $batting_deleted or $totals_changed)
		{
            $players_per_team = $match->GetMaximumPlayersPerTeam();
        
            $sql = "UPDATE $match_table SET $runs_and_wickets
                    players_per_team = $players_per_team, 
                    date_changed = " . gmdate('U') . ", 
                    modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ' ' . 
                    "WHERE match_id " . Sql::ProtectNumeric($match->GetId(), false, true);
            $this->LoggedQuery($sql);

			$this->QueueForNotification($match->GetId(), false);
		}

		return $players_affected;
	}


	/**
	 * @return array of IDs of players featured in records added, updated or deleted
	 * @param Match $match
	 * @param bool $is_home_innings
	 * @param int $batting_match_team_id
	 * @param int $bowling_match_team_id
	 * @param Overs[] $team_bowling
	 * @desc Saves the bowling scorecard for one innings
	 */
	private function SaveBowlingScorecard(Match $match, $is_home_innings, $batting_match_team_id, $bowling_match_team_id, $team_bowling)
	{
		require_once "stoolball/player-manager.class.php";
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());

		# Get tables
		$bowling_table = $this->GetSettings()->GetTable("Bowling");

		$unchanged_overs = array();
		$new_overs = array();
		$bowling_deleted = false;
		$affected_players = array();

		# check if bowling updated for this innings
		$position = 0;
		foreach ($team_bowling as $bowling)
		{
			$position++;

			/* @var $bowling Over */
			$sql = "SELECT bowling_id FROM $bowling_table WHERE
					match_team_id = $bowling_match_team_id
					AND player_id = " . Sql::ProtectNumeric($player_manager->SaveOrMatchPlayer($bowling->GetPlayer())) . "
					AND position = $position
					AND balls_bowled " . Sql::ProtectNumeric($bowling->GetBalls(), true, true) . "
					AND no_balls " . Sql::ProtectNumeric($bowling->GetNoBalls(), true, true) . "
					AND wides " . Sql::ProtectNumeric($bowling->GetWides(), true, true) . "
					AND runs_in_over " . Sql::ProtectNumeric($bowling->GetRunsInOver(), true, true);

			$result = $this->GetDataConnection()->query($sql);

			if ($row = $result->fetch())
			{
                $unchanged_overs[] = $row->bowling_id;
			}
			else
			{
				# Save id of bowler added or updated
				$bowler_id = $player_manager->SaveOrMatchPlayer($bowling->GetPlayer());
				$affected_players[] = $bowler_id;

				$sql = "INSERT INTO $bowling_table SET
					match_team_id = $bowling_match_team_id,
					player_id = " . Sql::ProtectNumeric($bowler_id) . ",
					position = $position,
					balls_bowled = " . Sql::ProtectNumeric($bowling->GetBalls(), true) . ",
					no_balls = " . Sql::ProtectNumeric($bowling->GetNoBalls(), true) . ",
					wides = " . Sql::ProtectNumeric($bowling->GetWides(), true) . ",
					runs_in_over = " . Sql::ProtectNumeric($bowling->GetRunsInOver(), true) . ",
					date_added = " . gmdate('U');
				$new_overs[] = $sql;
			}
		}

		# see if there are existing bowling records for this innings which we need to delete
		$sql = "SELECT bowling_id, player_id FROM $bowling_table WHERE match_team_id = $bowling_match_team_id"; # match_team_id is autonumber from db so can be trusted
		if (count($unchanged_overs))
		{
			$sql .= " AND bowling_id NOT IN (" . implode (",", $unchanged_overs) . ")";
		}
		$result  = $this->GetDataConnection()->query($sql);

        # Save ID of bowler updated or deleted
		while ($row = $result->fetch())
		{
			$affected_players[] = $row->player_id;
            $bowling_deleted = true;
		}

		# If anything has changed...
		if ($bowling_deleted or count($new_overs))
		{
			# All changes to master data are logged, because this method can be called from the public interface
			
			# Delete existing bowling for this innings
			if ($bowling_deleted)
			{
				$sql = "DELETE FROM $bowling_table WHERE match_team_id = $bowling_match_team_id"; # match_team_id is autonumber from db so can be trusted
				if (count($unchanged_overs))
				{
					$sql .= " AND bowling_id NOT IN (" . implode (",", $unchanged_overs) . ")";
				}
				$this->LoggedQuery($sql);
			}

			# Insert bowling for this innings if changed or added
			foreach ($new_overs as $sql)
			{
				$this->LoggedQuery($sql);
			}

			# Match data has changed so record for audit, and notify moderator.
			# We can also update the number of overs so that the same number of boxes are shown next time. 
            $overs_bowled = $match->GetOvers();
			$sql = "UPDATE nsa_match SET
			        overs = $overs_bowled, 
                    date_changed = " . gmdate('U') . ", 
                    modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ' ' . 
                    "WHERE match_id " . Sql::ProtectNumeric($match->GetId(), false, true);
            $this->LoggedQuery($sql);
			
			$this->QueueForNotification($match->GetId(), false);
		}

		return $affected_players;
	}

	/**
	 * @return void
	 * @param Match $o_match
	 * @desc Save the result and player(s) of the match to the database
	 */
	public function SaveHighlights(Match $o_match)
	{
		# To add a result there must always already be a match to update
		if (!$o_match->GetId()) return;

		require_once "stoolball/player-manager.class.php";
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());

		# build query
		$s_match = $this->GetSettings()->GetTable('Match');
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');
		$i_result = ($o_match->Result()->GetResultType() <= 0) ? null : $o_match->Result()->GetResultType();
		$player_of_match = $player_manager->SaveOrMatchPlayer($o_match->Result()->GetPlayerOfTheMatch());
		$player_of_match_home = $player_manager->SaveOrMatchPlayer($o_match->Result()->GetPlayerOfTheMatchHome());
		$player_of_match_away = $player_manager->SaveOrMatchPlayer($o_match->Result()->GetPlayerOfTheMatchAway());

		# Check whether anything's changed and don't re-save if not
		$s_sql = 'SELECT match_id FROM ' . $s_match . ' ';
		$s_where = $this->SqlAddCondition("", 'match_result_id' . Sql::ProtectNumeric($i_result, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'player_of_match_id ' . Sql::ProtectNumeric($player_of_match, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'player_of_match_home_id ' . Sql::ProtectNumeric($player_of_match_home, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'player_of_match_away_id ' . Sql::ProtectNumeric($player_of_match_away, true, true));
		$s_where = $this->SqlAddCondition($s_where, 'match_id = ' . Sql::ProtectNumeric($o_match->GetId()));
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		$o_result = $this->GetDataConnection()->query($s_sql);

		if ($o_result->fetch())
		{
			return;
		}

		# Should the match_title be regenerated?
		$s_sql = "SELECT custom_title FROM $s_match WHERE $s_match.match_id = " . Sql::ProtectNumeric($o_match->GetId());
		$o_result = $this->GetDataConnection()->query($s_sql);
		$o_row = $o_result->fetch();
		if (!is_null($o_row))
		{
			$o_match->SetUseCustomTitle($o_row->custom_title);
		}

		# Save IDs of players affected by any change
		$affected_players = array();
		if (!is_null($player_of_match)) $affected_players[] = $player_of_match;
		if (!is_null($player_of_match_home)) $affected_players[] = $player_of_match_home;
		if (!is_null($player_of_match_away)) $affected_players[] = $player_of_match_away;

		$s_sql = "SELECT player_of_match_id, player_of_match_home_id, player_of_match_away_id FROM $s_match WHERE $s_match.match_id = " . Sql::ProtectNumeric($o_match->GetId());
		$o_result = $this->GetDataConnection()->query($s_sql);
		$row = $o_result->fetch();
		if (!is_null($row))
		{
			if (!is_null($row->player_of_match_id)) $affected_players[] = $row->player_of_match_id;
			if (!is_null($row->player_of_match_home_id)) $affected_players[] = $row->player_of_match_home_id;
			if (!is_null($row->player_of_match_away_id)) $affected_players[] = $row->player_of_match_away_id;
		}

		# Update the main match record
        # All changes from here to master data are logged, because this method can be called from the public interface
  		$sql = "UPDATE $s_match SET ";
		if (!$o_match->GetUseCustomTitle()) {
            $sql .= "match_title = " . Sql::ProtectString($this->GetDataConnection(), $o_match->GetTitle()) . ", ";
        }
		$sql .= 'match_result_id = ' . Sql::ProtectNumeric($i_result, true) . ",
			player_of_match_id = " . Sql::ProtectNumeric($player_of_match, true) . ', ' .
			"player_of_match_home_id = " . Sql::ProtectNumeric($player_of_match_home, true) . ', ' .
			"player_of_match_away_id = " . Sql::ProtectNumeric($player_of_match_away, true) . ', 
			update_search = 1,  
            date_changed = ' . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ' ' .
			'WHERE match_id = ' . Sql::ProtectNumeric($o_match->GetId());

		$this->LoggedQuery($sql);
        
        # Copy updated match to statistics
        require_once('stoolball/statistics/statistics-manager.class.php');
        $statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
        $statistics_manager->UpdateMatchDataInStatistics($this, $o_match->GetId());    

		# Save IDs of players affected by any change
		if (count($affected_players))
		{
			$statistics_manager->UpdatePlayerOfTheMatchStatistics($o_match->GetId());
			$statistics_manager->DeleteObsoleteStatistics($o_match->GetId());
			$statistics_manager->UpdatePlayerStatistics($affected_players);
		}
        unset($statistics_manager);


		# Match data has changed so notify moderator
		$this->QueueForNotification($o_match->GetId(), false);
	}

    /**
     * Reset the flag which indicates that search needs to be updated
     * @param $match_id int
     */
    public function SearchUpdated($match_id) 
    {
        if (!is_integer($match_id)) throw new Exception("match_id must be an integer");
        $sql = "UPDATE nsa_match SET update_search = 0 WHERE match_id = " . SQL::ProtectNumeric($match_id, false);
        $this->GetDataConnection()->query($sql);
    }

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the db the Matches matching the supplied ids.
	 */
	public function DeleteMatch($a_ids)
	{
		# check parameter
		if (!is_array($a_ids)) die('No matches to delete');

		# build query
		$delete_sql = array();
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_season_match = $this->GetSettings()->GetTable('SeasonMatch');
		$s_mt = $this->GetSettings()->GetTable('MatchTeam');
		$batting = $this->GetSettings()->GetTable('Batting');
		$bowling = $this->GetSettings()->GetTable('Bowling');
		$stats = $this->GetSettings()->GetTable('PlayerMatch');
		$s_ids = join(', ', $a_ids);

		# delete batting and bowling
		$match_team_ids = array();
		$result = $this->GetDataConnection()->query("SELECT match_team_id FROM $s_mt WHERE match_id IN ($s_ids)");
		while ($row = $result->fetch())
		{
			$match_team_ids[] = $row->match_team_id;
		}
		$result->closeCursor();

		if (count($match_team_ids))
		{
			$match_team_ids = join(",", $match_team_ids);

			$delete_sql[] = "DELETE FROM $batting WHERE match_team_id IN ($match_team_ids)";
			$delete_sql[] = "DELETE FROM $bowling WHERE match_team_id IN ($match_team_ids)";
		}
		$this->GetDataConnection()->query("DELETE FROM $stats WHERE match_id IN ($s_ids)");

		# delete teams
		$delete_sql[] = "DELETE FROM $s_mt WHERE match_id IN ($s_ids)";

		# delete seasons
		$delete_sql[] = "DELETE FROM $s_season_match WHERE match_id IN ($s_ids)";

		# if this is a tournament, delete the matches
		$tournament_match_ids = array();
		$s_sql = 'SELECT match_id FROM ' . $s_match . ' WHERE tournament_match_id IN (' . $s_ids . ') ';
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$tournament_match_ids[] = $row->match_id;
		}
		$result->closeCursor();

		if (count($tournament_match_ids))
		{
			$this->DeleteMatch($tournament_match_ids);
	    }

		# delete comments thread
		$delete_sql[] = "DELETE FROM nsa_forum_message WHERE item_id IN ($s_ids) AND item_type = " . ContentType::STOOLBALL_MATCH;

		# delete match(es)
		$delete_sql[] = "DELETE FROM $s_match WHERE match_id IN ($s_ids);";

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_sql = "SELECT short_url FROM $s_match WHERE match_id IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		# get players involved in the match before it's deleted, so that player statistics can be updated
		require_once 'stoolball/player-manager.class.php';
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$player_ids = $player_manager->ReadPlayersInMatch($a_ids);
		unset($player_manager);

		# Run the collected delete commands
		foreach ($delete_sql as $sql)
		{
			$this->LoggedQuery($sql);
		}

		# update player stats, removing this match and any players who featured only in this match
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		if (count($player_ids))
		{
			$statistics_manager->UpdatePlayerStatistics($player_ids);
		}
		unset($statistics_manager);

		return $this->GetDataConnection()->GetAffectedRows();
	}
}
?>