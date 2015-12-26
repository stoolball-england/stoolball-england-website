<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/club-manager.class.php');
require_once('stoolball/team-list-control.class.php');

class CurrentPage extends StoolballPage 
{
	
	/**
	 * The club to view
	 *
	 * @var Club
	 */
	private $o_club;
	
	function OnLoadPageData()
	{
		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->Redirect();
		
		# new data manager
		$o_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());

		# get teams
		$o_manager->ReadById(array($_GET['item']));
		$this->o_club = $o_manager->GetFirst();
		
		# must have found a team
		if (!$this->o_club instanceof Club) $this->Redirect();

		# tidy up
		unset($o_manager);
	}
	
	function OnPrePageLoad()
	{
		$this->SetPageTitle($this->o_club->GetName());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}
	
	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->o_club->GetName()));
		
		$a_teams = $this->o_club->GetItems();
		if (count($a_teams) > 0)
		{
			echo new TeamListControl($a_teams);
		}
        
        if ($this->o_club->GetTwitterAccount())
        {
            ?>
            <div class="social screen">
                <a href="https://twitter.com/<?php echo Html::Encode(substr($this->o_club->GetTwitterAccount(), 1)); ?>" class="twitter-follow-button">Follow <?php echo Html::Encode($this->o_club->GetTwitterAccount()); ?></a>
                <script src="https://platform.twitter.com/widgets.js"></script>
            </div>
            <?php
        }
		
		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS)) 
		{
			require_once('stoolball/user-edit-panel.class.php');
			$panel = new UserEditPanel($this->GetSettings(), 'this club');
			$panel->AddLink('edit this club', $this->o_club->GetEditClubUrl());
			$panel->AddLink('delete this club', $this->o_club->GetDeleteClubUrl());
			echo $this->AddSeparator() . $panel;
		}

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>