<?php
require_once('data/data-manager.class.php');
require_once('club.class.php');

class ClubManager extends DataManager
{
	/**
	* @return ClubManager
	* @param SiteSettings $o_settings
	* @param MySqlConnection $o_db
	* @desc Read and write Clubs
	*/
	function ClubManager(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
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
		$s_sql = "SELECT club.club_id, club.club_name, club.twitter, club.short_url, 
		team.team_id, team.team_name, team.short_url AS team_short_url 
		FROM nsa_club AS club LEFT OUTER JOIN nsa_team AS team ON club.club_id = team.club_id ";

		# limit to specific club, if specified
		if (is_array($a_ids)) $s_sql .= 'WHERE club.club_id IN (' . join(', ', $a_ids) . ') ';

		# sort clubs
		$s_sql .= 'ORDER BY club.club_name ASC, team.team_name ASC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Club objects
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
		# use CollectionBuilder to handle duplicates
		$o_club_builder = new CollectionBuilder();
		$o_club = null;

		while($o_row = $o_result->fetch())
		{
			# check whether this is a new club
			if (!$o_club_builder->IsDone($o_row->club_id))
			{
				# store any exisiting club
				if ($o_club != null) $this->Add($o_club);

				# create the new club
				$o_club = new Club($this->GetSettings());
				$o_club->SetId($o_row->club_id);
				$o_club->SetName($o_row->club_name);
				$o_club->SetShortUrl($o_row->short_url);
                $o_club->SetTwitterAccount($o_row->twitter);

			}

			# team the only cause of multiple rows (so far) so add to current club
			if ($o_row->team_id)
			{
				$o_team = new Team($this->GetSettings());
				$o_team->SetId($o_row->team_id);
				$o_team->SetName($o_row->team_name);
				$o_team->SetShortUrl($o_row->team_short_url);
				$o_club->Add($o_team);
			}
		}
		# store final club
		if ($o_club != null) $this->Add($o_club);
	}


	/**
	* @return int
	* @param Club $o_club
	* @desc Save the supplied Club to the database, and return the id
	*/
	function Save($o_club)
	{
		# check parameters
		if (!$o_club instanceof Club) throw new Exception('Unable to save club');

		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$new_short_url = $o_url_manager->EnsureShortUrl($o_club);

		# if no id, it's a new club; otherwise update the club
		if ($o_club->GetId())
		{
			$s_sql = 'UPDATE ' . $this->GetSettings()->GetTable('Club') . ' SET ' .
			"club_name = " . Sql::ProtectString($this->GetDataConnection(), $o_club->GetName()) . ", " .
            "twitter = " . Sql::ProtectString($this->GetDataConnection(), $o_club->GetTwitterAccount()) . ", " .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_club->GetShortUrl()) . ", " .
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE club_id = ' . Sql::ProtectNumeric($o_club->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);
		}
		else
		{
			$s_sql = 'INSERT INTO ' . $this->GetSettings()->GetTable('Club') . ' SET ' .
			"club_name = " . Sql::ProtectString($this->GetDataConnection(), $o_club->GetName()) . ", " .
            "twitter = " . Sql::ProtectString($this->GetDataConnection(), $o_club->GetTwitterAccount()) . ", " .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_club->GetShortUrl()) . ", " .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U');

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$o_club->SetId($this->GetDataConnection()->insertID());
		}

		# Regenerate short URLs
		if (is_object($new_short_url))
		{
			$new_short_url->SetParameterValuesFromObject($o_club);
			$o_url_manager->Save($new_short_url);
		}
		unset($o_url_manager);

		return $o_club->GetId();

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
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_sql = "SELECT short_url FROM $s_club WHERE club_id IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		# delete relationships to teams
		$s_sql = 'UPDATE nsa_team SET club_id = NULL WHERE club_id IN (' . $s_ids . ') ';
		$this->GetDataConnection()->query($s_sql);

		# delete club(s)
		$s_sql = 'DELETE FROM ' . $s_club . ' WHERE club_id IN (' . $s_ids . ') ';
		$o_result = $this->GetDataConnection()->query($s_sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}

}
?>