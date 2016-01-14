<?php
require_once('data/data-manager.class.php');
require_once('stoolball/team.class.php');
require_once('stoolball/team-in-season.class.php');
require_once('stoolball/season.class.php');
require_once('stoolball/competition.class.php');
require_once('stoolball/ground.class.php');

class TeamManager extends DataManager
{
	private $filter_team_types;
	private $b_inactive_last;
	private $b_filter_active;
	private $a_filter_player_types = array();
	private $a_filter_except_teams = array();
	private $filter_grounds = array();
    private $filter_administrative_areas = array();
    private $filter_tournaments = array();

	/**
	 * @return TeamManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Read and write Teams
	 */
	public function TeamManager(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'Team';
		$this->b_inactive_last = true;
        
        # Exclude once-only teams by default
        $this->filter_team_types = array(Team::CLOSED_GROUP, Team::OCCASIONAL, Team::REGULAR, Team::REPRESENTATIVE);
	}

	/**
	 * Sets to true to show only active teams, or false to show only former teams
	 *
	 * @param bool $b_active
	 */
	public function FilterByActive($b_active)
	{
		$this->b_filter_active = (bool)$b_active;
	}

	/**
	 * Sets whether teams which don't exist any more should be grouped at the end
	 *
	 * @param bool $b_last
	 */
	public function SetGroupInactiveLast($b_last)
	{
		$this->b_inactive_last = (bool)$b_last;
	}

