<?php
require_once('data/data-manager.class.php');
require_once("player.class.php");

/**
 * Read and write players from the database
 * @author Rick
 *
 */
class PlayerManager extends DataManager
{
	private $is_internal_delete = false;
	private $filter_minimum_matches = 0;

	/**
	 * Filter supporting queries to include only players who have played at least the minimum number of matches
	 * @param int $minimum
	 */
	public function FilterByMinimumMatches($minimum)
	{
		$this->filter_minimum_matches = (int)$minimum;
	}

	/**
	 * @return PlayerManager
	 * @param SiteSettings $settings
	 * @param MySqlConnection $connection
	 * @desc Creates a new PlayerManager
	 */
	public function __construct(SiteSettings $settings, MySqlConnection $connection)
	{
		parent::DataManager($settings, $connection);
		$this->SetItemClass('Player');
	}

	/**
	 * Reads a player matching the supplied id
	 * @param int $player_id
	 * @return void
	 */
	public function ReadPlayerById($player_id = null)
	{
		$players = $this->GetSettings()->GetTable("Player");
		
		$sql = "SELECT $players.player_id, $players.player_name, $players.total_matches, $players.first_played, $players.last_played, $players.player_role, 
		$players.short_url, $players.update_search, 
		team.team_id, team.team_name, team.short_url AS team_short_url
		FROM $players INNER JOIN nsa_team AS team ON $players.team_id = team.team_id ";

		$where = "";
		if ($player_id) $where .= "$players.player_id = " . Sql::ProtectNumeric($player_id, false) . " ";
		if ($this->filter_minimum_matches >= 1)
        {
            if ($where) $where .= "AND ";
            $where .= "$players.total_matches >= " . Sql::ProtectNumeric($this->filter_minimum_matches, false) . " ";
        }
		if ($where) $sql .= "WHERE $where";

		$sql .= "ORDER BY $players.player_name ASC";

		$result = $this->GetDataConnection()->query($sql);

		if (!$this->GetDataConnection()->isError()) $this->BuildItems($result);

		$result->closeCursor();
		unset($result);
	}

	/**
	 * Reads a list of all the players in the given teams
	 * @param int[] $team_ids
	 * @return void
	 */
	public function ReadPlayersInTeam($team_ids)
	{
		$this->ValidateNumericArray($team_ids);

		$players = $this->GetSettings()->GetTable("Player");
		
		$sql = "SELECT $players.player_id, $players.player_name, $players.short_url, $players.player_role, $players.last_played,
		team.team_id, team.team_name, team.short_url AS team_short_url
		FROM $players INNER JOIN nsa_team AS team ON $players.team_id = team.team_id
		WHERE team.team_id IN (" . implode(",", $team_ids) . ") AND $players.total_matches IS NOT NULL AND $players.total_matches > 0
		ORDER BY team.team_id ASC, $players.player_role ASC, $players.player_name ASC";
		$result = $this->GetDataConnection()->query($sql);

		if (!$this->GetDataConnection()->isError()) $this->BuildItems($result);

		$result->closeCursor();
	}

