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
    
    private $has_permission = null;
    
    /**
     * @var Team
     */
    private $team;
    
    private $add_player_already_exists;
    
	public function OnPageInit()
	{
		# Create the editor and manager
		$this->player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$this->editor = new PlayerEditor($this->GetSettings(), $this->GetCsrfToken());
		$this->editor->SetAllowCancel(true);
		$this->RegisterControlForValidation($this->editor);

		# Get player to edit
		$this->player = new Player($this->GetSettings());
		$this->player->SetId($this->editor->GetDataObjectId());

        # If the team parameter is passed, that's a request to create a new player in a team
        if (isset($_GET['team']) and is_numeric($_GET['team'])) {
            $this->player->Team()->SetId($_GET['team']);
        }
	}

	public function OnPostback()
	{       
        if (isset($_GET['team']) and is_numeric($_GET['team'])) {
            $this->AddPlayer();
        } else {
            $this->UpdatePlayer();
        }
	}
	
	public function AddPlayer() {

        $this->player = $this->editor->GetDataObject();
        $this->player->Team()->SetId($_GET['team']);

        require_once("stoolball/team-manager.class.php");
        $team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $team_manager->ReadById(array($this->player->Team()->GetId()));
        $this->team = $team_manager->GetFirst();
        unset($team_manager);
 
        if ($this->editor->CancelClicked())
        {
            $this->Redirect($this->team->GetPlayersNavigateUrl());            
        }

        if ($this->IsValid())
        {
             # Now check again, because it's a new request, that the user has permission
            $this->CheckForPermission($this->team);
            
            # Check whether a player by that name already exists
            $this->player_manager->MatchExistingPlayer($this->player);

            if ($this->player->GetId())
            {
                $this->add_player_already_exists = true;
            } 
            else {
                $this->player_manager->SavePlayer($this->player);
                unset($player_manager);

                $this->Redirect($this->team->GetPlayersNavigateUrl());
            }
            
        }
	}
    
    public function UpdatePlayer() {
        
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
            
            # Now check again, because it's a new request, that the user has permission
            $this->CheckForPermission($player_to_edit->Team());
            
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
                    $this->SearchIndexer()->DeleteFromIndexById("player" . $this->player->GetId());
                    $this->SearchIndexer()->DeleteFromIndexById("player" . $player_to_edit->GetId());

                    # Re-request player to get details for search engine, and player URL to redirect to
                    require_once("search/player-search-adapter.class.php");
                    $this->player_manager->ReadPlayerById($player_to_edit->GetId());
                    $this->player = $this->player_manager->GetFirst();
                    $adapter = new PlayerSearchAdapter($this->player);
                    $this->SearchIndexer()->Index($adapter->GetSearchableItem());
                    $this->SearchIndexer()->CommitChanges();
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
                # Set the team short URL so that it can be used to regenerate the player's short URL
                $this->player->Team()->SetShortUrl($player_to_edit->Team()->GetShortUrl());             

                $this->player_manager->SavePlayer($this->player);
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
        
        # if it's a new player, get the team details
        if (!$this->player->GetId() or $this->add_player_already_exists) {
            if (!$this->team instanceof Team) {
                require_once("stoolball/team-manager.class.php");
                $team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
                $team_manager->ReadById(array($this->player->Team()->GetId()));
                $this->team = $team_manager->GetFirst();
                unset($team_manager);
            }
            
            $this->player->Team()->SetName($this->team->GetName());
            $this->player->Team()->SetShortUrl($this->team->GetShortUrl());
        }
        
        # ensure we have permission
        $this->CheckForPermission($this->player->Team());
	}

    private function CheckForPermission(Team $team) {
            
        if (is_null($this->has_permission)) {
            $this->has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS, $team->GetLinkedDataUri()));
            if (!$this->has_permission) {
                $this->GetAuthenticationManager()->GetPermission();
            }
        }
    }

	public function OnPrePageLoad()
	{
        # Different page title for add and edit
        if ($this->player->GetId() and !$this->add_player_already_exists)
        {
            $this->SetPageTitle("Rename " . $this->player->GetName());
        }
        else
        {
            $this->SetPageTitle("Add player for " . $this->player->Team()->GetName());
        }
		$this->SetContentConstraint(StoolballPage::ConstrainText());
		$this->LoadClientScript("playeredit.js", true);
	}

	public function OnPageLoad()
	{
		echo "<h1>" . htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false) . "</h1>";

        if ($this->add_player_already_exists) {
            ?>
            <p><a href="<?php echo Html::Encode($this->player->GetPlayerUrl()) ?>"><?php echo Html::Encode($this->player->GetName()) ?></a> is already listed as a player.</p>
            <p>Return to <a href="<?php echo Html::Encode($this->player->Team()->GetPlayersNavigateUrl()) ?>">players for <?php echo Html::Encode($this->player->Team()->GetName()) ?></a>.</p>
            <?php   
        } 
        else 
        {
            if ($this->player->GetPlayerRole() == Player::PLAYER) {
    		  $this->editor->SetDataObject($this->player);
    		  echo $this->editor;
            } else {
              ?>Sorry, an extras player can't be renamed.<?php
            }
        }
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>