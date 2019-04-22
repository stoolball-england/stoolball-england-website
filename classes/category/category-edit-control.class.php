<?php
require_once('data/data-edit-control.class.php');

class CategoryEditControl extends DataEditControl
{
	var $o_categories;

	/**
	 * Creates a new CategoryEditControl
	 * @param SiteSettings $settings
	 * @param CategoryCollection $categories
	 * @param string $csrf_token
	 */
	public function __construct(SiteSettings $settings, CategoryCollection $categories, $csrf_token)
	{
		# check input and store
		$this->o_categories = $categories;

		# set up element
		$this->SetDataObjectClass('Category');
		parent::__construct($settings);
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data object this control is editing
	*/
	function BuildPostedDataObject()
	{
		/* @var $o_category Category */
		$o_category = new Category();

		if (isset($_POST['item'])) $o_category->SetId($_POST['item']);
		$o_category->SetName($_POST['displayName']);
		$o_category->SetParentId($_POST['parent_id']);
		$o_category->SetUrl($_POST['name']);
		$o_category->SetSortOverride($_POST['sort']);
		
		$this->SetDataObject($o_category);
	}

	function CreateControls()
	{
		require_once('xhtml/forms/xhtml-select.class.php');
		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');

		/* @var $o_category Category */
		$o_category = $this->GetDataObject();

		# add categories
		$o_parent_list = new XhtmlSelect('parent_id');
		$o_parent_list->SetBlankFirst(true);
		$a_categories = $this->o_categories->GetItems();
		foreach($a_categories as $o_parent)
		{
			if (is_object($o_parent))
			{
				$o_opt = new XhtmlOption($o_parent->GetName(), $o_parent->GetId());
				$o_opt->AddAttribute('style', 'padding-left:' . ($o_parent->GetHierarchyLevel()-1) . '0px');
				$o_parent_list->AddControl($o_opt);
				unset($o_opt);
			}
		}
		$o_parent_list->SelectOption($o_category->GetParentId());
		$o_parent_part = new FormPart('Create in', $o_parent_list);
		$this->AddControl($o_parent_part);

		# add name
		$o_url_box = new TextBox('name', $o_category->GetUrl());
		$o_url = new FormPart('Name', $o_url_box);
		$this->AddControl($o_url);

		# add display name
		$o_name_box = new TextBox('displayName', $o_category->GetName());
		$o_name_box->AddAttribute('maxlength', 255);
		$o_name = new FormPart('Display name', $o_name_box);
		$this->AddControl($o_name);

        # add sort override
		$o_sort_box = new TextBox('sort', $o_category->GetSortOverride());
		$o_sort = new FormPart('Sort order', $o_sort_box);
		$this->AddControl($o_sort);
    }

	/**
	* @return void
	* @desc Create DataValidator objects to validate the edit control
	*/
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/plain-text-validator.class.php');
		require_once('data/validation/url-segment-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');

		$this->a_validators[] = new NumericValidator('parent_id', 'The parent team identifier should be a number');
		$this->a_validators[] = new UrlSegmentValidator('name', 'Please use only lowercase A-Z and numbers 0-9 in the name');
		$this->a_validators[] = new RequiredFieldValidator('displayName', 'Please add the display name of the category');
		$this->a_validators[] = new PlainTextValidator('displayName', 'Please use only letters, numbers and simple punctuation in the display name');
		$this->a_validators[] = new LengthValidator('displayName', 'Please make the category name shorter', 0, 255);
		$this->a_validators[] = new PlainTextValidator('name', 'Please use only letters, numbers and simple punctuation in the description');
		$this->a_validators[] = new NumericValidator('sort', 'The sort order should be a number');
	}
}
?>