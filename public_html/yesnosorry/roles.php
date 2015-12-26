<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	private $roles;

	function OnLoadPageData()
	{
		$authentication = $this->GetAuthenticationManager();
	   $this->roles = $authentication->ReadRoles();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Manage roles');
        $this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{

?>
<h1>Manage roles</h1>
<table>
    <thead><tr><th>Role</th><th>Action</th></tr></thead>
    <tbody>
	<?php

	foreach ($this->roles as $role)
	{
		?>
		<tr>
		    <td><a href="role.php?item=<?php echo Html::Encode($role->getRoleId())?>"><?php echo Html::Encode($role->getRoleName())?></a></td>
		    <td><a href="roledelete.php?item=<?php echo Html::Encode($role->getRoleId())?>">Delete</a></td>
		</tr>
		<?php 
	}
	?>
	</tbody>
</table>
<?php
$this->AddSeparator();

require_once ('stoolball/user-edit-panel.class.php');
$panel = new UserEditPanel($this->GetSettings(), '');
$panel->AddLink('add a role', 'role.php');
echo $panel;

}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_USERS_AND_PERMISSIONS, false);
?>