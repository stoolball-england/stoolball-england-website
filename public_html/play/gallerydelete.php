<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/media-gallery-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The media gallery being deleted
	 *
	 * @var MediaGallery
	 */
	private $o_data_object;
	/**
	 * Media galleries data manager
	 *
	 * @var MediaGalleryManager
	 */
	private $o_manager;

	private $b_deleted = false;

	function OnPageInit()
	{
		# new data manager
		$this->o_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());

		parent::OnPageInit();
	}

	function OnPostback()
	{
		# Get the gallery info and store it
		$i_id = $this->o_manager->GetItemId($this->o_data_object);
		$this->o_manager->ReadById(array($i_id), true);
		$this->o_data_object = $this->o_manager->GetFirst();

		# Check whether cancel was clicked
		if (isset($_POST['cancel']))
		{
			$this->Redirect($this->o_data_object->GetNavigateUrl());
		}

		# Check whether delete was clicked
		if (isset($_POST['delete']))
		{
			# Check again that the requester has permission to delete this album
			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS) or AuthenticationManager::GetUser()->GetId() == $this->o_data_object->GetAddedBy()->GetId());
			if ($has_permission)
			{
				# Get the images in the gallery
				$this->o_manager->Clear();
				$this->o_manager->ReadImagesByGalleryId(array($i_id));
				$gallery = $this->o_manager->GetFirst();
				if (!is_null($gallery)) $this->o_data_object->Images()->SetItems($gallery->Images()->GetItems());

				# Delete the images in the gallery
				$a_image_ids = array();
				foreach ($this->o_data_object->Images() as $image) $a_image_ids[] = $image->GetItem()->GetId();

				require_once('media/image-manager.class.php');
				$image_manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
				$image_manager->DeleteFromGallery($a_image_ids, $i_id);
				unset($image_manager);

				# Delete the gallery itself
				$this->o_manager->Delete(array($i_id));

                # Remove the gallery from search results
                require_once("search/lucene-search.class.php");
                $search = new LuceneSearch();
                $search->DeleteDocumentById("photos" . $i_id);
                $search->CommitChanges();
                
				# Note success
				$this->b_deleted = true;
			}
		}
	}

	function OnLoadPageData()
	{
		# get gallery
		if (!is_object($this->o_data_object))
		{
			$i_id = $this->o_manager->GetItemId($this->o_data_object);
			$this->o_manager->ReadById(array($i_id), true);
			$this->o_data_object = $this->o_manager->GetFirst();
		}

		# tidy up
		unset($this->o_manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Delete album: ' . $this->o_data_object->GetTitle());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		$this->SetContentCssClass('PhotosAdd'); # To use CSS already in stylesheet
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', 'Delete album: <cite>' . Html::Encode($this->o_data_object->GetTitle()) . '</cite>');

		if ($this->b_deleted)
		{

?>
<p>
	The album has been deleted.
</p>
<p>
	<a href="/play/photos.php">View all photo albums</a>
</p>
<?php
}
else
{
$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS) or AuthenticationManager::GetUser()->GetId() == $this->o_data_object->GetAddedBy()->GetId());
if ($has_permission)
{
if ($this->o_data_object->HasCoverImage())
{
$thumb = $this->o_data_object->GetCoverImage()->GetThumbnail();
$cover = new XhtmlElement('div', $thumb);
$cover->SetCssClass('photo thumbPhoto albumCover');
echo $cover;
}
?>
<p>
	Deleting an album cannot be undone. All photos in the album will be deleted.
</p>
<p>
	Are you sure you want to delete this album?
</p>
<form action="<?php echo Html::Encode($this->o_data_object->GetDeleteNavigateUrl()) ?>" method="post" class="deleteButtons">
	<div>
		<input type="submit" value="Delete album" name="delete" />
		<input type="submit" value="Cancel" name="cancel" />
	</div>
</form>
<?php

$this->AddSeparator();
require_once ('stoolball/user-edit-panel.class.php');
$panel = new UserEditPanel($this->GetSettings(), 'this album');

$panel->AddLink('view this album', $this->o_data_object->GetNavigateUrl());
$panel->AddLink('add more photos', $this->o_data_object->GetAddImagesNavigateUrl());
$panel->AddLink('edit this album', $this->o_data_object->GetEditGalleryUrl());

echo $panel;
}
else
{
?>
<p>
	Sorry, you can't delete someone else's photo album.
</p>
<p>
	<a href="<?php echo Html::Encode($this->o_data_object->GetNavigateUrl()) ?>">Go back to album</a>
</p>
<?php
}
}
}

}
new CurrentPage(new StoolballSettings(), PermissionType::AddMediaGallery(), false);
?>