<?php
# ensure WordPress ABSPATH is defined before it's used. Enabled other pages to
# include this to show a 404 without using WordPress.
if (defined('ABSPATH'))
{
	ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');
}

require_once ('page/stoolball-page.class.php');

class ErrorPage extends StoolballPage
{
	function OnPageInit()
	{
		$requested_url = strtolower($_SERVER['REQUEST_URI']);
		if (strlen($requested_url) >= 13 and substr($requested_url, 0, 13) == "/runningaclub")
		{
			header("Location: /manage" . substr($requested_url, 13));
			exit();
		}

		if (!headers_sent())
		{
			header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
		}
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Page not found');
	}

	function OnPageLoad()
	{
		require ('section-404.php');
	}

}

new ErrorPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>