	/**
	 * Reads the player ids of everyone involved in a match
	 * @param int[] $match_ids
	 */
	public function ReadPlayersInMatch($match_ids)
	{
		$this->ValidateNumericArray($match_ids);

		$matches = $this->GetSettings()->GetTable("Match");
		$players = $this->GetSettings()->GetTable("Player");
		$teams = $this->GetSettings()->GetTable("MatchTeam");
		$batting = $this->GetSettings()->GetTable("Batting");
		$bowling = $this->GetSettings()->GetTable("Bowling");

		$match_ids = implode(",", $match_ids);
		$player_ids = array();

		$sql = "SELECT player_of_match_id, player_of_match_home_id, player_of_match_away_id
					FROM $matches WHERE match_id IN ($match_ids)";
		$result = $this->GetDataConnection()->query($sql);

		while ($row = $result->fetch())
		{
			if (!in_array($row->player_of_match_id, $player_ids))
			{
				if (!is_null($row->player_of_match_id))$player_ids[] = $row->player_of_match_id;
			}

			if (!in_array($row->player_of_match_home_id, $player_ids))
			{
				if (!is_null($row->player_of_match_home_id)) $player_ids[] = $row->player_of_match_home_id;
			}

			if (!in_array($row->player_of_match_away_id, $player_ids))
			{
				if (!is_null($row->player_of_match_away_id)) $player_ids[] = $row->player_of_match_away_id;
			}
		}

		$sql = "SELECT player_id, dismissed_by_id, bowler_id
					FROM $batting bat INNER JOIN $teams mt ON bat.match_team_id = mt.match_team_id
					INNER JOIN $matches m ON mt.match_id = m.match_id
					WHERE m.match_id IN ($match_ids)";
		$result = $this->GetDataConnection()->query($sql);

		while ($row = $result->fetch())
		{
			if (!in_array($row->player_id, $player_ids))
			{
				$player_ids[] = $row->player_id;
			}

			if (!in_array($row->dismissed_by_id, $player_ids))
			{
				if (!is_null($row->dismissed_by_id)) $player_ids[] = $row->dismissed_by_id;
			}

			if (!in_array($row->bowler_id, $player_ids))
			{
				if (!is_null($row->bowler_id)) $player_ids[] = $row->bowler_id;
			}
		}

		$sql = "SELECT player_id
					FROM $bowling bowl INNER JOIN $teams mt ON bowl.match_team_id = mt.match_team_id
					INNER JOIN $matches m ON mt.match_id = m.match_id
					WHERE m.match_id IN ($match_ids)";
		$result = $this->GetDataConnection()->query($sql);

		while ($row = $result->fetch())
		{
			if (!in_array($row->player_id, $player_ids))
			{
				$player_ids[] = $row->player_id;
			}
		}

		return $player_ids;
	}

	/**
	 * Reads a list of all the players in the given teams, optimised for autocomplete
	 * @param int[] $team_ids
	 * @return void
	 */
	public function ReadPlayersForAutocomplete($team_ids)
	{
		$this->ValidateNumericArray($team_ids);

		$players = $this->GetSettings()->GetTable("Player");
		
		$sql = "SELECT $players.player_id, $players.player_name, $players.total_matches, $players.first_played, $players.last_played,
		team.team_id, team.team_name
		FROM $players INNER JOIN nsa_team AS team ON $players.team_id = team.team_id
		WHERE team.team_id IN (" . implode(",", $team_ids) . ") AND $players.player_role = " . Player::PLAYER . "
		ORDER BY team.team_id ASC, $players.probability DESC, $players.player_name ASC";
		$result = $this->GetDataConnection()->query($sql);

		if (!$this->GetDataConnection()->isError()) $this->BuildItems($result);

		$result->closeCursor();
	}

	/**
	 * Populates the collection of players from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $data
	 */
	protected function BuildItems(MySqlRawData $data)
	{
		$this->Clear();

		while ($row = $data->fetch())
		{
			$player = new Player($this->GetSettings());
			$player->SetId($row->player_id);
			$player->SetName($row->player_name);
			if (isset($row->total_matches)) $player->SetTotalMatches($row->total_matches);
			if (isset($row->first_played)) $player->SetFirstPlayedDate($row->first_played);
			if (isset($row->last_played)) $player->SetLastPlayedDate($row->last_played);
			if (isset($row->player_role)) $player->SetPlayerRole($row->player_role);
			if (isset($row->short_url)) $player->SetShortUrl($row->short_url);
			if (isset($row->update_search) and $row->update_search == 1) $player->SetSearchUpdateRequired();
            $player->Team()->SetId($row->team_id);
			$player->Team()->SetName($row->team_name);
			if (isset($row->team_short_url)) $player->Team()->SetShortUrl($row->team_short_url);
			$this->Add($player);
		}
	}

