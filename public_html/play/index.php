<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    function OnPrePageLoad()
	{
	    $this->SetPageTitle('Play stoolball');
    }

	function OnPageLoad()
	{
?>
<h1>Play stoolball</h1>

<p class="tiles-intro">Find out where you can play stoolball, track results and statistics, and get advice about running your own team.</p>

<nav><ul class="nav tiles">
<li><a href="/competitions" class="competitions-tile"><div><h2>Leagues and competitions</h2></div></a></li>
<li><a href="/teams/all" class="teams-tile"><div><h2>Teams</h2></div></a></li>
<li><a href="/matches" class="tournaments-tile"><div><h2>Matches and tournaments</h2></div></a></li>
<li><a href="/play/statistics-and-photos/" class="statistics-tile"><div><h2>Statistics and photos</h2></div></a></li>
<li><a href="/play/schools/" class="schools-tile"><div><h2>Schools</h2></div></a></li>
<li><a href="/play/coaching/" class="coaching-tile"><div><h2>Coaching</h2></div></a></li>
<li><a href="/play/equipment/" class="equipment-tile"><div><h2>Equipment and gifts</h2></div></a></li>
<li><a href="/play/manage/" class="manage-tile"><div><h2>Manage your team</h2></div></a></li>
</ul></nav>
			<?php
			$this->ShowSocialAccounts();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>