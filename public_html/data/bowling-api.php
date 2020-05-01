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

		$result = $this->GetDataConnection()->query("SELECT COUNT(*) AS total FROM nsa_bowling");
		$row = $result->fetch();
		return (int)$row->total;
	}

	public function OnLoadPageData()
	{
		# Read all to be migrated
		$total_bowling = $this->ReadTotalForMigration();
		$from = (int)$_GET['from'];
		$to = (int)$_GET['batchSize'];

		$result = $this->GetDataConnection()->query("SELECT bowling_id FROM nsa_bowling ORDER BY bowling_id LIMIT $from,$to");
		$ids = [];
		while($row = $result->fetch())
		{
			if (!in_array((int)$row->bowling_id, $ids, true)) {
				$ids[] = (int)$row->bowling_id;
			}
		}

		$result = $this->GetDataConnection()->query(
			"SELECT player_id, position, balls_bowled, no_balls, wides, runs_in_over, b.date_added,
			mt.match_id, mt.team_id
			FROM nsa_bowling b INNER JOIN nsa_match_team mt ON b.match_team_id = mt.match_team_id
			WHERE b.bowling_id IN (" . join(', ', $ids) . ") 
			ORDER BY b.bowling_id");

		$first = true;
		?>{"total":<?php echo $total_bowling ?>,"performances":[<?php
		while ($row = $result->fetch()) {
		
			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"matchId":<?php echo $row->match_id
		?>,"teamId":<?php echo $row->team_id
		?>,"playerId":<?php echo $row->player_id
		?>,"overNumber":<?php echo $row->position
		?>,"ballsBowled":<?php echo is_null($row->balls_bowled) ? "null" : $row->balls_bowled
		?>,"noBalls":<?php echo is_null($row->no_balls) ? "null": $row->no_balls
		?>,"wides":<?php echo is_null($row->wides) ? "null": $row->wides
		?>,"runsConceded":<?php echo is_null($row->runs_in_over) ? "null": $row->runs_in_over
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