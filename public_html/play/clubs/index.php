<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/clubs/club-manager.class.php');
require_once('stoolball/clubs/club-list-control.class.php');

class CurrentPage extends StoolballPage
{
	var $a_clubs;

	function OnLoadPageData()
	{
		# new data manager
		$o_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());

		# get teams
		$o_manager->ReadById();
		$this->a_clubs = $o_manager->GetItems();

		# tidy up
		unset($o_manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Stoolball clubs and schools');
        $this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
        echo new ClubListControl($this->a_clubs);
        
        if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS)) 
        {
            require_once("stoolball/user-edit-panel.class.php");
            $this->AddSeparator();
            $panel = new UserEditPanel($this->GetSettings(), "clubs and schools");
            $panel->AddLink("add a club or school", "/play/clubs/clubedit.php");
            echo $panel;
        }
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>