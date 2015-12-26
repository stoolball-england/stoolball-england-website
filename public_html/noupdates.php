<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPrePageLoad()
	{
		$this->SetPageTitle('Stoolball England - Upgrade in progress');
	}

	function OnPageLoad()
	{
		?>
		<h1>Upgrade in progress</h1>
		<p>We're busy updating the software this site runs on so that we can keep it running nice and fast. While we're doing that, some parts of the site have been turned off. Please try again later this evening.</p>
		<p>Thank you for your patience.</p>
		<?php
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>