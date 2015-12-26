<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/image-manager.class.php');
require_once('media/media-gallery-manager.class.php');
require_once('media/media-gallery-edit-control.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/team-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The media gallery being edited
	 *
	 * @var MediaGallery
	 */
	private $o_data_object;
	/**
	 * Media galleries data manager
	 *
	 * @var MediaGalleryManager
	 */
	private $o_manager;

	/**
	 * Editor for media gallery
	 *
	 * @var MediaGalleryEditControl
	 */
	private $o_edit;
	
	private $user_is_admin = false;

	function OnPageInit()
	{
		# new data manager
		$this->o_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());

		# new edit control
		$this->o_edit = new MediaGalleryEditControl($this->GetSettings());
		$this->RegisterControlForValidation($this->o_edit);

		# Lock to a single related item, if appropriate
		if (isset($_GET['season']) and is_numeric($_GET['season']))
		{
			$season_id = (int)$_GET['season'];
			$this->o_edit->LockToSeason($season_id);
		}
		elseif (isset($_GET['team']) and is_numeric($_GET['team']))
		{
			$team_id = (int)$_GET['team'];
			$this->o_edit->LockToTeam($team_id);
		}

		$this->user_is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS);

		parent::OnPageInit();
	}

	function OnPostback()
	{
		# get object
		$this->o_data_object = $this->o_edit->GetDataObject();

		# save data if valid
		if($this->IsValid())
		{
			# Delete images if requested
			$delete_ids = array();
			$images = $this->o_data_object->Images()->GetItems();
			$total_images = count($images);
			for ($i = 0; $i < $total_images; $i++)
			{
				if ($images[$i]->GetIsForDeletion())
				{
					$delete_ids[] = $images[$i]->GetItem()->GetId();
					unset($images[$i]);
				}
			}
			$this->o_data_object->Images()->SetItems($images);

			if (count($delete_ids))
			{
				$image_manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
				$image_manager->DeleteFromGallery($delete_ids, $this->o_data_object->GetId());
				unset($image_manager);
			}

			# Save the gallery
			$go_to_add_photos = (!$this->o_data_object->GetId() and !$this->user_is_admin);
			$id = $this->o_manager->Save($this->o_data_object);
			$this->o_data_object->SetId($id);
            
            # Add the gallery to search results
            require_once("search/lucene-search.class.php");
            $search = new LuceneSearch();
            $search->DeleteDocumentById("photos" . $this->o_data_object->GetId());
            $search->IndexGallery($this->o_data_object);
            $search->CommitChanges();
            
			if ($go_to_add_photos)
			{
				$this->Redirect($this->o_data_object->GetAddImagesNavigateUrl());
			}
		}
	}

	function OnLoadPageData()
	{
		# get id of object
		$i_id = $this->o_manager->GetItemId($this->o_data_object);

		# no need to read data if creating a new object
		if ($i_id)
		{
			# get gallery
			$this->o_manager->ReadById(array($i_id), true);
			$this->o_data_object = $this->o_manager->GetFirst();

			# get images
			$this->o_manager->Clear();
			$this->o_manager->ReadImagesByGalleryId(array($i_id));
			$gallery = $this->o_manager->GetFirst();
			if (!is_null($gallery)) $this->o_data_object->Images()->SetItems($gallery->Images()->GetItems());

		}

		if ($this->user_is_admin)
		{
			# get competitions
			$o_comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
			$o_comp_manager->ReadAllSummaries();
			$a_competitions = $o_comp_manager->GetItems();
			unset($o_comp_manager);

			$a_seasons = array();
			if (is_array($a_competitions))
			{
				foreach ($a_competitions as $o_obj)
				{
					if ($o_obj instanceof Competition)
					{
						$a_seasons = array_merge($a_seasons, $o_obj->GetSeasons());
					}
				}
			}
			$this->o_edit->SetSeasons($a_seasons);

			# get teams
			$o_team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
			$o_team_manager->ReadAll();
			$this->o_edit->SetTeams($o_team_manager->GetItems());
			unset($o_team_manager);
		}

		# tidy up
		unset($this->o_manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle((is_object($this->o_data_object) and $this->o_data_object->GetId()) ? 'Edit album: ' . $this->o_data_object->GetTitle() : 'Create a photo album');
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		$is_new = !(is_object($this->o_data_object) and $this->o_data_object->GetId());
		if ($is_new)
		{
			echo new XhtmlElement('h1', 'Create a photo album');

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
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Create your album here, add your photos on the next page'));
			$o_tab_tip->AddControl(new XhtmlElement('li', 'Photos must be under 2MB each. You may need to shrink your photos first'));
			$o_panel_inner2->AddControl($o_tab_tip);
			echo $o_panel;

			# set up object to edit
			if (!is_object($this->o_data_object)) $this->o_data_object = new MediaGallery($this->GetSettings());
			$this->o_edit->SetDataObject($this->o_data_object);
			echo $this->o_edit;

		}
		else
		{
			echo new XhtmlElement('h1', 'Edit album: <cite>' . Html::Encode($this->o_data_object->GetTitle()) . '</cite>');

			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS) or AuthenticationManager::GetUser()->GetId() == $this->o_data_object->GetAddedBy()->GetId());
			if ($has_permission)
			{
				# set up object to edit
				$this->o_edit->SetDataObject($this->o_data_object);
				echo $this->o_edit;

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), 'this album');

				$panel->AddLink('view this album', $this->o_data_object->GetNavigateUrl());
				$panel->AddLink('add more photos', $this->o_data_object->GetAddImagesNavigateUrl());
				$panel->AddLink('delete this album', $this->o_data_object->GetDeleteNavigateUrl());

				echo $panel;
			}
			else
			{
				?>
				<p>Sorry, you can't edit someone else's photo album.</p>
				<p><a href="<?php echo Html::Encode($this->o_data_object->GetNavigateUrl()) ?>">Go back to album</a></p>
				<?php
			}
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::AddMediaGallery(), false);
?>