<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/image-manager.class.php');
require_once('media/image-details-control.class.php');
require_once('xhtml/forms/xhtml-form.class.php');
require_once('xhtml/forms/button.class.php');
require_once('xhtml/forms/textbox.class.php');

class CurrentPage extends StoolballPage
{
	private $image;

	/**
	 * Manager for saving images
	 *
	 * @var ImageManager
	 */
	private $manager;

	public function OnPageInit()
	{
		$this->manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
	}

	public function OnPostback()
	{
		if (isset($_POST['approve']) and isset($_POST['item_id']) and is_numeric($_POST['item_id']) and !$this->IsRefresh())
		{
			$this->manager->Approve(array($_POST['item_id']));
		}
		else if (isset($_POST['reject']) and isset($_POST['item_id']) and is_numeric($_POST['item_id']) and !$this->IsRefresh())
		{
			$this->manager->Delete(array($_POST['item_id']));
		}
	}

	function OnLoadPageData()
	{
		# new data manager
		$this->manager->ReadNextImageForApproval();
		$this->image = $this->manager->GetFirst();
		unset($this->manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		if ($this->image instanceof XhtmlImage)
		{
			$this->SetPageTitle('Check image: ' . $this->image->GetDescription());
		}
		else
		{
			$this->SetPageTitle('Check images');
		}

		$this->LoadClientScript('gallerycheck-2.js', true);
		?>
		<style>
.ImageDetailsControl .thumbnail { height: auto; }
.ImageDetailsControl .thumbPhoto, .ImageDetailsControl h2 { margin-top: 0; }
.ImageDetailsControl li { margin-left: 20px; }
.ImageDetailsControl .imageContext { overflow: hidden; margin: 1em 0; }

.actionButtons { padding: 10px; background: #eee url(/images/wicket-light-tile.gif) repeat left top; border: 1px solid #99f; max-width: 552px; margin-bottom: 10px; }
.actionButtons input{ margin-right: 1em; }
		</style>
		<?php
	}

	function OnPageLoad()
	{
		echo '<h1>' . Html::Encode($this->GetPageTitle()) . '</h1>';

		if ($this->image instanceof XhtmlImage)
		{
			$buttons = new XhtmlForm();
			$buttons->SetCssClass('actionButtons');
			$image_id = new TextBox('item_id', $this->image->GetId());
			$image_id->SetMode(TextBoxMode::Hidden());
			$buttons->AddControl($image_id);
			$buttons->AddControl(new Button('approve', 'Approve image'));
			$buttons->AddControl(new Button('reject', 'Reject image'));
			echo $buttons;

			# display the image
			echo new ImageDetailsControl($this->GetSettings(), $this->image);

		}
		else
		{
			echo '<p>There are no more images waiting to be checked.</p>';
		}

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ApproveImage(), false);
?>