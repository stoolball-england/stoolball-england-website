<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');
require_once('stoolball/ground-manager.class.php');

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
		$manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$manager->ReadById();
		$first = true;
		?>[<?php
		foreach ($manager->GetItems() as $ground) {
			
			$s_directions = '';
			$s_parking = '';
			$s_facilities = '';
			if ($ground->GetDirections())
			{
				$s_directions = htmlentities($ground->GetDirections(), ENT_QUOTES, "UTF-8", false);
				$s_directions = XhtmlMarkup::ApplyCharacterEntities($s_directions);
				$s_directions = XhtmlMarkup::ApplyParagraphs($s_directions);
				$s_directions = XhtmlMarkup::ApplySimpleTags($s_directions);
				$s_directions = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $s_directions)))));
				$s_directions = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $s_directions));
				$s_directions = '<h2>Directions</h2>' . $s_directions;
			}
	
			if ($ground->GetParking())
			{
				$s_parking = htmlentities($ground->GetParking(), ENT_QUOTES, "UTF-8", false);
				$s_parking = XhtmlMarkup::ApplyCharacterEntities($s_parking);
				$s_parking = XhtmlMarkup::ApplyParagraphs($s_parking);
				$s_parking = XhtmlMarkup::ApplySimpleTags($s_parking);
				$s_parking = XhtmlMarkup::ApplyLinks($s_parking);
				$s_parking = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $s_parking)))));
				$s_parking = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $s_parking));
				$s_parking = '<h2>Parking</h2>' . $s_parking;
			}
	
			if ($ground->GetFacilities())
			{
				$s_facilities = htmlentities($ground->GetFacilities(), ENT_QUOTES, "UTF-8", false);
				$s_facilities = XhtmlMarkup::ApplyCharacterEntities($s_facilities);
				$s_facilities = XhtmlMarkup::ApplyParagraphs($s_facilities);
				$s_facilities = XhtmlMarkup::ApplySimpleTags($s_facilities);
				$s_facilities = XhtmlMarkup::ApplyLinks($s_facilities);
				$s_facilities = str_replace("\"", "\\\"", str_replace('&#039;', "'", str_replace("\r", '', str_replace("\r\n", '', str_replace("\n", '', $s_facilities)))));
				$s_facilities = str_replace('\\\\"', '\\"', str_replace('\\', '\\\\', $s_facilities));	
				$s_facilities = '<h2>Facilities</h2>' . $s_facilities;
			}


			if ($first) {
				$first = false;
			} else {
				?>,<?php
			}
	?>{"groundId":<?php echo $ground->GetId()
		?>,"sortName":"<?php echo str_replace('&amp;', '&', $ground->GetAddress()->GenerateSortName()) 
		?>","directions":"<?php echo $s_directions 
		?>","parking":"<?php echo $s_parking 
		?>","facilities":"<?php echo $s_facilities 
		?>","saon":"<?php echo $ground->GetAddress()->GetSaon() 
		?>","paon":"<?php echo str_replace('&amp;', '&', $ground->GetAddress()->GetPaon()) 
		?>","street":"<?php echo $ground->GetAddress()->GetStreetDescriptor() 
		?>","locality":"<?php echo $ground->GetAddress()->GetLocality() 
		?>","town":"<?php echo $ground->GetAddress()->GetTown() 
		?>","administrativeArea":"<?php echo $ground->GetAddress()->GetAdministrativeArea() 
		?>","postcode":"<?php echo $ground->GetAddress()->GetPostcode() 
		?>","latitude":<?php echo is_null($ground->GetAddress()->GetLatitude()) ? "null" : $ground->GetAddress()->GetLatitude() 
		?>,"longitude":<?php echo is_null($ground->GetAddress()->GetLongitude()) ? "null" : $ground->GetAddress()->GetLongitude() 
		?>,"geoPrecision":<?php echo is_null($ground->GetAddress()->GetGeoPrecision()) ? "null" : $ground->GetAddress()->GetGeoPrecision() 
		?>,"route":"<?php echo $ground->GetShortUrl()
		?>","dateCreated":"<?php echo Date::Microformat($ground->GetDateAdded()) 
		?>","dateUpdated":"<?php echo Date::Microformat($ground->GetDateUpdated())
		?>"}<?php
		}
		?>]<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>