<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('stoolball/competition-manager.class.php');

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
		# Read all to be migrated
		$manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
		$manager->ReadById();
		$first = true;
		?>[<?php
		foreach ($manager->GetItems() as $competition) {

			$intro = htmlentities($competition->GetIntro(), ENT_QUOTES, "UTF-8", false);
			$intro = XhtmlMarkup::ApplyParagraphs($intro);
			$intro = XhtmlMarkup::ApplyLists($intro);
			$intro = XhtmlMarkup::ApplySimpleXhtmlTags($intro, false);
			$intro = XhtmlMarkup::ApplyLinks($intro);
			$intro = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $intro)))));

			$publicContact = htmlentities($competition->GetContact(), ENT_QUOTES, "UTF-8", false);
            $publicContact = XhtmlMarkup::ApplyCharacterEntities($publicContact);
			$publicContact = XhtmlMarkup::ApplyParagraphs($publicContact);
			$publicContact = XhtmlMarkup::ApplyLists($publicContact);
			$publicContact = XhtmlMarkup::ApplySimpleXhtmlTags($publicContact, false);
			$publicContact = XhtmlMarkup::ApplyLinks($publicContact);
			$publicContact = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $publicContact)))));

			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"competitionId":<?php echo $competition->GetId();
		?>,"name":"<?php echo $competition->GetName() 
		?>","introduction":<?php echo $intro ? "\"" . $intro . "\"" : "null"
		?>,"publicContactDetails":<?php echo $publicContact ? "\"" . $publicContact . "\"" : "null"
		?>,"website":<?php echo $competition->GetWebsiteUrl() ? "\"" . $competition->GetWebsiteUrl() . "\"" : "null"
		?>,"twitterAccount":<?php echo $competition->GetTwitterAccount() ? "\"" . $competition->GetTwitterAccount() . "\"" : "null"
		?>,"facebookUrl":<?php echo $competition->GetFacebookUrl() ? "\"" . $competition->GetFacebookUrl() . "\"" : "null"
		?>,"instagramAccount":<?php echo $competition->GetInstagramAccount() ? "\"" . $competition->GetInstagramAccount() . "\"" : "null"
		?>,"notificationEmail":<?php echo $competition->GetNotificationEmail() ? "\"" . $competition->GetNotificationEmail() . "\"" : "null"
		?>,"playersPerTeam":<?php echo $competition->GetMaximumPlayersPerTeam()
		?>,"overs":<?php echo $competition->GetOvers()
		?>,"playerType":<?php echo $competition->GetPlayerType()-1
		?>,"route":"<?php echo $competition->GetShortUrl()
		?>","untilDate":<?php echo $competition->GetIsActive() ? "null" : "\"" . Date::Microformat() . "\"" 
		?>,"dateCreated":"<?php echo Date::Microformat($competition->GetDateAdded())
		?>","dateUpdated":"<?php echo Date::Microformat($competition->GetDateUpdated())
		?>"}<?php
		}
		?>]<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>