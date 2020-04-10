<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('stoolball/team-manager.class.php');

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
		$manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
		$manager->FilterByTeamType([]);
		$manager->ReadById();
		$first = true;
		?>[<?php
		foreach ($manager->GetItems() as $team) {

			$intro = htmlentities($team->GetIntro(), ENT_QUOTES, "UTF-8", false);
			$intro = XhtmlMarkup::ApplyParagraphs($intro);
			$intro = XhtmlMarkup::ApplyLists($intro);
			$intro = XhtmlMarkup::ApplySimpleXhtmlTags($intro, false);
			$intro = XhtmlMarkup::ApplyLinks($intro);
			$intro = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $intro)))));

			$age_lower = is_null($team->GetClub()) || !$team->GetClub()->GetAgeRangeLower() ? "null" : $team->GetClub()->GetAgeRangeLower();
			$age_upper = is_null($team->GetClub()) || !$team->GetClub()->GetAgeRangeUpper() ? "null" : $team->GetClub()->GetAgeRangeUpper();
			$school_years = $team->GetSchoolYears();
			for ($i = 1; $i <= 12; $i++) {
				if ($school_years[$i]) {
					$school_age_lower = $i+4;
					$school_age_upper = $i+5;
					if ($age_lower == "null" || $school_age_lower < $age_lower) $age_lower = $school_age_lower;
					if ($age_upper == "null" || $school_age_upper > $age_upper) $age_upper = $school_age_upper;
				}
			}

			$publicContact = htmlentities($team->GetContact(), ENT_QUOTES, "UTF-8", false);
			$publicContact = XhtmlMarkup::ApplyParagraphs($publicContact);
			$publicContact = XhtmlMarkup::ApplyLists($publicContact);
            $publicContact = XhtmlMarkup::ApplySimpleXhtmlTags($publicContact, false);
			$publicContact = XhtmlMarkup::ApplyLinks($publicContact);
			$publicContact = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $publicContact)))));

			$privateContact = htmlentities($team->GetPrivateContact(), ENT_QUOTES, "UTF-8", false);
			$privateContact = XhtmlMarkup::ApplyParagraphs($privateContact);
			$privateContact = XhtmlMarkup::ApplyLists($privateContact);
            $privateContact = XhtmlMarkup::ApplySimpleXhtmlTags($privateContact, false);
			$privateContact = XhtmlMarkup::ApplyLinks($privateContact);
			$privateContact = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $privateContact)))));

			$playingTimes = htmlentities($team->GetPlayingTimes(), ENT_QUOTES, "UTF-8", false);
			$playingTimes = XhtmlMarkup::ApplyParagraphs($playingTimes);
			$playingTimes = XhtmlMarkup::ApplyLists($playingTimes);
			$playingTimes = XhtmlMarkup::ApplySimpleXhtmlTags($playingTimes, false);
			$playingTimes = XhtmlMarkup::ApplyLinks($playingTimes);
			$playingTimes = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $playingTimes)))));

			$cost = XhtmlMarkup::ApplyParagraphs(htmlentities($team->GetCost(), ENT_QUOTES, "UTF-8", false));
            $cost = XhtmlMarkup::ApplyLists($cost);
            $cost = XhtmlMarkup::ApplySimpleXhtmlTags($cost, false);
            $cost = XhtmlMarkup::ApplyLinks($cost);
			$cost = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $cost)))));

			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"teamId":<?php echo $team->GetId();
		?>,"name":"<?php echo $team->GetName() 
		?>","clubId":<?php echo is_null($team->GetClub()) || !$team->GetClub()->GetId() || $team->GetClub()->GetTypeOfClub() == Club::SCHOOL ? "null" : $team->GetClub()->GetId()
		?>,"schoolId":<?php echo is_null($team->GetClub()) || !$team->GetClub()->GetId() || $team->GetClub()->GetTypeOfClub() == Club::STOOLBALL_CLUB ? "null" : $team->GetClub()->GetId()
		?>,"teamType":<?php echo $team->GetTeamType()
		?>,"playerType":<?php echo $team->GetPlayerType()-1
		?>,"ageRangeLower":<?php echo $age_lower
		?>,"ageRangeUpper":<?php echo $age_upper
		?>,"introduction":<?php echo $intro ? "\"" . $intro . "\"" : "null"
		?>,"publicContactDetails":<?php echo $publicContact ? "\"" . $publicContact . "\"" : "null"
		?>,"privateContactDetails":<?php echo $privateContact ? "\"" . $privateContact . "\"" : "null"
		?>,"playingTimes":<?php echo $playingTimes ? "\"" . $playingTimes . "\"" : "null"
		?>,"cost":<?php echo $cost ? "\"" . $cost . "\"" : "null"
		?>,"website":<?php echo $team->GetWebsiteUrl() ? "\"" . $team->GetWebsiteUrl() . "\"" : "null"
		?>,"route":"<?php echo $team->GetShortUrl()
		?>","untilDate":<?php echo $team->GetIsActive() ? "null" : "\"" . Date::Microformat() . "\"" 
		?>,"dateCreated":<?php echo $team->GetDateAdded() === 0 ? "null" : "\"" . Date::Microformat($team->GetDateAdded()) . "\"" 
		?>,"dateUpdated":<?php echo $team->GetDateUpdated() === 0 ? "null" : "\"" . Date::Microformat($team->GetDateUpdated()) . "\""
		?>}<?php
		}
		?>]<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>