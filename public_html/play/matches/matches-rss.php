<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../' . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');

require_once ('data/mysql-connection.class.php');
require_once ('context/stoolball-settings.class.php');
require_once ('context/site-context.class.php');
require_once ('stoolball/match-manager.class.php');
require_once('xhtml/html.class.php');

# set up error handling, unless local and using xdebug
$settings = new StoolballSettings();
if (!(SiteContext::IsDevelopment()))
{
    require_once('page/exception-manager.class.php');
    require_once('page/email-exception-publisher.class.php');
    $errors = new ExceptionManager(array(new EmailExceptionPublisher($settings->GetTechnicalContactEmail(), $settings->GetEmailTransport())));

    # Use production settings recommended by
    # http://perishablepress.com/advanced-php-error-handling-via-htaccess/
    # Not possible to set in .htaccess because PHP is running as a CGI. This is the
    # next best thing.
    ini_set("display_startup_errors", "off");
    ini_set("display_errors", "off");
    ini_set("html_errors", "off");
    ini_set("log_errors", "on");
    ini_set("ignore_repeated_errors", "off");
    ini_set("ignore_repeated_source", "off");
    ini_set("report_memleaks", "on");
    ini_set("track_errors", "on");
    ini_set("docref_root", "0");
    ini_set("docref_ext", "0");
    ini_set("error_reporting", "-1");
    ini_set("log_errors_max_len", "0");
    ini_set("error_log", $_SERVER['DOCUMENT_ROOT'] . "/php-errors.log");
}

# set up INI options
date_default_timezone_set('Europe/London');

// set the Content Type of the document
header('Content-type: application/rss+xml');

$database = new MySqlConnection($settings->DatabaseHost(), $settings->DatabaseUser(), $settings->DatabasePassword(), $settings->DatabaseName());

$manager = new MatchManager($settings, $database);

# Option to tweet new entries to a user, for use with www.iftt.com or similar            
$tweet = isset($_GET['format']) && $_GET['format'] === "tweet";

# check for date range, or use defaults
$i_one_day = 86400;
$today = isset($_GET['today']) && $_GET['today'] === "true";

$from = "";
if ($today) {
    $from = mktime(0,0,0);
}
else if (isset($_GET['from']) and is_string($_GET['from']))
{
    # Replace slashes with hyphens in submitted date because then it's treated as a British date, not American
    $date = is_numeric($_GET['from']) ? (int)$_GET['from'] : strtotime(str_replace("/", "-", $_GET['from']));
    if ($date !== false) $from = $date;
}
if (!$from) $from = gmdate('U') - ($i_one_day * 1); # yesterday
$manager->FilterByDateStart($from);

$to = "";
if ($today) {
    $to = $from + $i_one_day;
}
else if (isset($_GET['to']) and is_string($_GET['to']))
{
    # Replace slashes with hyphens in submitted date because then it's treated as a British date, not American
    $date = is_numeric($_GET['to']) ? (int)$_GET['to'] : strtotime(str_replace("/", "-", $_GET['to']));
    if ($date !== false) $to = $date;
}
if (!$to) {
    # in the next year, or as specified
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 365;
    $to = gmdate('U') + ($i_one_day * $days);
}

$manager->FilterByDateEnd($to);

# Check for player type
$player_type = null;
$player_types = null;
if (isset($_GET['player']))
{
    $player_type = is_numeric($_GET['player']) ? (int)$_GET['player'] : null;
    if (!is_null($player_type))
    {
        $player_types = array($player_type);
        if ($player_type == PlayerType::JUNIOR_MIXED) 
        {
            $player_types[] = PlayerType::GIRLS;
           $player_types[] = PlayerType::BOYS;
        }
        $player_type = PlayerType::Text($player_type) . " ";
    }
    $manager->FilterByPlayerType($player_types);
}

# Check for match type
$i_match_type = null;
if (isset($_GET['type']) and is_numeric($_GET['type']))
{
    $i_match_type = (int)$_GET['type'];
    if ($i_match_type < 0 or $i_match_type > 50) $i_match_type = null;
    if (!is_null($i_match_type)) {
        $manager->FilterByMatchType(array($i_match_type));
    }
}

# Check for team
$team_id = null;
if (isset($_GET['team']) and is_numeric($_GET['team']))
{
    $team_id = (int)$_GET['team'];
    if ($team_id < 1) $team_id = null;
    if (!is_null($team_id)) {
        $manager->FilterByTeam(array($team_id));
    }
}

