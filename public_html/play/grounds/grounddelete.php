<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/ground-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The object being deleted
	 *
	 * @var Ground
	 */
	private $data_object;
	/**
	 * Data manager
	 *
	 * @var GroundManager
	 */
	private $manager;

	private $deleted = false;
	private $has_permission = false;
	
	function OnPageInit()
	{
		$this->manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());

		$this->has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_GROUNDS));
		if (!$this->has_permission) 
		{
			header("HTTP/1.1 401 Unauthorized");
		}
		parent::OnPageInit();
	}

	function OnPostback()
	{
		# Get the item info and store it
		if (isset($_GET['item']) and is_numeric($_GET['item'])) {
			$this->manager->ReadById(array($_GET['item']));
			$this->data_object = $this->manager->GetFirst();
		}

		# Check whether cancel was clicked
		if (isset($_POST['cancel']))
		{
			$this->Redirect($this->data_object->GetNavigateUrl());
		}

		# Check whether delete was clicked
		if (isset($_POST['delete']))
		{
			# Check again that the requester has permission to delete this item
			if ($this->has_permission)
			{
				# Delete it
				$this->manager->Delete(array($this->data_object->GetId()));

                # Remove the ground from search results
                $this->SearchIndexer()->DeleteFromIndexById("ground" . $this->data_object->GetId());
                $this->SearchIndexer()->CommitChanges();

				# Note success
				$this->deleted = true;
			}
		}
	}

	function OnLoadPageData()
	{
		# get item to be deleted
		if (!is_object($this->data_object))
		{
			if (isset($_GET['item']) and is_numeric($_GET['item'])) {
				$this->manager->ReadById(array($_GET['item']));
				$this->data_object = $this->manager->GetFirst();
			}
		}

		# tidy up
		unset($this->manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Delete ground: ' . $this->data_object->GetName());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode('Delete ground: ' . $this->data_object->GetName()));

		if ($this->deleted)
		{

?>
<p>
	The ground has been deleted.
</p>
<p>
	<a href="/yesnosorry/groundlist.php">View all grounds</a>
</p>
<?php
}
else
{
if ($this->has_permission)
{
?>
<p>
	Deleting a ground cannot be undone. The ground will be removed from existing matches.
</p>
<p>
	Are you sure you want to delete this ground?
</p>
<form action="<?php echo Html::Encode($this->data_object->GetDeleteGroundUrl()) ?>" method="post" class="deleteButtons">
	<div>
		<input type="submit" value="Delete ground" name="delete" />
		<input type="submit" value="Cancel" name="cancel" />
	</div>
</form>
<?php

$this->AddSeparator();
require_once ('stoolball/user-edit-panel.class.php');
$panel = new UserEditPanel($this->GetSettings(), 'this ground');

$panel->AddLink('view this ground', $this->data_object->GetNavigateUrl());
$panel->AddLink('edit this ground', $this->data_object->GetEditGroundUrl());

echo $panel;
}
else
{
?>
<p>
	Sorry, you're not allowed to delete this ground.
</p>
<p>
	<a href="<?php echo Html::Encode($this->data_object->GetNavigateUrl()) ?>">Go back to the ground</a>
</p>
<?php
}
}
}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_GROUNDS, false);
?>