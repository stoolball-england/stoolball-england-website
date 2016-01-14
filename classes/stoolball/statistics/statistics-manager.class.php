<?php
require_once('data/data-manager.class.php');
require_once('stoolball/player-type.enum.php');
require_once('stoolball/match-result.class.php');
require_once('stoolball/match.class.php');

class StatisticsManager extends DataManager
{
	private $filter_teams = array();
	private $filter_opposition = array();
	private $filter_players = array();
	private $filter_seasons = array();
	private $filter_competitions = array();
	private $filter_grounds = array();
	private $filter_batting_position = array();
    private $filter_tournaments = array();
	private $filter_max_results = false;
	private $filter_before_date = null;
	private $filter_after_date = null;
	private $filter_page_size = null;
	private $filter_page = null;
    private $filter_player_of_match = false;
	private $output_as_csv = null;

	/**
	 * @return StatisticsManager
	 * @param SiteSettings $o_settings
	 * @desc Read and write player statistics
	 */
	public function __construct($o_settings, $o_db)
	{
		parent::DataManager($o_settings, $o_db);
	}

	/**
	 * Limits supporting queries to returning only a single page of data
	 *
	 * @param int $page_size
	 * @param int $current_page
	 */
	public function FilterByPage($page_size, $current_page)
	{
		$this->filter_page_size = is_null($page_size) ? null : (int)$page_size;
		$this->filter_page = is_null($current_page) ? null : (int)$current_page;
	}

	/**
	 * Limits supporting queries to returning only players in the supplied array of ids
	 *
	 * @param string[] $player_ids
	 */
	public function FilterByPlayer($player_ids)
	{
		if (is_array($player_ids))
		{
			$this->ValidateNumericArray($player_ids);
			$this->filter_players = $player_ids;
		}
		else $this->filter_players = array();
	}

	/**
	 * Limits supporting queries to returning only teams in the supplied array of ids
	 *
	 * @param string[] $team_ids
	 */
	public function FilterByTeam($team_ids)
	{
		if (is_array($team_ids))
		{
			$this->ValidateNumericArray($team_ids);
			$this->filter_teams = $team_ids;
		}
		else $this->filter_teams = array();
	}

	/**
	 * Limits supporting queries to returning only performances against teams in the supplied array of ids
	 *
	 * @param string[] $team_ids
	 */
	public function FilterByOpposition($team_ids)
	{
		if (is_array($team_ids))
		{
			$this->ValidateNumericArray($team_ids);
			$this->filter_opposition = $team_ids;
		}
		else $this->filter_opposition = array();
	}

	/**
	 * Limits supporting queries to returning only matches in the supplied array of season ids
	 *
	 * @param int[] $season_ids
	 */
	public function FilterBySeason($season_ids)
	{
		if (is_array($season_ids))
		{
			$this->ValidateNumericArray($season_ids);
			$this->filter_seasons = $season_ids;
		}
		else $this->filter_seasons = array();
	}

	/**
	 * Limits supporting queries to returning only matches in the supplied array of competition ids
	 *
	 * @param int[] $competition_ids
	 */
	public function FilterByCompetition($competition_ids)
	{
		if (is_array($competition_ids))
		{
			$this->ValidateNumericArray($competition_ids);
			$this->filter_competitions = $competition_ids;
		}
		else $this->filter_competitions = array();
	}

	/**
	 * Limits supporting queries to returning only statistics at the grounds in the supplied array of ids
	 *
	 * @param int[] $ground_ids
	 */
	public function FilterByGround($ground_ids)
	{
		if (is_array($ground_ids))
		{
			$this->ValidateNumericArray($ground_ids);
			$this->filter_grounds = $ground_ids;
		}
		else $this->filter_grounds = array();
	}

	/**
	 * Limits supporting queries to returning only statistics where the player batted in one of the supplied positions
	 *
	 * @param int[] $batting_positions
	 */
	public function FilterByBattingPosition($batting_positions)
	{
		if (is_array($batting_positions))
		{
			$this->ValidateNumericArray($batting_positions);
			$this->filter_batting_position = $batting_positions;
		}
		else $this->filter_batting_position = array();
	}

    /**
     * Limits supporting queries to returning only statistics for the tournaments in the supplied array of ids
     *
     * @param int[] $tournament_ids
     */
    public function FilterByTournament($tournament_ids)
    {
        if (is_array($tournament_ids))
        {
            $this->ValidateNumericArray($tournament_ids);
            $this->filter_tournaments = $tournament_ids;
        }
        else $this->filter_tournaments = array();
    }

    /**
     * Limits supporting queries to consider only statistics where the player was nominated player of the match
     * @param bool $apply_filter
     * @return void
     */
    public function FilterPlayerOfTheMatch($apply_filter)
    {
        $this->filter_player_of_match = (bool)$apply_filter;
    }
    
	/**
	 * Sets the maximum number of results for supporting queries
	 * @param $maximum
	 * @return void
	 */
	public function FilterMaxResults($maximum)
	{
		$this->filter_max_results = ($maximum) ? (int)$maximum : false;
	}

	/**
	 * Sets the date to get matches up to and including
	 * @param int $timestamp
	 * @return void
	 */
	public function FilterBeforeDate($timestamp)
	{
		$this->filter_before_date = is_numeric($timestamp) ? (int)$timestamp : null;
	}

	/**
	 * Sets the date to get matches from and after
	 * @param int $timestamp
	 * @return void
	 */
	public function FilterAfterDate($timestamp)
	{
		$this->filter_after_date = is_numeric($timestamp) ? (int)$timestamp : null;
	}

	/**
	 * Adds standard filters to the WHERE clause
	 * @param string $where
	 * @return string
	 */
	private function ApplyFilters($where)
	{
		$players = $this->GetSettings()->GetTable("Player");
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");
		$sm = $this->GetSettings()->GetTable('SeasonMatch');
		$seasons = $this->GetSettings()->GetTable("Season");

        if ($this->filter_player_of_match) $where .= "AND $statistics.player_of_match = 1 ";
		if (count($this->filter_players)) $where .= "AND $statistics.player_id IN (" . implode(",",$this->filter_players) . ") ";
		if (count($this->filter_seasons)) $where .= "AND $sm.season_id IN (" . implode(",",$this->filter_seasons) . ") ";
		if (count($this->filter_competitions)) $where .= "AND $seasons.competition_id IN (" . implode(",",$this->filter_competitions) . ") ";
		if (count($this->filter_teams)) $where .= "AND $statistics.team_id IN (" . implode(",",$this->filter_teams) . ") ";
		if (count($this->filter_opposition)) $where .= "AND $statistics.opposition_id IN (" . implode(",",$this->filter_opposition) . ") ";
		if (count($this->filter_grounds)) $where .= "AND $statistics.ground_id IN (" . implode(",",$this->filter_grounds) . ") ";
		if (count($this->filter_batting_position)) $where .= "AND $statistics.batting_position IN (" . implode(",",$this->filter_batting_position) . ") ";
        if (count($this->filter_tournaments)) $where .= "AND $statistics.tournament_id IN (" . implode(",",$this->filter_tournaments) . ") ";
		if (!is_null($this->filter_after_date)) $where .= "AND $statistics.match_time >= " . $this->filter_after_date . " ";
		if (!is_null($this->filter_before_date)) $where .= "AND $statistics.match_time <= " . $this->filter_before_date . " ";

		return $where;
	}
	/**
	 * Output query results as a CSV download rather than returning an array
	 * @param string[] $field_labels
	 */
	public function OutputAsCsv($field_labels)
	{
		if (!is_array($field_labels)) throw new Exception("Array of field labels must be supplied for CSV output");
		$this->output_as_csv = $field_labels;
	}

	/**
	 * Read matches with enough data for statistics calculations
	 *
	 */
	public function ReadMatchStatistics()
	{
		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$m = $this->GetSettings()->GetTable('Match');
		
		# Safe to INNER JOIN to teams here because only interested in matches with results, not unknowns
		$sql = "SELECT $m.match_id, $m.start_time, $m.match_result_id, $m.home_runs, $m.away_runs, $m.ground_id, " .
				'home_team.team_id AS home_team_id, home_team.team_name AS home_team_name, home_team.short_url AS home_short_url, ' .
				'away_team.team_id AS away_team_id, away_team.team_name AS away_team_name, away_team.short_url AS away_short_url ' .
				"FROM (((($m INNER JOIN $mt AS home_link ON $m.match_id = home_link.match_id AND home_link.team_role = " . TeamRole::Home() . ') ' .
				"INNER JOIN nsa_team AS home_team ON home_link.team_id = home_team.team_id AND home_link.team_role = " . TeamRole::Home() . ') ' .
				"INNER JOIN $mt AS away_link ON $m.match_id = away_link.match_id AND away_link.team_role = " . TeamRole::Away() . ') ' .
				"INNER JOIN nsa_team AS away_team ON away_link.team_id = away_team.team_id AND away_link.team_role = " . TeamRole::Away() . ') ' .
				"WHERE $m.match_result_id IN (" . join(',', array(MatchResult::HOME_WIN, MatchResult::AWAY_WIN, MatchResult::TIE)) . ") " .
				"AND $m.match_type IN (" . join(',', array(MatchType::CUP, MatchType::FRIENDLY, MatchType::LEAGUE, MatchType::TOURNAMENT_MATCH)) . ") ";

		if (count($this->filter_teams))
		{
			$team_ids = implode(",",$this->filter_teams);
			$sql .= "AND (home_link.team_id IN ($team_ids) OR away_link.team_id IN ($team_ids)) ";
		}
		if (!is_null($this->filter_after_date)) $sql .= "AND $m.start_time >= " . $this->filter_after_date . " ";
		if (!is_null($this->filter_before_date)) $sql .= "AND $m.start_time <= " . $this->filter_before_date . " ";

		$result = $this->GetDataConnection()->query($sql);
		$this->BuildItems($result);
		$result->closeCursor();

	}

