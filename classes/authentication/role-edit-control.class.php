<?php
require_once ('data/data-edit-control.class.php');
require_once ('authentication/permissions-editor.class.php');
require_once("authentication/role.class.php");
require_once("data/id-value.class.php");

class RoleEditControl extends DataEditControl
{
	/**
	 * Aggregated editor for security permissions
	 *
	 * @var PermissionsEditor
	 */
	private $permissions_editor;

	/**
	 * Creates a new RoleEditControl
	 *
	 * @param SiteSettings $settings
	 * @param string $csrf_token
	 */
	public function __construct(SiteSettings $settings, $csrf_token)
	{
		$this->SetDataObjectClass('Role');
		parent::__construct($settings, $csrf_token);
		$this->permissions_editor = new PermissionsEditor($this->GetSettings(), $this, "permissions", "Permissions", $csrf_token);
    }
    
	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control
	 * is editing
	 */
	function BuildPostedDataObject()
	{
		$role = new Role();
        if (isset($_POST['item'])) 
        {
		  $role->setRoleId($_POST['item']);
        }
		$role->setRoleName($_POST['name']);

        $permissions = $this->permissions_editor->DataObjects()->GetItems();
        foreach ($permissions as $permission) 
        {
            $role->Permissions()->AddPermission($permission->GetId(), $permission->GetValue());
        }
		$this->SetDataObject($role);
	}

	function CreateControls()
	{
		require_once ('xhtml/forms/form-part.class.php');
		require_once ('xhtml/forms/textbox.class.php');

		$role = $this->GetDataObject();

		# add id
		$id_box = new TextBox('item', (string)$role->getRoleId());
		$id_box->SetMode(TextBoxMode::Hidden());
		$this->AddControl($id_box);

		# add name
		$name_box = new TextBox('name', $role->getRoleName(), $this->IsValidSubmit());
		$name_box->AddAttribute('maxlength', 100);
		$name = new FormPart('Role name', $name_box);
		$this->AddControl($name);

		# Permissions
		if (!$this->IsPostback() or $this->IsValidSubmit())
		{
		    $permissions = $role->Permissions()->ToArray();
			foreach ($permissions as $permission => $scopes)
			{
	           foreach ($scopes as $scope => $ignore) 
               {
                   if ($scope == PermissionType::GLOBAL_PERMISSION_SCOPE)
                   {
                       $scope = "";
                   } 
                   $this->permissions_editor->DataObjects()->Add(new IdValue($permission, $scope));       
               }
            }
		}
		$this->AddControl(new FormPart("Permissions", $this->permissions_editor));
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
		if (!$this->IsInternalPostback())
		{
			require_once ('data/validation/required-field-validator.class.php');
			require_once ('data/validation/plain-text-validator.class.php');
			require_once ('data/validation/length-validator.class.php');
			
			$this->a_validators[] = new RequiredFieldValidator('name', 'Please add a role name');
			$this->a_validators[] = new PlainTextValidator('name', 'Please use only letters, numbers and simple punctuation in the role name');
			$this->a_validators[] = new LengthValidator('name', 'Please make the role name shorter', 0, 100);
		}

		$a_aggregated_validators = array();
		$a_aggregated_validators = $this->permissions_editor->GetValidators();
		$this->a_validators = array_merge($this->a_validators, $a_aggregated_validators);
	}

}
?>