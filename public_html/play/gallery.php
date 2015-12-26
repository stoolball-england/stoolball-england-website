<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# include required functions
require_once('page/stoolball-page.class.php');
require_once('media/media-gallery-manager.class.php');
require_once('media/media-list-control.class.php');
require_once('stoolball/user-edit-panel.class.php');

# create page
class CurrentPage extends StoolballPage
{
	/**
	 * The gallery containing the current photo
	 *
	 * @var MediaGallery
	 */
	private $gallery;

	function OnLoadPageData()
	{
		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item']))
		{
			$this->Redirect();
		}

		# new data manager
		$manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());

		# get objects
		$manager->ReadImagesByGalleryId(array($_GET['item']));
		$this->gallery = $manager->GetFirst();

		# must have found an object
		if (!$this->gallery instanceof MediaGallery)
		{
			$this->Redirect();
		}

		# tidy up
		unset($manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Album: ' . $this->gallery->GetTitle());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		
		$description = $this->gallery->GetDescription();
		if ($description and !substr_compare($description, ".", -1, 1) === 0) $description  .= ".";
		if ($description) $description .= " ";
		$photos_count = $this->gallery->Images()->GetCount();
		$photos = ($photos_count > 1) ? $photos_count . " photos" : " 1 photo";
		$description .= "This album contains $photos.";
		$this->SetPageDescription($description);
	}

	function OnPageLoad()
	{
		# display the object
		echo new XhtmlElement('h1', 'Album: <cite>' . htmlentities($this->gallery->GetTitle(), ENT_QUOTES, "UTF-8", false) . '</cite>');

		$s_summary = $this->gallery->GetDescription();
		if ($s_summary)
		{
		    $s_summary = htmlentities($s_summary, ENT_QUOTES, "UTF-8", false);
			$s_summary = XhtmlMarkup::ApplyCharacterEntities($s_summary);
			$s_summary = XhtmlMarkup::ApplyParagraphs($s_summary);
			$s_summary = XhtmlMarkup::ApplyLinks($s_summary);
			$s_summary = XhtmlMarkup::ApplyLists($s_summary);
			$s_summary = XhtmlMarkup::ApplySimpleTags($s_summary);
			echo $s_summary;
		}

		if ($this->gallery->Images()->GetCount())
		{
			$o_control = new MediaListControl($this->gallery->Images()->GetItems());
			$o_control->SetListCssClass('thumbnails');
			echo $o_control;
		}
		else
		{
			echo '<p><strong>There are no photos in this album.</strong></p>';
		}

		$this->AddSeparator();
		$panel = new UserEditPanel($this->GetSettings(), 'this album');

		$user_is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS);
		$user_is_owner = (AuthenticationManager::GetUser()->GetId() == $this->gallery->GetAddedBy()->GetId());
		if ($user_is_admin or $user_is_owner)
		{
			$panel->AddLink('add more photos', $this->gallery->GetAddImagesNavigateUrl());
			$panel->AddLink('edit this album', $this->gallery->GetEditGalleryUrl());
			$panel->AddLink('delete this album', $this->gallery->GetDeleteNavigateUrl());
		}
		$panel->AddLink('create a photo album', $this->GetSettings()->GetUrl('MediaGalleryAdd'));
		echo $panel;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>