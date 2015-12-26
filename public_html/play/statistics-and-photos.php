<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    function OnPrePageLoad()
	{
	    $this->SetPageTitle('Stoolball statistics and photos');
    }

	function OnPageLoad()
	{
?>
<h1>Stoolball statistics and photos</h1>

<nav><ul class="nav tiles">
<li><a href="/play/statistics/" class="statistics-tile"><div><h2>Statistics</h2></div></a></li>
<li><a href="http://www.facebook.com/stoolball/photos_albums" class="facebook-tile"><div><h2>Photos on Facebook</h2></div></a></li>
</ul></nav>
			<?php
			$this->ShowSocialAccounts();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>