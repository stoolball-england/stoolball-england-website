<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('authentication/personal-info-form.class.php');

class PersonalInfoPage extends StoolballPage
{
	private $o_editing_user;
	private $o_form;

	function OnLoadPageData()
	{
		# create obj to represent person being edited
		$this->o_editing_user = new User();

		# get details of person to edit
		$authentication_manager = $this->GetAuthenticationManager();
		$authentication_manager->ReadUserById(array(AuthenticationManager::GetUser()->GetId()));
		$this->o_editing_user = $authentication_manager->GetFirst();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('More about you, ' . AuthenticationManager::GetUser()->GetName());

		$this->LoadClientScript("/scripts/tiny_mce/jquery.tinymce.js");
		$this->LoadClientScript("/scripts/tinymce.js");

		# create form object
		$this->o_form = new PersonalInfoForm($this->o_editing_user);
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', $this->GetPageTitle());

		# display the form
		echo $this->o_form;
	}

	# process submitted data
	function OnPostback()
	{
		$user = AuthenticationManager::GetUser();
		if (isset($_POST['gender'])) $user->SetGender($_POST['gender']);
		$user->SetOccupation(trim($_POST['occupation']));
		$user->SetInterests(trim($_POST['interests']));
		$user->SetLocation(trim($_POST['location']));

		$authentication_manager = $this->GetAuthenticationManager();
		$authentication_manager->SavePersonalInfo($user);

		# redirect to edit profile home
		$this->Redirect($this->GetSettings()->GetUrl('AccountEdit'));
	}


}
new PersonalInfoPage(new StoolballSettings(), PermissionType::EditPersonalInfo(), false);
?>