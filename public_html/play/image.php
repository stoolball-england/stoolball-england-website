<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/media-gallery-manager.class.php');
require_once('media/image-control.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The gallery containing the current image
	 *
	 * @var MediaGallery
	 */
	private $gallery;
	private $b_can_edit = false;

	function OnLoadPageData()
	{
		# check parameter
		if (!isset($_GET['gallery']) or !is_numeric($_GET['gallery']) or !isset($_GET['image']) or !is_numeric($_GET['image']))
		{
			$this->Redirect();
		}

		# new data manager
		$manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());

		# get objects
		$manager->ReadImagesByGalleryId(array($_GET['gallery']));
		$this->gallery = $manager->GetFirst();
		unset($manager);

		# must have found an object
		if (!$this->gallery instanceof MediaGallery)
		{
			$this->Redirect();
		}

		$this->gallery->SetCurrentImageById($_GET['image']);
		if ($this->gallery->GetCurrentImagePosition() === false)
		{
			$this->Redirect($this->gallery->GetNavigateUrl());
		}
	}

	function OnPrePageLoad()
	{
		$b_is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS);
		$b_is_gallery_owner = (AuthenticationManager::GetUser()->GetId() == $this->gallery->GetAddedBy()->GetId());
		$this->b_can_edit = ($b_is_admin or $b_is_gallery_owner);

		$this->SetPageTitle($this->gallery->GetCurrentImage()->GetItem()->GetDescription());
		$this->SetContentCssClass('image');

		if ($this->b_can_edit) $this->SetContentConstraint($this->ConstrainColumns());
	}
	function OnPageLoad()
	{
		$image = $this->gallery->GetCurrentImage()->GetItem();
		echo new XhtmlElement('h1', Html::Encode($image->GetDescription()), "aural");

		echo new ImageControl($image, $this->gallery);

		if ($this->b_can_edit)
		{
			/* @var $gallery MediaGallery */

			$this->AddSeparator();
			require_once('stoolball/user-edit-panel.class.php');
			$panel = new UserEditPanel($this->GetSettings(), 'this photo');
			$panel->AddLink('add more photos', $this->gallery->GetAddImagesNavigateUrl());
			$panel->AddLink('edit this album', $this->gallery->GetEditGalleryUrl());
			$panel->AddLink('delete this album', $this->gallery->GetDeleteNavigateUrl());
			$panel->AddLink('create a photo album', $this->GetSettings()->GetUrl('MediaGalleryAdd'));
			echo $panel;
		}
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>