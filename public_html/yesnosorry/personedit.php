<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('person/person-edit-control.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The user to edit
	 *
	 * @var User
	 */
	private $user;
	private $editor;

	function OnPageInit()
	{
		# New edit control
		$this->editor = new PersonEditControl($this->GetSettings());
		$this->RegisterControlForValidation($this->editor);

        if (!isset($_GET["item"]) or !is_numeric($_GET['item']))
        {
            http_response_code(400);
            exit;
        }

		parent::OnPageInit();
	}

	function OnPostback()
	{
		# get object
		$this->user = $this->editor->GetDataObject();

		# save data if valid
		if($this->IsValid())
		{
			$id = $this->GetAuthenticationManager()->SaveUser($this->user);
			$this->user->SetId($id);
            $this->GetAuthenticationManager()->SaveUserSecurity($this->user);
			$this->Redirect("personlist.php");
		}
	}

	function OnLoadPageData()
	{
		$authentication = $this->GetAuthenticationManager();

		# get id of person
		$id = $this->editor->GetDataObjectId();
		$authentication->ReadUserById(array($id));
		$this->user = $authentication->GetFirst();

        if (!($this->user instanceof User)) {
            http_response_code(404);
            exit();
        }
        
        $this->editor->SetRoles($authentication->ReadRoles());
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle($this->user->GetName() . ': Edit user');
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', $this->GetPageTitle());

		$this->editor->SetDataObject($this->user);
		echo $this->editor;

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_USERS_AND_PERMISSIONS, false);
?>