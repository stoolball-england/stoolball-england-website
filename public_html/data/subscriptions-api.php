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
		$result = $this->GetDataConnection()->query("SELECT item_id AS match_id, match_title, start_time, u.user_id, u.email, s.date_changed FROM nsa_email_subscription s
			INNER JOIN nsa_user u ON s.user_id = u.user_id
			INNER JOIN nsa_match m ON s.item_id = m.match_id");

		$first = true;
		?>[<?php
		while ($row = $result->fetch()) {

			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
		?>{"matchId":<?php echo $row->match_id
		?>,"displayName":"<?php echo "Comments on " . html_entity_decode($row->match_title) . ", " . Date::BritishDate($row->start_time)
		?>","user_id":<?php echo $row->user_id
		?>,"email":"<?php echo $row->email
		?>","date_changed":"<?php echo Date::Microformat($row->date_changed)
		?>"}<?php
		}
		?>]<?php

		$result->closeCursor();

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>