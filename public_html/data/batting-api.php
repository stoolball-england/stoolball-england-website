<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');

class CurrentPage extends Page
{
	public function OnPageInit()
	{
		# Check that the request comes from an allowed origin
		$allowedOrigins = $this->GetSettings()->GetCorsAllowedOrigins();
		if (isset($_SERVER["HTTP_ORIGIN"]) && !in_array($_SERVER["HTTP_ORIGIN"], $allowedOrigins, true)) {
			exit();
		}

		# Check that parameters are specified
		if (!isset($_GET['from']) or !isset($_GET['batchSize']))
		{
			http_response_code(400);
			exit();
		}

		# This is a JavaScript file
		if (!headers_sent()) {
			header("Content-Type: text/javascript; charset=utf-8");
			if (isset($_SERVER["HTTP_ORIGIN"])) {
				header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
			}
		}
		
		# Require an API key to include personal contact details to avoid spam bots picking them up
        $api_keys = $this->GetSettings()->GetApiKeys();
        $valid_key = false;
        if (!isset($_GET['key']) or !in_array($_GET['key'], $api_keys)) 
        {
            exit();
		}
	}

	public function ReadTotalForMigration() {

		$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS total FROM nsa_batting");
		$row = $result->fetch();
		return (int)$row->total;
	}

	public function OnLoadPageData()
	{
		# Read all to be migrated
		$total_batting = $this->ReadTotalForMigration();
		$from = (int)$_GET['from'];
		$to = (int)$_GET['batchSize'];

		$result = $this->GetDataConnection()->query("SELECT batting_id FROM nsa_batting ORDER BY batting_id LIMIT $from,$to");
		$ids = [];
		while($row = $result->fetch())
		{
			if (!in_array((int)$row->batting_id, $ids, true)) {
				$ids[] = (int)$row->batting_id;
			}
		}

		$result = $this->GetDataConnection()->query(
			"SELECT player_id, position, how_out, dismissed_by_id, bowler_id, runs, balls_faced, b.date_added,
			mt.match_id, mt.team_id
			FROM nsa_batting b INNER JOIN nsa_match_team mt ON b.match_team_id = mt.match_team_id
			WHERE b.batting_id IN (" . join(', ', $ids) . ") 
			ORDER BY b.batting_id");

		$first = true;
		?>{"total":<?php echo $total_batting ?>,"performances":[<?php
		while ($row = $result->fetch()) {
		
			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"matchId":<?php echo $row->match_id
		?>,"teamId":<?php echo $row->team_id
		?>,"playerId":<?php echo $row->player_id
		?>,"battingPosition":<?php echo $row->position
		?>,"howOut":<?php echo $row->how_out == 11 ? "null" : $row->how_out 
		?>,"dismissedById":<?php echo is_null($row->dismissed_by_id) ? "null": $row->dismissed_by_id
		?>,"bowlerId":<?php echo is_null($row->bowler_id) ? "null": $row->bowler_id
		?>,"runsScored":<?php echo is_null($row->runs) ? "null": $row->runs
		?>,"ballsFaced":<?php echo is_null($row->balls_faced) ? "null": $row->balls_faced
		?>,"dateCreated":"<?php echo Date::Microformat($row->date_added)
		?>"}<?php
		}
		?>]}<?php

		$result->closeCursor();
		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>