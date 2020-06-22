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

	public function OnLoadPageData()
	{
		$result = $this->GetDataConnection()->query(
			"SELECT match_id, player_of_match_id, player_of_match_home_id, player_of_match_away_id
			FROM nsa_match
			WHERE player_of_match_id IS NOT NULL OR player_of_match_home_id IS NOT NULL OR player_of_match_away_id IS NOT NULL");

		$first = true;
		?>[<?php
		while ($row = $result->fetch()) {

			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
		?>{"matchId":<?php echo $row->match_id
		?>,"player_of_match_id":<?php echo $row->player_of_match_id ? $row->player_of_match_id : "null"
		?>,"player_of_match_home_id":<?php echo $row->player_of_match_home_id ? $row->player_of_match_home_id : "null"
		?>,"player_of_match_away_id":<?php echo $row->player_of_match_away_id ? $row->player_of_match_away_id : "null"
		?>}<?php
		}
		?>]<?php

		$result->closeCursor();

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>