	/**
	 * Populates the player's id if they have already been recorded
	 * @param $player
	 * @return Player
	 */
	public function MatchExistingPlayer(Player $player)
	{
		$sql = "SELECT player_id FROM " . $this->GetSettings()->GetTable("Player").
		" WHERE comparable_name = " . Sql::ProtectString($this->GetDataConnection(), $player->GetComparableName()) . "
		AND team_id " . Sql::ProtectNumeric($player->Team()->GetId(), true, true);

		$result = $this->GetDataConnection()->query($sql);
		if ($row = $result->fetch())
		{
            $player->SetId($row->player_id);
		}
		$result->closeCursor();

		return $player;
	}

	/**
	 * Merges the records for two players, retaining the id of the destination player
	 * @param int $source_player_id
	 * @param int $destination_player_id
	 * @return void
	 */
	public function MergePlayers(Player $source_player, Player $destination_player)
	{
		if (!$source_player->GetId()) throw new Exception("source_player must have an Id");
		if (!$destination_player->GetId()) throw new Exception("destination_player must have an Id");
		if ($source_player->GetPlayerRole() != PLAYER::PLAYER) throw new Exception("Cannot merge source player because it's an extras player");
		if ($destination_player->GetPlayerRole() != PLAYER::PLAYER) throw new Exception("Cannot merge destination player because it's an extras player");

		$players = $this->GetSettings()->GetTable("Player");
		$batting = $this->GetSettings()->GetTable("Batting");
		$bowling = $this->GetSettings()->GetTable("Bowling");
		$matches = $this->GetSettings()->GetTable("Match");
		$statistics = $this->GetSettings()->GetTable("PlayerMatch");

		# Make a note of matches where source player was involved
		$sql = "SELECT DISTINCT match_team_id FROM $batting WHERE player_id = " . $source_player->GetId();
		$result = $this->GetDataConnection()->query($sql);
		$source_batted = array();
		while ($row = $result->fetch())
		{
			$source_batted[] = $row->match_team_id;
		}

		$sql = "SELECT match_team_id FROM $statistics
				WHERE player_id = " . $source_player->GetId() . " AND (run_outs > 0 OR catches > 0)";
		$result = $this->GetDataConnection()->query($sql);
		$source_fielded = array();
		while ($row = $result->fetch())
		{
			$source_fielded[] = $row->match_team_id;
		}

		$sql = "SELECT match_team_id FROM $statistics
				WHERE player_id = " . $source_player->GetId() . " AND wickets IS NOT NULL";
		$result = $this->GetDataConnection()->query($sql);
		$source_bowled = array();
		while ($row = $result->fetch())
		{
			$source_bowled[] = $row->match_team_id;
		}

		$sql = "SELECT match_id FROM $statistics
				WHERE player_id = " . $source_player->GetId() . " AND player_of_match = 1";
		$result = $this->GetDataConnection()->query($sql);
		$source_player_of_match = array();
		while ($row = $result->fetch())
		{
			$source_player_of_match[] = $row->match_id;
		}

		# Transfer batting and bowling
		$this->GetDataConnection()->query(
		"UPDATE $batting SET player_id = " . Sql::ProtectNumeric($destination_player->GetId()) . "
		WHERE player_id = " . Sql::ProtectNumeric($source_player->GetId()));

		$this->GetDataConnection()->query(
		"UPDATE $batting SET dismissed_by_id = " . Sql::ProtectNumeric($destination_player->GetId()) . "
		WHERE dismissed_by_id = " . Sql::ProtectNumeric($source_player->GetId()));

		$this->GetDataConnection()->query(
		"UPDATE $batting SET bowler_id = " . Sql::ProtectNumeric($destination_player->GetId()) . "
		WHERE bowler_id = " . Sql::ProtectNumeric($source_player->GetId()));

		$this->GetDataConnection()->query(
		"UPDATE $bowling SET player_id = " . Sql::ProtectNumeric($destination_player->GetId()) . "
		WHERE player_id = " . Sql::ProtectNumeric($source_player->GetId()));

		# Update dismissals in stats table too, because then fielding statistics will update correctly below.
		# Normally dismissals are updated with the batting, but here it's quite possible we are only updating the fielding.
		$this->GetDataConnection()->query(
		"UPDATE $statistics SET caught_by = " . Sql::ProtectNumeric($destination_player->GetId()) . "
		WHERE caught_by = " . Sql::ProtectNumeric($source_player->GetId()));

		$this->GetDataConnection()->query(
		"UPDATE $statistics SET run_out_by = " . Sql::ProtectNumeric($destination_player->GetId()) . "
		WHERE run_out_by = " . Sql::ProtectNumeric($source_player->GetId()));

		if (!$this->is_internal_delete)
		{
			# Doing an internal delete the destination player will be Unknown. Transfer batting and bowling
			# because that preserves the position for other bowlers and batters as well as related statistics
			# such as number of runs. But set player of the match to null because there's not much value in
			# setting it unknown.

			# Transfer player of the match award
			$this->GetDataConnection()->query(
			"UPDATE $matches SET player_of_match_id = " . Sql::ProtectNumeric($destination_player->GetId()) . ",
			date_changed = " . gmdate('U') . "
			WHERE player_of_match_id = " . Sql::ProtectNumeric($source_player->GetId()));

			$this->GetDataConnection()->query(
			"UPDATE $matches SET player_of_match_home_id = " . Sql::ProtectNumeric($destination_player->GetId()) . ",
			date_changed = " . gmdate('U') . "
			WHERE player_of_match_home_id = " . Sql::ProtectNumeric($source_player->GetId()));

			$this->GetDataConnection()->query(
			"UPDATE $matches SET player_of_match_away_id = " . Sql::ProtectNumeric($destination_player->GetId()) . ",
			date_changed = " . gmdate('U') . "
			WHERE player_of_match_away_id = " . Sql::ProtectNumeric($source_player->GetId()));

			# If a user has claimed either player, remember that. If two different claimants, prefer the destination one.
			if ($source_player->GetUser() instanceof User and $source_player->GetUser()->GetId())
			{
				$this->GetDataConnection()->query(
				"UPDATE $players
				SET user_id = " . Sql::ProtectNumeric($source_player->GetUser()->GetId()) . ",
				date_changed = " . gmdate('U') . "
				WHERE player_id = " . Sql::ProtectNumeric($destination_player->GetId()) . " AND user_id = NULL");
			}

			# Now that the source player's data has been moved, delete the source player
			$this->Delete(array($source_player->GetId()));
		}

		# Recalculate all the derived data

		# Note: this method is tightly integrated with the Delete() method. They call each other. When the source player
		# is deleted, above, it will call back into this method before all the derived statistics for the source player
		# have gone. Therefore the queries at the top of this method will find the source player still exists. That in turn
		# leads to these methods being called for a player which has only derived statistics, and no actual data. It is
		# important therefore to call DeleteObsoleteStatistics() to clear out the redundant records as soon as they created.
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		if (count($source_batted)) $statistics_manager->UpdateBattingStatistics(array($destination_player->GetId()), $source_batted);
		if (count($source_fielded)) $statistics_manager->UpdateFieldingStatistics(array($destination_player->GetId()), $source_fielded);
		if (count($source_bowled)) $statistics_manager->UpdateBowlingStatistics(array($destination_player->GetId()), $source_bowled);
		foreach ($source_player_of_match as $match_id)
		{
			$statistics_manager->UpdatePlayerOfTheMatchStatistics($match_id);
			$statistics_manager->DeleteObsoleteStatistics($match_id);
		}
		$statistics_manager->UpdatePlayerStatistics(array($destination_player->GetId()));
		unset($statistics_manager);
	}

	/**
	 * @return int
	 * @param Player $player
     * @param bool $rename_only
	 * @desc Save the supplied player to the database, and return the id
	 */
	public function SavePlayer($player, $rename_only=false)
	{
		/* @var $player Player */
		if (!$player instanceof Player) throw new Exception("Unable to save player");

		# Get tables
		$players = $this->GetSettings()->GetTable('Player');
		$matches = $this->GetSettings()->GetTable("Match");
		$team_matches = $this->GetSettings()->GetTable("MatchTeam");

		# The short URL depends on the team. Ensure we have the team's short URL before calculating
		# the short URL for the player.
		if ($player->Team()->GetId())
		{
			$sql = "SELECT short_url FROM nsa_team WHERE team_id = " . Sql::ProtectNumeric($player->Team()->GetId(), false);
			$result = $this->GetDataConnection()->query($sql);
			if ($row = $result->fetch())
			{
                $player->Team()->SetShortUrl($row->short_url);
			}
			$result->closeCursor();
		}

		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$new_short_url = $url_manager->EnsureShortUrl($player);

		$corrected_player_name = ($player->GetPlayerRole() == Player::PLAYER) ? $this->CapitaliseName($player->GetName()) : $player->GetName();
 
		# if no id, it's new; otherwise update
		if ($player->GetId())
		{
			$sql = "UPDATE $players SET
			player_name = " . Sql::ProtectString($this->GetDataConnection(), $corrected_player_name, false) . ",
			comparable_name = " . Sql::ProtectString($this->GetDataConnection(), $player->GetComparableName(), false) . ",
			short_url = " . Sql::ProtectString($this->GetDataConnection(), $player->GetShortUrl(), false) . ",
            update_search = 1,  
			date_changed = " . gmdate('U');
            if (!$rename_only) {
                $sql .= ", team_id = " . Sql::ProtectNumeric($player->Team()->GetId(), false) . ",
                           player_role = " . Sql::ProtectNumeric($player->GetPlayerRole(), false);
            }
			$sql .= " WHERE player_id = " . Sql::ProtectNumeric($player->GetId());

			$this->LoggedQuery($sql);
		}
		else
		{
			$sql = "INSERT INTO $players SET
			player_name = " . Sql::ProtectString($this->GetDataConnection(), $corrected_player_name, false) . ",
			comparable_name = " . Sql::ProtectString($this->GetDataConnection(), $player->GetComparableName(), false) . ",
			team_id = " . Sql::ProtectNumeric($player->Team()->GetId(), false) . ",
			player_role = " . Sql::ProtectNumeric($player->GetPlayerRole(), false) . ",
			short_url = " . Sql::ProtectString($this->GetDataConnection(), $player->GetShortUrl(), false) . ",
			update_search = 1,  
			date_added = " . gmdate('U') . ",
			date_changed = " . gmdate('U');

			$this->LoggedQuery($sql);

			# get autonumber
			$player->SetId($this->GetDataConnection()->insertID());
		}

		# Regenerate short URLs
		if (is_object($new_short_url))
		{
			$new_short_url->SetParameterValuesFromObject($player);
			$url_manager->Save($new_short_url);
		}
		unset($url_manager);
        
		return $player->GetId();
	}

	/**
	 * Try to ensure names are correctly capitalised
	 * @param string $player_name
	 * @return string
	 */
	private function CapitaliseName($player_name)
	{
		$segments = explode(" ", $player_name);
		$segments = array_map(array($this, "CapitaliseNameSegment"), $segments);
		return implode(" ", $segments);
	}

	/**
	 * Ensure one word within a name is correctly capitalised
	 * @param string $segment
	 * @return string
	 */
	private function CapitaliseNameSegment($segment)
	{
		$exceptions = array("de", "la", "di", "da", "della", "van", "von");
		if ($segment == strtoupper($segment)) $segment = strtolower($segment);
		if (in_array($segment, $exceptions, true))
		{
			return $segment;
		}
		else
		{
			return ucfirst($segment);
		}
	}

	/**
	 * Finds an existing or saves a new player and returns the id
	 * @param Player $player
	 * @return int
	 */
	public function SaveOrMatchPlayer($player)
	{
		# This method is called regardless and there may be no player,
		# which is why there's no type hint for the parameter.
		if (!$player instanceof Player) return null;

		$player = $this->MatchExistingPlayer($player);

		if (!$player->GetId())
		{
			$this->SavePlayer($player);
		}

		return $player->GetId();
	}

	/**
     * Creates players for a new team to represent types of extras conceded
     * @param int $team_id
     */
	public function CreateExtrasPlayersForTeam($team_id) 
    {
        $player = new Player($this->GetSettings());
        $player->Team()->SetId($team_id);
        $player->SetPlayerRole(Player::NO_BALLS);
        $this->SavePlayer($player);

        $player = new Player($this->GetSettings());
        $player->Team()->SetId($team_id);
        $player->SetPlayerRole(Player::WIDES);
        $this->SavePlayer($player);

        $player = new Player($this->GetSettings());
        $player->Team()->SetId($team_id);
        $player->SetPlayerRole(Player::BYES);
        $this->SavePlayer($player);

        $player = new Player($this->GetSettings());
        $player->Team()->SetId($team_id);
        $player->SetPlayerRole(Player::BONUS_RUNS);
        $this->SavePlayer($player);
    }
    
    /**
     * Reset the flag which indicates that search needs to be updated
     * @param $player_id int
     */
    public function SearchUpdated($player_id) 
    {
        if (!is_integer($player_id)) throw new Exception("player_id must be an integer");
        $sql = "UPDATE nsa_player SET update_search = 0 WHERE player_id = " . SQL::ProtectNumeric($player_id, false);
        $this->GetDataConnection()->query($sql);
    }
	
	/**
	 * @access public
	 * @return void
	 * @param int[] $player_ids
	 * @desc Delete from the database the players matching the supplied ids
	 */
	public function Delete($player_ids)
	{
		# check parameter
		$this->ValidateNumericArray($player_ids);

		# Get tables
		$players = $this->GetSettings()->GetTable('Player');
		$matches = $this->GetSettings()->GetTable("Match");
		$batting = $this->GetSettings()->GetTable("Batting");
		$bowling = $this->GetSettings()->GetTable("Bowling");
		$stats = $this->GetSettings()->GetTable('PlayerMatch');
		$player_id_list = implode(', ', $player_ids);

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$sql = "SELECT short_url FROM $players WHERE player_id IN ($player_id_list)";
		$result = $this->GetDataConnection()->query($sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		# remove player of the match awards
		$sql = "UPDATE $matches SET player_of_match_id = NULL WHERE player_of_match_id IN ($player_id_list)";
		$this->GetDataConnection()->query($sql);

		$sql = "UPDATE $matches SET player_of_match_home_id = NULL WHERE player_of_match_home_id IN ($player_id_list)";
		$this->GetDataConnection()->query($sql);

		$sql = "UPDATE $matches SET player_of_match_away_id = NULL WHERE player_of_match_away_id IN ($player_id_list)";
		$this->GetDataConnection()->query($sql);

		# Reassign batting and bowling to 'unknown' player
		$unknown_player = new Player($this->GetSettings());
		$unknown_player->SetName("Unknown");
		foreach($player_ids as $player_id)
		{
			$sql = "SELECT team_id FROM $players WHERE player_id  = " . Sql::ProtectNumeric($player_id);
			$result	= $this->GetDataConnection()->query($sql);
			$row = $result->fetch();
			$unknown_player->Team()->SetId($row->team_id);
			$unknown_player->SetId($this->SaveOrMatchPlayer($unknown_player));

			$player = new Player($this->GetSettings());
			$player->SetId($player_id);

			$this->is_internal_delete = true;
			$this->MergePlayers($player, $unknown_player);
			$this->is_internal_delete = false;
		}

		# Delete statistics
		$sql = "DELETE FROM $stats WHERE player_id IN ($player_id_list)";
		$this->GetDataConnection()->query($sql);

		# delete the player
		$sql = "DELETE FROM $players WHERE player_id IN ($player_id_list)";
		$this->GetDataConnection()->query($sql);
 	}

}