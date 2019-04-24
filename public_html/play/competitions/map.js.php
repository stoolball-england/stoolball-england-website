<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');

class CurrentPage extends Page
{
	private $season_id;
    
	public function OnPageInit()
	{
		# This is a JavaScript file
		if (!headers_sent()) header("Content-Type: text/javascript; charset=utf-8");
        
       if (isset($_GET['season']) and is_numeric($_GET['season'])) 
       {
           $this->season_id = (int)$_GET['season'];
       }
       else {
           header( 'HTTP/1.1 400 Bad Request');
           exit();
       }
    
        
	}

	public function OnLoadPageData()
	{
        require_once('stoolball/competition-manager.class.php');
        $manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
        $manager->ReadById(null, array($this->season_id));
        $competition = $manager->GetFirst();
        unset($manager);
        
        if (!($competition instanceof Competition))
        {
            return ;
        } 

		require_once('stoolball/ground-manager.class.php');
		$manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$manager->FilterBySeason(array($competition->GetWorkingSeason()->GetId()));   
    	$manager->ReadById();
		$grounds = $manager->GetItems();
		unset($manager);
		?>
$(function() {
// Make the placeholder big enough for a map
var mapControl = document.getElementById("map");
mapControl.style.width = '100%';
mapControl.style.height = '500px'; // Create the map var
var bounds = new google.maps.LatLngBounds();
var myOptions = { mapTypeId : google.maps.MapTypeId.ROADMAP };
var map = new google.maps.Map(mapControl, myOptions);
var markers = [];
var info;
		<?php
		foreach ($grounds as $ground)
		{
			/* @var $ground Ground */
			if (!$ground->GetAddress()->GetLatitude() or !$ground->GetAddress()->GetLongitude()) continue;

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

			# And marker and click event to trigger info window. Wrap info window in function to isolate marker, otherwise the last marker
			# is always used for the position of the info window.
			echo "var marker = new google.maps.Marker({
			position : new google.maps.LatLng(" . $ground->GetAddress()->GetLatitude() . "," . $ground->GetAddress()->GetLongitude() . "),
			shadow: Stoolball.Maps.WicketShadow(),
			icon: Stoolball.Maps.WicketIcon(),
			title : '" . str_replace("'", "\'", $ground->GetNameAndTown()) . "'
		  });
		  markers.push(marker);
		  bounds.extend(marker.position);

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
map.fitBounds(bounds);
var style = [{ url: '/images/features/map-markers.gif', height: 67, width: 31, textColor: '#ffffff', textSize: 10 }];
var clusterer = new MarkerClusterer(map, markers, { 'gridSize': 30, styles: style});
});
		<?php
		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>