<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('stoolball/clubs/club-manager.class.php');

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
	}

	public function OnLoadPageData()
	{
		# Read all roles to be migrated
		$manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());
		if (isset($_GET['type']) && $_GET["type"] == "schools") {
			$manager->FilterByClubType(Club::SCHOOL);
		} else {
			$manager->FilterByClubType(Club::STOOLBALL_CLUB);
		}
		$manager->ReadById();
		$first = true;
		?>[<?php
		foreach ($manager->GetItems() as $club) {
			
			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"clubId":<?php echo $club->GetId();
		?>,"name":"<?php echo $club->GetName() 
		?>","playsOutdoors":<?php echo is_null($club->GetPlaysOutdoors()) ? "null" : ($club->GetPlaysOutdoors() ? "true" : "false")
		?>,"playsIndoors":<?php echo is_null($club->GetPlaysIndoors()) ? "null" : ($club->GetPlaysIndoors() ? "true" : "false")
		?>,"twitterAccount":"<?php echo $club->GetTwitterAccount()
		?>","facebookUrl":"<?php echo $club->GetFacebookUrl()
		?>","instagramAccount":"<?php echo $club->GetInstagramAccount()
		?>","clubmarkAccredited":<?php echo $club->GetClubmarkAccredited() ? "true" : "false"
		?>,"howManyPlayers":<?php echo is_null($club->GetHowManyPlayers()) ? "null" : $club->GetHowManyPlayers()
		?>,"route":"<?php echo $club->GetShortUrl()
		?>","dateCreated":<?php echo $club->GetDateAdded() === 0 ? "null" : "\"" . Date::Microformat($club->GetDateAdded()) . "\"" 
		?>,"dateUpdated":<?php echo $club->GetDateChanged() === 0 ? "null" : "\"" . Date::Microformat($club->GetDateChanged()) . "\""
		?>}<?php
		}
		?>]<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>