	/**
	 * Gets all the teams that can be used to filter player statistics, indexed by team id
	 * @return Team[]
	 */
	public function ReadTeamsForFilter()
	{
		# Just select the teams with players. If the players didn't have statistics, they would have been deleted.
		$player = $this->GetSettings()->GetTable('Player');

		$sql = "SELECT DISTINCT team.team_id, team_name FROM nsa_team AS team INNER JOIN $player ON team.team_id = $player.team_id ORDER BY team_name";
		$data = $this->GetDataConnection()->query($sql);

		$teams = array();
		while ($row = $data->fetch())
		{
			$team = new Team($this->GetSettings());
			$team->SetId($row->team_id);
			$team->SetName($row->team_name);
			$teams[$row->team_id] = $team;
		}

		return $teams;
	}

	/**
	 * Gets all the opposition teams that can be used to filter player statistics, indexed by team id
	 * @return Team[]
	 */
	public function ReadOppositionTeamsForFilter()
	{
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");

		# Apply only the player filter, because the user can't change that one
		$where = "";
		if (count($this->filter_players)) $where .= "AND $statistics.player_id IN (" . implode(",",$this->filter_players) . ") ";
		if ($where) $where = "WHERE " . substr($where, 3, strlen($where)-3);

		$sql = "SELECT DISTINCT team.team_id, team.team_name
				FROM nsa_team AS team INNER JOIN $statistics ON team.team_id = $statistics.opposition_id
				$where
				ORDER BY team.team_name";
		$data = $this->GetDataConnection()->query($sql);

		$teams = array();
		while ($row = $data->fetch())
		{
			$team = new Team($this->GetSettings());
			$team->SetId($row->team_id);
			$team->SetName($row->team_name);
			$teams[$row->team_id] = $team;
		}

		return $teams;
	}

	/**
	 * Gets all the grounds that can be used to filter player statistics, indexed by ground id
	 * @return Ground[]
	 */
	public function ReadGroundsForFilter()
	{
		$ground = $this->GetSettings()->GetTable('Ground');
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');

		# Apply only the player filter, because the user can't change that one
		$where = "";
		if (count($this->filter_players)) $where .= "AND $statistics.player_id IN (" . implode(",",$this->filter_players) . ") ";
		if ($where) $where = "WHERE " . substr($where, 3, strlen($where)-3);

		$sql = "SELECT DISTINCT $ground.ground_id, saon, paon, locality, town
		FROM $ground INNER JOIN $statistics ON $ground.ground_id = $statistics.ground_id
		$where
		ORDER BY sort_name";
		$data = $this->GetDataConnection()->query($sql);

		$grounds = array();
		while ($row = $data->fetch())
		{
			$ground = new Ground($this->GetSettings());
			$ground->SetId($row->ground_id);
			$ground->GetAddress()->SetSaon($row->saon);
			$ground->GetAddress()->SetPaon($row->paon);
			$ground->GetAddress()->SetLocality($row->locality);
			$ground->GetAddress()->SetTown($row->town);
			$grounds[$row->ground_id] = $ground;
		}

		return $grounds;
	}

	/**
	 * Gets all the competitions that can be used to filter player statistics, indexed by competition id
	 * @return Competition[]
	 */
	public function ReadCompetitionsForFilter()
	{
		$competition = $this->GetSettings()->GetTable('Competition');
		$season = $this->GetSettings()->GetTable('Season');
		$season_link = $this->GetSettings()->GetTable('SeasonMatch');
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');

		# Initally applied player filter here to show only competitions the player had been involved in.
		# Query slowed down to unusable levels, so filter has to show all competitions. 

		$sql = "SELECT $competition.competition_id, competition_name
		FROM $competition 
		ORDER BY active DESC, competition_name";
		$data = $this->GetDataConnection()->query($sql);

		$competitions = array();
		while ($row = $data->fetch())
		{
			$competition = new Competition($this->GetSettings());
			$competition->SetId($row->competition_id);
			$competition->SetName($row->competition_name);
			$competitions[$row->competition_id] = $competition;
		}

		return $competitions;
	}

	/**
	 * Read the batting positions for which statistics have been recorded
	 * @return int
	 */
	public function ReadBattingPositionsForFilter()
	{
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');

		$positions = array();

		# Apply only the player filter, because the user can't change that one
		$where = "";
		if (count($this->filter_players)) $where .= "AND $statistics.player_id IN (" . implode(",",$this->filter_players) . ") ";
		if ($where) $where = "WHERE " . substr($where, 3, strlen($where)-3);

		$sql = "SELECT DISTINCT batting_position FROM $statistics $where ORDER BY batting_position";
		$result = $this->GetDataConnection()->query($sql);
		while ($row = $result->fetch())
		{
			$positions[$row->batting_position] = $row->batting_position;
		}
		$result->closeCursor();

		return $positions;
	}

	/**
	 * Read when and how often a player has played
	 */
	public function ReadPlayerSummary()
	{
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");
		$sm = $this->GetSettings()->GetTable('SeasonMatch');
		$seasons = $this->GetSettings()->GetTable("Season");

		$from = "FROM $statistics INNER JOIN nsa_team AS team ON $statistics.team_id = team.team_id ";

		$competition_filter_active = (bool)count($this->filter_competitions);
		if (count($this->filter_seasons) or $competition_filter_active) $from .= "INNER JOIN $sm ON $statistics.match_id = $sm.match_id ";
		if ($competition_filter_active) $from .= "INNER JOIN $seasons ON $sm.season_id = $seasons.season_id ";

		$where = "";
		$where = $this->ApplyFilters($where);
		if ($where) $where = "WHERE " . substr($where, 3, strlen($where)-3);

		$sql = "SELECT player_id, player_name, player_role, player_url,
		team.team_id, team.team_name, team.short_url AS team_short_url,
		COUNT($statistics.match_id) AS total_matches, MIN($statistics.match_time) AS first_played, MAX($statistics.match_time) AS last_played
		$from
		$where
		GROUP BY $statistics.player_id
		ORDER BY player_name ASC";

		$data = array();
		$result = $this->GetDataConnection()->query($sql);
		if (!$this->GetDataConnection()->isError())
		{
			while ($row = $result->fetch())
			{
				$player = new Player($this->GetSettings());
				$player->SetId($row->player_id);
				$player->SetName($row->player_name);
				if (isset($row->total_matches)) $player->SetTotalMatches($row->total_matches);
				if (isset($row->first_played)) $player->SetFirstPlayedDate($row->first_played);
				if (isset($row->last_played)) $player->SetLastPlayedDate($row->last_played);
				if (isset($row->player_role)) $player->SetPlayerRole($row->player_role);
				if (isset($row->short_url)) $player->SetShortUrl($row->player_url);
				$player->Team()->SetId($row->team_id);
				$player->Team()->SetName($row->team_name);
				if (isset($row->team_short_url)) $player->Team()->SetShortUrl($row->team_short_url);
				$data[] = $player;
			}
		}

		$result->closeCursor();
		unset($result);

		return $data;
	}

	/**
	 * Gets best player aggregate data based on current filters
	 * @param string $field
	 * @return An array of aggregate data, or CSV download
	 */
	public function ReadBestPlayerAggregate($field)
	{
		$players = $this->GetSettings()->GetTable("Player");
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");
		$sm = $this->GetSettings()->GetTable('SeasonMatch');
		$seasons = $this->GetSettings()->GetTable("Season");

		$performances = array();

		# Select players with best aggregate data for the field, even it they're tied at the top of the table
		# Although $players table is not necessary here, it's actually FASTER with the join
		$from = "FROM $players INNER JOIN $statistics ON $players.player_id = $statistics.player_id ";

		$competition_filter_active = (bool)count($this->filter_competitions);
		if (count($this->filter_seasons) or $competition_filter_active) $from .= "INNER JOIN $sm ON $statistics.match_id = $sm.match_id ";
		if ($competition_filter_active) $from .= "INNER JOIN $seasons ON $sm.season_id = $seasons.season_id ";

		$where = "WHERE $players.player_role = " . Player::PLAYER . " ";
		$where = $this->ApplyFilters($where);

		$group = "GROUP BY $players.player_id ";
		$order_by = "ORDER BY SUM($field) DESC, COUNT(DISTINCT $statistics.match_id) ASC, $players.player_name ASC ";

		if ($this->filter_max_results)
		{
			# Need to get the value at the last position to show, but first must check there are at least as many
			# records as the total requested, and set a lower limit if not
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS count FROM (SELECT SUM($field) $from $where $group HAVING SUM($field) IS NOT NULL) AS total");
			$row = $result->fetch();
			$limit = (intval($row->count) > 0 and intval($row->count) < $this->filter_max_results) ? intval($row->count)-1 : ($this->filter_max_results-1);
			$having = "HAVING SUM($field) >= (SELECT SUM($field) $from $where $group $order_by LIMIT $limit,1) AND SUM($field) > 0 ";
		}
		else
		{
			$having = "HAVING SUM($field) > 0 ";
		}

		$sql = "SELECT $players.player_id, $players.player_name, $players.short_url, $statistics.team_id, $statistics.team_name,
		COUNT(DISTINCT $statistics.match_id) AS total_matches, SUM($field) AS statistic
		$from
		$where
		$group
		$having
		$order_by";

		if ($this->filter_page_size and $this->filter_page)
		{
			# Get the total number of results on all pages
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS total FROM (SELECT player_match_id $from $where $group $having) AS total");
			$row = $result->fetch();
			$performances[] = $row->total;

			# Limit main query to just the current page
			$sql .= "LIMIT " . ($this->filter_page_size * ($this->filter_page-1)) . ", $this->filter_page_size ";
		}

		$result = $this->GetDataConnection()->query($sql);
		$csv = is_array($this->output_as_csv);
		while ($row = $result->fetch())
		{
			# Return data as an array rather than objects because when we return the full,
			# page to dataset it's far more memory efficient
			$performance["player_id"] = $row->player_id;
			$performance["player_name"] = $row->player_name;
			if (!$csv) $performance["player_url"] = "/" . $row->short_url;
			$performance["team_id"] = $row->team_id;
			$performance["team_name"] = $row->team_name;

			$performance["total_matches"] = $row->total_matches;
			$performance["statistic"] = $row->statistic;

			$performances[] = $performance;
		}

		# Optionally add a header row and download a CSV file rather than returning the data
		if ($csv)
		{
			require_once("data/csv.class.php");
			array_unshift($performances, array("Player id", "Player", "Team id", "Team", "Total matches", $this->output_as_csv[0]));
			CSV::PublishData($performances);
		}

		$result->closeCursor();

		return $performances;
	}

