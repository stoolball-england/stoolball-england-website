<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/team-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The object being deleted
	 *
	 * @var Team
	 */
	private $data_object;
	/**
	 * Data manager
	 *
	 * @var TeamManager
	 */
	private $manager;

	private $deleted = false;
	private $has_permission = false;
	
	function OnPageInit()
	{
		$this->manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $this->manager->FilterByTeamType(array());

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

                # Delete team from search engine
                $this->SearchIndexer()->DeleteFromIndexById("team" . $id);
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
			$id = $this->manager->GetItemId($this->data_object);
			$this->manager->ReadById(array($id));
			$this->data_object = $this->manager->GetFirst();
		}

		# tidy up
		unset($this->manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Delete team: ' . $this->data_object->GetName());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', 'Delete team: ' . htmlentities($this->data_object->GetName(), ENT_QUOTES, "UTF-8", false));

		if ($this->deleted)
		{
			?>
			<p>The team has been deleted.</p>
			<p><a href="/teams">View all teams</a></p>
			<?php
		}
		else
		{
			if ($this->has_permission)
			{
				?>
				<p>Deleting a team cannot be undone. All players will be deleted, and the team will be removed from existing matches.</p>
				<p>Are you sure you want to delete this team?</p>
				<form action="<?php echo htmlentities($this->data_object->GetDeleteTeamUrl(), ENT_QUOTES, "UTF-8", false) ?>" method="post" class="deleteButtons">
				<div>
				<input type="submit" value="Delete team" name="delete" />
				<input type="submit" value="Cancel" name="cancel" />
				</div>
				</form>
				<?php

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), 'this team');

				$panel->AddLink('view this team', $this->data_object->GetNavigateUrl());
				$panel->AddLink('edit this team', $this->data_object->GetEditTeamUrl());

				echo $panel;
			}
			else
			{
				?>
				<p>Sorry, you're not allowed to delete this team.</p>
				<p><a href="<?php echo htmlentities($this->data_object->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) ?>">Go back to the team</a></p>
				<?php
			}
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_TEAMS, false);
?>