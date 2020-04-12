<?php
require_once('data/data-manager.class.php');
require_once('stoolball/competition.class.php');
require_once('stoolball/season.class.php');
require_once('stoolball/match-result.class.php');

class SeasonManager extends DataManager
{
	private $a_match_types = array();

	/**
	 * @return SeasonManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Read and write seasons
	 */
	function __construct(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::__construct($o_settings, $o_db);
		$this->s_item_class = 'Season';
	}

	private $filter_by_start_date = 0;

	/**
	 * Sets the date from before which data should not be returned
	 *
	 * @param int $timestamp
	 */
	public function FilterByDateStart($timestamp)
	{
		$this->filter_by_start_date = (int)$timestamp;
	}

	private $filter_by_end_date = 0;

	/**
	 * Sets the date after which data should not be returned
	 *
	 * @param int $timestamp
	 */
	public function FilterByDateEnd($timestamp)
	{
		$this->filter_by_end_date = (int)$timestamp;
	}

	/**
	 * Restricts results to seasons with the specified match type(s)
	 *
	 * @param int[] $types
	 */
	public function FilterByMatchType($types)
	{
		if (is_array($types)) $this->a_match_types = $types;
	}


	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the seasons matching the supplied ids
	 */
	function ReadById($a_ids=null)
	{
		# build query
		$s_season_link = $this->GetSettings()->GetTable('TeamSeason');
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_comp = $this->GetSettings()->GetTable('Competition');
		$s_rule = $this->GetSettings()->GetTable('SeasonRule');
		$s_points = $this->GetSettings()->GetTable('PointsAdjustment');
		$s_smt = $this->GetSettings()->GetTable('SeasonMatchType');

		$s_sql = 'SELECT ' . $s_season . '.season_id, ' . $s_season . '.competition_id, ' . $s_season . '.season_name, ' . $s_season . ".is_latest,
		$s_season.start_year, $s_season.end_year, $s_season.intro, $s_season.results, $s_season.show_table, $s_season.show_runs_scored, $s_season.show_runs_conceded, 
		$s_season.short_url, $s_season.date_added, $s_season.date_changed,
		$s_season_link.withdrawn_league, 
		$s_comp.competition_name, $s_comp.player_type_id, $s_comp.short_url AS competition_short_url, 
		team.team_id, team.team_name, team.ground_id AS team_ground_id, team.short_url AS team_short_url, " .
		$s_points . '.point_id, ' . $s_points . '.points, ' . $s_points . '.reason, ' . $s_points . '.team_id AS points_team_id, ' . $s_points . '.date_added AS points_date, ' .
		$s_rule . '.match_result_id, ' . $s_rule . '.season_rule_id, ' . $s_rule . '.home_points, ' . $s_rule . '.away_points, ' .
		$s_smt . '.match_type AS season_match_type ' .
		"FROM ((((($s_season INNER JOIN $s_comp ON $s_season.competition_id = $s_comp.competition_id) " .
		'LEFT OUTER JOIN ' . $s_season_link . ' ON ' . $s_season . '.season_id = ' . $s_season_link . '.season_id) ' .
		'LEFT OUTER JOIN nsa_team AS team ON ' . $s_season_link . '.team_id = team.team_id) ' .
		'LEFT OUTER JOIN ' . $s_rule . ' ON ' . $s_season . '.season_id = ' . $s_rule . '.season_id) ' .
		'LEFT OUTER JOIN ' . $s_points . ' ON ' . $s_season . '.season_id = ' . $s_points . '.season_id AND team.team_id = ' . $s_points . '.team_id) ' . 
		"LEFT OUTER JOIN $s_smt ON $s_season.season_id = $s_smt.season_id "; 

		# limit to specific seasons, if specified
		if (is_array($a_ids)) $s_sql .= 'WHERE ' . $s_season . '.season_id IN (' . join(', ', $a_ids) . ') ';

		# sort
		$s_sql .= 'ORDER BY ' . $s_season . '.end_year DESC, ' . $s_season . '.season_id, team.team_name ASC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * Read minimal details of seasons according to the supplied criteria
	 *
	 * @param int[] $a_player_types
	 */
	public function ReadSeasonSummaries($a_player_types=null)
	{
		# build query
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_comp = $this->GetSettings()->GetTable('Competition');
		$s_smt = $this->GetSettings()->GetTable('SeasonMatchType');

		$s_sql = 'SELECT ' . $s_season . '.season_id, ' . $s_season . '.competition_id, ' . $s_season . '.season_name, ' .
		$s_season . '.start_year, ' . $s_season . '.end_year, ' .
		"$s_comp.competition_name, " .
		$s_smt . '.match_type AS season_match_type ' .
		"FROM ($s_season INNER JOIN $s_comp ON $s_season.competition_id = $s_comp.competition_id) " .
		"INNER JOIN $s_smt ON $s_season.season_id = $s_smt.season_id ";

		$where = '';

		# Limit by player type if specified
		if (!is_null($a_player_types))
		{
			$this->ValidateNumericArray($a_player_types);
			$where = $this->SqlAddCondition($where, "$s_comp.player_type_id IN (" . join(', ', $a_player_types) . ")");
		}

		# Limit by dates if specified
		if ($this->filter_by_start_date) $where = $this->SqlAddCondition($where, "$s_season.start_year = " . gmdate('Y', $this->filter_by_start_date));
		if ($this->filter_by_end_date) $where = $this->SqlAddCondition($where, "$s_season.end_year = " . gmdate('Y', $this->filter_by_end_date));

		# Limit by match types
		if (count($this->a_match_types))
		{
			if ($this->ValidateNumericArray($this->a_match_types))
			{
				$where = $this->SqlAddCondition($where, "$s_smt.match_type IN (" . join(', ', $this->a_match_types) . ') ');
			}
		}


		$s_sql = $this->SqlAddWhereClause($s_sql, $where);

		# sort
		$s_sql .= "ORDER BY $s_season.end_year DESC, $s_comp.competition_name, $s_season.season_id ";

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
	 * @desc Read from the db the seasons of the supplied competition
	 */
	public function ReadByCompetitionId($a_ids)
	{
		/* @var $o_settings SiteSettings */
		$this->ValidateNumericArray($a_ids);

		# build query
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_comp = $this->GetSettings()->GetTable('Competition');

		$s_sql = 'SELECT ' . $s_season . '.season_id, ' . $s_season . '.season_name, ' . $s_season . '.is_latest, ' .
		"$s_season.start_year, $s_season.end_year, $s_season.short_url, 
		$s_comp.competition_id, $s_comp.short_url AS competition_short_url " .
		"FROM $s_season INNER JOIN $s_comp ON $s_season.competition_id = $s_comp.competition_id " .
		'WHERE ' . $s_season . '.competition_id IN (' . join(', ', $a_ids) . ') ' .
		'ORDER BY ' . $s_season . '.end_year DESC, ' . $s_season . '.season_id ';

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
	 * @param int[] $a_match_types
	 * @desc Read from the db the current seasons for the supplied team ids
	 */
	function ReadCurrentSeasonsByTeamId($a_ids, $a_match_types=null)
	{
		# validate data
		$this->ValidateNumericArray($a_ids);
		$b_limit_by_match_type = (!is_null($a_match_types) && (bool)count($a_match_types));
		if ($b_limit_by_match_type) $this->ValidateNumericArray($a_match_types);

		# build query
		$s_season_link = $this->GetSettings()->GetTable('TeamSeason');
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_comp = $this->GetSettings()->GetTable('Competition');
		$s_smt = $this->GetSettings()->GetTable('SeasonMatchType');

		$s_sql = "SELECT $s_season.season_id, $s_season.season_name, " .
		"$s_comp.competition_name, " .
		$s_smt . '.match_type AS season_match_type ' .
		"FROM (($s_season INNER JOIN $s_comp ON $s_season.competition_id = $s_comp.competition_id) " .
		'INNER JOIN ' . $s_season_link . ' ON ' . $s_season . '.season_id = ' . $s_season_link . '.season_id) ' .
		"INNER JOIN $s_smt ON $s_season.season_id = $s_smt.season_id " .
		'WHERE ' . $s_season_link . '.team_id IN (' . join(', ', $a_ids) . ') ' . # only these teams
		"AND end_year >= " . date('Y') . ' '; # only current, future or recently finished seasons

		# Limit to specified match types, if required
		if ($b_limit_by_match_type) $s_sql .= "AND $s_smt.match_type IN (" . join(', ', $a_match_types) . ') ';

		# sort
		$s_sql .= "ORDER BY $s_season.start_year ASC, $s_season.end_year ASC, $s_comp.competition_name ASC";

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}


	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		/* @var $o_season Season */
		$this->Clear();


		# use CollectionBuilder to handle duplicates
		$o_season_builder = new CollectionBuilder();
		$o_team_builder = new CollectionBuilder();
		$o_rule_builder = new CollectionBuilder();
		$o_points_builder = new CollectionBuilder();
		$o_type_builder = new CollectionBuilder();
		$o_season = null;

		while($o_row = $o_result->fetch())
		{
			# check whether this is a new season
			if (!$o_season_builder->IsDone($o_row->season_id))
			{
				# store any exisiting season and reset
				if ($o_season != null)
				{
					$this->Add($o_season);
					$o_team_builder->Reset();
					$o_rule_builder->Reset();
					$o_points_builder->Reset();
					$o_type_builder->Reset();
				}

				# create the new season
				$o_season = new Season($this->GetSettings());
				$this->BuildSeason($o_season, $o_row);
			}

			# Teams the first cause of multiple rows
			if (isset($o_row->team_id))
			{
				if (!$o_team_builder->IsDone($o_row->team_id))
				{
					if (isset($o_team)) unset($o_team);
					$o_team = new Team($this->GetSettings());
					$o_team->SetId($o_row->team_id);
					$o_team->SetName($o_row->team_name);
					$ground = new Ground($this->GetSettings());
					$ground->SetId($o_row->team_ground_id);
					$o_team->SetGround($ground);
					if (isset($o_row->team_short_url)) $o_team->SetShortUrl($o_row->team_short_url);
					$o_season->AddTeam($o_team);

					if (isset($o_row->withdrawn_league) and (bool)$o_row->withdrawn_league) $o_season->TeamsWithdrawnFromLeague()->Add($o_team);
				}

				# Points adjustments - should come with team and in order of team
				if (isset($o_row->point_id) and !$o_points_builder->IsDone($o_row->point_id))
				{
					$o_point = new PointsAdjustment($o_row->point_id, $o_row->points, $o_team, $o_row->reason, $o_row->points_date);
					$o_season->PointsAdjustments()->Add($o_point);
				}
			}

			# Season rules
			if (isset($o_row->season_rule_id) and !$o_rule_builder->IsDone($o_row->season_rule_id))
			{
				$o_mr = new MatchResult($o_row->match_result_id);
				$o_mr->SetHomePoints($o_row->home_points);
				$o_mr->SetAwayPoints($o_row->away_points);

				$o_season->PossibleResults()->Add($o_mr);

				unset($o_mr);
			}

			# Match types
			if (isset($o_row->season_match_type) and !$o_type_builder->IsDone($o_row->season_match_type))
			{
				$o_season->MatchTypes()->Add((int)$o_row->season_match_type);
			}
		}
		# store final season
		if ($o_season != null)
		{
			$this->Add($o_season);
		}

		return true;
	}

	/**
	 * Adds season summary and competition data to an existing season
	 *
	 * @param Season $season
	 * @param object $row
	 */
	private function BuildSeason(Season $season, $row)
	{
		if (isset($row->season_id))
		{
			$season->SetId($row->season_id);
			$season->SetName($row->season_name);
			if (isset($row->start_year)) $season->SetStartYear($row->start_year);
			if (isset($row->end_year)) $season->SetEndYear($row->end_year);
			if (isset($row->is_latest)) $season->SetIsLatest($row->is_latest);
			if (isset($row->intro)) $season->SetIntro($row->intro);
			if (isset($row->results)) $season->SetResults($row->results);
			if (isset($row->show_table)) $season->SetShowTable($row->show_table);
			if (isset($row->show_runs_scored)) $season->SetShowTableRunsScored($row->show_runs_scored);
			if (isset($row->show_runs_conceded)) $season->SetShowTableRunsConceded($row->show_runs_conceded);
			if (isset($row->short_url)) $season->SetShortUrl($row->short_url);
			if (isset($row->date_added)) $season->SetDateAdded($row->date_added);
			if (isset($row->date_changed)) $season->SetDateUpdated($row->date_changed);

			$comp = new Competition($this->GetSettings());
			if (isset($row->competition_id)) $comp->SetId($row->competition_id);
			if (isset($row->competition_name)) $comp->SetName($row->competition_name);
			if (isset($row->competition_short_url)) $comp->SetShortUrl($row->competition_short_url);
			if (isset($row->notification_email)) $comp->SetNotificationEmail($row->notification_email);
			if (isset($row->player_type_id)) $comp->SetPlayerType($row->player_type_id);
			$season->SetCompetition($comp);
		}
	}

	/**
	 * Checks whether a season already exists
	 *
	 * @param int $competition_id
	 * @param int $start_year
	 * @param int $end_year
	 * @return int season id, or false
	 */
	public function CheckIfSeasonExists($competition_id, $start_year, $end_year)
	{
		if (!is_numeric($competition_id) or !is_numeric($start_year) or !is_numeric($end_year)) return false;

		$result = $this->GetDataConnection()->query("SELECT season_id FROM " . $this->GetSettings()->GetTable('Season') . " WHERE competition_id = " . Sql::ProtectNumeric($competition_id) . " AND start_year = " . Sql::ProtectNumeric($start_year) . " AND end_year = " . Sql::ProtectNumeric($end_year));
		if ($row = $result->fetch())
		{
			$result->closeCursor();
            return (int)$row->season_id;
		}
		else
		{
			$result->closeCursor();
            return false;
		}
	}

	/**
	 * @return int
	 * @param Season $o_season
	 * @desc Save the supplied season to the database, and return the id
	 */
	function SaveSeason($o_season)
	{
		/* @var $o_result MySQlRawData */

		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$new_short_url = $o_url_manager->EnsureShortUrl($o_season);

		# build query
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_team_season = $this->GetSettings()->GetTable('TeamSeason');
		$s_rules = $this->GetSettings()->GetTable('SeasonRule');
		$s_smt = $this->GetSettings()->GetTable('SeasonMatchType');
		$s_points = $this->GetSettings()->GetTable('PointsAdjustment');

		$o_competition = $o_season->GetCompetition();
		$i_comp_id = null;
		if (is_object($o_competition))
		{
			$i_comp_id = $o_competition->GetId();
		}

		# if no id, it's new; otherwise update
		if ($o_season->GetId())
		{
			$s_sql = 'UPDATE ' . $s_season . ' SET ' .
			"season_name = " . Sql::ProtectString($this->GetDataConnection(), $o_season->GetName()) . ", " .
			'start_year = ' . Sql::ProtectNumeric($o_season->GetStartYear()) . ', ' .
			'end_year = ' . Sql::ProtectNumeric($o_season->GetEndYear()) . ', ' .
			"intro = " . Sql::ProtectString($this->GetDataConnection(), $o_season->GetIntro()) . ", " .
			"results = " . Sql::ProtectString($this->GetDataConnection(), $o_season->GetResults()) . ", " .
			'show_table = ' . Sql::ProtectBool($o_season->GetShowTable()) . ', ' .
			'show_runs_scored = ' . Sql::ProtectBool($o_season->GetShowTableRunsScored()) . ', ' .
			'show_runs_conceded = ' . Sql::ProtectBool($o_season->GetShowTableRunsConceded()) . ', ' .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_season->GetShortUrl()) . ", " .
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE season_id = ' . Sql::ProtectNumeric($o_season->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);

			# Update match types
			$s_sql = 'DELETE FROM ' . $s_smt . ' WHERE season_id = ' . Sql::ProtectNumeric($o_season->GetId());
			$this->GetDataConnection()->query($s_sql);

			while ($o_season->MatchTypes()->MoveNext())
			{
				# build query
				$s_sql = 'INSERT INTO ' . $s_smt . ' SET ' .
				'match_type = ' . Sql::ProtectNumeric($o_season->MatchTypes()->GetItem()) . ', ' .
				'season_id = ' . Sql::ProtectNumeric($o_season->GetId()) . ', ' .
				'date_added = ' . Sql::ProtectNumeric(gmdate('U'));

				# run query
				$this->GetDataConnection()->query($s_sql);
			}

			# Update season rules
			$s_sql = 'DELETE FROM ' . $s_rules . ' WHERE season_id = ' . Sql::ProtectNumeric($o_season->GetId());
			$this->GetDataConnection()->query($s_sql);

			$o_season->PossibleResults()->ResetCounter();
			while($o_season->PossibleResults()->MoveNext())
			{
				$o_mr = $o_season->PossibleResults()->GetItem();
				/* @var $o_mr MatchResult */

				$s_sql = 'INSERT INTO ' . $s_rules . ' SET ' .
				'season_id = ' . Sql::ProtectNumeric($o_season->GetId()) . ', ' .
				'match_result_id = ' . Sql::ProtectNumeric($o_mr->GetResultType()) . ', ' .
				'home_points = ' . Sql::ProtectNumeric($o_mr->GetHomePoints()) . ', ' .
				'away_points = ' . Sql::ProtectNumeric($o_mr->GetAwayPoints()) . ', ' .
				'date_added = ' . gmdate('U') . ', ' .
				'date_changed = ' . gmdate('U');

				$this->GetDataConnection()->query($s_sql);
			}

			# Update points adjustments
			$s_sql = 'DELETE FROM ' . $s_points . ' WHERE season_id = ' . Sql::ProtectNumeric($o_season->GetId());
			$this->GetDataConnection()->query($s_sql);

			if ($o_season->PointsAdjustments()->GetCount())
			{
				foreach ($o_season->PointsAdjustments() as $o_point)
				{
					/* @var $o_point PointsAdjustment */
					$s_sql = 'INSERT INTO ' . $s_points . ' SET ' .
					'points = ' . Sql::ProtectNumeric($o_point->GetPoints()) . ', ' .
					'team_id = ' . Sql::ProtectNumeric($o_point->GetTeam()->GetId()) . ', ' .
					'season_id = ' . Sql::ProtectNumeric($o_season->GetId()) . ', ' .
					'reason = ' . Sql::ProtectString($this->GetDataConnection(), $o_point->GetReason()) . ', ' .
					'date_added = ' . Sql::ProtectNumeric($o_point->GetDate());

					$this->GetDataConnection()->query($s_sql);
				}
			}

			# Update teams
			$s_sql = 'DELETE FROM ' . $s_team_season . ' WHERE season_id = ' . Sql::ProtectNumeric($o_season->GetId());
			$this->GetDataConnection()->query($s_sql);

			$a_teams = $o_season->GetTeams();
			foreach ($a_teams as $o_team)
			{
				$b_withdrawn_from_league = is_object($o_season->TeamsWithdrawnFromLeague()->GetItemByProperty('GetId', $o_team->GetId()));

				# build query
				$s_sql = 'INSERT INTO ' . $s_team_season . ' SET ' .
				'team_id = ' . Sql::ProtectNumeric($o_team->GetId()) . ', ' .
				'season_id = ' . Sql::ProtectNumeric($o_season->GetId()) . ', ' .
				'withdrawn_league ' . Sql::ProtectBool($b_withdrawn_from_league, false, true) . ', ' .
				'date_added = ' . Sql::ProtectNumeric(gmdate('U'));

				# run query
				$this->GetDataConnection()->query($s_sql);
			}

		}
		else
		{
			$s_sql = 'INSERT INTO ' . $s_season . ' SET ' .
			'competition_id = ' . Sql::ProtectNumeric($i_comp_id, true) . ', ' .
			"season_name = " . Sql::ProtectString($this->GetDataConnection(), $o_season->GetName()) . ", " .
			'start_year = ' . Sql::ProtectNumeric($o_season->GetStartYear()) . ', ' .
			'end_year = ' . Sql::ProtectNumeric($o_season->GetEndYear()) . ', ' .
			"intro = " . Sql::ProtectString($this->GetDataConnection(), $o_season->GetIntro()) . ", " .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_season->GetShortUrl()) . ", " .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U');

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$o_season->SetId($this->GetDataConnection()->insertID());

			# Since this is a new season, save time by starting off with the teams from the previous season,
			# excluding those marked as not playing any more
			$s_sql = "SELECT team.team_id " .
			'FROM (' . $s_season . ' INNER JOIN ' . $s_team_season . ' ON ' . $s_season . '.season_id = ' . $s_team_season . '.season_id) ' .
			"INNER JOIN nsa_team AS team ON $s_team_season.team_id = team.team_id " .
			'WHERE ' . $s_season . '.competition_id = ' . Sql::ProtectNumeric($i_comp_id) . ' AND ' . $s_season . ".is_latest = 1 AND team.active = 1";

			$o_result = $this->GetDataConnection()->query($s_sql);

			if (!is_null($o_result))
			{
				while ($o_row = $o_result->fetch())
				{
					$s_sql = 'INSERT INTO ' . $s_team_season . ' SET ' .
					'team_id = ' . Sql::ProtectNumeric($o_row->team_id) . ', ' .
					'season_id = ' . Sql::ProtectNumeric($o_season->GetId()) . ', ' .
					'date_added = ' . Sql::ProtectNumeric(gmdate('U'));

					$this->GetDataConnection()->query($s_sql);
				}
			}

			# ...match types from the previous season too
			$s_sql = 'SELECT match_type ' .
			'FROM ' . $s_season . ' INNER JOIN ' . $s_smt . ' ON ' . $s_season . '.season_id = ' . $s_smt . '.season_id ' .
			'WHERE ' . $s_season . '.competition_id = ' . Sql::ProtectNumeric($i_comp_id) . ' AND ' . $s_season . '.is_latest = 1';

			$o_result = $this->GetDataConnection()->query($s_sql);

			if (!is_null($o_result))
			{
				while ($o_row = $o_result->fetch())
				{
					$s_sql = 'INSERT INTO ' . $s_smt . ' SET ' .
					'match_type = ' . Sql::ProtectNumeric($o_row->match_type) . ', ' .
					'season_id = ' . Sql::ProtectNumeric($o_season->GetId()) . ', ' .
					'date_added = ' . gmdate('U');

					$this->GetDataConnection()->query($s_sql);
				}
			}

			# ...and league table settings
			$s_sql = "SELECT $s_season.show_table,$s_season.show_runs_scored, $s_season.show_runs_conceded, $s_rules.match_result_id, $s_rules.home_points, $s_rules.away_points " .
			"FROM $s_season LEFT JOIN $s_rules ON $s_season.season_id = $s_rules.season_id " .
			"WHERE $s_season.competition_id = " . Sql::ProtectNumeric($i_comp_id) . " AND $s_season.is_latest = 1";

			$o_result = $this->GetDataConnection()->query($s_sql);

			if (!is_null($o_result))
			{
				$show_table_copied = false;
				while ($o_row = $o_result->fetch())
				{
					if (!$show_table_copied)
					{
						$s_sql = "UPDATE $s_season SET
						show_table = $o_row->show_table,
						show_runs_scored = $o_row->show_runs_scored,
						show_runs_conceded = $o_row->show_runs_conceded
						WHERE season_id = " . Sql::ProtectNumeric($o_season->GetId());
						$this->GetDataConnection()->query($s_sql);
						$show_table_copied = true;
					}


					if (!is_null($o_row->match_result_id))
					{
						$s_sql = 'INSERT INTO ' . $s_rules . ' SET ' .
						'season_id = ' . Sql::ProtectNumeric($o_season->GetId()) . ', ' .
						'match_result_id = ' . Sql::ProtectNumeric($o_row->match_result_id) . ', ' .
						'home_points = ' . Sql::ProtectNumeric($o_row->home_points) . ', ' .
						'away_points = ' . Sql::ProtectNumeric($o_row->away_points) . ', ' .
						'date_added = ' . gmdate('U') . ', ' .
						'date_changed = ' . gmdate('U');
						$this->GetDataConnection()->query($s_sql);
					}


				}
			}

		}

		# Update latest season
		if ($i_comp_id != null) $this->UpdateLatestSeason($i_comp_id);

		# Regenerate short URLs
		if (is_object($new_short_url))
		{
			$new_short_url->SetParameterValuesFromObject($o_season);
			$o_url_manager->Save($new_short_url);
		}
		unset($o_url_manager);

		return $o_season->GetId();

	}