	/**
	 * Gets best player average data based on current filters
	 * @param string $divide_field
	 * @param string $divide_by_field
	 * @param bool $higher_is_better
	 * @param string $qualifier_field
	 * @param int $qualifier_minimum
	 * @return An array of average data, or CSV download
	 */
	public function ReadBestPlayerAverage($divide_field, $divide_by_field, $higher_is_better = true, $qualifier_field = null, $qualifier_minimum = null)
	{
		$players = $this->GetSettings()->GetTable("Player");
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");
		$sm = $this->GetSettings()->GetTable('SeasonMatch');
		$seasons = $this->GetSettings()->GetTable("Season");

		$performances = array();

		# Select players with best average data for the field, even if they're tied at the top of the table
        # Although $players table is not necessary here, it's actually FASTER with the join
		$from = "FROM $players INNER JOIN $statistics ON $players.player_id = $statistics.player_id ";

		$competition_filter_active = (bool)count($this->filter_competitions);
		if (count($this->filter_seasons) or $competition_filter_active) $from .= "INNER JOIN $sm ON $statistics.match_id = $sm.match_id ";
		if ($competition_filter_active) $from .= "INNER JOIN $seasons ON $sm.season_id = $seasons.season_id ";

		$where = "WHERE $players.player_role = " . Player::PLAYER . " ";
		$where = $this->ApplyFilters($where);

		$group = "GROUP BY $players.player_id ";

		$having = "HAVING SUM($divide_field) IS NOT NULL AND SUM($divide_by_field) > 0 ";
		if ($qualifier_field and $qualifier_minimum) $having .= "AND COUNT($qualifier_field) >= $qualifier_minimum ";

		$order_by = "ORDER BY SUM($divide_field)/SUM($divide_by_field) ";
		$order_by .= $higher_is_better ? "DESC" : "ASC";
		$order_by .= ", COUNT(DISTINCT $statistics.match_id) ASC, $players.player_name ASC ";

		if ($this->filter_max_results)
		{
			# Need to get the value at the last position to show, but first must check there are at least as many
			# records as the total requested, and set a lower limit if not
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS count FROM (SELECT SUM($divide_field)/SUM($divide_by_field) $from $where $group) AS total");
			$row = $result->fetch();
			$limit = (intval($row->count) > 0 and intval($row->count) < $this->filter_max_results) ? intval($row->count)-1 : ($this->filter_max_results-1);

			# Use SUM($divide_field) IS NOT NULL in the HAVING clause to ensure we only get players with statistics recorded.
			# Not actually interested in the SUM, that's just so it works in HAVING. Has to be there rather than in WHERE so that
			# all records are considered for the count of total matches played. Test for IS NOT NULL rather than > 0 because an
			# average of 0 is perfectly valid.
			$more_or_less = $higher_is_better ? ">=" : "<=";
			$having .= "AND SUM($divide_field)/SUM($divide_by_field) $more_or_less
						(
						 SELECT SUM($divide_field)/SUM($divide_by_field) $from $where $group $having $order_by LIMIT $limit,1
						) ";
		}

		$sql = "SELECT $players.player_id, $players.player_name, $players.short_url, $statistics.team_id, $statistics.team_name,
		COUNT(DISTINCT $statistics.match_id) AS total_matches, SUM($divide_field)/SUM($divide_by_field) AS statistic
		$from
		$where
		$group
		$having
		$order_by";

		if ($this->filter_page_size and $this->filter_page)
		{
			# Get the total number of results on all pages
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS total FROM (SELECT player_match_id $from $where $group $having) AS total");
			$row = $result->fetch();
			$performances[] = $row->total;

			# Limit main query to just the current page
			$sql .= "LIMIT " . ($this->filter_page_size * ($this->filter_page-1)) . ", $this->filter_page_size ";
		}

		$result = $this->GetDataConnection()->query($sql);
		$csv = is_array($this->output_as_csv);
		while ($row = $result->fetch())
		{
			# Return data as an array rather than objects because when we return the full,
			# page to dataset it's far more memory efficient
			$performance["player_id"] = $row->player_id;
			$performance["player_name"] = $row->player_name;
			if (!$csv) $performance["player_url"] = "/" . $row->short_url;
			$performance["team_id"] = $row->team_id;
			$performance["team_name"] = $row->team_name;

			$performance["total_matches"] = $row->total_matches;
			$performance["statistic"] = round($row->statistic, 2);

			$performances[] = $performance;
		}

		# Optionally add a header row and download a CSV file rather than returning the data
		if ($csv)
		{
			require_once("data/csv.class.php");
			array_unshift($performances, array("Player id", "Player", "Team id", "Team", "Total matches", $this->output_as_csv[0]));
			CSV::PublishData($performances);
		}

		$result->closeCursor();

		return $performances;
	}

	/**
	 * Gets best batting performances based on current filters
	 * @param $exclude_extras bool
	 * @return An array of Batting performances, or CSV download
	 */
	public function ReadBestBattingPerformance($exclude_extras = true)
	{
		$players = $this->GetSettings()->GetTable("Player");
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");
		$sm = $this->GetSettings()->GetTable('SeasonMatch');
		$seasons = $this->GetSettings()->GetTable("Season");

		$performances = array();

		$competition_filter_active = (bool)count($this->filter_competitions);
		$from = "FROM $statistics ";
		if (count($this->filter_seasons) or $competition_filter_active) $from .= "INNER JOIN $sm ON $statistics.match_id = $sm.match_id ";
		if ($competition_filter_active) $from .= "INNER JOIN $seasons ON $sm.season_id = $seasons.season_id ";

		$where = "WHERE runs_scored IS NOT NULL ";
		if ($exclude_extras) $where .= "AND player_role = " . Player::PLAYER . " ";
		$where = $this->ApplyFilters($where);

		if ($this->filter_max_results)
		{
			# Need to get the value at the last position to show, but first must check there are at least as many
			# records as the total requested, and set a lower limit if not
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS count $from $where");
			$row = $result->fetch();
			$limit = (intval($row->count) > 0 and intval($row->count) < $this->filter_max_results) ? intval($row->count)-1 : ($this->filter_max_results-1);
			$max_results = "AND runs_scored >= (SELECT runs_scored $from $where ORDER BY runs_scored DESC LIMIT $limit,1) ";
		}
		else
		{
			$max_results = "";
		}

		$sql = "SELECT player_id, player_name, player_url, team_id, team_name,
		runs_scored, how_out, $statistics.match_id, match_time, opposition_id, opposition_name
		$from 
		$where 
		$max_results
		ORDER BY runs_scored DESC, how_out ASC ";

		if ($this->filter_page_size and $this->filter_page)
		{
			# Get the total number of results on all pages
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS total FROM (SELECT player_match_id $from $where $max_results) AS total");
			$row = $result->fetch();
			$performances[] = $row->total;

			# Limit main query to just the current page
			$sql .= "LIMIT " . ($this->filter_page_size * ($this->filter_page-1)) . ", $this->filter_page_size ";
		}

		$result = $this->GetDataConnection()->query($sql);
		$csv = is_array($this->output_as_csv);
		while ($row = $result->fetch())
		{
			# Return data as an array rather than objects because when we return the full,
			# page to dataset it's far more memory efficient
			$performance["player_id"] = $row->player_id;
			$performance["player_name"] = $row->player_name;
			if (!$csv) $performance["player_url"] = "/" . $row->player_url;
			$performance["team_id"] = $row->team_id;
			$performance["team_name"] = $row->team_name;

			$performance["opposition_id"] = $row->opposition_id;
			$performance["opposition_name"] = $row->opposition_name;

			$performance["match_id"] = $row->match_id;
			$performance["match_time"] = $csv ? Date::Microformat($row->match_time) : $row->match_time;

			$performance["runs_scored"] = $row->runs_scored;
			$performance["how_out"] = $csv ? Batting::Text($row->how_out) : $row->how_out;

			$performances[] = $performance;
		}

		# Optionally add a header row and download a CSV file rather than returning the data
		if ($csv)
		{
			require_once("data/csv.class.php");
			array_unshift($performances, array("Player id", "Player", "Team id", "Team", "Opposition id", "Opposition", "Match id", "Match date (UTC)", "Runs", "How out"));
			CSV::PublishData($performances);
		}

		return $performances;
	}

