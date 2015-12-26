<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('media/image-manager.class.php');

class CurrentPage extends Page
{
	public function OnPostback()
	{
		if (isset($_POST['image']) and isset($_POST['caption']))
		{
			$manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
			if ($manager->SaveCaption(html_entity_decode($_POST['image']), html_entity_decode($_POST['caption'])))
			{
				echo 'saved';
			}
		}
		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::AddImage(), false);
?>