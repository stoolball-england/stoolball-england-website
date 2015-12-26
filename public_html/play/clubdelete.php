<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/club-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The object being deleted
	 *
	 * @var Club
	 */
	private $data_object;
	/**
	 * Data manager
	 *
	 * @var ClubManager
	 */
	private $manager;

	private $deleted = false;
	private $has_permission = false;
	
	function OnPageInit()
	{
		$this->manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());

		$this->has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS));
		if (!$this->has_permission) 
		{
			header("HTTP/1.1 401 Unauthorized");
		}
		parent::OnPageInit();
	}

	function OnPostback()
	{
		# Get the item info and store it
		$id = $this->manager->GetItemId($this->data_object);
		$this->manager->ReadById(array($id));
		$this->data_object = $this->manager->GetFirst();

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
				$this->manager->Delete(array($id));

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
			$id = $this->manager->GetItemId($this->data_object);
			$this->manager->ReadById(array($id));
			$this->data_object = $this->manager->GetFirst();
		}

		# tidy up
		unset($this->manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Delete club: ' . $this->data_object->GetName());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode('Delete club: ' . $this->data_object->GetName()));

		if ($this->deleted)
		{
			?>
			<p>The club has been deleted.</p>
			<p><a href="/yesnosorry/clublist.php">View all clubs</a></p>
			<?php
		}
		else
		{
			if ($this->has_permission)
			{
				?>
				<p>Deleting a club cannot be undone.</p>
				<p>Are you sure you want to delete this club?</p>
				<form action="<?php echo Html::Encode($this->data_object->GetDeleteClubUrl()) ?>" method="post" class="deleteButtons">
				<div>
				<input type="submit" value="Delete club" name="delete" />
				<input type="submit" value="Cancel" name="cancel" />
				</div>
				</form>
				<?php

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), 'this club');

				$panel->AddLink('view this club', $this->data_object->GetNavigateUrl());
				$panel->AddLink('edit this club', $this->data_object->GetEditClubUrl());

				echo $panel;
			}
			else
			{
				?>
				<p>Sorry, you're not allowed to delete this club.</p>
				<p><a href="<?php echo Html::Encode($this->data_object->GetNavigateUrl()) ?>">Go back to the club</a></p>
				<?php
			}
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_TEAMS, false);
?>