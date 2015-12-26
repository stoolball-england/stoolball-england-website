<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/ground-manager.class.php');
require_once('stoolball/ground-list-control.class.php');

class CurrentPage extends StoolballPage
{
	private $a_grounds;

	function OnLoadPageData()
	{
		# new data manager
		$o_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());

		# get grounds
		$o_manager->ReadAll();
		$this->a_grounds = $o_manager->GetItems();

		# tidy up
		unset($o_manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Stoolball grounds');
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
        echo new GroundListControl($this->a_grounds);
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>