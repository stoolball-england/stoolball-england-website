<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    function OnPrePageLoad()
	{
	    $this->SetPageTitle('Stoolball matches and tournaments');
    }

	function OnPageLoad()
	{
?>
<h1>Stoolball matches and tournaments</h1>

<p class="tiles-intro">Find out where and when you can play or watch stoolball.</p>

<nav><ul class="nav tiles">
<li><a href="/matches/all" class="matches-tile"><div><h2>Match listings</h2></div></a></li>
<li><a href="/tournaments/all" class="tournaments-tile"><div><h2>Go to a tournament</h2></div></a></li>
<li><a href="/tournaments/add" class="host-tile"><div><h2>Host a tournament</h2></div></a></li>
</ul></nav>
			<?php
			$this->ShowSocialAccounts();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>