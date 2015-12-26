<?php
require_once('data/data-manager.class.php');
require_once('stoolball/competition.class.php');
require_once('stoolball/season.class.php');

class CompetitionManager extends DataManager
{
	private $b_exclude_inactive;
	private $a_player_types = array();

	/**
	 * @return CompetitionManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Read and write Competitions
	 */
	function CompetitionManager(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'Competition';
	}

	/**
	 * Sets whether to exclude competitions which don't exist any more
	 *
	 * @param bool $b_exclude
	 */
	public function SetExcludeInactive($b_exclude)
	{
		$this->b_exclude_inactive = (bool)$b_exclude;
	}

	/**
	 * Gets whether to exclude competitions which don't exist any more
	 *
	 * @return bool
	 */
	public function GetExcludeInactive()
	{
		return $this->b_exclude_inactive;
	}

	/**
	 * Restricts results to competitions of the specified player type(s)
	 *
	 * @param int[] $types
	 */
	public function FilterByPlayerType($types)
	{
		if (is_array($types)) $this->a_player_types = $types;
	}

	/**
	 * Gets the seasons in the supplied competitions, ready to add to the dropdown list
	 *
	 * @param Competition[] $a_competitions
	 */
	public static function GetSeasonsFromCompetitions($a_competitions)
	{
		$a_seasons = array();
		if (is_array($a_competitions))
		{
			foreach ($a_competitions as $o_obj)
			{
				if ($o_obj instanceof Competition)
				{
					$a_seasons = array_merge($a_seasons, $o_obj->GetSeasons());
				}
			}
		}
		return $a_seasons;
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @param int[] $a_season_ids
	 * @desc Read from the db the competitions matching the supplied ids
	 */
	function ReadById($a_ids=null, $a_season_ids=null)
	{
		/* @var $o_settings SiteSettings */
		if (!is_null($a_ids)) $this->ValidateNumericArray($a_ids);
		if (!is_null($a_season_ids)) $this->ValidateNumericArray($a_season_ids);

		# build query
		$s_comp = $this->o_settings->GetTable('Competition');
		$s_season_link = $this->GetSettings()->GetTable('TeamSeason');
		$s_season = $this->GetSettings()->GetTable('Season');
		$s_points = $this->GetSettings()->GetTable('PointsAdjustment');
		$s_matchtypes = $this->GetSettings()->GetTable('SeasonMatchType');

		$s_sql = "SELECT $s_comp.competition_id, $s_comp.competition_name, $s_comp.category_id, $s_comp.intro, $s_comp.contact, $s_comp.notification_email, 
		$s_comp.website, $s_comp.short_url, $s_comp.active, $s_comp.player_type_id, $s_comp.players_per_team, $s_comp.overs, $s_comp.update_search,
		$s_season_link.withdrawn_league,
		$s_season.season_id, $s_season.season_name, $s_season.is_latest, $s_season.start_year,	$s_season.end_year, 
		$s_season.intro AS season_intro, $s_season.results, $s_season.show_table, $s_season.show_runs_scored, $s_season.show_runs_conceded, $s_season.short_url AS season_short_url, 
		team.team_id, team.team_name, team.ground_id, team.short_url AS team_short_url, " .
		$s_points . '.point_id, ' . $s_points . '.points, ' . $s_points . '.reason, ' . $s_points . '.team_id AS points_team_id, ' . $s_points . '.date_added AS points_date, ' .
		$s_matchtypes . '.match_type ' .
		'FROM (((((' . $s_comp . ' INNER JOIN ' . $s_season . ' ON ' . $s_comp . '.competition_id = ' . $s_season . '.competition_id) ' .
		'LEFT OUTER JOIN ' . $s_season_link . ' ON ' . $s_season . '.season_id = ' . $s_season_link . '.season_id) ' .
		'LEFT OUTER JOIN nsa_team AS team ON ' . $s_season_link . '.team_id = team.team_id) ' .
		'LEFT OUTER JOIN ' . $s_points . ' ON ' . $s_season . '.season_id = ' . $s_points . '.season_id AND team.team_id = ' . $s_points . '.team_id) ' .
		'LEFT OUTER JOIN ' . $s_matchtypes . ' ON ' . $s_season . '.season_id = ' . $s_matchtypes . '.season_id) ';

		# Limit to latest or specified season
		$s_where = '';
		if ($this->GetExcludeInactive()) $s_where = $this->SqlAddCondition($s_where, $s_comp . '.active = 1');
		if (is_array($a_season_ids) and count($a_season_ids) > 0)
		{
			$s_where = $this->SqlAddCondition($s_where, $s_season . '.season_id IN (' . join(', ', $a_season_ids) . ') ');
		}
		else
		{
			$s_where = $this->SqlAddCondition($s_where, $s_season . '.is_latest = 1');
		}

		# Limit to specific competitions, if specified
		if (is_array($a_ids)) $s_where = $this->SqlAddCondition($s_where, $s_comp . '.competition_id IN (' . join(', ', $a_ids) . ') ');
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		# sort competitions
		$s_sql .= 'ORDER BY ' . $s_comp . '.competition_name ASC, ' . $s_season . '.end_year DESC, ' . $s_season . '.season_id, team.team_name ASC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Competition objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}


	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the Competitions
	 */
	function ReadAllSummaries()
	{
		$this->ReadSummariesById();
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the Competitions matching the supplied ids
	 */
	function ReadSummariesById($a_ids=null)
	{
		/* @var $o_settings SiteSettings */
		$o_settings = $this->o_settings;

		# build query
		$s_comp = $o_settings->GetTable('Competition');
		$s_season = $o_settings->GetTable('Season');

		$s_sql = 'SELECT ' . $s_comp . '.competition_id, ' . $s_comp . '.competition_name, ' . $s_comp . '.short_url, ' . $s_comp . '.active, ' . $s_comp . '.player_type_id, ' .
		$s_season . '.season_id, ' . $s_season . '.season_name, ' . $s_season . '.is_latest, ' . $s_season . '.start_year, ' . $s_season . '.end_year, ' . $s_season . '.short_url AS season_short_url ' .
		'FROM ' . $s_comp . ' INNER JOIN ' . $s_season . ' ON ' . $s_comp . '.competition_id = ' . $s_season . '.competition_id ';

		# limit to specific competitions, if specified
		$s_where = '';
		if ($this->GetExcludeInactive()) $s_where = $this->SqlAddCondition($s_where, $s_comp . '.active = 1');
		if (is_array($a_ids)) $s_where = $this->SqlAddCondition($s_where, $s_comp . '.competition_id IN (' . join(', ', $a_ids) . ') ');

		# limit by date, if specified
		if ($this->FilterByDateStartValue() > 0)
		{
			$year = gmdate('Y', $this->FilterByDateStartValue());
			$s_where = $this->SqlAddCondition($s_where, 'start_year >= ' . Sql::ProtectNumeric($year));
		}
		if ($this->FilterByDateEndValue() > 0)
		{
			$year = gmdate('Y', $this->FilterByDateEndValue());
			$s_where = $this->SqlAddCondition($s_where, 'end_year <= ' . Sql::ProtectNumeric($year));
		}
		if (count($this->a_player_types))
		{
			if ($this->ValidateNumericArray($this->a_player_types))
			{
				$s_where = $this->SqlAddCondition($s_where, "$s_comp.player_type_id IN (" . join(', ', $this->a_player_types) . ') ');
			}
		}
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		# sort competitions
		$s_sql .= 'ORDER BY ' . $s_comp . '.active DESC, ' . $s_comp . '.competition_name ASC, ' . $s_season . '.end_year DESC, ' . $s_season . '.season_id';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Competition objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the Competitions matching the supplied ids, sorted by and including category
	 */
	public function ReadCompetitionsInCategories($a_ids=null)
	{
		/* @var $o_settings SiteSettings */
		$o_settings = $this->o_settings;

		# build query
		$s_comp = $o_settings->GetTable('Competition');
		$s_season = $o_settings->GetTable('Season');
		$s_cat = $o_settings->GetTable('Category');

		$s_sql = 'SELECT ' . $s_comp . '.competition_id, ' . $s_comp . '.competition_name, ' . $s_comp . '.short_url, ' . $s_comp . '.active, ' . $s_comp . '.player_type_id, ' .
		$s_season . '.season_id, ' . $s_season . '.season_name, ' . $s_season . '.is_latest, ' . $s_season . '.start_year, ' . $s_season . '.end_year, ' . $s_season . '.short_url AS season_short_url, ' .
		"$s_cat.id AS category_id, $s_cat.name AS category_name, $s_cat.code " .
		'FROM (' . $s_comp . ' INNER JOIN ' . $s_season . ' ON ' . $s_comp . '.competition_id = ' . $s_season . '.competition_id) ' .
		"LEFT OUTER JOIN $s_cat ON $s_comp.category_id = $s_cat.id ";

		# limit to specific competitions, if specified
		$s_where = '';
		if ($this->GetExcludeInactive()) $s_where = $this->SqlAddCondition($s_where, $s_comp . '.active = 1');
		if (is_array($a_ids)) $s_where = $this->SqlAddCondition($s_where, $s_comp . '.competition_id IN (' . join(', ', $a_ids) . ') ');
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		# sort competitions
		$s_sql .= "ORDER BY $s_cat.sort_override ASC, $s_cat.name ASC, $s_comp.active DESC, $s_comp.competition_name ASC, $s_season.end_year DESC, $s_season.season_id";

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Competition objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * Reads the notification email address for a given competition
	 *
	 * @param Competition $o_comp
	 * @return string
	 */
	public function ReadNotificationEmail(Competition $o_comp)
	{
		$s_email = '';

		$s_sql = 'SELECT notification_email FROM ' . $this->GetSettings()->GetTable('Competition') . ' WHERE competition_id = ' . Sql::ProtectNumeric($o_comp->GetId());
		$o_result = $this->GetDataConnection()->query($s_sql);
		if (is_object($o_result))
		{
			$o_row = $o_result->fetch();
			$s_email = $o_row->notification_email;
		}
		$o_result->closeCursor();
		unset($o_result);

		return $s_email;
	}


	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		/* @var $o_competition Competition */

		# use CollectionBuilder to handle duplicates
		$o_comp_builder = new CollectionBuilder();
		$o_season_builder = new CollectionBuilder();
		$o_team_builder = new CollectionBuilder();
		$o_points_builder = new CollectionBuilder();
		$o_matchtype_builder = new CollectionBuilder();
		$o_competition = null;
		$o_season = null;

		while($o_row = $o_result->fetch())
		{
			# check whether this is a new competition
			if (!$o_comp_builder->IsDone($o_row->competition_id))
			{
				# store any exisiting competition and reset
				if ($o_competition != null)
				{
					if ($o_season != null) $o_competition->AddSeason($o_season, true);
					$o_season = null;
					$o_matchtype_builder->Reset();

					$this->Add($o_competition);
					$o_season_builder->Reset();
				}

				# create the new competition
				$o_competition = new Competition($this->o_settings);
				$o_competition->SetId($o_row->competition_id);
				$o_competition->SetName($o_row->competition_name);
				if (isset($o_row->intro)) $o_competition->SetIntro($o_row->intro);
				if (isset($o_row->contact)) $o_competition->SetContact($o_row->contact);
				if (isset($o_row->notification_email)) $o_competition->SetNotificationEmail($o_row->notification_email);
				if (isset($o_row->website)) $o_competition->SetWebsiteUrl($o_row->website);
				if (isset($o_row->short_url)) $o_competition->SetShortUrl($o_row->short_url);
				if (isset($o_row->active)) $o_competition->SetIsActive($o_row->active);
				if (isset($o_row->players_per_team)) $o_competition->SetMaximumPlayersPerTeam($o_row->players_per_team);
				if (isset($o_row->overs)) $o_competition->SetOvers($o_row->overs);
				$o_competition->SetPlayerType($o_row->player_type_id);
                if (isset($o_row->update_search) and $o_row->update_search == 1) $o_competition->SetSearchUpdateRequired();
        
				if (isset($o_row->category_id) && !is_null($o_row->category_id))
				{
					$cat = new Category();
					$cat->SetId($o_row->category_id);
					if (isset($o_row->category_name)) $cat->SetName($o_row->category_name);
					if (isset($o_row->code)) $cat->SetUrl($o_row->code);
					$o_competition->SetCategory($cat);
				}
			}

			# Seasons are the first cause of multiple rows (first in sort order after competition)
			if (isset($o_row->season_id))
			{
				if (!$o_season_builder->IsDone($o_row->season_id))
				{
					if ($o_season != null)
					{
						$o_competition->AddSeason($o_season, true);
					}

					$o_season = new Season($this->o_settings);
					$o_season->SetId($o_row->season_id);
					$o_season->SetName($o_row->season_name);
					$o_season->SetIsLatest($o_row->is_latest);
					$o_season->SetStartYear($o_row->start_year);
					$o_season->SetEndYear($o_row->end_year);
					if (isset($o_row->season_intro)) $o_season->SetIntro($o_row->season_intro);
					if (isset($o_row->results)) $o_season->SetResults($o_row->results);
					if (isset($o_row->show_table)) $o_season->SetShowTable($o_row->show_table);
					if (isset($o_row->show_runs_scored)) $o_season->SetShowTableRunsScored($o_row->show_runs_scored);
					if (isset($o_row->show_runs_conceded)) $o_season->SetShowTableRunsConceded($o_row->show_runs_conceded);
					if (isset($o_row->season_short_url)) $o_season->SetShortUrl($o_row->season_short_url);
				}

				# Team only present if there is a season
				if (isset($o_row->team_id))
				{
					if (!$o_team_builder->IsDone($o_row->team_id))
					{
						if (isset($o_team)) unset($o_team);
						$o_team = new Team($this->GetSettings());
						$o_team->SetId($o_row->team_id);
						$o_team->SetName($o_row->team_name);
						$o_team->GetGround()->SetId($o_row->ground_id);
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

				# Match types come with a season
				if (isset($o_row->match_type) and !$o_matchtype_builder->IsDone($o_row->match_type))
				{
					$o_season->MatchTypes()->Add((int)$o_row->match_type);
				}
			}
		}
		# store final competition
		if ($o_competition != null)
		{
			if ($o_season != null) $o_competition->AddSeason($o_season, true);
			$this->Add($o_competition);
		}
	}


	/**
	 * @return int
	 * @param Competition $o_competition
	 * @desc Save the supplied Competition to the database, and return the id
	 */
	function SaveCompetition($o_competition)
	{
		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$new_short_url = $o_url_manager->EnsureShortUrl($o_competition);

		# build query
		$category_id = (is_null($o_competition->GetCategory()) ? null : $o_competition->GetCategory()->GetId());

		# if no id, it's a new Competition; otherwise update the Competition
		$is_new = !$o_competition->GetId();
		if ($is_new)
		{
			$s_sql = 'INSERT INTO ' . $this->GetSettings()->GetTable('Competition') . ' SET ' .
			"competition_name = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetName()) . ", " .
			"category_id = " . Sql::ProtectNumeric($category_id, true, false) . ', ' .
			"intro = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetIntro()) . ", " .
			"contact = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetContact()) . ", " .
			"notification_email = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetNotificationEmail()) . ", " .
			"website = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetWebsiteUrl()) . ", " .
			'active = ' . Sql::ProtectBool($o_competition->GetIsActive()) . ', ' .
			'player_type_id = ' . Sql::ProtectNumeric($o_competition->GetPlayerType()) . ", " .
			'players_per_team = ' . Sql::ProtectNumeric($o_competition->GetMaximumPlayersPerTeam()) . ", " .
			'overs = ' . Sql::ProtectNumeric($o_competition->GetOvers()) . ", " .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetShortUrl()) . ", " .
            "update_search = 1, " . 
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U');

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$o_competition->SetId($this->GetDataConnection()->insertID());

			# create a default season
			require_once('stoolball/season-manager.class.php');
			$o_season = new Season($this->GetSettings());
			$o_season->SetCompetition($o_competition);
			$o_season->SetStartYear(gmdate('Y', gmdate('U')));
			$o_season->SetEndYear(gmdate('Y', gmdate('U')));
			$o_season->SetIsLatest(true);
			$o_season_mgr = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
			$o_season_mgr->SaveSeason($o_season);
			unset($o_season_mgr);
		}
		else
		{
			$s_sql = 'UPDATE ' . $this->GetSettings()->GetTable('Competition') . ' SET ' .
			"competition_name = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetName()) . ", " .
			"category_id = " . Sql::ProtectNumeric($category_id, true, false) . ', ' .
			"intro = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetIntro()) . ", " .
			"contact = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetContact()) . ", " .
			"notification_email = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetNotificationEmail()) . ", " .
			"website = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetWebsiteUrl()) . ", " .
			'active = ' . Sql::ProtectBool($o_competition->GetIsActive()) . ', ' .
			'player_type_id = ' . Sql::ProtectNumeric($o_competition->GetPlayerType()) . ", " .
			'players_per_team = ' . Sql::ProtectNumeric($o_competition->GetMaximumPlayersPerTeam()) . ", " .
			'overs = ' . Sql::ProtectNumeric($o_competition->GetOvers()) . ", " .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_competition->GetShortUrl()) . ", " .
            "update_search = 1, " . 
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE competition_id = ' . Sql::ProtectNumeric($o_competition->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);
		}

        # Request search update for related objects which mention the competition        
        $seasons = array();
        $sql = "SELECT season_id FROM nsa_season WHERE competition_id = " . SQL::ProtectNumeric($o_competition->GetId(),false);
        $result = $this->GetDataConnection()->query($sql);
        while ($row = $result->fetch())
        {
            $seasons[] = $row->season_id;
        }
        $result->closeCursor();
        $seasons = implode(", ",$seasons);
        
        $sql = "UPDATE nsa_team SET update_search = 1 WHERE team_id IN 
                ( 
                    SELECT team_id FROM nsa_team_season WHERE season_id IN ($seasons)
                )";
        $this->GetDataConnection()->query($sql);

        
        $sql = "UPDATE nsa_match SET update_search = 1 WHERE match_id IN 
                ( 
                    SELECT match_id FROM nsa_season_match WHERE season_id IN ($seasons)
                )";
        $this->GetDataConnection()->query($sql);

		# Regenerate short URLs
		if (is_object($new_short_url))
		{
			$new_short_url->SetParameterValuesFromObject($o_competition);
			$o_url_manager->Save($new_short_url);

			# season URLs are generated from the competition, so regenerate those too
			if (!$is_new)
			{
				$o_season_mgr = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
				$o_season_mgr->ReadByCompetitionId(array($o_competition->GetId()));
				$seasons = $o_season_mgr->GetItems();
				unset($o_season_mgr);
				foreach ($seasons as $season)
				{
					/* @var $season Season */
					$new_short_url = $o_url_manager->EnsureShortUrl($season, true);

					if (is_object($new_short_url))
					{
						$s_sql = "UPDATE " . $this->GetSettings()->GetTable('Season') .
					" SET short_url = " . Sql::ProtectString($this->GetDataConnection(), $new_short_url->GetShortUrl()) .
					" WHERE season_id = " . Sql::ProtectNumeric($season->GetId());
						$this->GetDataConnection()->query($s_sql);

						$new_short_url->SetParameterValuesFromObject($season);
						$o_url_manager->Save($new_short_url);
					}
				}
			}

		}
		unset($o_url_manager);

		return $o_competition->GetId();
	}

    /**
     * Reset the flag which indicates that search needs to be updated
     * @param $competition_id int
     */
    public function SearchUpdated($competition_id) 
    {
        if (!is_integer($competition_id)) throw new Exception("competition_id must be an integer");
        $sql = "UPDATE nsa_competition SET update_search = 0 WHERE competition_id = " . SQL::ProtectNumeric($competition_id, false);
        $this->GetDataConnection()->query($sql);
    }

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the db the Competitions matching the supplied ids
	 */
	function Delete($a_ids)
	{
		# check paramter
		if (!is_array($a_ids)) die('No Competitions to delete');

		# build query
		$s_comp = $this->o_settings->GetTable('Competition');
		$s_season = $this->o_settings->GetTable('Season');
		$s_ids = join(', ', $a_ids);

		# get the seasons and use SeasonManager to deal with them
		$season_ids = array();
		$s_sql = 'SELECT season_id FROM ' . $s_season . ' WHERE competition_id IN (' . $s_ids . ') ';
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch()) { $season_ids[] = $row->season_id; }
		$result->closeCursor();

		require_once('stoolball/season-manager.class.php');
		$season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
		$season_manager->Delete($season_ids);
		unset($season_manager);

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_sql = "SELECT short_url FROM $s_comp WHERE competition_id IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		# delete competition(s)
		$s_sql = 'DELETE FROM ' . $s_comp . ' WHERE competition_id IN (' . $s_ids . ') ';
		$o_result = $this->GetDataConnection()->query($s_sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}
}
?>