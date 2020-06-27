<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('forums/forum-message.class.php');

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
			"SELECT item_id AS match_id, m.date_added, message, u.user_id, u.email
			FROM nsa_forum_message m INNER JOIN nsa_user u ON m.user_id = u.user_id
			WHERE item_type IS NOT NULL");

		$first = true;
		?>[<?php
		while ($row = $result->fetch()) {

			$message = new ForumMessage($this->GetSettings(), $this->GetAuthenticationManager()->GetUser());
			$message->SetBody($row->message);
			$message_html = $message->GetFormattedBody($this->GetSettings());

			$message_html = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $message_html)))));
			$message_html = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', str_replace('&nbsp;', ' ', str_replace('&amp;', '&', str_replace('&quot;', '\"', $message_html)))));
			$message_html = preg_replace("/\s+/s", " ", $message_html);

			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
		?>{"matchId":<?php echo $row->match_id ? $row->match_id : "null"
		?>,"date_added":"<?php echo Date::Microformat($row->date_added)
		?>","message":"<?php echo $message_html
		?>","user_id":<?php echo $row->user_id ? $row->user_id : "null"
		?>,"email":<?php echo $row->email ? "\"" . $row->email . "\"" : "null"
		?>}<?php
		}
		?>]<?php

		$result->closeCursor();

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>