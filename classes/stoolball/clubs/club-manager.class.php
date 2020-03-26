<?php
require_once('data/data-manager.class.php');
require_once('club.class.php');

class ClubManager extends DataManager
{
    private $filter_club_type = null;
    
    /**
     * Filters the results of supporting queries to clubs matching the specified type
     */
    public function FilterByClubType($club_type) {
        $this->filter_club_type = (int)$club_type;
    }
    
	/**
	* @return ClubManager
	* @param SiteSettings $settings
	* @param MySqlConnection $db
	* @desc Read and write Clubs
	*/
	function __construct(SiteSettings $settings, MySqlConnection $db)
	{
		parent::__construct($settings, $db);
		$this->s_item_class = 'Club';
	}


	/**
	* @access public
	* @return void
	* @param int[] $a_ids
	* @desc Read from the db the Clubs matching the supplied ids, or all Clubs
	*/
	function ReadById($a_ids=null)
	{
		$sql = "SELECT club.club_id, club.club_name, club.club_type, club.how_many_players, club.age_range_lower, club.age_range_upper, 
		club.plays_outdoors, club.plays_indoors, club.twitter, club.facebook, club.instagram, club.clubmark, club.short_url, 
		club.date_added, club.date_changed,
		team.team_id, team.team_name, team.short_url AS team_short_url 
		FROM nsa_club AS club LEFT OUTER JOIN nsa_team AS team ON club.club_id = team.club_id ";

		# limit to specific club, if specified
		$where = '';
		if (is_array($a_ids)) $where = $this->SqlAddCondition($where, 'club.club_id IN (' . join(', ', $a_ids) . ')');
        if (!is_null($this->filter_club_type)) $where = $this->SqlAddCondition($where, "club.club_type = $this->filter_club_type");

        if ($where) {
            $sql .= "WHERE $where";
        }

		# sort clubs
		$sql .= 'ORDER BY club.club_name ASC, team.team_name ASC';

		# run query
		$result = $this->GetDataConnection()->query($sql);

		# build raw data into Club objects
		$this->BuildItems($result);

		# tidy up
		$result->closeCursor();
		unset($result);
	}


	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $result
	 */
	protected function BuildItems(MySqlRawData $result)
	{
		# use CollectionBuilder to handle duplicates
		$club_builder = new CollectionBuilder();
		$club = null;

		while($row = $result->fetch())
		{
			# check whether this is a new club
			if (!$club_builder->IsDone($row->club_id))
			{
				# store any exisiting club
				if ($club != null) $this->Add($club);

				# create the new club
				$club = new Club($this->GetSettings());
				$club->SetId($row->club_id);
				$club->SetName($row->club_name);
                $club->SetTypeOfClub($row->club_type);
                $club->SetHowManyPlayers($row->how_many_players);
                $club->SetAgeRangeLower($row->age_range_lower);
                $club->SetAgeRangeUpper($row->age_range_upper);
                $club->SetPlaysOutdoors($row->plays_outdoors);
                $club->SetPlaysIndoors($row->plays_indoors);
				$club->SetShortUrl($row->short_url);
                $club->SetTwitterAccount($row->twitter);
                $club->SetFacebookUrl($row->facebook);
                $club->SetInstagramAccount($row->instagram);
				$club->SetClubmarkAccredited($row->clubmark);
				$club->SetDateAdded($row->date_added);
				$club->SetDateChanged($row->date_changed);

			}

			# team the only cause of multiple rows (so far) so add to current club
			if ($row->team_id)
			{
				$team = new Team($this->GetSettings());
				$team->SetId($row->team_id);
				$team->SetName($row->team_name);
				$team->SetShortUrl($row->team_short_url);
				$club->Add($team);
			}
		}
		# store final club
		if ($club != null) $this->Add($club);
	}


