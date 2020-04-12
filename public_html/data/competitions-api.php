<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/season-manager.class.php');

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
		$competition_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
		$season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
		$competition_manager->ReadById();
		$first = true;
		?>[<?php
		foreach ($competition_manager->GetItems() as $competition) {

			$season_manager->ReadByCompetitionId([$competition->GetId()]);
			$seasons = $season_manager->GetItems();

			$competition_intro = htmlentities($competition->GetIntro(), ENT_QUOTES, "UTF-8", false);
			$competition_intro = XhtmlMarkup::ApplyParagraphs($competition_intro);
			$competition_intro = XhtmlMarkup::ApplyLists($competition_intro);
			$competition_intro = XhtmlMarkup::ApplySimpleXhtmlTags($competition_intro, false);
			$competition_intro = XhtmlMarkup::ApplyLinks($competition_intro);
			$competition_intro = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $competition_intro)))));

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
		?>","introduction":<?php echo $competition_intro ? "\"" . $competition_intro . "\"" : "null"
		?>,"publicContactDetails":<?php echo $publicContact ? "\"" . $publicContact . "\"" : "null"
		?>,"website":<?php echo $competition->GetWebsiteUrl() ? "\"" . $competition->GetWebsiteUrl() . "\"" : "null"
		?>,"twitterAccount":<?php echo $competition->GetTwitterAccount() ? "\"" . $competition->GetTwitterAccount() . "\"" : "null"
		?>,"facebookUrl":<?php echo $competition->GetFacebookUrl() ? "\"" . $competition->GetFacebookUrl() . "\"" : "null"
		?>,"instagramAccount":<?php echo $competition->GetInstagramAccount() ? "\"" . $competition->GetInstagramAccount() . "\"" : "null"
		?>,"notificationEmail":<?php echo $competition->GetNotificationEmail() ? "\"" . $competition->GetNotificationEmail() . "\"" : "null"
		?>,"playersPerTeam":<?php echo $competition->GetMaximumPlayersPerTeam()
		?>,"overs":<?php echo $competition->GetOvers()
		?>,"playerType":<?php echo $competition->GetPlayerType()-1
		?>,"seasons":[<?php
$first_season = true;
		foreach ($seasons as $season) {
			if ($first_season) {
				$first_season = false;
			} else {
				?>,<?php
			}

			$season_intro = htmlentities($season->GetIntro(), ENT_QUOTES, "UTF-8", false);
            $season_intro = XhtmlMarkup::ApplyCharacterEntities($season_intro);
			$season_intro = XhtmlMarkup::ApplyParagraphs($season_intro);
			$season_intro = XhtmlMarkup::ApplyLinks($season_intro);
			$season_intro = XhtmlMarkup::ApplyLists($season_intro);
			$season_intro = XhtmlMarkup::ApplySimpleTags($season_intro);
            $season_intro = XhtmlMarkup::ApplyTables($season_intro);
			$season_intro = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $season_intro)))));
			$season_intro = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $season_intro));

			$results = htmlentities($season->GetResults(), ENT_QUOTES, "UTF-8", false);
            $results = XhtmlMarkup::ApplyCharacterEntities($results);
			$results = XhtmlMarkup::ApplyParagraphs($results);
			$results = XhtmlMarkup::ApplyLinks($results);
			$results = XhtmlMarkup::ApplyLists($results);
			$results = XhtmlMarkup::ApplySimpleTags($results);
            $results = XhtmlMarkup::ApplyTables($results);
			$results = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $results)))));
			$results = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $results));

	?>{"seasonId":<?php echo $season->GetId() 
	?>,"name":"<?php echo $season->GetName()
	?>","isLatestSeason":<?php echo $season->GetIsLatest() ? "true" : "false"
	?>,"startYear":<?php echo $season->GetStartYear()
	?>,"endYear":<?php echo $season->GetEndYear()
	?>,"introduction":<?php echo $season_intro ? "\"" . $season_intro . "\"" : "null"
	?>,"results":<?php echo $results ? "\"" . $results . "\"" : "null"
	?>,"showTable":<?php echo $season->GetShowTable() ? "true" : "false"
	?>,"showRunsScored":<?php echo $season->GetShowTableRunsScored() ? "true" : "false"
	?>,"showRunsConceded":<?php echo $season->GetShowTableRunsConceded() ? "true" : "false"
	?>,"route":"<?php echo $season->GetShortUrl()
	?>","dateCreated":"<?php echo Date::Microformat($season->GetDateAdded())
	?>","dateUpdated":"<?php echo Date::Microformat($season->GetDateUpdated())
?>"}<?php
	}
		?>],"route":"<?php echo $competition->GetShortUrl()
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