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
		$data[] = array("Club/ facility name","Address 1", "Address 2", "Address 3", "Address 4", "Address 5", "City", "Postcode", "Country", "Tel", "Email", "Website", "Activities", "Opening times");

		require_once('stoolball/team-manager.class.php');
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
		$team_manager->FilterByActive(true);
		$team_manager->FilterByTeamType(array(Team::REGULAR));
		$team_manager->ReadById();
		while($team_manager->MoveNext())
		{
			/* @var $team Team */
			$team = $team_manager->GetItem();

			# NHS choices can only import records with a postcode
			if (!$team->GetGround()->GetAddress()->GetPostcode()) continue;

			$address = array();
			if ($team->GetGround()->GetAddress()->GetSaon()) $address[] = $team->GetGround()->GetAddress()->GetSaon();
			if ($team->GetGround()->GetAddress()->GetPaon()) $address[] = $team->GetGround()->GetAddress()->GetPaon();
			if ($team->GetGround()->GetAddress()->GetStreetDescriptor()) $address[] = $team->GetGround()->GetAddress()->GetStreetDescriptor();
			if ($team->GetGround()->GetAddress()->GetLocality()) $address[] = $team->GetGround()->GetAddress()->GetLocality();
			if ($team->GetGround()->GetAddress()->GetAdministrativeArea()) $address[] = $team->GetGround()->GetAddress()->GetAdministrativeArea();

			$data[] = array(
			$team->GetName() . " Stoolball Club",
			isset($address[0]) ? $address[0] : "",
			isset($address[1]) ? $address[1] : "",
			isset($address[2]) ? $address[2] : "",
			isset($address[3]) ? $address[3] : "",
			isset($address[4]) ? $address[4] : "",
			$team->GetGround()->GetAddress()->GetTown(),
			$team->GetGround()->GetAddress()->GetPostcode(),
			"England",
            $valid_key ? $team->GetContactPhone() : "",
            $valid_key ? $team->GetContactEmail() : "",
    		$team->GetWebsiteUrl() ? $team->GetWebsiteUrl() : "https://" . $this->GetSettings()->GetDomain() . $team->GetNavigateUrl(),
			"stoolball",
			preg_replace('/\[[^]]+\]/', "", $team->GetPlayingTimes())
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