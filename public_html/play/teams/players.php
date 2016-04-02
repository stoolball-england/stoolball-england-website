<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/player-manager.class.php');
require_once("stoolball/user-edit-panel.class.php");

class CurrentPage extends StoolballPage
{
	/**
	 * The team to list players for
	 * @var Team
	 */
	private $team;
	private $players;

	public function OnPageInit()
	{
		# Get team to display players for
		if (!isset($_GET['team']) or !is_numeric($_GET['team'])) $this->Redirect();
		$this->team = new Team($this->GetSettings());
		$this->team->SetId($_GET['team']);
	}

	public function OnLoadPageData()
	{
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$player_manager->ReadPlayersInTeam(array($this->team->GetId()));
		$this->players = $player_manager->GetItems();
		unset($player_manager);

		if (count($this->players))
		{
			$this->team = $this->players[0]->Team();
		}
	}

	public function OnPrePageLoad()
	{
		# set up page
		$this->SetPageTitle("Players for " . $this->team->GetName() . ' stoolball team');
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		$this->SetContentCssClass("playersPage");
	}

	public function OnPageLoad()
	{
        echo '<article typeof="schema:SportsTeam" about="' . htmlentities($this->team->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '">';
        echo '<h1>Players for <span property="schema:name">' . htmlentities($this->team->GetName(), ENT_QUOTES, "UTF-8", false) . "</span></h1>";

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => $this->team->GetNavigateUrl());
        $tabs['Players'] = '';
        $tabs['Statistics'] = $this->team->GetStatsNavigateUrl();
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php
        
        
		if (count($this->players) > 4)
		{
            $threshold = ((int)gmdate("Y"))-1;
            $list_open = false;
            
            echo '<div class="player-list">';
			foreach ($this->players as $player)
			{
				/* @var $player Player */
				if ($player->GetPlayerRole() == Player::PLAYER and (gmdate("Y", $player->GetLastPlayedDate()) >= $threshold or $player->GetLastPlayedDate() == 0))
				{
					if (!$list_open)
					{
						echo '<h2>Current and recent players</h2><ol rel="schema:members">';
						$list_open = true;
					}
					echo  '<li typeof="schema:Person" about="' . htmlentities($player->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '"><a property="schema:name" rel="schema:url" href="' . htmlentities($player->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . "</a></li>";
				}
			}
            if ($list_open) echo "</ol>";
    
            $list_open = false;
            foreach ($this->players as $player)
            {
                /* @var $player Player */
                if ($player->GetPlayerRole() == Player::PLAYER and gmdate("Y", $player->GetLastPlayedDate()) < $threshold and $player->GetLastPlayedDate() != 0)
                {
                    if (!$list_open)
                    {
                        echo '<h2>Former players</h2><ol rel="schema:members">';
                        $list_open = true;
                    }
                    echo  '<li typeof="schema:Person" about="' . htmlentities($player->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '"><a property="schema:name" rel="schema:url" href="' . htmlentities($player->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . "</a></li>";
                }
            }
            if ($list_open) echo "</ol>";
            
            $list_open = false;
            foreach ($this->players as $player)
            {
                /* @var $player Player */
                if ($player->GetPlayerRole() != Player::PLAYER)
                {
                    if (!$list_open)
                    {
                        echo "<h2>Extras</h2><ul>";
                        $list_open = true;
                    }
                    echo '<li><a href="' . htmlentities($player->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . "</a></li>";
                }
            }
            if ($list_open) echo "</ul>";
            
			echo "</div>";
		}
		else
		{
			?>
<p>There aren't any player statistics for this team yet.</p>
<p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results &#8211; why you should add yours</a>.</p>
			<?php
		}

        ?>
        </div>
        </div>
        </article>
        <?php 
        $this->AddSeparator();
        
		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS, $this->team->GetLinkedDataUri()))
		{
			# Create a panel with actions
			$panel = new UserEditPanel($this->GetSettings());
            $panel->AddCssClass("with-tabs");
			#$panel->AddLink("add a player", $this->team->GetPlayerAddNavigateUrl());
			echo $panel;
            $this->BuySomething();
		}
        else {
            echo '<div class="with-tabs">';
            $this->BuySomething();
            echo '</div>';
        }
        
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>