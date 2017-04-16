<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../' . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('data/mysql-connection.class.php');
require_once ('context/stoolball-settings.class.php');
require_once ('context/site-context.class.php');
require_once ('stoolball/match-manager.class.php');
require_once('xhtml/html.class.php');
require_once ("Zend/Feed.php");

# set up error handling, unless local and using xdebug
$settings = new StoolballSettings();
if (!(SiteContext::IsDevelopment()))
{
    require_once('page/exception-manager.class.php');
    require_once('page/email-exception-publisher.class.php');
    $errors = new ExceptionManager(array(new EmailExceptionPublisher($settings->GetTechnicalContactEmail())));

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

$database = new MySqlConnection($settings->DatabaseHost(), $settings->DatabaseUser(), $settings->DatabasePassword(), $settings->DatabaseName());

$manager = new MatchManager($settings, $database);

# check for date range, or use defaults
$i_one_day = 86400;

$from = "";
if (isset($_GET['from']) and is_string($_GET['from']))
{
    # Replace slashes with hyphens in submitted date because then it's treated as a British date, not American
    $date = is_numeric($_GET['from']) ? (int)$_GET['from'] : strtotime(str_replace("/", "-", $_GET['from']));
    if ($date !== false) $from = $date;
}
if (!$from) $from = gmdate('U') - ($i_one_day * 1); # yesterday
$manager->FilterByDateStart($from);

$to = "";
if (isset($_GET['to']) and is_string($_GET['to']))
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
$feedData = array('title' => $title, 
                  'description' => "New or updated " . strtolower($player_type) . "stoolball matches on the Stoolball England website", 
                  'link' => 'https://www.stoolball.org.uk/play/matches', 
                  'charset' => 'utf-8', 
                  "language" => "en-GB", 
                  "author" => "Stoolball England", 
                  "image" => "https://www.stoolball.org.uk/images/feed-ident.gif", 
                  'entries' => array());

# Option to tweet new entries to a user, for use with www.iftt.com                  
$tweet = isset($_GET['format']) && $_GET['format'] === "tweet";
                  
foreach ($matches as $match) 
{
    if ($tweet)
    {
        $item_title = "New #stoolball match: " . $match->GetTitle() . ", " . $match->GetStartTimeFormatted(true, true, true);
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
    $feedData["entries"][]  = array('title' => $item_title, 
                                'description' => $description, 
                                'link' => "http://" . $settings->GetDomain() . $match->GetNavigateUrl() . "?utm_source=stoolballengland&amp;utm_medium=" . $medium . "&amp;utm_campaign=matches", 
                                'guid' => $match->GetLinkedDataUri(), 
                                "lastUpdate" => $match->GetLastAudit()->GetTime(),
                                "category" => array(array("term" => strtolower(PlayerType::Text($match->GetPlayerType())))));
}
// create our feed object and import the data
$feed = Zend_Feed::importArray($feedData, 'rss');

// set the Content Type of the document
header('Content-type: text/xml');

// echo the contents of the RSS xml document
echo $feed->send();
?>