<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');
set_time_limit(0);

require_once('page/stoolball-page.class.php');
require_once('stoolball/player.class.php');

class CurrentPage extends StoolballPage
{
	/* @var $process ProcessManager */
	private $process;

	function OnLoadPageData()
	{
		require_once("data/process-manager.class.php");
		$this->process = new ProcessManager();

		if ($this->process->ReadyToDeleteAll())
		{
			$stats = $this->GetSettings()->GetTable("PlayerMatch");
			$sql = "TRUNCATE TABLE $stats";
			$this->GetDataConnection()->query($sql);
		}

		$matches = $this->GetSettings()->GetTable("MatchTeam");
		$mt = $this->GetSettings()->GetTable('MatchTeam');
		$sql = "SELECT match_id, match_team_id FROM $matches ORDER BY match_team_id " . $this->process->GetQueryLimit();
		$result =  $this->GetDataConnection()->query($sql);

		require_once "stoolball/player-manager.class.php";
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());

		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());

		while($row = $result->fetch())
		{
			$affected_players = $player_manager->ReadPlayersInMatch(array($row->match_id));
			# generate player statistics from the data entered
			if(count($affected_players))
			{
				$statistics_manager->UpdateBattingStatistics($affected_players, array($row->match_team_id));

				# get the match_team_id for the bowling that goes with this batting
				$sql = "SELECT match_team_id FROM $mt
						WHERE match_id = (SELECT match_id FROM $mt WHERE match_team_id = $row->match_team_id)
						AND match_team_id != $row->match_team_id
						AND team_role IN (" . TeamRole::Home() . ", " . TeamRole::Away() . ")";
				$result2 = $this->GetDataConnection()->query($sql);
				$row2 = $result2->fetch();
				$bowling_match_team_id = $row2->match_team_id;

				$statistics_manager->UpdateFieldingStatistics($affected_players, array($bowling_match_team_id));
				$statistics_manager->UpdateBowlingStatistics($affected_players, array($bowling_match_team_id));
				$statistics_manager->UpdatePlayerOfTheMatchStatistics($row->match_id);
				$statistics_manager->DeleteObsoleteStatistics($row->match_id);
			}
			$this->process->OneMoreDone();
		}
	}
	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Generate player statistics');
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
		$this->process->ShowProgress();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_STATISTICS, false);
?>