	/**
	 * Gets whether teams which don't exist any more should be grouped at the end
	 *
	 * @return bool
	 */
	public function GetGroupInactiveLast()
	{
		return $this->b_inactive_last;
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
	 * Limits queries to returning only teams which are not the supplied array of ids
	 *
	 * @param string[] $team_ids
	 */
	public function FilterExceptTeams($team_ids)
	{
		if (is_array($team_ids))
		{
			$this->ValidateNumericArray($team_ids);
			$this->a_filter_except_teams = $team_ids;
		}
		else $this->a_filter_except_teams = array();
	}


	/**
	 * Limits queries to returning only teams based at the supplied ground ids
	 *
	 * @param int[] $grounds
	 */
	public function FilterByGround($grounds)
	{
		if (is_array($grounds))
		{
			$this->ValidateNumericArray($grounds);
			$this->filter_grounds = $grounds;
		}
		else $this->filter_grounds = array();
	}

    /**
     * Limits queries to returning only teams based in the supplied administrative areas
     *
     * @param string[] $administrative_areas
     */
    public function FilterByAdministrativeArea($administrative_areas)
    {
        if (is_array($administrative_areas))
        {
            $this->filter_administrative_areas = $administrative_areas;
        }
        else $this->filter_administrative_areas = array();
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
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the Teams matching the supplied ids, or all Teams
	 */
	public function ReadById($a_ids=null)
	{
		/* @var $o_settings SiteSettings */
		if (!is_null($a_ids)) $this->ValidateNumericArray($a_ids);

		# build query
		$s_season_link = $this->o_settings->GetTable('TeamSeason');
		$s_season = $this->o_settings->GetTable('Season');
		$s_comp = $this->o_settings->GetTable('Competition');
		$s_ground = $this->o_settings->GetTable('Ground');

		$s_sql = "SELECT team.team_id, team.team_name, team.website, team.active, team.team_type, 
		team.intro, team.player_type_id, team.playing_times, team.cost, team.short_url, 
		team.contact, team.contact_nsa, team.update_search, team.date_changed, team.modified_by_id,  
	    club.club_id, club.club_name, club.twitter, club.clubmark, club.short_url AS club_short_url, 
		$s_season_link.withdrawn_league, " .
		$s_season . '.season_id, ' . $s_season . '.season_name, ' . $s_season . '.is_latest, ' . $s_season . '.start_year, ' . $s_season . '.end_year, ' . $s_season . '.short_url AS season_short_url, ' .
		$s_comp . '.competition_id, ' . $s_comp . '.competition_name, ' .
		$s_ground . '.ground_id, ' . $s_ground . '.saon, ' . $s_ground . '.paon, ' . $s_ground . '.street_descriptor, ' . $s_ground . '.locality, ' . $s_ground . '.town, ' . $s_ground . '.administrative_area, ' . $s_ground . '.postcode, ' .
		$s_ground . '.short_url AS ground_short_url, ' . $s_ground . ".latitude, $s_ground.longitude, 
		user.known_as " . 
		'FROM (((((nsa_team AS team LEFT OUTER JOIN nsa_club AS club ON team.club_id = club.club_id) ' .
		'LEFT OUTER JOIN ' . $s_season_link . ' ON team.team_id = ' . $s_season_link . '.team_id) ' .
		'LEFT OUTER JOIN ' . $s_season . ' ON ' . $s_season_link . '.season_id = ' . $s_season . '.season_id) ' .
		'LEFT OUTER JOIN ' . $s_comp . ' ON ' . $s_season . '.competition_id = ' . $s_comp . '.competition_id) ' .
		'LEFT OUTER JOIN ' . $s_ground . ' ON team.ground_id = ' . $s_ground . '.ground_id) ' . 
        "LEFT OUTER JOIN nsa_user AS user ON team.modified_by_id = user.user_id ";

		# Impose conditions
		$s_where = '';
		if (is_array($a_ids)) $s_where = $this->SqlAddCondition($s_where, "team.team_id IN (" . join(', ', $a_ids) . ')');
		if (isset($this->b_filter_active)) $s_where = $this->SqlAddCondition($s_where, 'team.active = ' . ($this->b_filter_active ? '1' : '0'));
		if (count($this->filter_team_types)) $s_where = $this->SqlAddCondition($s_where, "team.team_type IN (" . join(",", $this->filter_team_types) . ") ");
		if (count($this->a_filter_except_teams)) $s_where = $this->SqlAddCondition($s_where, "team.team_id NOT IN (" . join(', ', $this->a_filter_except_teams) . ") ");
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		# sort teams
		$s_sql .= 'ORDER BY ';
		if ($this->b_inactive_last) $s_sql .= 'team.active DESC, ';
		$s_sql .= "team.team_name ASC, $s_comp.competition_name ASC, $s_season.start_year DESC, $s_season.end_year ASC";

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Team objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}


	/**
	 * @access public
	 * @return void
     * @desc Read from the db summary details of Teams matching the current filters
	 */
	public function ReadTeamSummaries()
	{
		# build query
        $select = 'SELECT team.team_id, team.team_name, team.short_url, team.player_type_id, ' .
		"ground.ground_id, ground.town, ground.administrative_area ";
        
		$from = "FROM nsa_team AS team INNER JOIN nsa_ground AS ground ON team.ground_id = ground.ground_id ";

		# Impose conditions
		$where = '';
		if (count($this->a_filter_player_types)) $where = $this->SqlAddCondition($where, 'team.player_type_id IN (' . join(', ', $this->a_filter_player_types) . ')');
		if (isset($this->b_filter_active)) $where = $this->SqlAddCondition($where, 'team.active = ' . ($this->b_filter_active ? '1' : '0'));
		if (count($this->filter_team_types)) $where = $this->SqlAddCondition($where, "team.team_type IN (" . join(",", $this->filter_team_types) . ") ");
        if (count($this->a_filter_except_teams)) $where = $this->SqlAddCondition($where, "team.team_id NOT IN (" . join(', ', $this->a_filter_except_teams) . ") ");
		if (count($this->filter_grounds)) $where = $this->SqlAddCondition($where, "team.ground_id IN (" . join(",", $this->filter_grounds) . ") ");
        if (count($this->filter_administrative_areas))
        {
            $administrative_areas = array();
            foreach ($this->filter_administrative_areas as $name) 
            {
                # HACK: Deal with two-word counties. Would be better to save a comparable name when saving the ground.
                $name = strtolower($name);
                if (strpos($name, "east") === 0 or strpos($name, "west") === 0) 
                {
                    $name = substr($name, 0, 4) . " " . substr($name,4);
                }
                $administrative_areas[] = $this->SqlString($name);
            }
            
            $where = $this->SqlAddCondition($where, "ground.administrative_area IN (" . join(",", $administrative_areas) . ") ");  
        } 
        if (count($this->filter_tournaments)) 
        {
            $from .= "INNER JOIN nsa_match_team AS mt ON team.team_id = mt.team_id ";
            $where = $this->SqlAddCondition($where, "mt.match_id IN (" . implode(",",$this->filter_tournaments) . ") ");
        }
		$sql = $select . $from;
		$sql = $this->SqlAddWhereClause($sql, $where);

        # sort teams
		$sql .= 'ORDER BY team.team_name ASC ';
		if ($this->FilterByMaximumResultsValue()) $sql .= 'LIMIT 0, ' . $this->FilterByMaximumResultsValue();

		# run query
		$o_result = $this->GetDataConnection()->query($sql);

		# build raw data into Team objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

    /**
     * @access public
     * @return string[]
     * Read the administrative areas which have teams matching the current filters
     */
    public function ReadAdministrativeAreas()
    {
        $administrative_areas = array();
        
        # build query
        $s_sql = 'SELECT DISTINCT ground.administrative_area ' .
        "FROM nsa_team AS team INNER JOIN nsa_ground AS ground ON team.ground_id = ground.ground_id ";

        # Impose conditions
        $s_where = '';
        if (count($this->a_filter_player_types)) $s_where = $this->SqlAddCondition($s_where, 'team.player_type_id IN (' . join(', ', $this->a_filter_player_types) . ')');
        if (isset($this->b_filter_active)) $s_where = $this->SqlAddCondition($s_where, 'team.active = ' . ($this->b_filter_active ? '1' : '0'));
        if (count($this->filter_team_types)) $s_where = $this->SqlAddCondition($s_where, "team.team_type IN (" . join(",", $this->filter_team_types) . ") ");
        if (count($this->filter_grounds)) $s_where = $this->SqlAddCondition($s_where, "team.ground_id IN (" . join(",", $this->filter_grounds) . ") ");
        $s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

        # sort 
        $s_sql .= 'ORDER BY ground.administrative_area ASC ';
        
        # run query
        $result = $this->GetDataConnection()->query($s_sql);

        while ($row = $result->fetch()) 
        {
            if ($row->administrative_area)
            { 
                $administrative_areas[] = $row->administrative_area;
            }
        }
        
        # tidy up
        $result->closeCursor();
        unset($result);
        
        return $administrative_areas;
    }

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the Teams matching the supplied season id
	 */
	public function ReadBySeasonId($a_ids)
	{
		/* @var $o_settings SiteSettings */
		$this->ValidateNumericArray($a_ids);

		# build query
		$s_season_link = $this->GetSettings()->GetTable('TeamSeason');
		$s_ground = $this->GetSettings()->GetTable('Ground');

		$s_sql = "SELECT team.team_id, team.team_name, team.contact, team.contact_nsa, team.short_url, 
		$s_ground" . '.ground_id, ' . $s_ground . '.saon, ' . $s_ground . '.paon, ' . $s_ground . '.street_descriptor, ' . $s_ground . '.locality, ' .
		$s_ground . '.town, ' . $s_ground . '.administrative_area, ' . $s_ground . '.postcode ' .
		'FROM (nsa_team AS team INNER JOIN ' . $s_season_link . ' ON team.team_id = ' . $s_season_link . '.team_id) ' .
		'LEFT OUTER JOIN ' . $s_ground . ' ON team.ground_id = ' . $s_ground . '.ground_id ';

		# Impose conditions
		$s_where = $this->SqlAddCondition('', $s_season_link . '.season_id IN (' . join(', ', $a_ids) . ') ');
		if (isset($this->b_filter_active)) $s_where = $this->SqlAddCondition($s_where, 'team.active = ' . ($this->b_filter_active ? '1' : '0'));
		if (count($this->filter_team_types)) $s_where = $this->SqlAddCondition($s_where, "team.team_type IN (" . join(",", $this->filter_team_types) . ") ");
		if (count($this->a_filter_except_teams)) $s_where = $this->SqlAddCondition($s_where, "team.team_id NOT IN (" . join(', ', $this->a_filter_except_teams) . ") ");
		$s_sql = $this->SqlAddWhereClause($s_sql, $s_where);

		# sort teams
		$s_sql .= 'ORDER BY team.team_name ASC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Team objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * Read opposing teams the supplied team(s) have played most recently
	 *
	 * @param IDs of teams whose opponents you want $a_team_ids
	 * @param How many months to go back $i_months
	 */
	public function ReadRecentOpponents($a_team_ids, $i_months=0)
	{
		$this->ValidateNumericArray($a_team_ids);

		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$ground = $this->GetSettings()->GetTable('Ground');
		$match = $this->GetSettings()->GetTable('Match');

		$month = 60*60*24*31; # don't need to be exact for this query so ignore 28/30 day months
		$earliest_match = time()-($month*$i_months);

		$sql = "SELECT DISTINCT team.team_id, team.team_name, " .
		"$ground.ground_id, $ground.saon, $ground.paon, $ground.town " .
		"FROM ((($mt INNER JOIN $mt AS opposing_teams ON $mt.match_id = opposing_teams.match_id AND opposing_teams.team_id != $mt.team_id) " .
		"INNER JOIN nsa_team AS team ON opposing_teams.team_id = team.team_id) " .
		"LEFT OUTER JOIN $ground ON team.ground_id = $ground.ground_id) ";

		if ($i_months) $sql .= "INNER JOIN $match ON $mt.match_id = $match.match_id ";

		$sql .= "WHERE $mt.team_id IN (" . join(', ', $a_team_ids) . ") ";
		if (count($this->a_filter_except_teams)) $sql .= "AND opposing_teams.team_id NOT IN (" . join(', ', $this->a_filter_except_teams) . ") ";
		if ($i_months) $sql .= "AND $match.start_time > " . $earliest_match;
		$sql .= " ORDER BY team.team_name ASC ";

		$result = $this->GetDataConnection()->query($sql);
		$this->BuildItems($result);
		$result->closeCursor();
		unset($result);
	}

    /**
     * Populates the team's id if it has already been recorded
     * @param Team $team
     * @return Team
     */
    public function MatchExistingTeam(Team $team)
    {
        $sql = "SELECT team_id FROM nsa_team 
                WHERE comparable_name = " . Sql::ProtectString($this->GetDataConnection(), $team->GetComparableName());
        
        $result = $this->GetDataConnection()->query($sql);
        if ($row = $result->fetch())
        {
            $team->SetId($row->team_id);
        }
        $result->closeCursor();

        return $team;
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

		/* @var $o_team Team */

		# use CollectionBuilder to handle duplicates
		$o_team_builder = new CollectionBuilder();
		$o_season_builder = new CollectionBuilder();
		$o_team = null;

		while($row = $o_result->fetch())
		{
			# check whether this is a new team
			if (!$o_team_builder->IsDone($row->team_id))
			{
				# store any exisiting team
				if ($o_team != null)
				{
			     	$this->Add($o_team);
                    $o_season_builder->Reset();
				}

				# create the new team
				$o_team = new Team($this->o_settings);
				$o_team->SetId($row->team_id);
				$o_team->SetName($row->team_name);
				if (isset($row->website)) $o_team->SetWebsiteUrl($row->website);
				if (isset($row->active)) $o_team->SetIsActive($row->active);
				if (isset($row->team_type)) $o_team->SetTeamType($row->team_type);
				if (isset($row->intro)) $o_team->SetIntro($row->intro);
				if (isset($row->playing_times)) $o_team->SetPlayingTimes($row->playing_times);
				if (isset($row->cost)) $o_team->SetCost($row->cost);
				if (isset($row->contact)) $o_team->SetContact($row->contact);
				if (isset($row->contact_nsa)) $o_team->SetPrivateContact($row->contact_nsa);
				if (isset($row->short_url)) $o_team->SetShortUrl($row->short_url);
				if (isset($row->player_type_id)) $o_team->SetPlayerType($row->player_type_id);
                if (isset($row->update_search) and $row->update_search == 1) $o_team->SetSearchUpdateRequired();
                if (isset($row->date_changed))
                {
                    $o_team->SetLastAudit(new AuditData($row->modified_by_id, $row->known_as, $row->date_changed));
                }
        
				if (isset($row->club_id))
				{
					$o_club = new Club($this->GetSettings());
					$o_club->SetId($row->club_id);
					if (isset($row->club_name)) $o_club->SetName($row->club_name);
                    if (isset($row->twitter)) $o_club->SetTwitterAccount($row->twitter);
                    if (isset($row->clubmark)) $o_club->SetClubmarkAccredited($row->clubmark);
					if (isset($row->club_short_url)) $o_club->SetShortUrl($row->club_short_url);
					$o_team->SetClub($o_club);
				}

				if (isset($row->ground_id) and $row->ground_id)
				{
					$o_ground = new Ground($this->o_settings);
					$o_ground->SetId($row->ground_id);
					$address = $o_ground->GetAddress();
					if (isset($row->saon)) $address->SetSaon($row->saon);
					if (isset($row->town))
					{
						if (isset($row->paon)) $address->SetPaon($row->paon);
						if (isset($row->street_descriptor)) $address->SetStreetDescriptor($row->street_descriptor);
						if (isset($row->locality)) $address->SetLocality($row->locality);
						$address->SetTown($row->town);
						if (isset($row->administrative_area)) $address->SetAdministrativeArea($row->administrative_area);
						if (isset($row->postcode)) $address->SetPostcode($row->postcode);
                        if (isset($row->latitude)) $address->SetGeoLocation($row->latitude, $row->longitude, null);
						$o_ground->SetAddress($address);
					}
					if (isset($row->ground_short_url)) $o_ground->SetShortUrl($row->ground_short_url);
					$o_team->SetGround($o_ground);
				}
			}

			# Competition/Season a cause of multiple rows
			if (isset($row->season_id) and !$o_season_builder->IsDone($row->season_id) and isset($row->competition_id))
			{
				$o_season = new Season($this->o_settings);
				$o_season->SetId($row->season_id);
				$o_season->SetName($row->season_name);
				$o_season->SetIsLatest($row->is_latest);
				$o_season->SetStartYear($row->start_year);
				$o_season->SetEndYear($row->end_year);
				if (isset($row->season_short_url)) $o_season->SetShortUrl($row->season_short_url);
            
                $o_competition = new Competition($this->o_settings);
                $o_competition->SetId($row->competition_id);
                $o_competition->SetName($row->competition_name);
                $o_season->SetCompetition($o_competition);
                
                $o_team->Seasons()->Add(new TeamInSeason(null, $o_season, isset($row->withdrawn_league) ? $row->withdrawn_league : null));
                unset($o_season);
                unset($o_competition);
			}
		}
		# store final team
		if ($o_team != null)
		{
			$this->Add($o_team);
		}

		return true;
	}


	/**
	 * @return int
	 * @param Team $team
	 * @desc Save the supplied Team to the database, and return the id
	 */
	public function SaveTeam(Team $team)
	{
		# First job is to check permissions. There are several scenarios:
		# - adding regular teams requires the highest privileges
		# - adding once-only teams requires low privileges
		# - editing teams has less access for a team owner than for a site admin 
        # 
        # Important to check the previous team type from the database before trusting
        # the one submitted, as changing the team type changes editing privileges
		$user = AuthenticationManager::GetUser();
	    $is_admin = $user->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS);
        $is_team_owner = $user->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS,$team->GetLinkedDataUri());
        $adding = !(boolean)$team->GetId();

        $old_team = null;
        if (!$adding)
        { 
            $this->ReadById(array($team->GetId()));
            $old_team = $this->GetFirst();
            $team->SetTeamType($this->GetPermittedTeamType($old_team->GetTeamType(), $team->GetTeamType()));
        }
        
        $is_once_only = ($team->GetTeamType() == Team::ONCE);
                
        # To add a regular team we need global manage teams permission
        if ($adding and !$is_once_only and !$is_admin)
        {
            throw new Exception("Unauthorised");
        }

        # To edit a team we need global manage teams permission, or team owner permission
        if (!$adding and !$is_admin and !$is_team_owner) 
        {
            throw new Exception("Unauthorised");
        }
    
        # Only an admin can change the short URL after the team is created
        if ($adding or $is_admin)
        {
    		# Set up short URL manager
            # Before changing the short URL, important that $old_team has a note of the current resource URI
    		require_once('http/short-url-manager.class.php');
    		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
    		$new_short_url = $o_url_manager->EnsureShortUrl($team);
        }
        
		# build query
		$i_club_id = (!is_null($team->GetClub())) ? $team->GetClub()->GetId() : null;
        $allowed_html = array('p','br','strong','em','a[href]', 'ul', 'ol', 'li');
                
		# if no id, it's a new Team; otherwise update the Team
		if ($adding)
		{
            $sql = 'INSERT INTO nsa_team SET ' .
            "team_name = " . $this->SqlString($team->GetName()) . ", 
            comparable_name = " . Sql::ProtectString($this->GetDataConnection(), $team->GetComparableName(), false) . ",
            club_id = " . Sql::ProtectNumeric($i_club_id, true) . ", 
            website = " . $this->SqlString($team->GetWebsiteUrl()) . ", " .
            'ground_id = ' . Sql::ProtectNumeric($team->GetGround()->GetId(), true) . ', ' .
            'active = ' . Sql::ProtectBool($team->GetIsActive()) . ", 
            team_type = " . Sql::ProtectNumeric($team->GetTeamType()) . ', ' .
            'player_type_id = ' . Sql::ProtectNumeric($team->GetPlayerType()) . ",
            intro = " . $this->SqlHtmlString($team->GetIntro(), $allowed_html) . ",
            playing_times = " . $this->SqlHtmlString($team->GetPlayingTimes(), $allowed_html) . ",  
            cost = " . $this->SqlHtmlString($team->GetCost(), $allowed_html) . ", " .
            "contact = " . $this->SqlHtmlString($team->GetContact(), $allowed_html) . ", " .
            "contact_nsa = " . $this->SqlHtmlString($team->GetPrivateContact(), $allowed_html) . ", " .
            "short_url = " . $this->SqlString($team->GetShortUrl()) . ", 
            update_search = " . ($is_once_only ? "0" : "1") . ", 
            date_added = " . gmdate('U') . ', ' .
            'date_changed = ' . gmdate('U') . ", " . 
            "modified_by_id = " . Sql::ProtectNumeric($user->GetId());
            
            # run query
            $this->LoggedQuery($sql);

            # get autonumber
            $team->SetId($this->GetDataConnection()->insertID());

            # Create default extras players
            require_once "player-manager.class.php";
            $player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
            $player_manager->CreateExtrasPlayersForTeam($team->GetId());
            unset($player_manager);
            
            # Create owner role
            require_once("authentication/authentication-manager.class.php");
            require_once("authentication/role.class.php");
            $authentication_manager = new AuthenticationManager($this->GetSettings(), $this->GetDataConnection(), null);
            $role = new Role();
            $role->setRoleName("Team owner: " . $team->GetName());
            $role->Permissions()->AddPermission(PermissionType::MANAGE_TEAMS, $team->GetLinkedDataUri());
            $authentication_manager->SaveRole($role);
            
            $sql = "UPDATE nsa_team SET owner_role_id = " . Sql::ProtectNumeric($role->getRoleId(), false, false) . 
            ' WHERE team_id = ' . Sql::ProtectNumeric($team->GetId());
            $this->LoggedQuery($sql);
            
            # If creating a once-only team, make the current user an owner
            if ($is_once_only and !$is_admin)
            {
                $authentication_manager->AddUserToRole($user->GetId(), $role->getRoleId());
                $authentication_manager->LoadUserPermissions();
            } 
            unset($authentication_manager);
		}
		else
		{
            # Now update the team, depending on permissions
            $sql = 'UPDATE nsa_team SET ' .
            "website = " . $this->SqlString($team->GetWebsiteUrl()) . ", " .
            "intro = " . $this->SqlHtmlString($team->GetIntro(), $allowed_html) . ", " .
            "cost = " . $this->SqlHtmlString($team->GetCost(), $allowed_html) . ", " .
            "contact = " . $this->SqlHtmlString($team->GetContact(), $allowed_html) . ", " .
            "contact_nsa = " . $this->SqlHtmlString($team->GetPrivateContact(), $allowed_html) . ",  
            update_search = " . ($is_once_only ? "0" : "1") . ",  
            date_changed = " . gmdate('U') . ", 
            modified_by_id = " . Sql::ProtectNumeric($user->GetId()) . ' ';           

            if (!$is_once_only)
            {
                $sql .= ", 
                        active = " . Sql::ProtectBool($team->GetIsActive()) . ", 
                        team_type = " . Sql::ProtectNumeric($team->GetTeamType()) . ", 
                        ground_id = " . Sql::ProtectNumeric($team->GetGround()->GetId(), true) . ", 
                        playing_times = " . $this->SqlHtmlString($team->GetPlayingTimes(), $allowed_html);
            }             

            if ($is_admin or $is_once_only) 
            {
                $sql .= ",
                        team_name = " . $this->SqlString($team->GetName()); 
            }
                                    
            if ($is_admin) 
            {
                $sql .= ",
                        club_id = " . Sql::ProtectNumeric($i_club_id, true) . ", 
                        player_type_id = " . Sql::ProtectNumeric($team->GetPlayerType()) . ", 
                        comparable_name = " . Sql::ProtectString($this->GetDataConnection(), $team->GetComparableName(), false) . ",
                        short_url = " . $this->SqlString($team->GetShortUrl()) . " ";
            }
            
            $sql .= "WHERE team_id = " . Sql::ProtectNumeric($team->GetId());

            $this->LoggedQuery($sql);
            
            # In case team name changed, update stats table
            if ($is_admin or $is_once_only) 
            {
                $sql = "UPDATE nsa_player_match SET team_name = " . $this->SqlString($team->GetName()) . " WHERE team_id = " . Sql::ProtectNumeric($team->GetId());
                $this->LoggedQuery($sql);
                $sql = "UPDATE nsa_player_match SET opposition_name = " . $this->SqlString($team->GetName()) . " WHERE opposition_id = " . Sql::ProtectNumeric($team->GetId());
                $this->LoggedQuery($sql);
            }
		}

        if ($adding or $is_admin)
        {
            # Regenerate short URLs
            if (is_object($new_short_url))
            {
                $new_short_url->SetParameterValuesFromObject($team);
                $o_url_manager->Save($new_short_url);
                if (!$adding) { 
                    $o_url_manager->ReplacePrefixForChildUrls(Player::GetShortUrlFormatForType($this->GetSettings()), $old_team->GetShortUrl(), $team->GetShortUrl());
                    
                    $old_prefix = $this->SqlString($old_team->GetShortUrl() . "/%");
                    $new_prefix = $this->SqlString($team->GetShortUrl());
                    $sql = "UPDATE nsa_player_match SET
                            player_url = CONCAT($new_prefix, RIGHT(player_url,CHAR_LENGTH(player_url)-LOCATE('/',player_url)+1))
                            WHERE player_url LIKE $old_prefix";

                    $this->LoggedQuery($sql);
                }
            }
            unset($o_url_manager);

            # Owner permission is based on the resource URI, which in turn is based on short URL, 
            # so if it's changed update the permissions
            if ($old_team instanceof Team)
            {
                $old_resource_uri = $old_team->GetLinkedDataUri();
                $new_resource_uri = $team->GetLinkedDataUri();
                if ($old_resource_uri != $new_resource_uri)
                {
                    $permissions_table = $this->GetSettings()->GetTable("PermissionRoleLink");
                    $sql = "UPDATE $permissions_table SET resource_uri = " . $this->SqlString($new_resource_uri) . 
                            " WHERE resource_uri = " . $this->SqlString($old_resource_uri);
                    $this->LoggedQuery($sql);
                } 
            }
        }

        if (!$is_once_only) 
        {
            # Request search update for affected  competitions
            $sql = "UPDATE nsa_competition SET update_search = 1 WHERE competition_id IN 
                (
                    SELECT competition_id FROM nsa_season WHERE season_id IN
                    (
                        SELECT season_id FROM nsa_team_season WHERE team_id = " . SQL::ProtectNumeric($team->GetId(),false) . " 
                    )
                )";
            $this->LoggedQuery($sql);

            # Request searched update for effects of changing the team name           
            $sql = "UPDATE nsa_player SET update_search = 1 WHERE team_id = " . SQL::ProtectNumeric($team->GetId(),false);
            $this->LoggedQuery($sql);
            
            $sql = "UPDATE nsa_match SET update_search = 1 WHERE match_id IN ( SELECT match_id FROM nsa_match_team WHERE team_id = " . SQL::ProtectNumeric($team->GetId(),false) . ")";
            $this->LoggedQuery($sql);

            # Request search update for changing the team home ground
            $sql = "UPDATE nsa_ground SET update_search = 1 WHERE ground_id = " . Sql::ProtectNumeric($team->GetGround()->GetId(), false);
            $this->LoggedQuery($sql);
        }
         
        
		return $team->GetId();
	}

	/**
     * Gets the team type if allowed, or the previous team type if not
     * @param int $previous_type
     * @param int $submitted_type
     * @return int
     */
	private function GetPermittedTeamType($previous_type, $submitted_type) 
	{
	    switch($previous_type)
        {
            case Team::ONCE:
                # Can't change this
                return Team::ONCE;
                break;
            
            default:
                if ($submitted_type == Team::ONCE)
                {
                    # Can't change to this
                    return $previous_type;
                } 
                else 
                {
                    return $submitted_type;
                }
        }
	}
    
    /**
     * Finds an existing team or saves a new team and returns the id
     * @param Match $tournament
     * @param Team $team
     * @return int
     */
    public function SaveOrMatchTournamentTeam(Match $tournament, Team $team)
    {
        if (is_null($team->GetPlayerType()))
        { 
            $team->SetPlayerType($tournament->GetPlayerType());
        }
        $team = $this->MatchExistingTeam($team);

        if (!$team->GetId())
        {
            $team->SetShortUrlPrefix($tournament->GetShortUrl());
            $team = $this->MatchExistingTeam($team);
        }
        
        if (!$team->GetId())
        {
            $team->SetTeamType(Team::ONCE);
            $team->SetGround($tournament->GetGround());
            $this->SaveTeam($team);
        }

        return $team->GetId();
    }

    /**
     * Reset the flag which indicates that search needs to be updated
     * @param $team_id int
     */
    public function SearchUpdated($team_id) 
    {
        if (!is_integer($team_id)) throw new Exception("team_id must be an integer");
        $sql = "UPDATE nsa_team SET update_search = 0 WHERE team_id = " . SQL::ProtectNumeric($team_id, false);
        $this->GetDataConnection()->query($sql);
    }
	
	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the db the Teams matching the supplied ids
	 */
	public function Delete($a_ids)
	{
		# check parameter
		$this->ValidateNumericArray($a_ids);
		if (!count($a_ids)) throw new Exception('No teams to delete');
        $s_ids = join(', ', $a_ids);
        
        # Get more information on the teams
        $teams = array();
        
        $s_sql = "SELECT team_id, short_url, owner_role_id FROM nsa_team WHERE team_id IN ($s_ids)";
        $result = $this->GetDataConnection()->query($s_sql);
        while ($row = $result->fetch())
        {
            $team = new Team($this->GetSettings());
            $team->SetId($row->team_id);
            $team->SetShortUrl($row->short_url);
            $team->SetOwnerRoleId($row->owner_role_id);
            $teams[] = $team;
        }
        $result->closeCursor();
                
        # Check that current user is an admin or a team owner
        require_once("authentication/authentication-manager.class.php");
        $user = AuthenticationManager::GetUser();
        foreach ($teams as $team) 
        {
            /* @var $team Team */
            if (!$user->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS,$team->GetLinkedDataUri()))
            {
                throw new Exception("Unauthorised");
            }
        }

        # delete owner role
        $authentication_manager = new AuthenticationManager($this->GetSettings(), $this->GetDataConnection(), null);
        foreach ($teams as $team) 
        {
            /* @var $team Team */
            if ($team->GetOwnerRoleId())
            {
                $authentication_manager->DeleteRole($team->GetOwnerRoleId());
            }
        }
        unset($authentication_manager);
		
		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		foreach ($teams as $team) 
        {
            /* @var $team Team */
            $o_url_manager->Delete($team->GetShortUrl());
		}
		unset($o_url_manager);
    
		# Delete relationships to matches
        $s_match_link = $this->GetSettings()->GetTable('MatchTeam');
		$s_sql = 'DELETE FROM ' . $s_match_link . ' WHERE team_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		# Delete relationships to competitions
        $s_season_link = $this->GetSettings()->GetTable('TeamSeason');
		$s_sql = 'DELETE FROM ' . $s_season_link . ' WHERE team_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		# Delete players
		require_once "player-manager.class.php";
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$player_manager->ReadPlayersInTeam($a_ids);
		$players = $player_manager->GetItems();

		if (count($players))
		{
			$player_ids = array();
			foreach ($players as $player) $player_ids[] = $player->GetId();
			$player_manager->Delete($player_ids);
		}
		unset($player_manager);

		# delete team(s)
		$s_sql = 'DELETE FROM nsa_team WHERE team_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}
}
?>