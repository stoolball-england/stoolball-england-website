<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class EditProfilePage extends StoolballPage
{
	function OnPrePageLoad()
	{
		$this->SetPageTitle('Edit profile for ' . AuthenticationManager::GetUser()->GetName());
	}

	function OnPageLoad()
	{
		echo '<h1>' . htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false) . '</h1>';

        echo '<div class="nav">' . 
        '<h2><a href="' . $this->GetSettings()->GetUrl('AccountEssential') . '">Essential information</a></h2>' . 
		'<p>Change your name, email address or password.</p>' . 
        '<h2><a href="' . $this->GetSettings()->GetUrl('EmailAlerts') . '">Email alerts</a></h2>' . 
		"<p>Change the email alerts you get when someone adds a comment.</p>" . 
		"</div>";
    }
}
new EditProfilePage(new StoolballSettings(), PermissionType::EditPersonalInfo(), false);
?>