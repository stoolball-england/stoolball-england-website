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

$database = new MySqlConnection($settings->DatabaseHost(), $settings->DatabaseUser(), $settings->DatabasePassword(), $settings->DatabaseName());

$manager = new MatchManager($settings, $database);

# get matches
$i_one_day = 86400;
# from yesterday
$i_start = gmdate('U') - ($i_one_day * 1);

# in the next year, or as specified
$days = isset($_GET['days']) ? (int)$_GET['days'] : 365;
$i_end = gmdate('U') + ($i_one_day * $days);

# Check for player type
$player_type = null;
$player_types = null;
if (isset($_GET['player']))
{
	$player_type = PlayerType::Parse($_GET['player']);
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
}

$manager->FilterByMatchType(array(MatchType::TOURNAMENT));
$manager->FilterByPlayerType($player_types);
$manager->SortBy("date_changed DESC");
$manager->FilterByDateStart($i_start);
$manager->FilterByDateEnd($i_end);
$manager->ReadMatchSummaries();
$matches = $manager->GetItems();
unset($manager);

$database->Disconnect();

$title = 'Stoolball tournaments';
if ($player_type)
{
    $title = $player_type . strtolower($title);
}

# Option to tweet new entries to a user, for use with www.iftt.com                  
$tweet = isset($_GET['format']) && $_GET['format'] === "tweet";

// set the Content Type of the document
header('Content-type: text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<rss xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0">
  <channel>
    <title><![CDATA[<?php echo $title ?>]]></title>
    <link>https://www.stoolball.org.uk/tournaments</link>
    <description><![CDATA[New or updated <?php echo strtolower($player_type) ?>stoolball tournaments on the Stoolball England website]]></description>
    <pubDate><?php echo date('r'); ?></pubDate>
    <image>
      <url>https://www.stoolball.org.uk/images/feed-ident.gif</url>
      <title><![CDATA[<?php echo $title ?>]]></title>
      <link>https://www.stoolball.org.uk/tournaments</link>
    </image>
    <language>en-GB</language>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>
<?php
foreach ($matches as $tournament) 
{
    if ($tweet)
    {
        $days = ($days != 365);
        $prefix = $days ? PlayerType::Text($tournament->GetPlayerType()) : "New " . strtolower(PlayerType::Text($tournament->GetPlayerType()));
        $item_title = $prefix . " #stoolball tournament: " . $tournament->GetTitle() . ", " . $tournament->GetStartTimeFormatted(true, true, true);
    }
    else
    {
        $item_title = $tournament->GetTitle() . ", " . $tournament->GetStartTimeFormatted();
    }
        
    /* @var $tournament Match */
    $description = PlayerType::Text($tournament->GetPlayerType()) . " tournament";
    if ($tournament->GetQualificationType() === MatchQualification::OPEN_TOURNAMENT)
    {
        $description  = Html::Encode("Any team may enter this " . strtolower($description));
    } 
    else if ($tournament->GetQualificationType() === MatchQualification::CLOSED_TOURNAMENT)
    {
        $description .= " for invited or qualifying teams only";
    }
    $description .= ". ";
    
    if ($tournament->GetIsMaximumPlayersPerTeamKnown())
    {
        $description .= Html::Encode($tournament->GetMaximumPlayersPerTeam() . " players per team. ");
    }
    
    if ($tournament->GetIsOversKnown())
    {
        $description .= Html::Encode("Matches are " . $tournament->GetOvers() . " overs. ");
    }

    if ($tournament->GetGround())
    {
        $description .= "<br /><br />This tournament will take place at " . Html::Encode($tournament->GetGround()->GetNameAndTown()) . ". ";
    } 
    
    if ($tournament->GetNotes()) 
    {
        $description .= "<br /><br />" . Html::Encode($tournament->GetNotes());
    }
    
    $medium = $tweet ? "twitter" : "rss";
    ?>
    <item>
        <title><![CDATA[<?php echo $item_title ?>]]></title>
        <description><![CDATA[<?php echo $description ?>]]></description>
        <link>https://<?php echo $settings->GetDomain() . $tournament->GetNavigateUrl() . "?utm_source=stoolballengland&amp;utm_medium=" . $medium . "&amp;utm_campaign=tournaments" ?></link>
        <pubDate><?php echo date('r', $tournament->GetLastAudit()->GetTime()) ?></pubDate>
        <guid isPermaLink="false"><?php echo $tournament->GetLinkedDataUri() ?></guid>
        <source url="https://<?php echo $settings->GetDomain() . $_SERVER['REQUEST_URI'] ?>" />
        <category><![CDATA[<?php echo strtolower(PlayerType::Text($tournament->GetPlayerType())) ?>]]></category>
    </item>
    <?php
}
?>
</channel>
</rss>