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
			$competition_intro = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $competition_intro));
			$competition_intro = preg_replace("/\s+/s", " ", $competition_intro);

			$publicContact = htmlentities($competition->GetContact(), ENT_QUOTES, "UTF-8", false);
            $publicContact = XhtmlMarkup::ApplyCharacterEntities($publicContact);
			$publicContact = XhtmlMarkup::ApplyParagraphs($publicContact);
			$publicContact = XhtmlMarkup::ApplyLists($publicContact);
			$publicContact = XhtmlMarkup::ApplySimpleXhtmlTags($publicContact, false);
			$publicContact = XhtmlMarkup::ApplyLinks($publicContact);
			$publicContact = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $publicContact)))));
			$publicContact = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $publicContact));
			$publicContact = preg_replace("/\s+/s", " ", $publicContact);

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

			$season_manager->ReadById([$season->GetId()]);
			$season = $season_manager->GetFirst();

			$season_intro = htmlentities($season->GetIntro(), ENT_QUOTES, "UTF-8", false);
            $season_intro = XhtmlMarkup::ApplyCharacterEntities($season_intro);
			$season_intro = XhtmlMarkup::ApplyParagraphs($season_intro);
			$season_intro = XhtmlMarkup::ApplyLinks($season_intro);
			$season_intro = XhtmlMarkup::ApplyLists($season_intro);
			$season_intro = XhtmlMarkup::ApplySimpleTags($season_intro);
            $season_intro = XhtmlMarkup::ApplyTables($season_intro);
			$season_intro = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $season_intro)))));
			$season_intro = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $season_intro));
			$season_intro = preg_replace("/\s+/s", " ", $season_intro);

			$results = htmlentities($season->GetResults(), ENT_QUOTES, "UTF-8", false);
            $results = XhtmlMarkup::ApplyCharacterEntities($results);
			$results = XhtmlMarkup::ApplyParagraphs($results);
			$results = XhtmlMarkup::ApplyLinks($results);
			$results = XhtmlMarkup::ApplyLists($results);
			$results = XhtmlMarkup::ApplySimpleTags($results);
            $results = XhtmlMarkup::ApplyTables($results);
			$results = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $results)))));
			$results = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $results));
			$results = preg_replace("/\s+/s", " ", $results);

	?>{"seasonId":<?php echo $season->GetId() 
	?>,"name":"<?php echo $season->GetName()
	?>","isLatestSeason":<?php echo $season->GetIsLatest() ? "true" : "false"
	?>,"startYear":<?php echo $season->GetStartYear()
	?>,"endYear":<?php echo $season->GetEndYear()
	?>,"introduction":<?php echo $season_intro ? "\"" . $season_intro . "\"" : "null"
	?>,"results":<?php echo $results ? "\"" . $results . "\"" : "null"
	?>,"teams":[<?php
	$first_team = true;
	foreach ($season->GetTeams() as $team) {
		if ($first_team) {
			$first_team = false;
		} else {
			?>,<?php
		}
		?>{"teamId":<?php echo $team->GetId()
		?>,"withdrawnDate":<?php 
		$withdrawn = is_object($season->TeamsWithdrawnFromLeague()->GetItemByProperty('GetId', $team->GetId()));
		if ($withdrawn) {
			$withdrawn_date = $season->GetStartYear() == $season->GetEndYear() ? mktime(0,0,0,5,1,$season->GetStartYear()) : mktime(0,0,0,12,1,$season->GetStartYear());
			echo "\"" . Date::Microformat($withdrawn_date) . "\"";
		} else { echo "null"; }
		?>}<?php 
	}
	?>],"matchTypes":[<?php
	$first_matchType = true;
	foreach ($season->MatchTypes() as $matchType) {
		if ($first_matchType) {
			$first_matchType = false;
		} else {
			?>,<?php
		}
		echo $matchType;
	}
	?>],"pointsRules":[<?php
	$first_rule = true;
	foreach ($season->PossibleResults() as $rule) {
		if ($first_rule) {
			$first_rule = false;
		} else {
			?>,<?php
		}
		?>{"matchTypeId":<?php echo $rule->GetResultType()
		?>,"homePoints":<?php echo $rule->GetHomePoints()
		?>,"awayPoints":<?php echo $rule->GetAwayPoints()
		?>}<?php 
	}
	?>],"showTable":<?php echo $season->GetShowTable() ? "true" : "false"
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