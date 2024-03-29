<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPrePageLoad()
	{
		$this->SetPageTitle('Manage ' . $this->GetSettings()->GetSiteName());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

		$user = AuthenticationManager::GetUser();

		$list = '';
		if ($user->Permissions()->HasPermission(PermissionType::MANAGE_CATEGORIES)) $list .= '<li><a href="/yesnosorry/categorylist.php">Categories</a></li>';
		if ($user->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS)) {
		    $list .= '<li><a href="/play/clubs">Clubs and schools</a></li>';
            $list .= '<li><a href="/yesnosorry/contact-audit.php">Contact details audit</a></li>';
        }
		if ($user->Permissions()->HasPermission(PermissionType::MANAGE_GROUNDS)) $list .= '<li><a href="/yesnosorry/groundlist.php">Grounds</a></li>';
		if ($user->Permissions()->HasPermission(PermissionType::MANAGE_USERS_AND_PERMISSIONS))
        {
          $list .= '<li><a href="/users">Users</a></li>' . 
                '<li><a href="/yesnosorry/roles.php">Roles</a></li>';
        }
        if ($user->Permissions()->HasPermission(PermissionType::MANAGE_URLS)) $list  .= '<li><a href="regenerate-short-urls.php">Regenerate short URL cache</a></li>';
        if ($user->Permissions()->HasPermission(PermissionType::MANAGE_SEARCH)) $list  .= '<li><a href="/search/reindex.php">Reindex search</a></li>';
        if ($user->Permissions()->HasPermission(PermissionType::MANAGE_STATISTICS)) $list  .= '<li><a href="/play/statistics/recalculate-player-statistics.php">Recalculate player statistics</a></li>';
        
		if ($list) echo '<ul>' . $list . '</ul>';
	}
}

new CurrentPage(new StoolballSettings(), PermissionType::VIEW_ADMINISTRATION_PAGE, false);
?>