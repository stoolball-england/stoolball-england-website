<?php
require_once('data/data-manager.class.php');
require_once('ground.class.php');
require_once("team.class.php");

class GroundManager extends DataManager
{
	private $b_filter_active;
	private $a_filter_player_types = array();
    private $filter_team_types;
    private $filter_seasons = array();
    
	/**
	 * @return GroundManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Read and write Grounds
	 */
	function __construct(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'Ground';
                
        # Exclude once-only teams by default
        $this->filter_team_types = array(Team::CLOSED_GROUP, Team::OCCASIONAL, Team::REGULAR, Team::REPRESENTATIVE);
	}

	/**
	 * Set to true to show only grounds with active teams, or false to show only grounds with former teams
	 *
	 * @param bool $b_active
	 */
	public function FilterByActive($b_active)
	{
		$this->b_filter_active = (bool)$b_active;
	}

	/**
	 * Limits queries to returning only teams which match the supplied player type ids
	 *
	 * @param int[] $a_types
	 */
	public function FilterByPlayerType($a_types)
	{
		if (is_array($a_types))
		{
			$this->ValidateNumericArray($a_types);
			$this->a_filter_player_types = $a_types;
		}
		else $this->a_filter_player_types = array();
	}

    /**
     * Limits queries to returning only teams which match the supplied team type ids
     *
     * @param int[] $a_types
     */
    public function FilterByTeamType($a_types)
    {
        if (is_array($a_types))
        {
            $this->ValidateNumericArray($a_types);
            $this->filter_team_types = $a_types;
        }
        else $this->filter_team_types = array();
    }