# Check for club
$club_id = null;
if (isset($_GET['club']) and is_numeric($_GET['club']))
{
    $club_id = (int)$_GET['club'];
    if ($club_id < 1) $club_id = null;
    if (!is_null($club_id)) {
        
        require_once('stoolball/clubs/club-manager.class.php');
       $club_manager = new ClubManager($settings, $database);
        $club_manager->ReadById(array($_GET['club']));
        $club = $club_manager->GetFirst();
        unset($club_manager);
        
        $teams = $club->GetItems();
        if (count($teams) > 0)
        {
            $team_ids = array();
            foreach ($teams as $team)
            {
                $team_ids[] = $team->GetId();
            }
            $manager->FilterByTeam($team_ids);
        }
    }
}

# Check for competition
$competition_id = null;
if (isset($_GET['competition']) and is_numeric($_GET['competition']))
{
    $competition_id = (int)$_GET['competition'];
    if ($competition_id < 1) $competition_id = null;
    if (!is_null($competition_id)) {
        $manager->FilterByCompetition(array($competition_id));
    }
}

if ($tweet) {
    # Only tweet matches which have no result, therefore excluding cancelled and postponed matches
    $manager->FilterByMatchResult(array(MatchResult::UNKNOWN));
}

$manager->SortBy("date_changed DESC");
$manager->ReadMatchSummaries();
$matches = $manager->GetItems();
unset($manager);

$database->Disconnect();

$title = 'Stoolball matches';
if ($player_type)
{
    $title = $player_type . strtolower($title);
}
echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<rss xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0">
  <channel>
    <title><![CDATA[<?php echo $title ?>]]></title>
    <link>https://www.stoolball.org.uk/play/matches</link>
    <description><![CDATA[New or updated <?php echo strtolower($player_type) ?>stoolball matches on the Stoolball England website]]></description>
    <pubDate><?php echo date('r'); ?></pubDate>
    <image>
      <url>https://www.stoolball.org.uk/images/feed-ident.gif</url>
      <title><![CDATA[<?php echo $title ?>]]></title>
      <link>https://www.stoolball.org.uk/play/matches</link>
    </image>
    <language>en-GB</language>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>
<?php
foreach ($matches as $match) 
{
    /* @var $match Match */
    if ($tweet)
    {       
        $one_hour = 60*60;
        $is_update = $match->GetLastAudit()->GetTime() > ($match->GetDateAdded() + $one_hour);
        $title = $match->GetTitle();
        $type = "match";
        if ($match->GetMatchType() === MatchType::TOURNAMENT) $type = "tournament";
         if ($match->GetMatchType() === MatchType::PRACTICE) {
             $type = "practice";
            $title = "";
         }
         
         if ($today) 
         {
            if ($match->GetMatchType() === MatchType::TOURNAMENT) {
                if (time() < mktime(8)) continue; # don't tweet until 8am
                $item_title = "We're off to $title! #stoolball";
            }
            else if ($match->GetMatchType() === MatchType::PRACTICE) {
                if (time() < mktime(16)) continue; # don't tweet until 4pm
                $item_title = "It's practice night - come and join us! #stoolball";    
            }
            else {
                if (time() < mktime(16)) continue; # don't tweet until 4pm
                $item_title = "It's match night! $title #stoolball";	
            }             
             
         }
         else 
         {
             if ($title) $title .= ", ";
            $item_title = ($is_update ? "Updated " : "New ") .  "$type: $title" . $match->GetStartTimeFormatted(true, true, true) . " #stoolball";
        }
         
    }
    else
    {
        $item_title = $match->GetTitle() . ", " . $match->GetStartTimeFormatted();
    }
        
    /* @var $match Match */
    $description = "";
    
    if ($match->GetGround())
    {
        $description .= "This match will take place at " . Html::Encode($match->GetGround()->GetNameAndTown()) . ". ";
    } 
    
    $medium = $tweet ? "twitter" : "rss";
    ?>
    <item>
        <title><![CDATA[<?php echo $item_title ?>]]></title>
        <description><![CDATA[<?php echo $description ?>]]></description>
        <link>https://<?php echo $settings->GetDomain() . $match->GetNavigateUrl() . "?utm_source=stoolballengland&amp;utm_medium=" . $medium . "&amp;utm_campaign=matches" ?></link>
        <pubDate><?php echo date('r', $today ? mktime(8) : $match->GetLastAudit()->GetTime()) ?></pubDate>
        <guid isPermaLink="false"><?php echo $today ? $match->GetLinkedDataUri() . "/today" : $match->GetLinkedDataUri() ?></guid>
        <source url="https://<?php echo $settings->GetDomain() . $_SERVER['REQUEST_URI'] ?>" />
        <category><![CDATA[<?php echo strtolower(PlayerType::Text($match->GetPlayerType())) ?>]]></category>
    </item>
    <?php
}
?>
  </channel>
</rss>