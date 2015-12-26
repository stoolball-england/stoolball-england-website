<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('page/stoolball-page.class.php');
require_once ('authentication/role-edit-control.class.php');

class CurrentPage extends StoolballPage
{
	private $editor;

	/**
	 * The role to edit
	 *
	 * @var Role
	 */
	private $role;
	private $authentication_manager;

	function OnPageInit()
	{
		$this->authentication_manager = $this->GetAuthenticationManager();

		# New edit control
		$this->editor = new RoleEditControl($this->GetSettings());
		$this->RegisterControlForValidation($this->editor);

		# run template method
		parent::OnPageInit();
	}

	function OnPostback()
	{
		# get object
		$this->role = $this->editor->GetDataObject();

		# save data if valid
		if ($this->IsValid())
		{
			$this->authentication_manager->SaveRole($this->role);
			$this->Redirect("roles.php");
		}
	}

	function OnLoadPageData()
	{
		# get id of role
		$id = $this->authentication_manager->GetItemId();

		# no need to read data if creating a new role
		if ($id and !isset($_POST['item']))
		{
			# get role
			$this->role = $this->authentication_manager->ReadRoleById($id);
		}
	}

	function OnPrePageLoad()
	{
		if (is_object($this->role))
		{
            $this->SetPageTitle($this->role->getRoleName() . ': Edit role');
		}
		else
		{
			$this->SetPageTitle('New role');
		}

	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

		if (!is_object($this->role))
		{
			$this->role = new Role();
		}
		$this->editor->SetDataObject($this->role);
		echo $this->editor;

		parent::OnPageLoad();
	}

}

new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_USERS_AND_PERMISSIONS, false);
?>