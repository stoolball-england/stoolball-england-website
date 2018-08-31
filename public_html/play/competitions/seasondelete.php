<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/season-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The object being deleted
	 *
	 * @var Season
	 */
	private $data_object;
	/**
	 * Data manager
	 *
	 * @var SeasonManager
	 */
	private $manager;

	private $deleted = false;
	private $has_permission = false;
	private $seasons_in_competition;
		
	function OnPageInit()
	{
		$this->manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());

		$this->has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_COMPETITIONS));
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
			# Only offer delete option if there's more than one season. Don't want to delete last season because 
			# that leaves an empty competition which won't display. Instead, must delete whole competition with its one remaining season.

			# How many seasons in this competition?
			if (is_object($this->data_object) and $this->data_object->GetCompetition() instanceof Competition and $this->data_object->GetCompetition()->GetId())
			{
				$this->manager->Clear();
				$this->manager->ReadByCompetitionId(array($this->data_object->GetCompetition()->GetId()));
				$this->seasons_in_competition = $this->manager->GetCount();
			}
			
			if ($this->seasons_in_competition > 1)
			{
				# Check again that the requester has permission to delete this item
				if ($this->has_permission)
				{
					# Delete it
					$this->manager->Delete(array($this->data_object->GetId()));

                    # Update the competition in the search engine, because the latest season may have changed.
                    require_once('stoolball/competition-manager.class.php');
                    require_once("search/competition-search-adapter.class.php");
                    $this->SearchIndexer()->DeleteFromIndexById("competition" . $this->data_object->GetCompetition()->GetId());
                    $competition_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
                    $competition_manager->ReadById(array($this->data_object->GetCompetition()->GetId()));
                    $competition = $competition_manager->GetFirst();
                    $adapter = new CompetitionSearchAdapter($competition);
                    $this->SearchIndexer()->Index($adapter->GetSearchableItem());
                    $this->SearchIndexer()->CommitChanges();
	
					# Note success
					$this->deleted = true;
				}
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
			
			# How many seasons in this competition?
			if (is_object($this->data_object) and $this->data_object->GetCompetition() instanceof Competition and $this->data_object->GetCompetition()->GetId())
			{
				$this->manager->Clear();
				$this->manager->ReadByCompetitionId(array($this->data_object->GetCompetition()->GetId()));
				$this->seasons_in_competition = $this->manager->GetCount();
			}			
		}

		# tidy up
		unset($this->manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Delete season: ' . $this->data_object->GetCompetitionName());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode('Delete season: ' . $this->data_object->GetCompetitionName()));

		if ($this->deleted)
		{
			?>
			<p>The season has been deleted.</p>
			<p><a href="<?php echo Html::Encode($this->data_object->GetCompetition()->GetNavigateUrl());?>">View <?php echo Html::Encode($this->data_object->GetCompetition()->GetName());?></a></p>
			<?php
		}
		else
		{
			# Only offer delete option if there's more than one season. Don't want to delete last season because 
			# that leaves an empty competition which won't display. Instead, must delete whole competition with its one remaining season.
			if ($this->has_permission and $this->seasons_in_competition > 1)
			{
				?>
				<p>Deleting a season cannot be undone. Matches will not be deleted, but they will no longer be a part of this season.</p>
				<p>Are you sure you want to delete this season?</p>
				<form action="<?php echo Html::Encode($this->data_object->GetDeleteSeasonUrl()) ?>" method="post" class="deleteButtons">
				<div>
				<input type="submit" value="Delete season" name="delete" />
				<input type="submit" value="Cancel" name="cancel" />
				</div>
				</form>
				<?php

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), 'this season');

				$panel->AddLink('view this season', $this->data_object->GetNavigateUrl());
				$panel->AddLink('edit this season', $this->data_object->GetEditSeasonUrl());

				echo $panel;
			}
			else
			{
				?>
				<p>Sorry, you're not allowed to delete this season.</p>
				<p><a href="<?php echo Html::Encode($this->data_object->GetNavigateUrl()) ?>">Go back to the season</a></p>
				<?php
			}
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_COMPETITIONS, false);
?>