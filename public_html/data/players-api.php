<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('stoolball/player-manager.class.php');

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

	public function OnLoadPageData()
	{
		# Read all to be migrated
		$manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$total_players = $manager->ReadTotalForMigration();
		$manager->ReadForMigration((int)$_GET['from'],(int)$_GET['batchSize']);
		$first = true;
		?>{"total":<?php echo $total_players ?>,"players":[<?php
		foreach ($manager->GetItems() as $player) {
		
			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"playerId":<?php echo $player->GetId()
		?>,"name":"<?php echo str_replace('&amp;', '&', preg_replace('/\s/i', ' ', $player->GetName())) 
		?>","teamId":<?php echo $player->Team()->GetId()
		?>,"firstPlayed":<?php echo is_null($player->GetFirstPlayedDate()) ? "null" : "\"" . Date::Microformat($player->GetFirstPlayedDate()) . "\"" 
		?>,"lastPlayed":<?php echo is_null($player->GetLastPlayedDate()) ? "null" : "\"" . Date::Microformat($player->GetLastPlayedDate()) . "\"" 
		?>,"totalMatches":<?php echo is_null($player->GetTotalMatches()) ? "null": $player->GetTotalMatches()
		?>,"missedMatches":<?php echo is_null($player->GetMissedMatches()) ? "null": $player->GetMissedMatches()
		?>,"probability":<?php echo is_null($player->GetProbability()) ? "null": $player->GetProbability()
		?>,"playerRole":<?php echo $player->GetPlayerRole()
		?>,"route":"<?php echo $player->GetShortUrl()
		?>","dateCreated":"<?php echo Date::Microformat($player->GetDateAdded())
		?>","dateUpdated":"<?php echo Date::Microformat($player->GetDateUpdated())
		?>"}<?php
		}
		?>]}<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>