<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/media-gallery-manager.class.php');
require_once('media/image-manager.class.php');
require_once('media/image-edit-control.class.php');
require_once('stoolball/user-edit-panel.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The gallery to add photos to
	 *
	 * @var MediaGallery
	 */
	private $gallery;

	/**
	 * Fallback editor for a single image
	 *
	 * @var ImageEditControl
	 */
	private $editor;

	private $gallery_id;
	
	public function OnPageInit()
	{
		if (!isset($_GET['album']) or !is_numeric($_GET['album'])) $this->Redirect();
		$this->gallery_id = $_GET['album'];

		# Create the non-Javascript, non-Flash control
		$this->editor = new ImageEditControl($this->GetSettings());
		$this->editor->LockToGallery($this->gallery_id);
		$this->RegisterControlForValidation($this->editor);

		parent::OnPageInit();
	}

	public function OnPostback()
	{
		# Call IsValid before getting the object, because we want to validate the uploaded image 
		# while it's still a temporary file. GetDataObject moves it to a permanent location.
		if($this->IsValid())
		{
			$image = $this->editor->GetDataObject();
			$manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
			$manager->Save($image);
			unset($manager);
        
            # Get gallery so that we have details for search and to redirect
            $gallery_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());
            $gallery_manager->ReadImagesByGalleryId(array($this->gallery_id));
            $this->gallery = $gallery_manager->GetFirst();
                
            # update media gallery in search results
            require_once("search/lucene-search.class.php");
            $search = new LuceneSearch();
            $search->DeleteDocumentById("photos" . $this->gallery_id);
            $search->IndexGallery($this->gallery);
            $search->CommitChanges();
            
		    $this->Redirect($this->gallery->GetNavigateUrl());
        }
	}

	public function OnLoadPageData()
	{
		$gallery_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());
		$gallery_manager->ReadById(array($this->gallery_id));
		$this->gallery = $gallery_manager->GetFirst();
		if (!$this->gallery instanceof MediaGallery) $this->Redirect();
		unset($gallery_manager);
	}

	public function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Add photo to album: ' . $this->gallery->GetTitle());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	public function OnPageLoad()
	{
		echo new XhtmlElement('h1', 'Add a photo to <cite>' . Html::Encode($this->gallery->GetTitle()) . '</cite>');
		$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS) or AuthenticationManager::GetUser()->GetId() == $this->gallery->GetAddedBy()->GetId());
		if ($has_permission)
		{
			/* Create instruction panel */
			$o_panel_inner2 = new XhtmlElement('div');
			$o_panel_inner1 = new XhtmlElement('div', $o_panel_inner2);
			$o_panel = new XhtmlElement('div', $o_panel_inner1);
			$o_panel->SetCssClass('panel instructionPanel');

			$o_title_inner3 = new XhtmlElement('span', 'How to add your photo:');
			$o_title_inner2 = new XhtmlElement('span', $o_title_inner3);
			$o_title_inner1 = new XhtmlElement('span', $o_title_inner2);
			$o_title = new XhtmlElement('h2', $o_title_inner1);
			$o_panel_inner2->AddControl($o_title);

			$o_tab_tip = new XhtmlElement('ul');
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Photos must be under 2MB each. You may need to shrink your photo first'));
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Your photo won\'t show without a caption &#8211; make sure you add one'));
			$o_panel_inner2->AddControl($o_tab_tip);
			echo $o_panel;

			echo $this->editor;
		}
		else
		{
				?>
		<p>Sorry, you can't add photos to someone else's photo album.</p>
		<p><a href="<?php echo Html::Encode($this->gallery->GetNavigateUrl()) ?>">Go back to album</a></p>
		<?php

		}
		$this->AddSeparator();
		$o_panel = new UserEditPanel($this->GetSettings(), 'adding photos');
		$o_panel->AddLink('view this album', $this->gallery->GetNavigateUrl());
		$o_panel->AddLink('edit this album', $this->gallery->GetEditGalleryUrl());
		echo $o_panel;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::AddImage(), false);
?>