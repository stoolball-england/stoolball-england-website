<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/media-gallery-manager.class.php');
require_once('media/image-manager.class.php');
require_once('stoolball/user-edit-panel.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The gallery to add photos to
	 *
	 * @var MediaGallery
	 */
	private $gallery;
	private $gallery_id;
	
	public function OnPageInit()
	{
		if (!isset($_GET['album']) or !is_numeric($_GET['album'])) $this->Redirect();
		$this->gallery_id = $_GET['album'];

		parent::OnPageInit();
	}

	public function OnLoadPageData()
	{
		$gallery_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());
		$gallery_manager->ReadById(array($this->gallery_id));
		$this->gallery = $gallery_manager->GetFirst();
		if (!$this->gallery instanceof MediaGallery) $this->Redirect();
	}

	public function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Add photos to album: ' . $this->gallery->GetTitle());
		$this->SetContentCssClass('PhotosAdd');
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());

		$this->LoadClientScript('/swfupload/swfupload-1.js');
		$this->LoadClientScript('photosadd-2.js', true);
	}

	public function OnCloseHead()
	{
		echo '<link href="/swfupload/swfupload-1.css" rel="stylesheet" type="text/css" media="all" />';
		parent::OnCloseHead();
	}

	public function OnPageLoad()
	{
		echo new XhtmlElement('h1', 'Add photos to <cite>' . Html::Encode($this->gallery->GetTitle()) . '</cite>');

		$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS) or AuthenticationManager::GetUser()->GetId() == $this->gallery->GetAddedBy()->GetId());
		if ($has_permission)
		{
			/* Create instruction panel */
			$o_panel_inner2 = new XhtmlElement('div');
			$o_panel_inner1 = new XhtmlElement('div', $o_panel_inner2);
			$o_panel = new XhtmlElement('div', $o_panel_inner1);
			$o_panel->SetCssClass('panel instructionPanel');

			$o_title_inner3 = new XhtmlElement('span', 'How to add your photos:');
			$o_title_inner2 = new XhtmlElement('span', $o_title_inner3);
			$o_title_inner1 = new XhtmlElement('span', $o_title_inner2);
			$o_title = new XhtmlElement('h2', $o_title_inner1);
			$o_panel_inner2->AddControl($o_title);

			$o_tab_tip = new XhtmlElement('ul');
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Photos must be under 2MB each. You may need to shrink your photos first'));
			$o_panel_inner2->AddControl($o_tab_tip);
			echo $o_panel;
?>
	<p class="aural">If the JavaScript or Flash on this page are difficult for you, <a href="<?php echo Html::Encode($this->gallery->GetAddImagesNavigateUrl(false)) ?>">add a photo</a> without them.</p>
	<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" id="swfupload">
	<div>
	<input type="hidden" id="album" name="album" value="<?php echo Html::Encode($this->gallery_id); ?>" />
	<input type="hidden" id="viewas" name="viewas" value="<?php echo Html::Encode(session_id()); ?>" />
	</div>
	</form>

	<div id="degraded_container">
		<p>You need to add photos one-at-a-time. To add several photos quickly, turn on JavaScript and get Flash Player 9 or later.</p>
		<p><a href="<?php echo Html::Encode($this->gallery->GetAddImagesNavigateUrl(false)) ?>">Add a photo</a></p>
	</div>
	<?php
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