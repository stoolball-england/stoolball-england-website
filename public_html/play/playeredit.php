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
            # First check in the database that this is not an extras player that can't be renamed
            $this->player_manager->ReadPlayerById($this->player->GetId());
            $player_to_edit = $this->player_manager->GetFirst();
            if ($player_to_edit->GetPlayerRole() != Player::PLAYER) {
                http_response_code(401);
                return;
            }
            
			# Get the existing player for their team, change the name and see if the renamed player 
			# already exists and is different from the one being edited
			$player_to_edit->SetName($this->player->GetName());
			$this->player_manager->MatchExistingPlayer($player_to_edit);

			if ($player_to_edit->GetId() and $player_to_edit->GetId() != $this->player->GetId())
			{
				if ($this->editor->IsMergeRequested())
				{
					$this->player_manager->MergePlayers($this->player, $player_to_edit);

                    # Remove details of both players from the search engine.
                    require_once("search/lucene-search.class.php");
                    $search = new LuceneSearch();
                    $search->DeleteDocumentById("player" . $this->player->GetId());
                    $search->DeleteDocumentById("player" . $player_to_edit->GetId());

                    # Re-request player to get details for search engine, and player URL to redirect to
                    $this->player_manager->ReadPlayerById($player_to_edit->GetId());
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
				$this->player_manager->SavePlayer($this->player, true);
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
	}

	public function OnPrePageLoad()
	{
		$this->SetPageTitle("Rename " . $this->player->GetName());
		$this->SetContentConstraint(StoolballPage::ConstrainText());
		$this->LoadClientScript("playeredit.js", true);
	}

	public function OnPageLoad()
	{
		echo "<h1>" . htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false) . "</h1>";

        if ($this->player->GetPlayerRole() == Player::PLAYER) {
		  $this->editor->SetDataObject($this->player);
		  echo $this->editor;
        } else {
          ?>Sorry, an extras player can't be renamed.<?php
        }
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_PLAYERS, false);
?>