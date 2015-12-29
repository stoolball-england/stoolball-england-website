<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/player-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The player being deleted
	 *
	 * @var Player
	 */
	private $player;
	/**
	 * Player manager
	 *
	 * @var PlayerManager
	 */
	private $player_manager;

	private $b_deleted = false;

	public function OnPageInit()
	{
		$this->player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
	}

	public function OnPostback()
	{
		# Get the player info and store it
		$id = $this->player_manager->GetItemId($this->player);
		$this->player_manager->ReadPlayerById($id);
		$this->player = $this->player_manager->GetFirst();
		if (!$this->player instanceof Player)
		{
			# This can be the case if the back button is used to go back to the "player has been deleted" page.
			$this->b_deleted = true;
			return;
		}
        
        if ($this->player->GetPlayerRole() != Player::PLAYER) {
            http_response_code(401);
            return;
        }

		# Check whether cancel was clicked
		if (isset($_POST['cancel']))
		{
			$this->Redirect($this->player->GetPlayerUrl());
		}

		# Check whether delete was clicked
		if (isset($_POST['delete']))
		{
			# Check again that the requester has permission to delete this player
			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_PLAYERS));

			if ($has_permission)
			{
				# Delete the player
				$this->player_manager->Delete(array($id));

                # Delete player's entry in the search engine
                require_once("search/lucene-search.class.php");
                $search = new LuceneSearch();
                $search->DeleteDocumentById("player" . $id);    
                $search->CommitChanges();


				# Note success
				$this->b_deleted = true;
			}
		}
	}

	public function OnLoadPageData()
	{
		# get player
		if (!is_object($this->player))
		{
			$id = $this->player_manager->GetItemId($this->player);
			if ($id)
			{
				$this->player_manager->ReadPlayerById($id);
				$this->player = $this->player_manager->GetFirst();
			}
		}

		# tidy up
		unset($this->player_manager);
	}

	public function OnPrePageLoad()
	{
		# set page title
		if ($this->player instanceof Player)
		{
			$this->SetPageTitle('Delete player: ' . $this->player->GetName());
			$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		}
		else
		{
			$this->SetPageTitle('Player already deleted');
		}
	}

	function OnPageLoad()
	{
		if (!$this->player instanceof Player)
		{
			echo new XhtmlElement('h1', 'Player already deleted');
			echo new XhtmlElement('p', "The player you're trying to delete does not exist or has already been deleted.");
			return ;
		}
        
        echo new XhtmlElement('h1', 'Delete player: <cite>' . Html::Encode($this->player->GetName()) . '</cite>');

        if ($this->player->GetPlayerRole() != Player::PLAYER) {
          ?>Sorry, an extras player can't be deleted.<?php
          return;
        }
        

		if ($this->b_deleted)
		{
			echo "<p>" . Html::Encode($this->player->GetName()) . "'s information has been deleted.</p>
			<p><a href=\"" . Html::Encode($this->player->Team()->GetPlayersNavigateUrl()) . "\">List players for " . Html::Encode($this->player->Team()->GetName()) . "</a></p>";
		}
		else
		{
			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_PLAYERS));
			if ($has_permission)
			{
				$s_detail = $this->player->GetName() . " has ";
				switch ($this->player->GetTotalMatches())
				{
					case 0:
						$s_detail .= "not played any matches";
						break;
					case 1:
						$s_detail .= "played one match";
						break;
					default:
						$s_detail .= "played " . $this->player->GetTotalMatches() . " matches";
						break;
				}
				$s_detail .= " for " . $this->player->Team()->GetName() . '. ';

				echo new XhtmlElement('p', Html::Encode($s_detail));

				?>
<p>Deleting a player cannot be undone. Their scores and performances
will be removed from all match scorecards and statistics.</p>
<p>Are you sure you want to delete this player?</p>
<form action="<?php echo Html::Encode($this->player->GetDeleteUrl()) ?>" method="post"
	class="deleteButtons">
<div><input type="submit" value="Delete player" name="delete" /> <input
	type="submit" value="Cancel" name="cancel" /></div>
</form>
				<?php

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), $this->player->GetName());

				$panel->AddLink("view this player", $this->player->GetPlayerUrl());
				$panel->AddLink("rename this player", $this->player->GetEditUrl());

				echo $panel;
			}
			else
			{
				?>
<p>Sorry, you can't delete a player unless you're responsible for
updating their team.</p>
<p><a href="<?php echo Html::Encode($this->player->GetPlayerUrl()) ?>">Go back to <?php echo Html::Encode($this->player->GetName())?>'s
profile</a></p>
				<?php
			}
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_PLAYERS, false);
?>