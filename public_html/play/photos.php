<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/media-gallery-manager.class.php');
require_once('media/media-gallery-list-control.class.php');

class CurrentPage extends StoolballPage 
{
	private $a_galleries;
	private $user_is_admin;
	
	function OnLoadPageData()
	{
		$this->user_is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS);
		
		# new data manager
		$o_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());
		
		# get albums
		$o_manager->ReadAll();
		$this->a_galleries = $o_manager->GetItems();

		# tidy up
		unset($o_manager);
	}
	
	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Stoolball photo albums');
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}
	
	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
		
		# display the galleries
		echo new MediaGalleryListControl($this->a_galleries, false);
				
		$this->AddSeparator();
		
		require_once('stoolball/user-edit-panel.class.php');
		$panel = new UserEditPanel($this->GetSettings());
		$panel->AddLink('create a photo album', $this->GetSettings()->GetUrl('MediaGalleryAdd'));
		if ($this->user_is_admin)
		{
			$panel->AddLink('check new images','/yesnosorry/gallerycheck.php');
		}
		echo $panel;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>