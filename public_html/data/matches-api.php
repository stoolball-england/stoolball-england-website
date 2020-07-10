<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('stoolball/match-manager.class.php');

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
		if (!isset($_GET['from']) or !isset($_GET['type']) or !isset($_GET['batchSize']))
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
		# Read all matches to be migrated
		$manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
		$manager->FilterByMatchType([(int)$_GET['type']]);
		$total_matches = $manager->ReadTotalForMigration();
		$manager->ReadForMigration((int)$_GET['from'],(int)$_GET['batchSize']);
		$first = true;
		?>{"total":<?php echo $total_matches ?>,"matches":[<?php
		foreach ($manager->GetItems() as $match) {
			
			$notes = htmlentities($match->GetNotes(), ENT_QUOTES, "UTF-8", false);
            $notes = XhtmlMarkup::ApplyCharacterEntities($notes);
			$notes = XhtmlMarkup::ApplyHeadings($notes);
			$notes = XhtmlMarkup::ApplyParagraphs($notes);
			$notes = XhtmlMarkup::ApplyLists($notes);
			$notes = XhtmlMarkup::ApplySimpleTags($notes);
			$notes = XhtmlMarkup::ApplyLinks($notes);
			$notes = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $notes)))));
			$notes = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $notes));
			$notes = preg_replace("/\s+/s", " ", $notes);
			if (strpos($notes, '<p>') == -1) { $notes = "<p>$notes</p>"; }

			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"matchId":<?php echo $match->GetId();
		?>,"title":"<?php echo str_replace('&#039;', "'", str_replace('&amp;', "&", $match->GetTitle()))
		?>","customTitle": <?php echo $match->GetUseCustomTitle() ? "true" : "false"
		?>,"groundId": <?php echo (!is_null($match->GetGround()) and $match->GetGround()->GetId()) ? $match->GetGround()->GetId() : "null"
		?>,"matchType": <?php echo $match->GetMatchType()
		?>,"qualification": <?php echo $match->GetQualificationType()
		?>,"playerType": <?php echo !is_null($match->GetPlayerType()) ? $match->GetPlayerType() : PlayerType::MIXED // no null instances are legit, so default to the most inclusive
		?>,"playersPerTeam": <?php echo $match->GetIsMaximumPlayersPerTeamKnown() ? $match->GetMaximumPlayersPerTeam() : "null"
		?>,"overs": <?php echo $match->GetIsOversKnown() ? $match->GetOvers() : "null"
		?>,"tournamentMatchId": <?php echo (!is_null($match->GetTournament()) and $match->GetTournament()->GetId()) ? $match->GetTournament()->GetId() : "null"
		?>,"orderInTournament": <?php echo $match->GetOrderInTournament() ? $match->GetOrderInTournament() : "null"
		?>,"maximumTeamsInTournament": <?php echo $match->GetMaximumTeamsInTournament() ? $match->GetMaximumTeamsInTournament() : "null"
		?>,"spacesInTournament": <?php echo $match->GetSpacesLeftInTournament() ? $match->GetSpacesLeftInTournament() : "null"
		?>,"startTime":"<?php echo Date::Microformat($match->GetStartTime())
		?>","startTimeKnown": <?php echo $match->GetIsStartTimeKnown() ? "true" : "false"
		?>,"tossWonBy": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetTossWonBy())) ? $match->Result()->GetTossWonBy() : "null"
		?>,"homeBatFirst": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetHomeBattedFirst())) ? $match->Result()->GetHomeBattedFirst() ? "true" : "false" : "null"
		?>,"homeRuns": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetHomeRuns())) ? $match->Result()->GetHomeRuns() : "null"
		?>,"homeWickets": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetHomeWickets())) ? $match->Result()->GetHomeWickets() : "null"
		?>,"awayRuns": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetAwayRuns())) ? $match->Result()->GetAwayRuns() : "null"
		?>,"awayWickets": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetAwayWickets())) ? $match->Result()->GetAwayWickets() : "null"
		?>,"resultType": <?php echo (!is_null($match->Result()) and $match->Result()->GetResultType() > 0) ? $match->Result()->GetResultType() : "null"
		?>,"teams":[<?php
		$first_team = true;
		if (!is_null($match->GetHomeTeam()) && $match->GetHomeTeamId()) {
			$first_team = false;
			?>{"teamId":<?php echo $match->GetHomeTeamId()
			?>,"matchTeamId":<?php echo $match->GetHomeTeam()->GetMatchTeamId()
			?>,"teamRole":<?php echo TeamRole::Home()
			?>}<?php
		}
		foreach ($match->GetAwayTeams() as $team) {
			if (!$team->GetId()) continue;
			if ($first_team) {
				$first_team = false;
			} else {
				?>,<?php
			}
			?>{"teamId":<?php echo $team->GetId()
			?>,"matchTeamId":<?php echo $team->GetMatchTeamId()
			?>,"teamRole":<?php echo TeamRole::Away()
			?>}<?php
		}
		?>],"seasons":[<?php
		$first_season = true;
		foreach ($match->Seasons() as $season) {
			if ($first_season) {
				$first_season = false;
			} else {
				?>,<?php
			}
			echo $season->GetId();
		}
		?>],"playerOfTheMatchId": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetPlayerOfTheMatch()) and $match->Result()->GetPlayerOfTheMatch()->GetId()) ? $match->Result()->GetPlayerOfTheMatch()->GetId() : "null"
		?>,"playerOfTheMatchHomeId": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetPlayerOfTheMatchHome()) and $match->Result()->GetPlayerOfTheMatchHome()->GetId()) ? $match->Result()->GetPlayerOfTheMatchHome()->GetId() : "null"
		?>,"playerOfTheMatchAwayId": <?php echo (!is_null($match->Result()) and !is_null($match->Result()->GetPlayerOfTheMatchAway()) and $match->Result()->GetPlayerOfTheMatchAway()->GetId()) ? $match->Result()->GetPlayerOfTheMatchAway()->GetId() : "null"
		?>,"notes":"<?php echo $notes
		?>","route":"<?php echo $match->GetShortUrl()
		?>","dateCreated":<?php echo $match->GetDateAdded() === 0 ? "null" : "\"" . Date::Microformat($match->GetDateAdded()) . "\"" 
		?>,"createdBy":<?php echo (!is_null($match->GetAddedBy()) and $match->GetAddedBy()->GetId()) ? $match->GetAddedBy()->GetId() : "null" 
		?>,"dateUpdated":<?php echo (!is_null($match->GetLastAudit()) and $match->GetLastAudit()->GetTime()) ? "\"" . Date::Microformat($match->GetLastAudit()->GetTime()) . "\"" : "null"
		?>,"updatedBy":<?php echo (!is_null($match->GetLastAudit()) and !is_null($match->GetLastAudit()->GetUser()) and $match->GetLastAudit()->GetUser()->GetId()) ? $match->GetLastAudit()->GetUser()->GetId() : "null"
		?>}<?php
		}
		?>]}<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>