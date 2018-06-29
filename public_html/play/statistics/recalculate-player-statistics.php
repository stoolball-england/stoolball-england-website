<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');
set_time_limit(0);

require_once('page/stoolball-page.class.php');
require_once('stoolball/player.class.php');

class CurrentPage extends StoolballPage
{
	/* @var $process ProcessManager */
	private $process;
	
	/* @var $match Match */
	private $match;

	function OnLoadPageData()
	{
		$user = AuthenticationManager::GetUser();

		if ($_SERVER['REQUEST_METHOD'] == "POST") 
		{
			require_once("data/process-manager.class.php");
			$this->process = new ProcessManager('', 100, $_POST["set"]);

			require_once('stoolball/statistics/statistics-manager.class.php');
			$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());

			require_once "stoolball/player-manager.class.php";
			$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());

			$matches = $this->GetSettings()->GetTable("MatchTeam");
			$mt = $this->GetSettings()->GetTable('MatchTeam');
			$stats = $this->GetSettings()->GetTable("PlayerMatch");
			$sql = "";

			if ($_POST["set"] === "all" and $user->Permissions()->HasPermission(PermissionType::MANAGE_STATISTICS)) {
				if ($this->process->ReadyToDeleteAll())
				{
					$sql = "TRUNCATE TABLE $stats";
					$this->GetDataConnection()->query($sql);
				}

				$sql = "SELECT match_id, match_team_id FROM $matches ORDER BY match_team_id " . $this->process->GetQueryLimit();
			} 
			else if ($_POST["set"] === "match" and $user->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES)) 
			{
				$match_id = Sql::ProtectNumeric(intval($_GET["match"]));
				if ($this->process->ReadyToDeleteAll())
				{
					$sql = "DELETE FROM $stats WHERE match_id = $match_id";
					$this->GetDataConnection()->query($sql);
				}

				$sql = "SELECT match_id, match_team_id FROM $matches WHERE match_id = $match_id ORDER BY match_team_id " . $this->process->GetQueryLimit();
			}

			if (!$sql) return;
			$result =  $this->GetDataConnection()->query($sql);

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
		else if (isset($_GET["match"]) and is_numeric($_GET["match"])) {

			require_once('stoolball/match-manager.class.php');
			$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
			$match_manager->ReadByMatchId(array($_GET['match']));
			$this->match = $match_manager->GetFirst();
			unset($match_manager);
		}
	}
	function OnPrePageLoad()
	{
		if ($this->match instanceof Match) 
		{
			$this->SetPageTitle("Recalculate statistics for " . $this->match->GetTitle() . ", " . $this->match->GetStartTimeFormatted());
		}
		else
		{
			$this->SetPageTitle('Recalculate statistics for all matches, ever');
		}
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
		
		$user = AuthenticationManager::GetUser();
		if ($_SERVER['REQUEST_METHOD'] == "GET") 
		{
			?>
			<p>If there are errors in the match statistics for players you can recalculate them from the original match data.</p>
			<?php
			if ($this->match instanceof Match and
				$user->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES)) 
			{
				?>
				<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
				<div>
				<input type="hidden" name="set" value="match" /> 
				<input type="submit" value="Recalculate statistics" />
				</div>
			</form>
				<?php
			}
			else if ($user->Permissions()->HasPermission(PermissionType::MANAGE_STATISTICS)) 	
			{
				?>
				<p><strong>Use with caution.</strong> Recalculating all statistics takes a long time and, while it's happening, 
				statistics on the website are missing or incomplete.</p>
				<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
					<div>
						<input type="hidden" name="set" value="all" />
						<input type="submit" value="Recalculate all statistics, ever" />
					</div>
				</form>
				<?php
			}
		}
		else if ($_SERVER['REQUEST_METHOD'] == "POST") 
		{
			$this->process->ShowProgress();
		}		
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_STATISTICS, false);
?>