	/**
	 * Gets best bowling performances based on current filters
	 * @return An array of Bowling performances, or CSV download
	 */
	public function ReadBestBowlingPerformance()
	{
		# NOTE: Don't check for runs_conceded IS NOT NULL in this stat, because 5/NULL is still better than 4/20

		$statistics = $this->GetSettings()->GetTable("PlayerMatch");
        $sm = $this->GetSettings()->GetTable('SeasonMatch');
		$seasons = $this->GetSettings()->GetTable("Season");

		$performances = array();

		$competition_filter_active = (bool)count($this->filter_competitions);
		$from = "FROM $statistics ";
		if (count($this->filter_seasons) or $competition_filter_active) $from .= "INNER JOIN $sm ON $statistics.match_id = $sm.match_id ";
		if ($competition_filter_active) $from .= "INNER JOIN $seasons ON $sm.season_id = $seasons.season_id ";

		// Wickets, even if 0, means the player bowled in the match, so check it is not null.
		// Can trust wickets, not other fields, because wickets figure is generated whether bowling is recorded on batting and/or bowling card.
		$where = "WHERE player_role = " . Player::PLAYER . " AND wickets IS NOT NULL ";
		$where = $this->ApplyFilters($where);

		if ($this->filter_max_results)
		{
			# Need to get the value at the last position to show, but first must check there are at least as many
			# records as the total requested, and set a lower limit if not
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS count $from $where");
			$row = $result->fetch();
			$limit = (intval($row->count) > 0 and intval($row->count) < $this->filter_max_results) ? intval($row->count)-1 : ($this->filter_max_results-1);
			$max_results = "AND wickets >= (SELECT wickets $from $where ORDER BY wickets DESC, has_runs_conceded DESC, runs_conceded ASC LIMIT $limit,1) ";
		}
		else
		{
			$max_results = "";
		}

		$sql = "SELECT player_id, player_name, player_url, team_id, team_name,
		overs, maidens, runs_conceded, wickets, match_id, match_time, opposition_id, opposition_name
		$from 
		$where 
		$max_results
		ORDER BY wickets DESC, has_runs_conceded DESC, runs_conceded ASC ";

		if ($this->filter_page_size and $this->filter_page)
		{
			# Get the total number of results on all pages
			$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS total FROM (SELECT player_match_id $from $where $max_results) AS total");
			$row = $result->fetch();
			$performances[] = $row->total;

			# Limit main query to just the current page
			$sql .= "LIMIT " . ($this->filter_page_size * ($this->filter_page-1)) . ", $this->filter_page_size ";
		}

		$result = $this->GetDataConnection()->query($sql);
		$csv = is_array($this->output_as_csv);
		while ($row = $result->fetch())
		{
			# Return data as an array rather than objects because when we return the full,
			# page to dataset it's far more memory efficient
			$performance["player_id"] = $row->player_id;
			$performance["player_name"] = $row->player_name;
			if (!$csv) $performance["player_url"] = "/" . $row->player_url;
			$performance["team_id"] = $row->team_id;
			$performance["team_name"] = $row->team_name;

			$performance["opposition_id"] = $row->opposition_id;
			$performance["opposition_name"] = $row->opposition_name;

			$performance["match_id"] = $row->match_id;
			$performance["match_time"] = $csv ? Date::Microformat($row->match_time) : $row->match_time;

			$performance["overs"] = $row->overs;
			$performance["maidens"] = $row->maidens;
			$performance["wickets"] = $row->wickets;
			$performance["runs_conceded"] = $row->runs_conceded;

			$performances[] = $performance;
		}

		# Optionally add a header row and download a CSV file rather than returning the data
		if ($csv)
		{
			require_once("data/csv.class.php");
			array_unshift($performances, array("Player id", "Player", "Team id", "Team", "Opposition id", "Opposition", "Match id", "Match date (UTC)", "Wickets", "Runs"));
			CSV::PublishData($performances);
		}

		return $performances;
	}

	
    /**
     * Gets match performances by individual players based on current filters
     * @return An array of player performances, or CSV download
     */
    public function ReadMatchPerformances()
    {
        $statistics = $this->GetSettings()->GetTable("PlayerMatch");
        $sm = $this->GetSettings()->GetTable('SeasonMatch');
        $seasons = $this->GetSettings()->GetTable("Season");

        $performances = array();

        $competition_filter_active = (bool)count($this->filter_competitions);
        $from = "FROM $statistics  
                 INNER JOIN nsa_match ON $statistics.match_id = nsa_match.match_id ";
        if (count($this->filter_seasons) or $competition_filter_active) $from .= "INNER JOIN $sm ON $statistics.match_id = $sm.match_id ";
        if ($competition_filter_active) $from .= "INNER JOIN $seasons ON $sm.season_id = $seasons.season_id ";

        $where = "WHERE player_role = " . Player::PLAYER . " ";
        $where = $this->ApplyFilters($where);

        $sql = "SELECT player_id, player_name, player_url, 
        runs_scored, how_out, runs_conceded, wickets, catches, run_outs, $statistics.match_id, 
        nsa_match.match_title, match_time, nsa_match.short_url AS match_short_url
        $from 
        $where
        ORDER BY match_time DESC ";

        if ($this->filter_page_size and $this->filter_page)
        {
            # Get the total number of results on all pages
            $result = $this->GetDataConnection()->query("SELECT COUNT(*) AS total FROM (SELECT player_match_id $from $where) AS total");
            $row = $result->fetch();
            $performances[] = $row->total;

            # Limit main query to just the current page
            $sql .= "LIMIT " . ($this->filter_page_size * ($this->filter_page-1)) . ", $this->filter_page_size ";
        }
        else if ($this->filter_max_results)
        {
            $sql .= "LIMIT 0," . $this->filter_max_results;
        }
        
        $result = $this->GetDataConnection()->query($sql);
        $csv = is_array($this->output_as_csv);
        while ($row = $result->fetch())
        {
            # Return data as an array rather than objects because when we return the full,
            # page to dataset it's far more memory efficient
            $performance["player_id"] = $row->player_id;
            $performance["player_name"] = $row->player_name;
            if (!$csv) $performance["player_url"] = "/" . $row->player_url;

            $performance["match_id"] = $row->match_id;
            $performance["match_time"] = $csv ? Date::Microformat($row->match_time) : $row->match_time;
            $performance["match_title"] = $row->match_title;
            if (!$csv) $performance["match_url"] = "/" . $row->match_short_url;

            $performance["runs_scored"] = $row->runs_scored;
            $performance["how_out"] = $csv ? Batting::Text($row->how_out) : $row->how_out;
            $performance["wickets"] = $row->wickets;
            $performance["runs_conceded"] = $row->runs_conceded;
            $performance["catches"] = $row->catches;
            $performance["run-outs"] = $row->run_outs;

            $performances[] = $performance;
        }

        # Optionally add a header row and download a CSV file rather than returning the data
        if ($csv)
        {
            require_once("data/csv.class.php");
            array_unshift($performances, array("Player id", "Player", "Match id", "Match date (UTC)", "Match", "Runs", "How out", "Wickets", "Runs conceded", "Catches", "Run-outs"));
            CSV::PublishData($performances);
        }

        return $performances;
    }

