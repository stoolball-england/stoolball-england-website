<?php
require_once('data/related-item-editor.class.php');
require_once("data/id-value.class.php");

/**
 * Aggregated editor to manage permissions
 *
 */
class PermissionsEditor extends RelatedItemEditor
{
	/**
	 * The permissions which can be assigned
	 *
	 * @var IdValue[]
	 */
	private $permissions;

	/**
	 * Creates a PermissionsEditor
	 *
	 * @param SiteSettings $settings
	 * @param DataEditControl $controlling_editor
	 * @param string $s_id
	 * @param string $s_title
     */
	public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id, $s_title)
	{
		$this->SetDataObjectClass('IdValue');
		$this->SetDataObjectMethods('GetId', '', '');
		parent::__construct($settings, $controlling_editor, $s_id, $s_title, array("Permission", "Resource URI"));

		# initialise arrays
		$this->permissions = array(
            new IdValue(PermissionType::ViewPage(), ucfirst(PermissionType::Text(PermissionType::ViewPage()))),
            new IdValue(PermissionType::ForumAddTopic(), ucfirst(PermissionType::Text(PermissionType::ForumAddTopic()))),
            new IdValue(PermissionType::ForumAddMessage(), ucfirst(PermissionType::Text(PermissionType::ForumAddMessage()))),
            new IdValue(PermissionType::ForumSubscribe(), ucfirst(PermissionType::Text(PermissionType::ForumSubscribe()))),
            new IdValue(PermissionType::MANAGE_FORUMS, ucfirst(PermissionType::Text(PermissionType::MANAGE_FORUMS))),
            new IdValue(PermissionType::EditPersonalInfo(), ucfirst(PermissionType::Text(PermissionType::EditPersonalInfo()))),
            new IdValue(PermissionType::MANAGE_CATEGORIES, ucfirst(PermissionType::Text(PermissionType::MANAGE_CATEGORIES))),
            new IdValue(PermissionType::MANAGE_USERS_AND_PERMISSIONS, ucfirst(PermissionType::Text(PermissionType::MANAGE_USERS_AND_PERMISSIONS))),
            new IdValue(PermissionType::AddImage(), ucfirst(PermissionType::Text(PermissionType::AddImage()))),
            new IdValue(PermissionType::AddMediaGallery(), ucfirst(PermissionType::Text(PermissionType::AddMediaGallery()))),
            new IdValue(PermissionType::MANAGE_ALBUMS, ucfirst(PermissionType::Text(PermissionType::MANAGE_ALBUMS))),
            new IdValue(PermissionType::ApproveImage(), ucfirst(PermissionType::Text(PermissionType::ApproveImage()))),
            new IdValue(PermissionType::PageSubscribe(), ucfirst(PermissionType::Text(PermissionType::PageSubscribe()))),
            new IdValue(PermissionType::MANAGE_URLS, ucfirst(PermissionType::Text(PermissionType::MANAGE_URLS))),
            new IdValue(PermissionType::MANAGE_SEARCH, ucfirst(PermissionType::Text(PermissionType::MANAGE_SEARCH))),
            new IdValue(PermissionType::VIEW_ADMINISTRATION_PAGE, ucfirst(PermissionType::Text(PermissionType::VIEW_ADMINISTRATION_PAGE))),
            new IdValue(PermissionType::VIEW_WORDPRESS_LOGIN, ucfirst(PermissionType::Text(PermissionType::VIEW_WORDPRESS_LOGIN))),
            new IdValue(PermissionType::EXCLUDE_FROM_ANALYTICS, ucfirst(PermissionType::Text(PermissionType::EXCLUDE_FROM_ANALYTICS))),
            new IdValue(PermissionType::MANAGE_TEAMS, ucfirst(PermissionType::Text(PermissionType::MANAGE_TEAMS))),
            new IdValue(PermissionType::MANAGE_COMPETITIONS, ucfirst(PermissionType::Text(PermissionType::MANAGE_COMPETITIONS))),
            new IdValue(PermissionType::MANAGE_GROUNDS, ucfirst(PermissionType::Text(PermissionType::MANAGE_GROUNDS))),
            new IdValue(PermissionType::ADD_MATCH, ucfirst(PermissionType::Text(PermissionType::ADD_MATCH))),
            new IdValue(PermissionType::EDIT_MATCH, ucfirst(PermissionType::Text(PermissionType::EDIT_MATCH))),
            new IdValue(PermissionType::DELETE_MATCH, ucfirst(PermissionType::Text(PermissionType::DELETE_MATCH))),
            new IdValue(PermissionType::MANAGE_MATCHES, ucfirst(PermissionType::Text(PermissionType::MANAGE_MATCHES))),
            new IdValue(PermissionType::MANAGE_PLAYERS, ucfirst(PermissionType::Text(PermissionType::MANAGE_PLAYERS))),
            new IdValue(PermissionType::MANAGE_STATISTICS, ucfirst(PermissionType::Text(PermissionType::MANAGE_STATISTICS)))
        );
	}

	/**
	 * Re-build from data posted by this control a single data object which this control is editing
	 *
	 * @param int $i_counter
	 * @param int $i_id
	 */
	protected function BuildPostedItem($i_counter=null, $i_id=null)
	{
		$s_key = $this->GetNamingPrefix() . 'Permission' . $i_counter;
		$permission = null;
		if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$permission = $_POST[$s_key];
		}

		$s_key = $this->GetNamingPrefix() . 'Resource' . $i_counter;
		$resource = (isset($_POST[$s_key]) and strlen($_POST[$s_key]) <= 250) ? $_POST[$s_key] : '';
		
		if ($permission)
		{
			$o_adjust = new IdValue($permission, $resource);
			# $i_date = $this->BuildPostedItemModifiedDate($i_counter, $o_adjust);
			# $o_adjust->SetDate($i_date);
			$this->DataObjects()->Add($o_adjust);
		}
		else
		{
			$this->IgnorePostedItem($i_counter);
		}

	}


	/**
	 * Gets a hash of the specified permission
	 *
	 * @param IdValue $data_object
	 * @return string
	 */
	protected function GetDataObjectHash($data_object)
	{
		return $this->GenerateHash(array($data_object->GetId(), $data_object->GetValue()));
	}


	/**
	 * Create a table row to add or edit a permission
	 *
	 * @param object $data_object
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 */
	protected function AddItemToTable($data_object=null, $i_row_count=null, $i_total_rows=null)
	{		
		/* @var $data_object IdValue */
		$b_has_data_object = !is_null($data_object);
		
		// Which permission?
		$permission_select = new XhtmlSelect();
		foreach ($this->permissions as $permission) $permission_select->AddControl(new XhtmlOption($permission->GetValue(), $permission->GetId()));
		$permission_select = $this->ConfigureSelect($permission_select, $b_has_data_object ? $data_object->GetId() : '', 'Permission', $i_row_count, $i_total_rows, true); 

		// For a particular resource?
		$resource_box = $this->CreateTextBox($b_has_data_object ? $data_object->GetValue() : '', 'Resource', $i_row_count, $i_total_rows, true);
		$resource_box->SetMaxLength(250);

		# Add controls to table
		$this->AddRowToTable(array($permission_select, $resource_box));
	}


	/**
	 * Create validators to check a single permission
	 *
	 * @param string $s_item_suffix
	 */
	protected function CreateValidatorsForItem($s_item_suffix='')
	{
		require_once('data/validation/url-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');

		$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Permission' . $s_item_suffix, 'The permission identifier should be a number');
		$this->a_validators[] = new UrlValidator($this->GetNamingPrefix() . 'Resource' . $s_item_suffix, 'The resource must be a valid URI');
		$this->a_validators[] = new LengthValidator($this->GetNamingPrefix() . 'Resource' . $s_item_suffix, 'The resource URI should be 250 characters or fewer', 0, 250);
	}
}
?>