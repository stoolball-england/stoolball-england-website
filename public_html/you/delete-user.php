<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The user being deleted
	 *
	 * @var User
	 */
	private $user_to_delete;

	private $b_deleted = false;

	public function OnPostback()
	{
		# Get the user info and store it
		if (isset($_GET['item']) and is_numeric($_GET['item'])) {
			$this->GetAuthenticationManager()->ReadUserById(array($_GET['item']));
			$this->user_to_delete = $this->GetAuthenticationManager()->GetFirst();
		}
		if (!$this->user_to_delete instanceof User)
		{
			# This can be the case if the back button is used to go back to the "user has been deleted" page.
			$this->b_deleted = true;
			return;
		}
        
		# Check whether cancel was clicked
		if (isset($_POST['cancel']))
		{
			$this->Redirect("/users");
		}

		# Check whether delete was clicked
		if (isset($_POST['delete']))
		{
			# Check again that the requester has permission to delete this user
			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_USERS_AND_PERMISSIONS));

			if ($has_permission)
			{
				# Delete the user
				$this->GetAuthenticationManager()->DeleteUsers(array($this->user_to_delete->GetId()));

				# Note success
				$this->b_deleted = true;
			}
		}
	}

	public function OnLoadPageData()
	{
		# get user
		if (!is_object($this->user_to_delete))
		{
			if (isset($_GET['item']) and is_numeric($_GET['item']))
			{
				$this->GetAuthenticationManager()->ReadUserById(array($_GET['item']));
				$this->user_to_delete = $this->GetAuthenticationManager()->GetFirst();
			}
		}
	}

	public function OnPrePageLoad()
	{
		if ($this->user_to_delete instanceof User)
		{
			$this->SetPageTitle('Delete user account: ' . $this->user_to_delete->GetName());
			$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		}
		else
		{
			$this->SetPageTitle('User account already deleted');
		}
	}

	function OnPageLoad()
	{
		if (!$this->user_to_delete instanceof User)
		{
			?>
			<h1>User account already deleted</h1>
			<p>The user account you're trying to delete does not exist or has already been deleted.</p>
			<p><a href="/users">Manage users</a></p>
			<?php
			return ;
		}
        
        echo new XhtmlElement('h1', 'Delete user account: <cite>' . Html::Encode($this->user_to_delete->GetName()) . '</cite>');

		if ($this->b_deleted)
		{
			?>
			<p><?php echo Html::Encode($this->user_to_delete->GetName()); ?>'s account has been deleted.</p>
			<p><a href="/users">Manage users</a></p>
			<?php
		}
		else
		{
			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_USERS_AND_PERMISSIONS));
			if ($has_permission)
			{
				if (is_null($this->user_to_delete->GetLastSignInDate())) {
					$s_detail = "This user has never signed in.";
				}
				else {
					$s_detail = "This user last signed in at " . Date::BritishDateAndTime($this->user_to_delete->GetLastSignInDate()) . ".";
				}

				echo new XhtmlElement('p', Html::Encode($s_detail));

				?>
<p>Deleting a user account cannot be undone.</p>
<ul>
<li>Email subscriptions will be cancelled</li>
<li>Their name will be removed from match comments</li>
<li>They will no longer be able to edit matches they have added</li>
<li>Their player record will <strong>not</strong> be affected</li>
<li>Contact details for teams or competitions will <strong>not</strong> be removed</li>
</ul>

<p>Are you sure you want to delete this user account?</p>
<form action="<?php echo Html::Encode($this->user_to_delete->GetDeleteUrl()) ?>" method="post"
	class="deleteButtons">
<div><input type="submit" value="Delete user account" name="delete" /> <input
	type="submit" value="Cancel" name="cancel" /></div>
</form>
				<?php

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), $this->user_to_delete->GetName());
				$panel->AddLink("edit this user", $this->user_to_delete->GetEditUrl());
				echo $panel;
			}
			else
			{
				?>
<p>Sorry, you don't have permission to delete a user account.</p>
				<?php
			}
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_USERS_AND_PERMISSIONS, false);
?>