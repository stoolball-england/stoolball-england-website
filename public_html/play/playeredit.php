<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/player-manager.class.php');
require_once 'stoolball/player-editor.class.php';
require_once("stoolball/user-edit-panel.class.php");

class CurrentPage extends StoolballPage
{
	/**
	 * Player to view
	 * @var Player
	 */
	private $player;

	/**
	 * Player editor
	 * @var PlayerEditor
	 */
	private $editor;

	/**
	 * Player manager
	 * @var PlayerManager
	 */
	private $player_manager;

	private $teams;

	public function OnPageInit()
	{
		# Create the editor and manager
		$this->player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$this->editor = new PlayerEditor($this->GetSettings());
		$this->editor->SetAllowCancel(true);
		$this->RegisterControlForValidation($this->editor);

		# Get player to edit
		$this->player = new Player($this->GetSettings());
		$this->player->SetId($this->player_manager->GetItemId());

		# If the team parameter is passed, that's a request to create a new player in a team
		if (isset($_GET['team']) and is_numeric($_GET['team'])) $this->player->Team()->SetId($_GET['team']);
	}

	public function OnPostback()
	{
		# If the player data is valid, save and redirect back to player
		$this->player = $this->editor->GetDataObject();

		if ($this->editor->CancelClicked())
		{
			$this->player_manager->ReadPlayerById($this->player->GetId());
			$this->player = $this->player_manager->GetFirst();
			$this->Redirect($this->player->GetPlayerUrl());
		}

		if ($this->IsValid())
		{
			# See if the player already exists and is different from the one being added/edited
			$duplicate_player = new Player($this->GetSettings());
			$duplicate_player->SetName($this->player->GetName());
			$duplicate_player->Team()->SetId($this->player->Team()->GetId());
			$duplicate_player->SetPlayerRole(Player::PLAYER);
			$this->player_manager->MatchExistingPlayer($duplicate_player);

			if ($duplicate_player->GetId() and $duplicate_player->GetId() != $this->player->GetId())
			{
				if ($this->editor->IsMergeRequested())
				{
					$this->player_manager->MergePlayers($this->player, $duplicate_player);

                    # Remove details of both players from the search engine.
                    require_once("search/lucene-search.class.php");
                    $search = new LuceneSearch();
                    $search->DeleteDocumentById("player" . $this->player->GetId());
                    $search->DeleteDocumentById("player" . $duplicate_player->GetId());

                    # Re-request player to get details for search engine, and player URL to redirect to
                    $this->player_manager->ReadPlayerById($duplicate_player->GetId());
                    $this->player = $this->player_manager->GetFirst();
                    $search->IndexPlayer($this->player);
                    $search->CommitChanges();
                    unset($player_manager);

					$this->Redirect($this->player->GetPlayerUrl());
				}
				else
				{
					$this->editor->SetCurrentPage(PlayerEditor::MERGE_PLAYER);
				}
			}
			else
			{
				$this->player_manager->Save($this->player);
                unset($player_manager);

				$this->Redirect($this->player->GetPlayerUrl());
			}
		}
	}

	public function OnLoadPageData()
	{
		# Read the player data if (a) it's not a new player and (b) it's not in the postback data
		if ($this->player->GetId() and !$this->IsPostback())
		{
			$this->player_manager->ReadPlayerById($this->player->GetId());
			$this->player = $this->player_manager->GetFirst();
		}
		unset($this->player_manager);

		# ensure we have a player
		if (!$this->player instanceof Player) $this->Redirect();

		# get possible teams
		require_once("stoolball/team-manager.class.php");
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
		$team_manager->ReadAll();
		$this->teams = $team_manager->GetItems();
		$this->editor->SetAvailableTeams($this->teams);
		unset($team_manager);
	}

	public function OnPrePageLoad()
	{
		# Different page title for add and edit
		if ($this->player->GetId())
		{
			$this->SetPageTitle("Edit " . $this->player->GetName());
		}
		else
		{
			# find the team name
			$this->SetPageTitle("Add new player");

			$player_team = $this->player->Team()->GetId();
			foreach ($this->teams as $team)
			{
				if ($team->GetId() == $player_team)
				{
					$this->SetPageTitle("Add player for " . $team->GetName());
					break;
				}
			}
		}
		$this->SetContentConstraint(StoolballPage::ConstrainText());
		$this->LoadClientScript("playeredit.js", true);
	}

	public function OnPageLoad()
	{
		echo "<h1>" . htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false) . "</h1>";

		# set up the editor
		$this->editor->SetDataObject($this->player);
		echo $this->editor;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_PLAYERS, false);
?>