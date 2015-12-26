<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/team-manager.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/competition-text-table.class.php');

class CurrentPage extends Page 
{
	private $a_comps;
	
	function OnLoadPageData()
	{
		# new data managers
		$o_comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
		$o_team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());

		# get comps
		$o_comp_manager->SetExcludeInactive(true);
		$o_comp_manager->ReadAllSummaries();
		$this->a_comps = $o_comp_manager->GetItems();
		
		# get teams
		$o_team_manager->FilterByActive(true);
		foreach ($this->a_comps as $o_comp)
		{
			/* @var $o_comp Competition */
			$a_seasons = array($o_comp->GetLatestSeason()->GetId());
			$o_team_manager->ReadBySeasonId($a_seasons);
			while($o_team_manager->MoveNext())
			{
				$o_comp->GetLatestSeason()->AddTeam($o_team_manager->GetItem());
			}
			$o_team_manager->Clear();
		}

		# tidy up
		unset($o_comp_manager);
		unset($o_team_manager);

	}
	
	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Stoolball teams');
		
		echo <<<CSS
<style type="text/css">
* { font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif; }
table { border-collapse: collapse; width: 100%; margin-bottom: 2em; }
tr { vertical-align: top; }
td, th { border: 1px solid #000; width: 33%; padding: 5px; text-align: left; }
caption { text-align: left; font-weight: bold; font-size: 1.5em; margin-bottom: .5em; }
p { margin-top: 0; }
.metadata { display: none; }
</style>
CSS
;
	}
	
	function OnPageLoad()
	{		
		foreach ($this->a_comps as $o_comp)
		{
			/* @var $o_comp Competition */
			echo new CompetitionTextTable($o_comp);
		}
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_TEAMS, false);
?>