    /**
     * Limits queries to returning only grounds with teams playing in the supplied seasons
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
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the Clubs matching the supplied ids, or all Clubs
	 */
	function ReadById($a_ids=null)
	{
		$ground = $this->GetSettings()->GetTable('Ground');
        $by_season = count($this->filter_seasons);
        $with_teams = (isset($this->b_filter_active) or $by_season);
		
		$sql = "SELECT $ground.ground_id, $ground.directions, $ground.parking, $ground.facilities, $ground.short_url,
		$ground.saon, $ground.paon, $ground.street_descriptor, $ground.locality, $ground.town, $ground.administrative_area, $ground.postcode,
		$ground.latitude, $ground.longitude, $ground.geo_precision, $ground.date_changed, $ground.update_search ";

		if ($with_teams)
		{
			$sql .= ", team.team_name, team.short_url AS team_short_url, team.player_type_id ";
		}

		# from clause
		$sql .= "FROM $ground ";
		if ($with_teams)
        {
            $sql .= "INNER JOIN nsa_team AS team ON $ground.ground_id = team.ground_id ";
        }
        if ($by_season)
        {
            $sql .= "INNER JOIN nsa_team_season AS ts ON team.team_id = ts.team_id ";
        } 
        
		# apply filters
		$where = "";
		if (is_array($a_ids)) $where .= "AND $ground.ground_id IN (" . join(', ', $a_ids) . ') ';
		if ($with_teams) 
		{
		    # HACK: Should have separate query to request grounds with teams
		    if (isset($this->b_filter_active)) $where .= "AND team.active = " . ($this->b_filter_active ? '1' : '0') . " ";
    		if (count($this->a_filter_player_types)) $where .= "AND team.player_type_id IN (" . join(', ', $this->a_filter_player_types) . ') ';
            if (count($this->filter_team_types)) $where .= "AND team.team_type IN (" . join(",", $this->filter_team_types) . ") ";
            if ($by_season) $where .= "AND ts.season_id IN (" . join(",", $this->filter_seasons) . ") ";
		}
		
		if ($where) $sql .= "WHERE " . substr($where, 4);

		# sort grounds
		$sql .= 'ORDER BY ' . $ground . '.sort_name ASC ';
		if ($with_teams) $sql .= ", team.team_name ASC ";

		# run query
		$o_result = $this->GetDataConnection()->query($sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

    /**
     * Reads the ground for a school by matching the name and town
     */
    public function ReadGroundForSchool(School $school) {
            
        $sql = "SELECT ground_id FROM nsa_ground 
        WHERE town = " . Sql::ProtectString($this->GetDataConnection(), $school->Ground()->GetAddress()->GetTown()) . " 
        AND paon = " . Sql::ProtectString($this->GetDataConnection(), $school->Ground()->GetAddress()->GetPaon());
        
        $result = $this->GetDataConnection()->query($sql);
        if ($row = $result->fetch())
        {
            $this->ReadById(array($row->ground_id));
        }
        unset($result);        
    }

	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		# use CollectionBuilder to handle duplicates
		$o_ground_builder = new CollectionBuilder();
		$o_ground = null;

		while($row = $o_result->fetch())
		{
			# check whether this is a new ground
			if (!$o_ground_builder->IsDone($row->ground_id))
			{
				# store any exisiting ground
				if ($o_ground != null) $this->Add($o_ground);

				# create the new ground
				$o_ground = new Ground($this->o_settings);
				$o_ground->SetId($row->ground_id);
				$o_ground->SetDirections($row->directions);
				$o_ground->SetParking($row->parking);
				$o_ground->SetFacilities($row->facilities);
				$o_ground->SetShortUrl($row->short_url);
				$o_ground->SetDateUpdated($row->date_changed);
                if (isset($row->update_search) and $row->update_search == 1) $o_ground->SetSearchUpdateRequired();
        

				$o_address = $o_ground->GetAddress();
				$o_address->SetSaon($row->saon);
				$o_address->SetPaon($row->paon);
				$o_address->SetStreetDescriptor($row->street_descriptor);
				$o_address->SetLocality($row->locality);
				$o_address->SetTown($row->town);
				$o_address->SetAdministrativeArea($row->administrative_area);
				$o_address->SetPostcode($row->postcode);

				if (isset($row->latitude))
				{
					$o_address->SetGeoLocation($row->latitude, $row->longitude, $row->geo_precision);
				}

				$o_ground->SetAddress($o_address);
			}

			if (isset($row->team_name))
			{
				$team = new Team($this->GetSettings());
				$team->SetName($row->team_name);
				$team->SetShortUrl($row->team_short_url);
				$team->SetPlayerType($row->player_type_id);
				$o_ground->Teams()->Add($team);
			}
		}
		# store final ground
		if ($o_ground != null) $this->Add($o_ground);
	}


	/**
	 * @return int
	 * @param Ground $o_ground
	 * @desc Save the supplied Ground to the database, and return the id
	 */
	function SaveGround($o_ground, $save_school_ground_fields_only=false)
	{
		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$new_short_url = $o_url_manager->EnsureShortUrl($o_ground);

		# build query
		$o_address = $o_ground->GetAddress();

        $fields_not_used_by_schools = '';
        if (!$save_school_ground_fields_only) {
            $fields_not_used_by_schools = "saon = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetSaon()) . ", " .
                                          "directions = " . Sql::ProtectString($this->GetDataConnection(), $o_ground->GetDirections()) . ", " .
                                          "parking = " . Sql::ProtectString($this->GetDataConnection(), $o_ground->GetParking()) . ", " .
                                          "facilities = " . Sql::ProtectString($this->GetDataConnection(), $o_ground->GetFacilities()) . ", ";
        }

		# if no id, it's a new ground; otherwise update the ground
		if ($o_ground->GetId())
		{
			$s_sql = 'UPDATE ' . $this->GetSettings()->GetTable('Ground') . ' SET ' .
			"sort_name = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GenerateSortName()) . ", " .
			"paon = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetPaon()) . ", " .
			"street_descriptor = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetStreetDescriptor()) . ", " .
			"locality = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetLocality()) . ", " .
			"town = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetTown()) . ", " .
			"administrative_area = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetAdministrativeArea()) . ", " .
			"postcode = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetPostcode()) . ", " .
            $fields_not_used_by_schools .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_ground->GetShortUrl()) . ", " .
			'latitude' . Sql::ProtectFloat($o_ground->GetAddress()->GetLatitude(), true, true) . ', ' .
			'longitude' . Sql::ProtectFloat($o_ground->GetAddress()->GetLongitude(), true, true) . ', ' .
			'geo_precision = ' . Sql::ProtectNumeric($o_ground->GetAddress()->GetGeoPrecision(), true, false) . ', ' .
            "update_search = 1, " . 
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE ground_id = ' . Sql::ProtectNumeric($o_ground->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);
		}
		else
		{
			$s_sql = 'INSERT INTO ' . $this->GetSettings()->GetTable('Ground') . ' SET ' .
			"sort_name = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GenerateSortName()) . ", " .
			"paon = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetPaon()) . ", " .
			"street_descriptor = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetStreetDescriptor()) . ", " .
			"locality = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetLocality()) . ", " .
			"town = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetTown()) . ", " .
			"administrative_area = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetAdministrativeArea()) . ", " .
			"postcode = " . Sql::ProtectString($this->GetDataConnection(), $o_address->GetPostcode()) . ", " .
            $fields_not_used_by_schools .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_ground->GetShortUrl()) . ", " .
			'latitude' . Sql::ProtectFloat($o_ground->GetAddress()->GetLatitude(), true, true) . ', ' .
			'longitude' . Sql::ProtectFloat($o_ground->GetAddress()->GetLongitude(), true, true) . ', ' .
			'geo_precision = ' . Sql::ProtectNumeric($o_ground->GetAddress()->GetGeoPrecision(), true, false) . ', ' .
            "update_search = 1, " . 
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U');

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$o_ground->SetId($this->GetDataConnection()->insertID());
		}

