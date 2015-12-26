<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The object being deleted
	 *
	 * @var Role
	 */
	private $data_object;
	/**
	 * Data manager
	 *
	 * @var AuthenticationManager
	 */
	private $manager;

	private $deleted = false;
		
	function OnPageInit()
	{
		$this->manager = $this->GetAuthenticationManager();
		parent::OnPageInit();
	}

	function OnPostback()
	{
		# Get the item info and store it
		$id = $this->manager->GetItemId($this->data_object);
		$this->data_object = $this->manager->ReadRoleById($id);
		
		# Check whether cancel was clicked
		if (isset($_POST['cancel']))
		{
			$this->Redirect("roles.php");
		}

		# Check whether delete was clicked
		if (isset($_POST['delete']))
		{
            # Delete it
			$this->manager->DeleteRole($id);

			# Note success
			$this->deleted = true;
        }
	}

	function OnLoadPageData()
	{
		# get item to be deleted
		if (!is_object($this->data_object))
		{
			$id = $this->manager->GetItemId($this->data_object);
			$this->data_object = $this->manager->ReadRoleById($id);
		}
    }

	function OnPrePageLoad()
	{
        if (is_object($this->data_object))
        {
		  $this->SetPageTitle('Delete role: ' . $this->data_object->getRoleName());
        }
        else 
        {
            $this->SetPageTitle("Delete role");
        }
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
	    if (is_object($this->data_object))
        {
            echo new XhtmlElement('h1', Html::Encode('Delete role: ' . $this->data_object->getRoleName()));
        }
        else 
        {
            echo new XhtmlElement('h1', 'Delete role');
            $this->deleted = true;
        }

		if ($this->deleted)
		{
			?>
			<p>The role has been deleted.</p>
			<p><a href="roles.php">View all roles</a></p>
			<?php
		}
		else
		{
		      ?>
				<p>Deleting a role cannot be undone.</p>
				<p>Are you sure you want to delete this role?</p>
				<form method="post" class="deleteButtons">
				<div>
				<input type="submit" value="Delete role" name="delete" />
				<input type="submit" value="Cancel" name="cancel" />
				</div>
				</form>
				<?php

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), 'this role');

				$panel->AddLink('edit this role', "role.php?item=" . $this->data_object->getRoleId());

				echo $panel;
        }
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_USERS_AND_PERMISSIONS, false);
?>