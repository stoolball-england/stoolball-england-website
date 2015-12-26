<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');
set_time_limit(0);

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
		$data[] = array("Match id","Title","Start time","Match type","Overs","Player type","Players per team","Latitude","Longitude","SAON","PAON","Town","Website","Description");

		require_once('stoolball/match-manager.class.php');
		$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
		$match_manager->FilterByDateStart(gmdate("U"));
		$match_manager->ReadByMatchId();
		while($match_manager->MoveNext())
		{
			$match = $match_manager->GetItem();

            /* @var $match Match */
            
            # Add this match to the data array
			$data[] = array(
                $match->GetId(),
                $match->GetTitle(),
		        Date::Microformat($match->GetStartTime()),
		        MatchType::Text($match->GetMatchType()),
		        $match->GetOvers(),
		        PlayerType::Text($match->GetPlayerType()),
		        $match->GetMaximumPlayersPerTeam(),
    			($match->GetGround() instanceof Ground) ? $match->GetGround()->GetAddress()->GetLatitude() : "",
    			($match->GetGround() instanceof Ground) ? $match->GetGround()->GetAddress()->GetLongitude() : "",
                ($match->GetGround() instanceof Ground) ? $match->GetGround()->GetAddress()->GetSaon() : "",
                ($match->GetGround() instanceof Ground) ? $match->GetGround()->GetAddress()->GetPaon() : "",
                ($match->GetGround() instanceof Ground) ? $match->GetGround()->GetAddress()->GetTown() : "",
    			"https://" . $this->GetSettings()->GetDomain() . $match->GetNavigateUrl(),
                $match->GetSearchDescription()
			);
		}
		unset($match_manager);

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