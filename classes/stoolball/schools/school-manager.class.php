<?php
require_once('data/data-manager.class.php');
require_once('stoolball/clubs/club.class.php');

class SchoolManager extends DataManager
{
     private $filter_search = null;
   
    /**
     * Filters the results of supporting queries to clubs with names matching the search term
     */
    public function FilterBySearch($search_term) {
        $this->filter_search = explode(" ", preg_replace("/[^A-Za-z0-9-' ]/", '', (string)$search_term));
    }
    
	/**
	* @return ClubManager
	* @param SiteSettings $settings
	* @param MySqlConnection $db
	* @desc Read and write Clubs
	*/
	function __construct(SiteSettings $settings, MySqlConnection $db)
	{
		parent::DataManager($settings, $db);
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
		team.team_id, team.team_name, team.short_url AS team_short_url 
		FROM nsa_club AS club LEFT OUTER JOIN nsa_team AS team ON club.club_id = team.club_id 
		WHERE club.club_type = " . Club::SCHOOL . " ";

		# limit to specific club, if specified
		if (is_array($a_ids)) $sql .=  'AND club.club_id IN (' . join(', ', $a_ids) . ') ';
        if (is_array($this->filter_search)) {
            $search_filter_active = false;    
            foreach ($this->filter_search as $search_term) {
                if ($search_term and !$this->IsNoiseWord($search_term)) {
                    $sql .= "AND club.club_name LIKE '%" . trim(Sql::ProtectString($this->GetDataConnection(), $search_term), "'") . "%' ";
                    $search_filter_active = true;
                }                
            }
            if (!$search_filter_active) {
                // If the only search terms were noise words, we don't want to return everything because that's noise.
                return;
            }
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

    private function IsNoiseWord($word) {
        # A noise word is an exact match, or the start of a matching word
        $noise_words = array("club", "stoolball", "school", "academy", "primary", "secondary", "college", "special", "free", "infant", "junior");
        $word = strtolower($word);
        $len = strlen($word);
        foreach ($noise_words as $noisy) {
            if (strlen($noisy) >= $len and strpos($noisy, $word) === 0) return true;
        }
        return false;
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
		$school_builder = new CollectionBuilder();
		$school = null;

		while($row = $result->fetch())
		{
			# check whether this is a new school
			if (!$school_builder->IsDone($row->club_id))
			{
				# store any exisiting school
				if ($school != null) $this->Add($school);

				# create the new school
				$school = new School($this->GetSettings());
				$school->SetId($row->club_id);
				$school->SetName($row->club_name);
                $school->SetTypeOfClub($row->club_type);
                $school->SetHowManyPlayers($row->how_many_players);
                $school->SetAgeRangeLower($row->age_range_lower);
                $school->SetAgeRangeUpper($row->age_range_upper);
                $school->SetPlaysOutdoors($row->plays_outdoors);
                $school->SetPlaysIndoors($row->plays_indoors);
				$school->SetShortUrl($row->short_url);
                $school->SetTwitterAccount($row->twitter);
                $school->SetFacebookUrl($row->facebook);
                $school->SetInstagramAccount($row->instagram);
                $school->SetClubmarkAccredited($row->clubmark);
         
                # Infer partial address from school name
                $school_name = $school->GetName();
                $comma = strrpos($school_name, ",");
                if ($comma !== false) {
                    $school->Ground()->GetAddress()->SetTown(trim(substr($school_name,$comma+1)));
                    $school->Ground()->GetAddress()->SetPaon(substr($school_name, 0, $comma));
                }
            }

			# team the only cause of multiple rows (so far) so add to current school
			if ($row->team_id)
			{
				$team = new Team($this->GetSettings());
				$team->SetId($row->team_id);
				$team->SetName($row->team_name);
				$team->SetShortUrl($row->team_short_url);
				$school->Add($team);
			}
		}
		# store final club
		if ($school != null) $this->Add($school);
	}


	/**
	* @return int
	* @param Club $club
	* @desc Save the supplied Club to the database, and return the id
	*/
	public function Save(Club $club)
	{
		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$new_short_url = $url_manager->EnsureShortUrl($club);

		# if no id, it's a new club; otherwise update the club
		if ($club->GetId())
		{
			$s_sql = 'UPDATE ' . $this->GetSettings()->GetTable('Club') . ' SET ' .
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
			$this->LoggedQuery($s_sql);
		}
		else
		{
			$s_sql = 'INSERT INTO ' . $this->GetSettings()->GetTable('Club') . ' SET ' .
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
			$result = $this->LoggedQuery($s_sql);

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
    * @return int
    * @param School $school
    * @desc Update the supplied school in the database
    */
    public function SaveSchool(School $school, GroundManager $ground_manager)
    {
        if (!$school->GetId())
        {
            throw new Exception("SaveSchool is for updates only. To save a new school, use Save()");
        }

        $sql = 'UPDATE nsa_club SET ' .
        "club_name = " . Sql::ProtectString($this->GetDataConnection(), $school->GetName()) . ", 
        club_type = " . Club::SCHOOL . ", 
        short_url = " . Sql::ProtectString($this->GetDataConnection(), $school->GetShortUrl()) . ", 
        date_changed = " . gmdate('U') . ' ' .
        'WHERE club_id = ' . Sql::ProtectNumeric($school->GetId());

        $this->LoggedQuery($sql);

        # Set the ground URL to be the same as the school URL, 
        # but with a different prefix, then update the ground
        $school->Ground()->SetShortUrl('ground' . substr($school->GetShortUrl(), 6));
        $ground_id = $ground_manager->SaveGround($school->Ground(), true);
        $school->Ground()->SetId($ground_id);
        
    }

    /**
    * @return int
    * @param School $school
    * @desc Update the social media accounts for the supplied school in the database
    */
    public function SaveSocialMedia(School $school)
    {
        if (!$school->GetId())
        {
            throw new Exception("SaveSocialMedia is for updates only. To save a new school, use Save()");
        }

        $sql = "UPDATE nsa_club SET 
        twitter = " . Sql::ProtectString($this->GetDataConnection(), $school->GetTwitterAccount()) . ", 
        facebook = " . Sql::ProtectString($this->GetDataConnection(), $school->GetFacebookUrl()) . ", 
        instagram = " . Sql::ProtectString($this->GetDataConnection(), $school->GetInstagramAccount()) . ", 
        date_changed = " . gmdate('U') . ' ' .
        'WHERE club_id = ' . Sql::ProtectNumeric($school->GetId());

        $this->LoggedQuery($sql);        
    }
}
?>