<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('forums/topic-manager.class.php');
require_once('markup/xhtml-markup.class.php');

class CurrentPage extends StoolballPage
{
	
	/**
	 * Allow session writes beyond the usual point so that SignOutIfRequested() can be called
	 */
	protected function SessionWriteClosing()
	{
		return false;
	}

	function OnPageInit()
	{
	    if (!$this->GetAuthenticationManager()->SignOutIfRequested()) {
	       http_response_code(400);   
		}
		
		# Safe to end session writes now
		session_write_close();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle("Sign out");
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
	    ?>
	    <h1>Signed out</h1>
	    <p>You have now signed out of the Stoolball England website.</p>
	    <p><a href="/">Go back to the home page</a></p>
	    <?php
	    
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>