	/**
	 * Recalculates the latest season for the given competition
	 *
	 * @param int $i_competition_id
	 */
	private function UpdateLatestSeason($i_competition_id)
	{
		if (!is_null($i_competition_id))
		{
			$s_season = $this->GetSettings()->GetTable('Season');

			$s_sql = 'UPDATE ' . $s_season . ' SET ' .
			'is_latest = 0 ' .
			'WHERE competition_id = ' . Sql::ProtectNumeric($i_competition_id);

			$this->GetDataConnection()->query($s_sql);

			$s_sql = 'SELECT season_id ' .
			'FROM ' . $s_season . ' ' .
			'WHERE competition_id = ' . Sql::ProtectNumeric($i_competition_id) . ' ' .
			'ORDER BY end_year DESC, start_year DESC LIMIT 0,1';

			$o_result = $this->GetDataConnection()->query($s_sql);
			$o_row = $o_result->fetch();

			if (is_object($o_row))
			{
				$s_sql = 'UPDATE ' . $s_season . ' SET ' .
				'is_latest = 1 ' .
				'WHERE season_id = ' . $o_row->season_id;

				$this->GetDataConnection()->query($s_sql);
			}
		}
	}

	/**
	 * Ensures the specified team is registered as part of the given season
	 *
	 * @param int $team_id
	 * @param int $season_id
	 */
	public function EnsureTeamIsInSeason($team_id, $season_id)
	{
		if (!$team_id or !is_numeric($team_id)) return;
		if (!$season_id or !is_numeric($season_id)) return;

		$teams_in_season = $this->GetSettings()->GetTable('TeamSeason');
		$sql = "SELECT team_id FROM $teams_in_season WHERE team_id = " . Sql::ProtectNumeric($team_id) . " AND season_id = " . Sql::ProtectNumeric($season_id);
		$result = $this->GetDataConnection()->query($sql);
		if (!$result->fetch())
		{
			$sql = 'INSERT INTO ' . $teams_in_season . ' SET ' .
			'team_id = ' . Sql::ProtectNumeric($team_id) . ', ' .
			'season_id = ' . Sql::ProtectNumeric($season_id) . ', ' .
			'date_added = ' . Sql::ProtectNumeric(gmdate('U'));

			$this->LoggedQuery($sql);
		}
		$result->closeCursor();
	}



	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the db the seasons matching the supplied ids
	 */
	function Delete($a_ids)
	{
		# check parameter
		$this->ValidateNumericArray($a_ids);

		# build query
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_season_link = $this->GetSettings()->GetTable('TeamSeason');
		$s_rule = $this->GetSettings()->GetTable('SeasonRule');
		$s_smt = $this->GetSettings()->GetTable('SeasonMatchType');
		$s_points = $this->GetSettings()->GetTable('PointsAdjustment');
		$s_match = $this->GetSettings()->GetTable('SeasonMatch');

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_ids = join(', ', $a_ids);
		$s_sql = "SELECT short_url FROM $s_season WHERE season_id IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		foreach ($a_ids as $i_season_id)
		{
			$i_season_id = Sql::ProtectNumeric($i_season_id);

			# Find out what competition we're dealing with
			$s_sql = 'SELECT competition_id FROM ' . $s_season . ' WHERE season_id = ' . $i_season_id;
			$o_result = $this->GetDataConnection()->query($s_sql);
			$o_row = $o_result->fetch();
			if (is_object($o_row))
			{
				$i_competition_id = $o_row->competition_id;

				# Delete matches
				$s_sql = 'DELETE FROM ' . $s_match . ' WHERE season_id = ' . $i_season_id;
				$this->GetDataConnection()->query($s_sql);

				# Delete match types
				$s_sql = 'DELETE FROM ' . $s_smt . ' WHERE season_id = ' . $i_season_id;
				$this->GetDataConnection()->query($s_sql);

				# Delete points adjustments
				$s_sql = 'DELETE FROM ' . $s_points . ' WHERE season_id = ' . $i_season_id;
				$this->GetDataConnection()->query($s_sql);

				# delete rules
				$s_sql = 'DELETE FROM ' . $s_rule . ' WHERE season_id = ' . $i_season_id;
				$this->GetDataConnection()->query($s_sql);

				# delete relationships to teams
				$s_sql = 'DELETE FROM ' . $s_season_link . ' WHERE season_id = ' . $i_season_id;
				$this->GetDataConnection()->query($s_sql);

				# delete season(s)
				$s_sql = 'DELETE FROM ' . $s_season . ' WHERE season_id = ' . $i_season_id;
				$this->GetDataConnection()->query($s_sql);

				# update latest season
				$this->UpdateLatestSeason($i_competition_id);
			}
		}
		unset($o_result);
	}
}
?>