	/**
	* @return int
	* @param Club $club
	* @desc Save the supplied Club to the database, and return the id
	*/
	public function Save(Club $club)
	{
		$adding = !(boolean)$club->GetId();

		$old_club = null;
        if (!$adding)
        { 
            $this->ReadById(array($club->GetId()));
            $old_club = $this->GetFirst();
        }

		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$regenerate_url = false;
		if (!$club->GetShortUrl() and !is_null($old_club) and $old_club->GetShortUrl())
		{
			$club->SetShortUrl($old_club->GetShortUrl());
			$regenerate_url = true;
		}
		$new_short_url = $url_manager->EnsureShortUrl($club, $regenerate_url);

		# if no id, it's a new club; otherwise update the club
		if ($club->GetId())
		{
			$s_sql = 'UPDATE nsa_club SET ' .
			"club_name = " . Sql::ProtectString($this->GetDataConnection(), $club->GetName()) . ", 
            club_type = " . Sql::ProtectNumeric($club->GetTypeOfClub(), false, false) . ", 
            how_many_players = " . Sql::ProtectNumeric($club->GetHowManyPlayers(), true, false) . ", 
            age_range_lower = " . Sql::ProtectNumeric($club->GetAgeRangeLower(), true, false) . ", 
            age_range_upper = " . Sql::ProtectNumeric($club->GetAgeRangeUpper(), true, false) . ", 
            plays_outdoors = " . Sql::ProtectBool($club->GetPlaysOutdoors(), true, false) . ",
            plays_indoors = " . Sql::ProtectBool($club->GetPlaysIndoors(), true, false) . ",
            twitter = " . Sql::ProtectString($this->GetDataConnection(), $club->GetTwitterAccount()) . ", 
            facebook = " . Sql::ProtectString($this->GetDataConnection(), $club->GetFacebookUrl()) . ", 
            instagram = " . Sql::ProtectString($this->GetDataConnection(), $club->GetInstagramAccount()) . ", 
            clubmark = " . Sql::ProtectBool($club->GetClubmarkAccredited()) . ",
			short_url = " . Sql::ProtectString($this->GetDataConnection(), $club->GetShortUrl()) . ", 
			date_changed = " . gmdate('U') . ' ' .
			'WHERE club_id = ' . Sql::ProtectNumeric($club->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);
		}
		else
		{
			$s_sql = 'INSERT INTO nsa_club SET ' .
			"club_name = " . Sql::ProtectString($this->GetDataConnection(), $club->GetName()) . ", 
            club_type = " . Sql::ProtectNumeric($club->GetTypeOfClub(), false, false) . ", 
            how_many_players = " . Sql::ProtectNumeric($club->GetHowManyPlayers(), true, false) . ", 
            age_range_lower = " . Sql::ProtectNumeric($club->GetAgeRangeLower(), true, false) . ", 
            age_range_upper = " . Sql::ProtectNumeric($club->GetAgeRangeUpper(), true, false) . ", 
            plays_outdoors = " . Sql::ProtectBool($club->GetPlaysOutdoors(), true, false) . ",
            plays_indoors = " . Sql::ProtectBool($club->GetPlaysIndoors(), true, false) . ",
            twitter = " . Sql::ProtectString($this->GetDataConnection(), $club->GetTwitterAccount()) . ", 
            facebook = " . Sql::ProtectString($this->GetDataConnection(), $club->GetFacebookUrl()) . ", 
            instagram = " . Sql::ProtectString($this->GetDataConnection(), $club->GetInstagramAccount()) . ", 
            clubmark = " . Sql::ProtectBool($club->GetClubmarkAccredited()) . ",
			short_url = " . Sql::ProtectString($this->GetDataConnection(), $club->GetShortUrl()) . ", 
			date_added = " . gmdate('U') . ', 
			date_changed = ' . gmdate('U');

			# run query
			$result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$club->SetId($this->GetDataConnection()->insertID());
		}

		# Regenerate short URLs
		if (is_object($new_short_url))
		{
			$new_short_url->SetParameterValuesFromObject($club);
			$url_manager->Save($new_short_url);
		}
		unset($url_manager);

		return $club->GetId();

	}

	/**
	* @access public
	* @return void
	* @param int[] $a_ids
	* @desc Delete from the db the Clubs matching the supplied ids. Teams will remain, unaffiliated with any Club.
	*/
	function Delete($a_ids)
	{
		# check parameter
		if (!is_array($a_ids)) die('No Clubs to delete');

		# build query
		$s_club = $this->o_settings->GetTable('Club');
		$s_ids = join(', ', $a_ids);

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_sql = "SELECT short_url FROM $s_club WHERE club_id IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($url_manager);

		# delete relationships to teams
		$s_sql = 'UPDATE nsa_team SET club_id = NULL WHERE club_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		# delete club(s)
		$s_sql = 'DELETE FROM ' . $s_club . ' WHERE club_id IN (' . $s_ids . ') ';
		$result = $this->GetDataConnection()->query($s_sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}

}
?>