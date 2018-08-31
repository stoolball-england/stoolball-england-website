<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('category/category-manager.class.php');
require_once('category/category-edit-control.class.php');

class CurrentPage extends StoolballPage
{
	private $o_edit;
	private $o_category;

	/**
	 * Data manager for the category being edited
	 *
	 * @var CategoryManager
	 */
	private $o_category_manager;

	function OnPageInit()
	{
		# new data manager
		$this->o_category_manager = new CategoryManager($this->GetSettings(), $this->GetDataConnection());

		# New edit control
		$this->o_edit = new CategoryEditControl($this->GetSettings(), $this->GetCategories());
		$this->RegisterControlForValidation($this->o_edit);

		# run template method
		parent::OnPageInit();
	}

	function OnPostback()
	{
		# get object
		$this->o_category = $this->o_edit->GetDataObject();

		# save data if valid
		if($this->IsValid())
		{
			$i_id = $this->o_category_manager->Save($this->o_category);
			$this->o_category->SetId($i_id);

			# Update category cache with changes
			$this->o_category_manager->UpdateHierarchyData($this, 'GetCategoryNavigateUrl');
			$this->SetCategories($this->GetAllCategories());
			$this->OnCreateSiteContext();
			
			$this->Redirect("/yesnosorry/categorylist.php");
		}
	}

	function OnLoadPageData()
	{
		# get id of category
		$i_id = $this->o_edit->GetDataObjectId();

		# no need to read data if creating a new category
		if ($i_id and !isset($_POST['item']))
		{
			# get category
			$this->o_category_manager->ReadById(array($i_id));
			$this->o_category = $this->o_category_manager->GetFirst();
		}

		# tidy up
		unset($this->o_category_manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle(is_object($this->o_category) ? $this->o_category->GetName() . ': Edit category' : 'New category');
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

		# display the category
		$o_category = (is_object($this->o_category)) ? $this->o_category : new Category();
		$this->o_edit->SetDataObject($o_category);
		echo $this->o_edit;

		if ($this->o_category instanceof Category) 
		{
			$this->AddSeparator();
	
			require_once("stoolball/user-edit-panel.class.php");
			$panel = new UserEditPanel($this->GetSettings(), "this category");
			$panel->AddLink("delete this category", $this->o_category->GetDeleteCategoryUrl());
			echo $panel;
		}
	}

	/**
	* @return string
	* @param Category[] $a_stack
	* @param int $i_index
	* @desc Generate a category URL from a category and its parents
	*/
	public function GetCategoryNavigateUrl($a_stack, $i_index)
	{
		switch ($i_index)
		{
			case 1:
				$s_url = $this->GetSettings()->GetClientRoot();
				break;

			case 2:
				$s_url = $this->GetSettings()->GetClientRoot() . $a_stack[1]->GetUrl() . '/';
				break;

			case 3:
				$s_url = $this->GetSettings()->GetClientRoot() . $a_stack[1]->GetUrl() . '/' . $a_stack[2]->GetUrl() . '/';
				break;
			default:
				$s_url = $_SERVER['PHP_SELF'];
		}

		return $s_url;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_CATEGORIES, false);
?>