        # Request search update for objects which mention the ground
        $sql = "UPDATE nsa_team SET update_search = 1 WHERE ground_id = " . SQL::ProtectNumeric($o_ground->GetId(),false);
        $this->GetDataConnection()->query($sql);

		$matches = $this->GetSettings()->GetTable("Match");
        $sql = "UPDATE $matches SET update_search = 1 WHERE ground_id = " . SQL::ProtectNumeric($o_ground->GetId(),false);
        $this->GetDataConnection()->query($sql);

        # Regenerate short URLs
		if (is_object($new_short_url))
		{
			$new_short_url->SetParameterValuesFromObject($o_ground);
			$o_url_manager->Save($new_short_url);
		}
		unset($o_url_manager);

		return $o_ground->GetId();

	}

    /**
     * Reset the flag which indicates that search needs to be updated
     * @param $ground_id int
     */
    public function SearchUpdated($ground_id) 
    {
        if (!is_integer($ground_id)) throw new Exception("ground_id must be an integer");
        $sql = "UPDATE nsa_ground SET update_search = 0 WHERE ground_id = " . SQL::ProtectNumeric($ground_id, false);
        $this->GetDataConnection()->query($sql);
    }
    
	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the db the Grounds matching the supplied ids.
	 */
	function Delete($a_ids)
	{
		# check parameter
		if (!is_array($a_ids)) die('No grounds to delete');

		# build query
		$s_ground = $this->GetSettings()->GetTable('Ground');
		$s_match = $this->GetSettings()->GetTable('Match');
		$statistics = $this->GetSettings()->GetTable('PlayerMatch');
		$s_ids = join(', ', $a_ids);

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_sql = "SELECT short_url FROM $s_ground WHERE ground_id IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		# delete relationships
		$s_sql = 'UPDATE nsa_team SET ground_id = NULL WHERE ground_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		$s_sql = 'UPDATE ' . $s_match . ' SET ground_id = NULL WHERE ground_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		$s_sql = 'UPDATE ' . $statistics . ' SET ground_id = NULL WHERE ground_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		# delete ground(s)
		$s_sql = 'DELETE FROM ' . $s_ground . ' WHERE ground_id IN (' . $s_ids . ') ';
		$o_result = $this->GetDataConnection()->query($s_sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}

}
?>