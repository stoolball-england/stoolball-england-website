<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/club-manager.class.php');
require_once('stoolball/club-list-control.class.php');

class CurrentPage extends StoolballPage
{
	var $a_clubs;

	function OnLoadPageData()
	{
		# new data manager
		$o_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());

		# get teams
		$o_manager->ReadAll();
		$this->a_clubs = $o_manager->GetItems();

		# tidy up
		unset($o_manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Stoolball clubs');
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
        echo new ClubListControl($this->a_clubs);
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>