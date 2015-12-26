<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('stoolball/match-manager.class.php');

class CurrentPage extends Page
{
	public function OnPageInit()
	{
		# This is a JavaScript file
		if (!headers_sent()) header("Content-Type: text/javascript; charset=utf-8");
	}

	public function OnLoadPageData()
	{
		$i_one_day = 86400;
		$i_start = gmdate('U')-($i_one_day*1); # yesterday
		$i_end = gmdate('U')+($i_one_day*365); # this year

        # Check for player type
        $player_type = null;
        if (isset($_GET['player']) and is_numeric($_GET['player']))
        {
            $player_type = (int)$_GET['player'];
        }
        $a_player_types = is_null($player_type) ? null : array($player_type);
        if ($player_type == PlayerType::JUNIOR_MIXED) 
        {
            $a_player_types[] = PlayerType::GIRLS;
            $a_player_types[] = PlayerType::BOYS;
        }

		$manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
		$manager->FilterByMatchType(array(MatchType::TOURNAMENT));
        $manager->FilterByPlayerType($a_player_types);
        $manager->FilterByDateStart($i_start);
        $manager->FilterByDateEnd($i_end);
		$manager->ReadMatchSummaries();
		$tournaments = $manager->GetItems();
		unset($manager);
		?>
$(function()
{
	// Make the placeholder big enough for a map
	var mapControl = document.getElementById("map");
	mapControl.style.width = '100%';
	mapControl.style.height = '400px';

	// Create the map
	var myLatlng = new google.maps.LatLng(51.07064141136184, -0.31114161014556885); // Horsham
	var myOptions = {
		zoom : $(window).width() > 400 ? 9 : 8,
		center : myLatlng,
		mapTypeId : google.maps.MapTypeId.ROADMAP
	};
	var map = new google.maps.Map(mapControl, myOptions);
	var markers = [];
	var info;
<?php
# There might be multiple tournaments at one ground. Rearrange tournaments so that they're indexed by ground.
$grounds = array();
foreach ($tournaments as $tournament)
{
	/* @var $tournament Match */
	$ground_id = $tournament->GetGroundId();
	if (!array_key_exists($ground_id, $grounds)) $grounds[$ground_id] = array();
	$grounds[$ground_id][] = $tournament;
}

foreach ($grounds as $tournaments)
{
	/* @var $ground Ground */
	$ground = $tournaments[0]->GetGround();
	if (!is_object($ground) or !$ground->GetAddress()->GetLatitude() or !$ground->GetAddress()->GetLongitude()) continue;

	$content = "'<div class=\"map-info\">' +
	'<h2>" . str_replace("'", "\'", $ground->GetNameAndTown()) . "</h2>";

	foreach ($tournaments as $tournament)
	{
		/* @var $tournament Match */
		$title = $tournament->GetTitle() . ", " . Date::BritishDate($tournament->GetStartTime(), false, true, false);
		$content .= '<p><a href="' . $tournament->GetNavigateUrl() . '">' . str_replace("'", "\'", $title) . '</a></p>';
	}

	$content .= "</div>'";

	# And marker and click event to trigger info window. Wrap info window in function to isolate marker, otherwise the last marker
	# is always used for the position of the info window.
	echo "var marker = new google.maps.Marker({
			position : new google.maps.LatLng(" . $ground->GetAddress()->GetLatitude() . "," . $ground->GetAddress()->GetLongitude() . "),
			shadow: Stoolball.Maps.WicketShadow(),
			icon: Stoolball.Maps.WicketIcon(),
			title : '" . str_replace("'", "\'", $ground->GetNameAndTown()) . "'
		  });
		  markers.push(marker);

		  (function(marker){
			  google.maps.event.addListener(marker, 'click', function()
			  {
			  	var content = $content;
			  	if (!info) info = new google.maps.InfoWindow();
			  	info.setContent(content);
			  	info.open(map, marker);
			  });
		  })(marker);
		  ";
}
?>
	var style = [{
        url: '/images/features/map-markers.gif',
        height: 67,
        width: 31,
        textColor: '#ffffff',
        textSize: 10
      }];
	var clusterer = new MarkerClusterer(map, markers, { 'gridSize': 30, styles: style });
});
<?php
		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>