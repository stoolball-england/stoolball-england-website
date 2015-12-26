<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/page.class.php');
require_once("context/stoolball-settings.class.php");

class CurrentPage extends Page
{
	function OnLoadPageData()
	{
	    # Require an API key to include personal contact details to avoid spam bots picking them up
        $api_keys = $this->GetSettings()->GetApiKeys();
        $valid_key = false;
        if (isset($_GET['key']) and in_array($_GET['key'], $api_keys)) 
        {
            $valid_key = true;
        }
        
		$data = array();
		$data[] = array("Team id","Team name","Player type","Home ground name","Street name", "Locality","Town", "Administrative area","Postcode","Country","Latitude","Longitude","Contact phone","Contact email","Website","Description");

		require_once('stoolball/team-manager.class.php');
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
		$team_manager->FilterByActive(true);
		$team_manager->FilterByTeamType(array(Team::REGULAR));
		$team_manager->ReadById();
		while($team_manager->MoveNext())
		{
			$team = $team_manager->GetItem();
            /* @var $team Team */

			# Spogo can only import records with contact details
			if (!$team->GetContactPhone() and !$team->GetContactEmail())
            {
                continue;
            }

            # Combine free text fields into a description field
            $description = $team->GetIntro();
            if ($description) 
            {
                $description .= "\n\n";
            }
            if ($team->GetPlayingTimes()) 
            {
                $description .= $team->GetPlayingTimes() . "\n\n";
            }
            if ($team->GetCost()) 
            {
                $description .= $team->GetCost();
            }
            
            # Add this team to the data array
			$data[] = array(
                $team->GetId(),
                $team->GetName() . " Stoolball Club",
		        PlayerType::Text($team->GetPlayerType()), 
    			$team->GetGround()->GetAddress()->GetPaon(),
                $team->GetGround()->GetAddress()->GetStreetDescriptor(),
                $team->GetGround()->GetAddress()->GetLocality(),
                $team->GetGround()->GetAddress()->GetTown(),
                $team->GetGround()->GetAddress()->GetAdministrativeArea(),           
    			$team->GetGround()->GetAddress()->GetPostcode(),
    			"England",
    			$team->GetGround()->GetAddress()->GetLatitude(),
    			$team->GetGround()->GetAddress()->GetLongitude(),
    			$valid_key ? $team->GetContactPhone() : "",
    			$valid_key ? $team->GetContactEmail() : "",
    			$team->GetWebsiteUrl() ? $team->GetWebsiteUrl() : "https://" . $this->GetSettings()->GetDomain() . $team->GetNavigateUrl(),
                trim(html_entity_decode(strip_tags($description), ENT_QUOTES))
			);
		}
		unset($team_manager);

		require_once("data/csv.class.php");
  		CSV::PublishData($data);

		# Test code only. Comment out CSV publish line above to enable display as a table.
  		require_once("xhtml/tables/xhtml-table.class.php");
		$table = new XhtmlTable();
		$table->BindArray($data, false, false);
		echo $table;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>