<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');

class CurrentPage extends Page
{
	private $team_ids;
	
	public function OnPageInit()
	{
		# This is a JavaScript file
		if (!headers_sent()) header("Content-Type: text/javascript; charset=utf-8");
	}

	public function OnLoadPageData()
	{
		require_once('stoolball/ground-manager.class.php');
		$manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$manager->FilterByActive(true);
        
        # Check for team type
        if (isset($_GET["team-type"]) and is_string($_GET['team-type'])) 
        {
            # Sanitise input to ensure we work only with integers
            $team_types = explode(",", $_GET['team-type']);    
            foreach ($team_types as $key=>$value) {
                if (!is_numeric($value)) {
                    unset($team_types[$key]);
                } else {
                    $team_types[$key] = (int)$team_types[$key];
                }
            }
            if (count($team_types)) {
                $manager->FilterByTeamType($team_types);
            }
        }
	
    
        # Check for player type
        $player_type = null;
        if (isset($_GET['player']) and is_string($_GET['player']) and is_numeric($_GET['player']))
        {
            $player_type = (int)$_GET['player'];
        }
        if ($player_type === 0)
        {
            $manager->FilterByActive(false);
        }
        else if (!is_null($player_type)) 
        {
            $a_player_types = is_null($player_type) ? null : array($player_type);
            if ($player_type == PlayerType::JUNIOR_MIXED) 
            {
                $a_player_types[] = PlayerType::GIRLS;
                $a_player_types[] = PlayerType::BOYS;
            }
            $manager->FilterByPlayerType($a_player_types);
        }
    
    	$manager->ReadAll();
		$grounds = $manager->GetItems();
		unset($manager);

# JavaScript will create two sets of markers, one for each ground, and one for each team.
# This is so that, when clustered, we can display the number of teams by creating a marker for each (even though they're actually duplicates).
# You can't get an infoWindow for a cluster though, so once we're zoomed in far enough switch to using the ground markers, which are unique.

		?>
$(function() {
// Make the placeholder big enough for a map
var mapControl = document.getElementById("map");
mapControl.style.width = '100%';
mapControl.style.height = '600px'; // Create the map var
myLatlng = new google.maps.LatLng(51.8157917, -0.9166621); // Aylesbury
var myOptions = { zoom : 6, center : myLatlng, mapTypeId : google.maps.MapTypeId.ROADMAP };
var map = new google.maps.Map(mapControl, myOptions);
var groundMarkers = [], teamMarkers = [];
var info;
var clusterer;
var previousZoom = map.getZoom();

function createGroundMarkers() {

		<?php
		foreach ($grounds as $ground)
		{
			/* @var $ground Ground */
			if (!$ground->GetAddress()->GetLatitude() or !$ground->GetAddress()->GetLongitude()) continue;

            $content = $this->InfoWindowContent($ground);
            echo $this->MarkerScript($ground, $content, "groundMarkers");
		}
		?>
}

function createTeamMarkers() {

        <?php
        foreach ($grounds as $ground)
        {
            /* @var $ground Ground */
            if (!$ground->GetAddress()->GetLatitude() or !$ground->GetAddress()->GetLongitude()) continue;

            $content = $this->InfoWindowContent($ground);

            $teams = $ground->Teams()->GetItems();
            $length = count($teams);            

            for ($i = 0; $i<$length; $i++) {
                echo $this->MarkerScript($ground, $content, "teamMarkers");
            }            
        }
        ?>
}

function removeMarkers(removeMarkers) {
    var length = removeMarkers.length;
    for (var i = 0; i < length; i++) {
        removeMarkers[i].setMap(null);
    }
}

function plotMarkers(addMarkers) {    
    var style = [{ url: '/images/features/map-markers.gif', height: 67, width: 31, textColor: '#ffffff', textSize: 10 }];
    if (clusterer) clusterer.clearMarkers();
    clusterer = new MarkerClusterer(map, addMarkers, { 'gridSize': 30, styles: style});
}

function zoomChanged() {
    var currentZoom = map.getZoom();
    if (previousZoom < 14 && currentZoom >= 14) {
        removeMarkers(teamMarkers);
        plotMarkers(groundMarkers);
    } else if (previousZoom >= 14 && currentZoom < 14) {
        removeMarkers(groundMarkers);
        plotMarkers(teamMarkers);
    }
    previousZoom = currentZoom;
}

google.maps.event.addListener(map, 'zoom_changed', zoomChanged);

createGroundMarkers();
createTeamMarkers();
plotMarkers(teamMarkers);
});
		<?php
		exit();
	}

    private function InfoWindowContent(Ground $ground)
    {
        $content = "'<div class=\"map-info\">' +
            '<h2>" . str_replace("'", "\'", Html::Encode($ground->GetNameAndTown())) . "</h2>' +
            '<p>Home to: ";

        $teams = $ground->Teams()->GetItems();
        $length = count($teams);
        for ($i = 0; $i<$length; $i++)
        {
            if ($i > 0 and $i == $length-1) $content .= " and ";
            else if ($i > 0) $content .= ", ";
            $content .= '<a href="' . Html::Encode($teams[$i]->GetNavigateUrl()) . '">' . str_replace("'", "\'", Html::Encode($teams[$i]->GetNameAndType())) . '</a>';
        }

        $content .= "</p></div>'";
            
        return $content;        
    }

    private function MarkerScript(Ground $ground, $content, $markersArrayName) {
                # And marker and click event to trigger info window. Wrap info window in function to isolate marker, otherwise the last marker
        # is always used for the position of the info window.
        return "var marker = new google.maps.Marker({
        position : new google.maps.LatLng(" . $ground->GetAddress()->GetLatitude() . "," . $ground->GetAddress()->GetLongitude() . "),
        shadow: Stoolball.Maps.WicketShadow(),
        icon: Stoolball.Maps.WicketIcon(),
        title : '" . str_replace("'", "\'", $ground->GetNameAndTown()) . "'
      });
      $markersArrayName.push(marker);

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
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>