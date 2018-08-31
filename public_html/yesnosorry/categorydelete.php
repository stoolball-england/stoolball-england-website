<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('category/category-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The object being deleted
	 *
	 * @var Category
	 */
	private $data_object;
	/**
	 * Data manager
	 *
	 * @var CategoryManager
	 */
	private $manager;

	private $deleted = false;
	private $has_permission = false;
	
	function OnPageInit()
	{
		$this->manager = new CategoryManager($this->GetSettings(), $this->GetDataConnection());
        $this->has_permission = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_CATEGORIES);
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
			$this->Redirect($this->data_object->GetEditCategoryUrl());
		}

		# Check whether delete was clicked
		if (isset($_POST['delete']))
		{
			# Check again that the requester has permission to delete this item
			if ($this->has_permission)
			{
				# Delete it
				$this->manager->Delete(array($this->data_object->GetId()));

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
		$this->SetPageTitle('Delete category: ' . $this->data_object->GetName());
		$this->SetContentConstraint(StoolballPage::ConstrainText());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode('Delete category: ' . $this->data_object->GetName()));

		if ($this->deleted)
		{
			?>
			<p>The category has been deleted.</p>
			<p><a href="/yesnosorry/categorylist.php">View all categories</a></p>
			<?php
		}
		else
		{
			if ($this->has_permission)
			{
				?>
				<p>Deleting a category cannot be undone.</p>
				<ul>
					<li>Its competitions will not be listed in a category</li>
				</ul> 
					
				<p>Are you sure you want to delete this category?</p>
				<form action="<?php echo Html::Encode($this->data_object->GetDeleteCategoryUrl()) ?>" method="post" class="deleteButtons">
				<div>
				<input type="submit" value="Delete category" name="delete" />
				<input type="submit" value="Cancel" name="cancel" />
				</div>
				</form>
				<?php
			}
			else
			{
				?>
				<p>Sorry, you're not allowed to delete this category.</p>
				<p><a href="/yesnosorry/categorylist.php">Go back to the list of categories</a></p>
				<?php
			}
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_CATEGORIES, false);
?>