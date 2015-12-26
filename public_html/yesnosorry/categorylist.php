<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('category/category-tree-control.class.php');

class CurrentPage extends StoolballPage 
{
	function OnPrePageLoad()
	{
		$this->SetPageTitle('Stoolball categories');
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}
	
	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
		
		echo new CategoryTreeControl($this->GetCategories());
		
		$this->AddSeparator();

		require_once("stoolball/user-edit-panel.class.php");
		$panel = new UserEditPanel($this->GetSettings());
		$panel->AddLink("add a category", "categoryedit.php");
		echo $panel;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_CATEGORIES, false);
?>