	/**
	 * Reads the seasons which have player statistics fitting the current filters
	 */
	public function ReadSeasonsWithPlayerStatistics()
	{
		require_once("stoolball/season.class.php");

		# First, prepare all the filters that must apply to each query
		$players = $this->GetSettings()->GetTable("Player");
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");
		$sm = $this->GetSettings()->GetTable('SeasonMatch');
		$seasons = $this->GetSettings()->GetTable("Season");

		$from = "FROM $statistics ";
		$competition_filter_active = (bool)count($this->filter_competitions);
		if (count($this->filter_seasons) or $competition_filter_active) $from .= "INNER JOIN $sm ON $statistics.match_id = $sm.match_id ";
		if ($competition_filter_active) $from .= "INNER JOIN $seasons ON $sm.season_id = $seasons.season_id ";

		$where = "";
		$where = $this->ApplyFilters($where);
		if ($where) $where = "WHERE " . substr($where, 3, strlen($where)-3);

		# Create an array to collect the seasons
		$has_statistics = array();
		$mid_season = null;
		$today = gmdate("U");
		$three_months = (60*60*24*30*3);
		$six_months = (60*60*24*30*6);

		# First, get the oldest player record and work out which season it's in
		$sql = "SELECT MIN(match_time) AS match_time $from $where ";
		$result = $this->GetDataConnection()->query($sql);
		$row = $result->fetch();
		if ($row)
		{
			$season_dates = Season::SeasonDates($row->match_time);
			$season = new Season($this->GetSettings());
			$season->SetStartYear(gmdate("Y", $season_dates[0]));
			$season->SetEndYear(gmdate("Y", $season_dates[1]));
			$has_statistics[] = $season;

			$mid_season = $season_dates[0] + $three_months;
		}

		# If we found the oldest season, check each season up to the current day for statistics
		do
		{
			# Add six months to move to the next season
			$mid_season += $six_months;
			$season_dates = Season::SeasonDates($mid_season);

			# Now see if there are any statistics for this season
			$where_season = $where;
			$where_season .= ($where_season) ? "AND " : "WHERE ";
			$where_season .= ("$statistics.match_time >= " . $season_dates[0] . " AND $statistics.match_time <= " . $season_dates[1] . " ");

			$sql = "SELECT COUNT(match_time) AS total $from $where_season ;";
			$result = $this->GetDataConnection()->query($sql);
			$row = $result->fetch();
			if ($row->total)
			{
				$season = new Season($this->GetSettings());
				$season->SetStartYear(gmdate("Y", $season_dates[0]));
				$season->SetEndYear(gmdate("Y", $season_dates[1]));
				$has_statistics[] = $season;
			}
		}
		while ($mid_season < $today);

		return array_reverse($has_statistics);
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

		while($o_row = $o_result->fetch())
		{
			if (!$o_match_builder->IsDone($o_row->match_id))
			{
				if (isset($o_match))
				{
					$this->Add($o_match);
					$o_team_builder->Reset();
				}

				# create new
				$o_match = new Match($this->GetSettings());
				$o_match->SetId($o_row->match_id);
				if (isset($o_row->start_time)) $o_match->SetStartTime($o_row->start_time);
				if (isset($o_row->home_runs)) $o_match->Result()->SetHomeRuns($o_row->home_runs);
				if (isset($o_row->away_runs)) $o_match->Result()->SetAwayRuns($o_row->away_runs);
				if (isset($o_row->match_result_id)) $o_match->Result()->SetResultType($o_row->match_result_id);

				if (isset($o_row->home_team_id) and !is_null($o_row->home_team_id))
				{
					$o_home = new Team($this->o_settings);
					$o_home->SetId($o_row->home_team_id);
					if (isset($o_row->home_team_name)) $o_home->SetName($o_row->home_team_name);
					if (isset($o_row->home_short_url)) $o_home->SetShortUrl($o_row->home_short_url);
					$o_match->SetHomeTeam($o_home);
					unset($o_home);
				}

				if (isset($o_row->ground_id))
				{
					$o_ground = new Ground($this->GetSettings());
					$o_ground->SetId($o_row->ground_id);
					$o_match->SetGround($o_ground);
					unset($o_ground);
				}

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
		}

		# Add final match
		if (isset($o_match)) $this->Add($o_match);

		return true;
	}
	/**
	 * Update the statistics table for the player of the match
	 * @param int $match_id
	 */
	public function UpdatePlayerOfTheMatchStatistics($match_id)
	{
		# Get all the information we'll need if we have to insert a new record in the player statistics table
		$s_match = $this->GetSettings()->GetTable('Match');
		$s_mt = $this->GetSettings()->GetTable('MatchTeam');
		$players = $this->GetSettings()->GetTable("Player");
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');

		# Reset player of this match in the player statistics. Then we just have to make sure the new ones are added.
		$s_sql = "UPDATE $statistics SET player_of_match = 0 WHERE player_of_match = 1 AND match_id = " . Sql::ProtectNumeric($match_id);
		$this->GetDataConnection()->query($s_sql);

		$s_sql = "SELECT $s_match.match_id, $s_match.start_time, $s_match.ground_id, player_of_match_id, player_of_match_home_id, player_of_match_away_id,
			home_link.match_team_id as home_match_team_id, home_link.team_id as home_team_id,
			away_link.match_team_id as away_match_team_id, away_link.team_id as away_team_id,
			home_team.team_name AS home_team_name, away_team.team_name AS away_team_name,
			player_of_match.player_role, player_of_match.player_name, player_of_match.short_url, player_of_match.team_id,
			player_of_match_home.player_role AS player_role_home, player_of_match_home.player_name AS player_name_home, player_of_match_home.short_url AS player_url_home, 
			player_of_match_away.player_role AS player_role_away, player_of_match_away.player_name AS player_name_away, player_of_match_away.short_url AS player_url_away
			FROM " . $s_match . ' LEFT OUTER JOIN ' . $s_mt . ' AS home_link ON ' . $s_match . '.match_id = home_link.match_id AND home_link.team_role = ' . TeamRole::Home() . ' ' .
			"LEFT OUTER JOIN $s_mt AS away_link ON $s_match.match_id = away_link.match_id AND away_link.team_role = " . TeamRole::Away() . ' ' .
			"LEFT OUTER JOIN $players AS player_of_match ON $s_match.player_of_match_id = player_of_match.player_id
			LEFT OUTER JOIN $players AS player_of_match_home ON $s_match.player_of_match_home_id = player_of_match_home.player_id
            LEFT OUTER JOIN $players AS player_of_match_away ON $s_match.player_of_match_away_id = player_of_match_away.player_id
			LEFT OUTER JOIN nsa_team AS home_team ON home_link.team_id = home_team.team_id
			LEFT OUTER JOIN nsa_team AS away_team ON away_link.team_id = away_team.team_id
			WHERE $s_match.match_id = " . Sql::ProtectNumeric($match_id);
		$result = $this->GetDataConnection()->query($s_sql);
		$row = $result->fetch();

		if (!is_null($row->player_of_match_id))
		{
			# There is an edge case where this could be wrong. If both sides in the match are from the same team
			# and the same player played for both sides, we don't record which side the player of the match award
			# was for. Pretty unlikely though!
			if ($row->team_id == $row->home_team_id)
			{
				$match_team_id = $row->home_match_team_id;
                $team_name = $row->home_team_name;
				$opposition_id = $row->away_team_id;
                $opposition_name = $row->away_team_name;
			}
			else
			{
				$match_team_id = $row->away_match_team_id;
                $team_name = $row->away_team_name;
				$opposition_id = $row->home_team_id;
                $opposition_name = $row->home_team_name;
			}
			$this->AddPlayerOfMatchToStatistics($row->player_of_match_id, $row->player_role, $row->player_name, $row->short_url, $match_team_id, 
			                                    $row->match_id, $row->ground_id, $row->start_time, $row->team_id, $team_name, $opposition_id, $opposition_name);
		}
		if (!is_null($row->player_of_match_home_id))
		{
			$this->AddPlayerOfMatchToStatistics($row->player_of_match_home_id, $row->player_role_home, $row->player_name_home, $row->player_url_home, $row->home_match_team_id, 
			                                    $row->match_id, $row->ground_id, $row->start_time, $row->home_team_id, $row->home_team_name, $row->away_team_id, $row->away_team_name);
		}
		if (!is_null($row->player_of_match_away_id))
		{
			$this->AddPlayerOfMatchToStatistics($row->player_of_match_away_id, $row->player_role_away, $row->player_name_away, $row->player_url_away, $row->away_match_team_id, 
		                                        $row->match_id, $row->ground_id, $row->start_time, $row->away_team_id, $row->away_team_name, $row->home_team_id, $row->home_team_name);
		}
	}

	/**
	 * Add or update a statistics record to show that a player was player of the match
	 * @param int $player_id
	 * @param int $match_team_id
	 * @param int $match_id
	 * @param int $ground_id
	 * @param int $match_time
	 * @param int $opposition_id
	 */
	private function AddPlayerOfMatchToStatistics($player_id, $player_role, $player_name, $player_url, $match_team_id, $match_id, $ground_id, $match_time, $team_id, $team_name, $opposition_id, $opposition_name)
	{
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');

		$sql = "SELECT player_match_id FROM $statistics
				WHERE match_team_id = " . Sql::ProtectNumeric($match_team_id, false, false) . "
				AND player_id = " . Sql::ProtectNumeric($player_id, false, false) . "
				AND (player_innings IS NULL OR player_innings = 1)";
		$data = $this->GetDataConnection()->query($sql);
		$data_row = $data->fetch();
		if ($data_row)
		{
			$sql = "UPDATE $statistics SET player_of_match = 1 WHERE player_match_id = $data_row->player_match_id";
			$this->GetDataConnection()->query($sql);
		}
		else
		{
			$sql = "INSERT INTO $statistics SET
					player_id = " . Sql::ProtectNumeric($player_id, false, false) . ",
                    player_role = " . Sql::ProtectNumeric($player_role, false, false) . ",
                    player_name = " . Sql::ProtectString($this->GetDataConnection(), $player_name) . ",
                    player_url = " . Sql::ProtectString($this->GetDataConnection(), $player_url) . ",
					match_team_id = " . Sql::ProtectNumeric($match_team_id, false, false) . ",
					match_id = " . Sql::ProtectNumeric($match_id, false, false) . ",
					ground_id = " . Sql::ProtectNumeric($ground_id, true, false) . ",
					match_time = " . Sql::ProtectNumeric($match_time, false, false) . ",
                    team_id = " . Sql::ProtectNumeric($team_id, true, false) . ",
                    team_name = " . Sql::ProtectString($this->GetDataConnection(), $team_name) . ",
					opposition_id = " . Sql::ProtectNumeric($opposition_id, true, false) . ",
                    opposition_name = " . Sql::ProtectString($this->GetDataConnection(), $opposition_name) . ",
					player_of_match = 1,
					catches = 0,
					run_outs = 0";

			$this->GetDataConnection()->query($sql);
		}
	}

	/**
	 * Recalculate total/missed/first/last matches for all specified players in
	 * @param int[] $player_ids
	 * @return void
	 */
	public function UpdatePlayerStatistics($player_ids)
	{
		$this->ValidateNumericArray($player_ids);

		$matches = $this->GetSettings()->GetTable("Match");
		$players = $this->GetSettings()->GetTable("Player");
		$teams = $this->GetSettings()->GetTable("MatchTeam");
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");

		$player_ids = implode(",", $player_ids);
		$has_played_ids = array();
		$has_missed_ids = array();

		# First, get total matches, first match and last match from the database.
		# Include extras in this query because we want to know how many matches they're recorded in,
		# but no need to include them in subsequent queries which are about data for autocomplete and
		# deleting players we don't need.

		# However we have to catch an edge case first.  If a player hasn't been recorded in any matches
		# we won't get any total_matches data for them. If it's an ordinary player it doesn't matter
		# because they'll be deleted below. However for an extras player, if they are recorded they have
		# a record of their total_matches, but if their performance is then removed this would never be updated.
		# To deal with this, reset their data first.
		$sql = "UPDATE $players SET total_matches = NULL, first_played = NULL, last_played = NULL
				WHERE player_id IN ($player_ids) ";
		$this->GetDataConnection()->query($sql);

		$sql = "SELECT player_id, COUNT(DISTINCT match_id) AS total_matches, MIN(match_time) AS first_played, MAX(match_time) AS last_played
				FROM $statistics
				WHERE player_id IN ($player_ids)
				GROUP BY player_id";

		$result = $this->GetDataConnection()->query($sql);
		if (!$this->GetDataConnection()->isError())
		{
			while($row = $result->fetch())
			{
				$has_played_ids[] = $row->player_id;

				$sql = "UPDATE $players SET
						total_matches = $row->total_matches,
						first_played = $row->first_played,
						last_played = $row->last_played
						WHERE player_id = $row->player_id";
				$this->GetDataConnection()->query($sql);
			}
		}
		unset($result);

		# now get missed matches, and from that work out probability (working out probability in SQL didn't work...)
		$sql = "SELECT p.player_id, p.total_matches, COUNT( m.match_id ) AS missed_matches
				FROM $players p
				LEFT JOIN $teams mt ON mt.team_id = p.team_id
				LEFT JOIN $matches m ON m.match_id = mt.match_id
				WHERE p.player_id IN ($player_ids)
				AND p.player_role = " . Player::PLAYER . "
				AND m.start_time > p.last_played
				AND mt.team_role IN (" . TeamRole::Home() . "," . TeamRole::Away() . ")
				GROUP BY p.player_id";

		$result = $this->GetDataConnection()->query($sql);
		if (!$this->GetDataConnection()->isError())
		{
			while($row = $result->fetch())
			{
				$has_missed_ids[] = $row->player_id;

				$sql = "UPDATE $players SET
						missed_matches = $row->missed_matches,
						probability = " . (intval($row->total_matches) - intval($row->missed_matches)) . ",
						date_changed = " . gmdate('U') . "
						WHERE player_id = $row->player_id";
				$this->GetDataConnection()->query($sql);
			}
		}
		unset($result);

		$total_players = count($has_played_ids);
		if ($total_players)
		{
			# Second set didn't include people from first set who haven't missed any matches, so update those too
			# When there's not much data, it's possible that no-one has missed a match.
			$sql = "UPDATE $players SET
					missed_matches = 0,
					probability = total_matches,
					date_changed = " . gmdate('U') . "
					WHERE player_id IN (" . implode(",", $has_played_ids) . ")";

			if (count($has_missed_ids))
			{
				$sql .= " AND player_id NOT IN (" . implode(",", $has_missed_ids) . ")";
			}

			$this->GetDataConnection()->query($sql);
		}

		# finally, delete any players who haven't played any matches
		# (perhaps their only entry has just been replaced with a corrected name, which gets a new entry)
		$sql = "DELETE FROM $players WHERE player_id IN ($player_ids) AND player_role = " . Player::PLAYER;
		if ($total_players) $sql .= " AND player_id NOT IN (" . implode(",", $has_played_ids) . ")";
		$this->GetDataConnection()->query($sql);
	}

	/**
	 * Calculate batting figures based on batting card
	 * @param int[] $player_ids
	 * @param int[] $batting_match_team_ids
	 */
	public function UpdateBattingStatistics($player_ids, $batting_match_team_ids)
	{
		$this->ValidateNumericArray($player_ids);
		$this->ValidateNumericArray($batting_match_team_ids);

		$batting_table = $this->GetSettings()->GetTable("Batting");
		$player_table = $this->GetSettings()->GetTable("Player");
		$stats_table = $this->GetSettings()->GetTable("PlayerMatch");
		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$match_table = $this->GetSettings()->GetTable("Match");
		$player_id_list = implode(", ", $player_ids);
		$batting_match_team_id_list = implode(",", $batting_match_team_ids);
		$batter_ids_recorded = array();

		# reset batting stats for these players
		$sql = "UPDATE $stats_table SET batting_position = NULL, how_out = NULL, dismissed = NULL, bowled_by = NULL, caught_by = NULL, run_out_by = NULL, runs_scored = NULL
				WHERE player_id IN ($player_id_list) AND match_team_id IN ($batting_match_team_id_list)";
		$this->GetDataConnection()->query($sql);

		# delete any rows which represent the batter having a second, third etc go. We've just deleted all the important data in there anyway.
		$sql = "DELETE FROM $stats_table WHERE player_id IN ($player_id_list) AND match_team_id IN ($batting_match_team_id_list) AND player_innings > 1";
		$this->GetDataConnection()->query($sql);

		# Now generate batting figures based on the data entered
		$sql = "SELECT $player_table.player_id, $player_table.player_role, $player_table.player_name, $player_table.short_url,
		$mt.match_id, $mt.match_team_id, 
		m.start_time, m.tournament_match_id, m.ground_id, 
		position, how_out, dismissed_by_id, bowler_id, runs,
		team.team_id, team.team_name
		FROM $batting_table INNER JOIN $mt ON $batting_table.match_team_id = $mt.match_team_id
		INNER JOIN $match_table m ON $mt.match_id = m.match_id
		INNER JOIN $player_table ON $batting_table.player_id = $player_table.player_id
		INNER JOIN nsa_team AS team ON $player_table.team_id = team.team_id
		WHERE $player_table.player_id IN ($player_id_list)
		AND $batting_table.match_team_id IN ($batting_match_team_id_list)
		ORDER BY position";
		$result = $this->GetDataConnection()->query($sql);

		while($row = $result->fetch())
		{
			$is_extras = ($row->player_role == Player::NO_BALLS or $row->player_role == Player::WIDES or $row->player_role == Player::BYES or $row->player_role == Player::BONUS_RUNS);

			# Make catches and run outs easier to query
			$dismissed_by_id = Sql::ProtectNumeric($row->dismissed_by_id, true);
			$bowler_id = Sql::ProtectNumeric($row->bowler_id, true);

			$catcher_id = 'NULL';
			if ($row->how_out == Batting::CAUGHT) $catcher_id = $dismissed_by_id;
			if ($row->how_out == Batting::CAUGHT_AND_BOWLED) $catcher_id = $bowler_id;

			$run_out_by_id = "NULL";
			if ($row->how_out == Batting::RUN_OUT) $run_out_by_id = $dismissed_by_id;

			switch ($row->how_out)
			{
				case Batting::DID_NOT_BAT:
				case Batting::NOT_OUT:
				case Batting::RETIRED:
				case Batting::RETIRED_HURT:
					$dismissed = 0;
					break;
				case Batting::BOWLED:
				case Batting::CAUGHT:
				case Batting::CAUGHT_AND_BOWLED:
				case Batting::RUN_OUT:
				case Batting::BODY_BEFORE_WICKET:
				case Batting::HIT_BALL_TWICE:
				case Batting::TIMED_OUT:
				case Batting::UNKNOWN_DISMISSAL:
					$dismissed = 1;
					break;
				default:
					$dismissed = "NULL";
			}
			if ($is_extras) $dismissed = "NULL";

			$runs = Sql::ProtectNumeric($row->runs, true, false);
			$ground_id = Sql::ProtectNumeric($row->ground_id, true, false);
            $tournament_id = Sql::ProtectNumeric($row->tournament_match_id, true, false);
            $team_name = Sql::ProtectString($this->GetDataConnection(), $row->team_name);
            $player_name = Sql::ProtectString($this->GetDataConnection(), $row->player_name);
            $player_url = Sql::ProtectString($this->GetDataConnection(), $row->short_url);

			# Check if this player has batted before
			if (!array_key_exists($row->match_team_id, $batter_ids_recorded)) $batter_ids_recorded[$row->match_team_id] = array();
			if (!in_array($row->player_id, array_keys($batter_ids_recorded[$row->match_team_id])))
			{
				# This is the first time the player has batted in this innings (the normal situation)
				$batter_ids_recorded[$row->match_team_id][$row->player_id] = 1;
				$player_innings = $is_extras ? "NULL" : "1";
				$how_out = $is_extras ? "NULL" : $row->how_out;

				$position = $is_extras ? "NULL" : $row->position;
				if ($position == 2) $position = "1";

				# Set catches and run outs by the batter to 0; for those who took them, it'll be updated next
				$sql = "UPDATE $stats_table SET
					player_innings = $player_innings,
					batting_position = $position,
					how_out = $how_out,
					dismissed = $dismissed,
					caught_by = $catcher_id,
					run_out_by = $run_out_by_id,
					bowled_by = $bowler_id,
					runs_scored = $runs
					WHERE match_team_id = $row->match_team_id
					AND player_id = $row->player_id";
				$update_result = $this->GetDataConnection()->query($sql);

				if (!$this->GetDataConnection()->GetAffectedRows())
				{
					# Get the opposition team id
					$sql = "SELECT team.team_id, team.team_name
						FROM $mt INNER JOIN $match_table m ON $mt.match_id = m.match_id
						INNER JOIN nsa_team AS team ON $mt.team_id = team.team_id
						WHERE m.match_id = (SELECT match_id FROM $mt WHERE match_team_id = $row->match_team_id)
						AND match_team_id != $row->match_team_id
						AND team_role IN (" . TeamRole::Home() . ", " . TeamRole::Away() . ")";
					$data = $this->GetDataConnection()->query($sql);
					$data_row = $data->fetch();
					$opposition_id = $data_row ? $data_row->team_id : "NULL";
                    $opposition_name = $data_row ? Sql::ProtectString($this->GetDataConnection(), $data_row->team_name) : "NULL";
					$catches = $is_extras ? "NULL" : "0";
					$run_outs = $is_extras ? "NULL" : "0";
					$player_of_match = $is_extras ? "NULL" : "0";

					# this is the first record of the player playing in the match. Everyone is a fielder, so set fielding statistics to defaults as well.
					$sql = "INSERT INTO $stats_table
					(player_id, player_role, player_name, player_url, match_id, match_team_id, match_time, 
					 tournament_id, ground_id, team_id, team_name, opposition_id, opposition_name,  
					 player_innings, batting_position, how_out, dismissed, caught_by, run_out_by, bowled_by, runs_scored, catches, run_outs, player_of_match)
					VALUES
					($row->player_id, $row->player_role, $player_name, $player_url, $row->match_id, $row->match_team_id, $row->start_time, 
					 $tournament_id, $ground_id, $row->team_id, $team_name, $opposition_id, $opposition_name,  
					 $player_innings, $position, $how_out, $dismissed, $catcher_id, $run_out_by_id, $bowler_id, $runs, $catches, $run_outs, $player_of_match)";
					$this->GetDataConnection()->query($sql);
				}

			}
			else
			{
				# The player is batting for a second time in the innings (unusual, but it happens)
				$batter_ids_recorded[$row->match_team_id][$row->player_id]++;

				# Get the opposition team id
	            $sql = "SELECT team.team_id, team.team_name
    					FROM $mt INNER JOIN $match_table m ON $mt.match_id = m.match_id
	                    INNER JOIN nsa_team AS team ON $mt.team_id = team.team_id
    					WHERE m.match_id = (SELECT match_id FROM $mt WHERE match_team_id = $row->match_team_id)
						AND match_team_id != $row->match_team_id
						AND team_role IN (" . TeamRole::Home() . ", " . TeamRole::Away() . ")";
				$data = $this->GetDataConnection()->query($sql);
				$data_row = $data->fetch();
				$opposition_id = $data_row ? $data_row->team_id : "NULL";
                $opposition_name = $data_row ? Sql::ProtectString($this->GetDataConnection(), $data_row->team_name) : "NULL";
				$position = ($row->position == 2) ? 1 : $row->position;

				$sql = "INSERT INTO $stats_table
				(player_id, player_role, player_name, player_url, match_id, match_team_id, match_time, 
				 tournament_id, ground_id, team_id, team_name, opposition_id, opposition_name,
				 player_innings, batting_position, how_out, dismissed, caught_by, run_out_by, bowled_by, runs_scored)
				VALUES
				($row->player_id, $row->player_role, $player_name, $player_url, $row->match_id, $row->match_team_id, $row->start_time, 
				 $tournament_id, $ground_id, $row->team_id, $team_name, $opposition_id, $opposition_name, " .
				 $batter_ids_recorded[$row->match_team_id][$row->player_id] . ",
				 $position, $row->how_out, $dismissed, $catcher_id, $run_out_by_id, $bowler_id, $runs)";
				$this->GetDataConnection()->query($sql);

			}
		}
	}

	/**
	 * Calculate fielding figures based on batting card
	 * @param int[] $player_ids
	 * @param int[] $bowling_match_team_ids
	 */
	public function UpdateFieldingStatistics($player_ids, $bowling_match_team_ids)
	{
		$this->ValidateNumericArray($player_ids);
		$this->ValidateNumericArray($bowling_match_team_ids);

		$stats_table = $this->GetSettings()->GetTable("PlayerMatch");
		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$match_table = $this->GetSettings()->GetTable("Match");
		$player_id_list = implode(", ", $player_ids);
		$bowling_match_team_id_list = implode(",", $bowling_match_team_ids);

		# reset statistics for these players - this ensures that the number of affected rows on update
		# tells us whether or not there was an existing record. If we update a record with the same
		# information it already had, the number of affected rows is zero, which is the same as if
		# there had been no record
		$sql = "UPDATE $stats_table SET catches = NULL, run_outs = NULL
				WHERE player_id IN ($player_id_list) AND match_team_id IN ($bowling_match_team_id_list)";
		$this->GetDataConnection()->query($sql);

		foreach ($bowling_match_team_ids as $bowling_match_team_id)
		{
			# get the match_team_id for the batting that goes with this bowling
			$sql = "SELECT m.match_id, m.start_time, m.tournament_match_id, m.ground_id, match_team_id
			FROM $mt INNER JOIN $match_table m ON $mt.match_id = m.match_id
			WHERE m.match_id = (SELECT match_id FROM $mt WHERE match_team_id = $bowling_match_team_id)
			AND match_team_id != $bowling_match_team_id
			AND team_role IN (" . TeamRole::Home() . ", " . TeamRole::Away() . ")";
			$result = $this->GetDataConnection()->query($sql);
			$row = $result->fetch();
			$match_id = $row->match_id;
			$match_time = Sql::ProtectNumeric($row->start_time, true, false);
            $tournament_id = Sql::ProtectNumeric($row->tournament_match_id, true, false);
			$ground_id = Sql::ProtectNumeric($row->ground_id, true, false);
			$batting_match_team_id = $row->match_team_id;

			# Get the teams involved.
            $sql = "SELECT nsa_team.team_id, nsa_team.team_name, $mt.match_team_id 
                    FROM nsa_team INNER JOIN $mt ON nsa_team.team_id = $mt.team_id
                    WHERE $mt.match_team_id IN ($batting_match_team_id, $bowling_match_team_id)";
            $team_data = $this->GetDataConnection()->query($sql);
            while ($row = $team_data->fetch()) 
            {
                if ($row->match_team_id == $bowling_match_team_id) 
                {
                    $team_id = Sql::ProtectNumeric($row->team_id, false, false);
                    $team_name = Sql::ProtectString($this->GetDataConnection(), $row->team_name);    
                }
                else
                {
                    $opposition_id = Sql::ProtectNumeric($row->team_id, false, false);
                    $opposition_name = Sql::ProtectString($this->GetDataConnection(), $row->team_name);    
                }
            }
                    
			# Now get the catches completed by each player from the batting data
			$sql = "SELECT caught_by, COUNT(caught_by) AS catches
				FROM $stats_table 
				WHERE caught_by IN ($player_id_list)
				AND match_team_id = $batting_match_team_id
				GROUP BY caught_by";
			$result = $this->GetDataConnection()->query($sql);

			# Add catches to their figures for the match, bearing in mind they may not have any yet if only their fielding is recorded.
			# Start with an array with a key for each player and zero catches. Default is overwritten if a player has taken a catch.
			$catchers = array_fill_keys($player_ids, 0);
			while($row = $result->fetch())
			{
				$catchers[$row->caught_by] = $row->catches;
			}

			foreach ($catchers as $caught_by_id => $catches)
			{
		        $caught_by_id = Sql::ProtectNumeric($caught_by_id, false, false);
			    
				$sql = "UPDATE $stats_table SET
					catches = $catches
					WHERE match_team_id = $bowling_match_team_id
					AND player_id = $caught_by_id
					AND (player_innings IS NULL OR player_innings = 1)";
				$update_result = $this->GetDataConnection()->query($sql);

				if (!$this->GetDataConnection()->GetAffectedRows())
				{
					# This is the first record of the player in the match. 
                    
                    # Get the player's details
                    $sql = "SELECT player_role, player_name, short_url FROM nsa_player WHERE player_id = " . $caught_by_id;
					$player_data = $this->GetDataConnection()->query($sql);
                    $row = $player_data->fetch(); 
					$player_role = is_object($row) ? Sql::ProtectNumeric($row->player_role, false, false) : 'NULL';
					$player_name = is_object($row) ? Sql::ProtectString($this->GetDataConnection(), $row->player_name) : 'NULL'; 
					$player_url = is_object($row) ? Sql::ProtectString($this->GetDataConnection(), $row->short_url) : 'NULL';
					
                    # Now insert the record. They are fielding, so we can set run outs to 0 as well.
					$sql = "INSERT INTO $stats_table
							(player_id, player_role, player_name, player_url, match_id, match_team_id, 
							 match_time, tournament_id, ground_id, team_id, team_name, opposition_id, opposition_name, catches, run_outs, player_of_match)
							VALUES
							($caught_by_id, $player_role, $player_name, $player_url, $match_id, $bowling_match_team_id, 
							 $match_time, $tournament_id, $ground_id, $team_id, $team_name, $opposition_id, $opposition_name, $catches, 0, 0)";

					$this->GetDataConnection()->query($sql);
				}
			}

			# Now get the run-outs completed by each player from the batting data
			$sql = "SELECT run_out_by, COUNT(run_out_by) AS run_outs
				FROM $stats_table
				WHERE run_out_by IN ($player_id_list)
				AND match_team_id = $batting_match_team_id
				GROUP BY run_out_by";
			$result = $this->GetDataConnection()->query($sql);

			# Add run_outs to their figures for the match, bearing in mind they may not have any yet if only their fielding is recorded
			# Start with an array with a key for each player and zero run outs. Default is overwritten if a player has completed a run out.
			$run_outs_completed = array_fill_keys($player_ids, 0);
			while($row = $result->fetch())
			{
				$run_outs_completed[$row->run_out_by] = $row->run_outs;
			}

			foreach ($run_outs_completed as $run_out_by_id => $run_outs)
			{
				# Update player's record. There will definitely be one, because we just made sure everyone had a record for their catches
				$sql = "UPDATE $stats_table SET
					run_outs = $run_outs
					WHERE match_team_id = $bowling_match_team_id
					AND player_id = $run_out_by_id
					AND (player_innings IS NULL OR player_innings = 1)";
				$update_result = $this->GetDataConnection()->query($sql);
			}
		}
	}

	/**
	 * Calculate bowling figures based on overs and wickets taken
	 * @param int[] $player_ids
	 * @param int[] $bowling_match_team_ids
	 */
	public function UpdateBowlingStatistics($player_ids, $bowling_match_team_ids)
	{
		$this->ValidateNumericArray($player_ids);
		$this->ValidateNumericArray($bowling_match_team_ids);

		$batting_table = $this->GetSettings()->GetTable("Batting");
		$bowling_table = $this->GetSettings()->GetTable("Bowling");
		$stats_table = $this->GetSettings()->GetTable("PlayerMatch");
		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$match_table = $this->GetSettings()->GetTable("Match");
		$player_id_list = implode(", ", $player_ids);
		$bowling_match_team_id_list = implode(",", $bowling_match_team_ids);
		$has_bowling_data = array();

		# reset bowling stats for these players
		$sql = "UPDATE $stats_table SET first_over = NULL, balls_bowled = NULL, overs = NULL, overs_decimal = NULL, maidens = NULL,
				runs_conceded = NULL, has_runs_conceded = NULL, wickets = NULL, wickets_with_bowling = NULL
				WHERE player_id IN ($player_id_list) AND match_team_id IN ($bowling_match_team_id_list)";
		$this->GetDataConnection()->query($sql);

        # Get the teams involved, with enough detail to work out later which is the player's team and which is the opposition
        $sql = "SELECT nsa_match_team.match_id, nsa_match_team.match_team_id, team.team_id, team.team_name
                FROM nsa_match_team 
                LEFT JOIN nsa_team AS team ON nsa_match_team.team_id = team.team_id 
                WHERE match_id IN (SELECT match_id FROM nsa_match_team WHERE match_team_id IN ($bowling_match_team_id_list))";
        $result = $this->GetDataConnection()->query($sql);
        
        $team_data = array();
        while ($row = $result->fetch()) 
        {
            if (!array_key_exists($row->match_id, $team_data)) {
                $team_data[$row->match_id] = array();
            }
            
            $team_data[$row->match_id][$row->match_team_id] = array();
            $team_data[$row->match_id][$row->match_team_id]["team_id"] = $row->team_id;
            $team_data[$row->match_id][$row->match_team_id]["team_name"] = $row->team_name;
        }        

		# Now generate bowling figures based on the data entered
		$sql = "SELECT nsa_player.player_id, nsa_player.player_role, nsa_player.player_name, nsa_player.short_url, 
		$mt.match_id, $mt.match_team_id, m.start_time, m.tournament_match_id, m.ground_id,
		MIN(position) AS first_over, SUM(runs_in_over) AS runs_conceded, SUM(balls_bowled) AS balls_total,
		CONCAT(FLOOR(SUM(balls_bowled)/8), '.', FLOOR(SUM(balls_bowled) MOD 8)) AS overs, SUM(balls_bowled)/8 AS overs_decimal,
		(SELECT COUNT(bowling_id) FROM $bowling_table AS maiden_overs WHERE runs_in_over = 0 AND balls_bowled >= 8 AND maiden_overs.match_team_id = $bowling_table.match_team_id AND maiden_overs.player_id = $bowling_table.player_id) AS maidens
		FROM $bowling_table INNER JOIN $mt ON $bowling_table.match_team_id = $mt.match_team_id
		INNER JOIN nsa_player ON $bowling_table.player_id = nsa_player.player_id
		INNER JOIN $match_table m ON $mt.match_id = m.match_id
		WHERE nsa_player.player_id IN ($player_id_list)
		AND $bowling_table.match_team_id IN ($bowling_match_team_id_list)
		GROUP BY nsa_player.player_id, $bowling_table.match_team_id";
		$result = $this->GetDataConnection()->query($sql);

		while($row = $result->fetch())
		{
		    $balls_bowled = Sql::ProtectNumeric($row->balls_total, true);
		    $overs = Sql::ProtectNumeric($row->overs, true);
		    $overs_decimal = Sql::ProtectNumeric($row->overs_decimal, true);
		    $maidens = (is_null($row->overs) ? "NULL" : Sql::ProtectNumeric($row->maidens, true));
		    $runs_conceded = Sql::ProtectNumeric($row->runs_conceded, true);
		    $has_runs_conceded = (is_null($row->runs_conceded) ? "0" : "1");
            
			# Set wickets to 0; for those who took them, it'll be updated next
			$sql = "UPDATE $stats_table SET
					first_over = $row->first_over,
					balls_bowled = $balls_bowled,
					overs = $overs,
					overs_decimal = $overs_decimal,
					maidens = $maidens,
					runs_conceded = $runs_conceded,
					has_runs_conceded = $has_runs_conceded,  
					wickets = 0,
					wickets_with_bowling = 0
					WHERE match_team_id = $row->match_team_id
					AND player_id = $row->player_id
					AND (player_innings IS NULL OR player_innings = 1)";
			$update_result = $this->GetDataConnection()->query($sql);

			if (!$this->GetDataConnection()->GetAffectedRows())
			{
				# This is the first record of the player in the match. Everyone is a fielder, so set fielding statistics to defaults as well.
				$player_role = Sql::ProtectNumeric($row->player_role, false, false);
                $player_name = Sql::ProtectString($this->GetDataConnection(), $row->player_name);
                $player_url = Sql::ProtectString($this->GetDataConnection(), $row->short_url);
				
				$match_time = Sql::ProtectNumeric($row->start_time, true, false);
				$tournament_id = Sql::ProtectNumeric($row->tournament_match_id, true, false);
                $ground_id = Sql::ProtectNumeric($row->ground_id, true, false);
                
                $teams_in_match = $team_data[$row->match_id];
                $players_team = $teams_in_match[$row->match_team_id];
                unset($teams_in_match[$row->match_team_id]);
                $opposition_team = $teams_in_match[0];
                $team_id = Sql::ProtectNumeric($players_team["team_id"], false, false);
                $team_name = Sql::ProtectString($this->GetDataConnection(), $players_team["team_name"]);
                $opposition_id = Sql::ProtectNumeric($opposition_team["team_id"], false, false);
                $opposition_name = Sql::ProtectString($this->GetDataConnection(), $players_team["team_name"]);
                
				$sql = "INSERT INTO $stats_table
					(player_id, player_role, player_name, player_url, match_id, match_team_id, match_time, tournament_id, ground_id, 
					 team_id, team_name, opposition_id, opposition_name, first_over, balls_bowled, overs, overs_decimal, maidens, 
					 runs_conceded, has_runs_conceded, wickets, wickets_with_bowling, catches, run_outs, player_of_match)
					VALUES
					($row->player_id, $player_role, $player_name, $player_url, $row->match_id, $row->match_team_id, $match_time, $tournament_id, $ground_id, 
					 $team_id, $team_name, $opposition_id, $opposition_name, $row->first_over, $balls_bowled, $overs, $overs_decimal, $maidens, 
					 $runs_conceded, $has_runs_conceded, 0, 0, 0, 0, 0)";

					$this->GetDataConnection()->query($sql);
			}

			# Note that this player had bowling data
			if (!array_key_exists($row->match_team_id, $has_bowling_data)) $has_bowling_data[$row->match_team_id] = array();
			$has_bowling_data[$row->match_team_id][] = $row->player_id;
		}

		# Now get the wickets for each bowler from the batting data
		foreach ($bowling_match_team_ids as $bowling_match_team_id)
		{
			# get the match_team_id for the batting that goes with this bowling
			$sql = "SELECT m.match_id, m.start_time, m.tournament_match_id, m.ground_id, match_team_id, team_id
			FROM $mt INNER JOIN $match_table m ON $mt.match_id = m.match_id
			WHERE m.match_id = (SELECT match_id FROM $mt WHERE match_team_id = $bowling_match_team_id)
			AND match_team_id != $bowling_match_team_id
			AND team_role IN (" . TeamRole::Home() . ", " . TeamRole::Away() . ")";
			$result = $this->GetDataConnection()->query($sql);
			$row = $result->fetch();
			$match_id = $row->match_id;
			$batting_match_team_id = $row->match_team_id;
			$match_time = Sql::ProtectNumeric($row->start_time, true, false);
			$tournament_id = Sql::ProtectNumeric($row->tournament_match_id, true, false);
            $ground_id = Sql::ProtectNumeric($row->ground_id, true, false);
			$opposition_id = Sql::ProtectNumeric($row->team_id, true, false);

			# get the wickets for the bowlers in that innings
			# (don't filter by players here as we need to pick up unexpected players who are recorded as taking wickets but not bowling any overs)
			$sql = "SELECT bowler_id, COUNT(batting_id) AS wickets, player_role, player_name, short_url
				FROM $batting_table INNER JOIN nsa_player ON $batting_table.player_id = nsa_player.player_id
				WHERE how_out IN (" . Batting::CAUGHT . "," . Batting::CAUGHT_AND_BOWLED . "," . Batting::BOWLED . "," . Batting::BODY_BEFORE_WICKET . "," . Batting::HIT_BALL_TWICE . ")
				AND match_team_id = $batting_match_team_id
				AND bowler_id IS NOT NULL
				GROUP BY bowler_id";
			$result = $this->GetDataConnection()->query($sql);

			# Add wickets to their bowling figures for the match, bearing in mind they may not have any yet if the scorecard is incomplete
			$wickets_taken = array();
			while($row = $result->fetch())
			{
				$wickets_taken[$row->bowler_id] = array();
                $wickets_taken[$row->bowler_id]["player_role"] = $row->player_role;
                $wickets_taken[$row->bowler_id]["player_name"] = $row->player_name;
                $wickets_taken[$row->bowler_id]["player_url"] = $row->short_url;
				$wickets_taken[$row->bowler_id]["wickets"] = $row->wickets;
			}

			if (count($wickets_taken))
			{
				# reset wickets for these players - this ensures that the number of affected rows on update
				# tells us whether or not there was an existing record. If we update a record with the same
				# information it already had, the number of affected rows is zero, which is the same as if
				# there had been no record
				$sql = "UPDATE $stats_table SET wickets = NULL, wickets_with_bowling = NULL
				WHERE player_id IN (" . implode(", ", array_keys($wickets_taken)) . ") AND match_team_id = $bowling_match_team_id";
				$this->GetDataConnection()->query($sql);

				foreach ($wickets_taken as $bowler_id => $bowler)
				{
				    $wickets = Sql::ProtectNumeric($bowler["wickets"], false, false);
                    
					$sql = "UPDATE $stats_table SET
					wickets = $wickets,
					opposition_id = $opposition_id
					WHERE match_team_id = $bowling_match_team_id
					AND player_id = $bowler_id
					AND (player_innings IS NULL OR player_innings = 1)";
					$update_result = $this->GetDataConnection()->query($sql);

					if (!$this->GetDataConnection()->GetAffectedRows())
					{
						# This is the first record of this player in the match. They have been a fielder, so set catches and run outs to be zero as well.
						$player_role = Sql::ProtectNumeric($bowler["player_role"], false, false);
						$player_name = Sql::ProtectString($this->GetDataConnection(), $bowler["player_name"]);
						$player_url = Sql::ProtectString($this->GetDataConnection(), $bowler["player_url"]);
						
						$teams_in_match = $team_data[$match_id];
                        $team_id = Sql::ProtectNumeric($teams_in_match[$bowling_match_team_id]["team_id"], false, false);
                        $team_name = Sql::ProtectString($this->GetDataConnection(), $teams_in_match[$bowling_match_team_id]["team_name"]);
                        $opposition_id = Sql::ProtectNumeric($teams_in_match[$batting_match_team_id]["team_id"], false, false);
                        $opposition_name = Sql::ProtectString($this->GetDataConnection(), $teams_in_match[$batting_match_team_id]["team_name"]);
                        						
						$sql = "INSERT INTO $stats_table
								(player_id, player_role, player_name, player_url, match_id, match_team_id, match_time, tournament_id, ground_id, 
								 team_id, team_name, opposition_id, opposition_name, has_runs_conceded, wickets, catches, run_outs, player_of_match)
								VALUES
								($bowler_id, $player_role, $player_name, $player_url, $match_id, $bowling_match_team_id, $match_time, $tournament_id, $ground_id, 
								 $team_id, $team_name, $opposition_id, $opposition_name, 0, $wickets, 0, 0, 0)";
						$this->GetDataConnection()->query($sql);
					}

					# Record wickets again for those who had bowling data.
					# Only these players have enough information for all bowling statistics.
					if (array_key_exists($bowling_match_team_id, $has_bowling_data) and in_array($bowler_id, $has_bowling_data[$bowling_match_team_id]))
					{
						$sql = "UPDATE $stats_table SET
						wickets_with_bowling = $wickets
						WHERE match_team_id = $bowling_match_team_id
						AND player_id = $bowler_id
						AND (player_innings IS NULL OR player_innings = 1)";
						$this->GetDataConnection()->query($sql);
					}
				}
			}
		}
	}

	/**
	 * Deletes empty statistics records for players who have been removed from a match
	 * @param int $match_id
	 */
	public function DeleteObsoleteStatistics($match_id)
	{
		$stats_table = $this->GetSettings()->GetTable("PlayerMatch");

		# Check for the presence of fields which indicate that the player appeared on the batting or bowling scorecard
		# NOTE: runs_scored is the only field for extras
		$sql = "DELETE FROM $stats_table
				WHERE match_id = $match_id
				AND wickets IS NULL
				AND how_out IS NULL
				AND runs_scored IS NULL
				AND (catches IS NULL OR catches = 0)
				AND (run_outs IS NULL OR run_outs = 0)
				AND (player_of_match IS NULL OR player_of_match = 0)";
		$this->GetDataConnection()->query($sql);
	}
}
?>