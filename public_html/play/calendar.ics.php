<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/' . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../');
date_default_timezone_set('Europe/London');
require_once ('data/mysql-connection.class.php');
require_once ('context/stoolball-settings.class.php');
require_once ('stoolball/match-manager.class.php');

/**
 * Action to take if anything, *anything*, unexpected is encountered
 * @return unknown_type
 */
function Abort()
{
	# If there was a problem, this page doesn't exist!
	header("HTTP/1.1 404 Not Found");

	# Display the standard 404 page, which usually has its status set by WordPress
	require_once("wp-content/themes/stoolball/404.php");
	die();
}

$match_id;
$team_id;
$season_id;
$tournament_player_type;
$matches;
$calendar_title;
$calendar_url;

$settings = new StoolballSettings();
$database = new MySqlConnection($settings->DatabaseHost(), $settings->DatabaseUser(), $settings->DatabasePassword(), $settings->DatabaseName());
$match_manager = new MatchManager($settings, $database);

$one_day = 86400;
$yesterday = gmdate('U')-($one_day*1);

if (isset($_GET['match']) and is_numeric($_GET['match']))
{
	$match_id = (int)$_GET['match'];
    if ($match_id < 1) $match_id = null;
    if (!is_null($match_id)) {
		$match_manager->ReadByMatchId(array($match_id));
		$match = $match_manager->GetFirst();
		$matches = array($match);

		$calendar_title = $match->GetTitle();
        if ($match->GetMatchType() == MatchType::TOURNAMENT_MATCH) {
            $calendar_title .= " in the " . $match->GetTournament()->GetTitle();
        } 
		$calendar_title .= ' - ' . $match->GetStartTimeFormatted(true, false);
	}
}
else if (isset($_GET['team']) and is_numeric($_GET['team']))
{
	$team_id = (int)$_GET['team'];
    if ($team_id < 1) $team_id = null;
    if (!is_null($team_id)) {
		$match_manager->FilterByTeam(array($team_id));
		$match_manager->FilterByDateStart($yesterday);
		$match_manager->ReadMatchSummaries();
		$matches = $match_manager->GetItems();

		require_once('stoolball/team-manager.class.php');
		$team_manager = new TeamManager($settings, $database);
		$team_manager->ReadById(array($team_id));
		$team = $team_manager->GetFirst();
		$calendar_title = 'Matches for ' . $team->GetName() . ' stoolball team';
    }
}
else if (isset($_GET['season']) and is_numeric($_GET['season']))
{
	$season_id = (int)$_GET['season'];
    if ($season_id < 1) $season_id = null;
    if (!is_null($season_id)) {
		$match_manager->ReadBySeasonId(array($season_id));
		$matches = $match_manager->GetItems();

		require_once('stoolball/season-manager.class.php');
		$season_manager = new SeasonManager($settings, $database);
		$season_manager->ReadById(array($season_id));
		$season = $season_manager->GetFirst();
		$calendar_title = 'Matches in the ' . $season->GetCompetitionName();
    }
}
else if (isset($_GET['tournaments']) and $_GET['tournaments']) 
{
	# Check for player type
	$tournament_player_type = preg_replace('/[^a-z]/',"",$_GET['tournaments']);
	$player_type = PlayerType::Parse($tournament_player_type);
	$player_types = is_null($player_type) ? null : array($player_type);
	if ($player_type == PlayerType::JUNIOR_MIXED) 
	{
		$player_types[] = PlayerType::GIRLS;
		$player_types[] = PlayerType::BOYS;
	}

	$match_manager->FilterByMatchType(array(MatchType::TOURNAMENT));
	$match_manager->FilterByPlayerType($player_types);
	//$match_manager->FilterByDateStart($yesterday);
	$match_manager->ReadMatchSummaries();
	$matches = $match_manager->GetItems();

	$calendar_title = "Stoolball tournaments";
	if ($player_type)
	{
		$player_type_text = PlayerType::Text($player_type) . " ";
		if ($player_type == PlayerType::JUNIOR_MIXED) 
		{
			$player_type_text = "Junior ";
		}    
		$calendar_title = $player_type_text . strtolower($calendar_title);
	} 
}
else
{
	Abort();
}
unset($match_manager);

# Ensure all errors, old and new, lead to Abort()
//set_error_handler('Abort');
try
{
	# Return the result as a vCalendar
	$filename = str_replace('/', '-', str_replace('/calendar.ics', '', $_SERVER["REQUEST_URI"]));
	header("Content-Disposition: attachment; filename=stoolball" . $filename . ".ics");
	header('Content-Type: text/calendar; charset=utf-8');

	$calendar_url = "https://" . $settings->GetDomain() . $_SERVER["REQUEST_URI"];
	?>
BEGIN:VCALENDAR
X-ORIGINAL-URL:<?php echo $calendar_url . "\n" ?>
X-WR-CALNAME;CHARSET=UTF-8:<?php echo str_replace(',', '\,', $calendar_title) ?> - Stoolball England
VERSION:2.0
METHOD:PUBLISH
BEGIN:VTIMEZONE
TZID:UK
X-LIC-LOCATION:Europe/London
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:BST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE

<?php
	foreach ($matches as $match)
	{
		/* var $match Match */
		$start = new \DateTime("@" . $match->GetStartTime(), new \DateTimeZone("UTC"));
		$end = new \DateTime("@" . $match->GetEstimatedEndTime(), new \DateTimeZone("UTC"));
		$now = new \DateTime("now", new \DateTimeZone("UTC"));
		$ground = $match->GetGround();
		if (!is_null($ground)) $ground = $ground->GetNameAndTown();
?>
BEGIN:VEVENT
LOCATION;LANGUAGE=en;CHARSET=UTF-8:<?php echo $ground . "\n" ?>
SUMMARY;LANGUAGE=en;CHARSET=UTF-8:<?php echo str_replace(',', '\,', $match->GetTitle() . ' - ' . $match->GetStartTimeFormatted(true, false)) . "\n" ?>
UID:<?php echo $match->GetLinkedDataUri() . "\n" ?>
DTSTART:<?php echo $start->format("Ymd\THis\Z") ?> 
DTEND:<?php echo $end->format("Ymd\THis\Z") ?> 
DTSTAMP:<?php echo $now->format("Ymd\THis\Z") . "\n" ?>
X-MICROSOFT-CDO-INTENDEDSTATUS:FREE
X-MICROSOFT-CDO-BUSYSTATUS:FREE
TRANSP:TRANSPARENT
END:VEVENT

<?php
		}
}
catch (Exception $e)
{
	Abort();
}
?>

END:VCALENDAR