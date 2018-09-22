<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	private $a_people;

	function OnLoadPageData()
	{
		# get people
		$authentication = $this->GetAuthenticationManager();
		$authentication->ReadUserById();
		$this->a_people = $authentication->GetItems();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Manage users');
	}

	function OnPageLoad()
	{
		?>
		<h1>Manage users</h1>
		<table>
		<thead><tr><th>User</th><th>Email</th><th>Action</th></tr></thead>
		<tbody>
		<?php

		foreach ($this->a_people as $user)
		{
			if ($user instanceof User)
			{
				$edit = new XhtmlElement('a', Html::Encode($user->GetName()));
				$edit->AddAttribute('href', $user->GetEditUrl());
				$delete = new XhtmlElement('a', 'Delete');
				$delete->AddAttribute('href', $user->GetDeleteUrl());
				echo "<tr><td>$edit</td><td>" . $user->GetEmail() . "</td><td>$delete</td>";
			}
		}

		?>
		</tbody></table>
		<?php
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_USERS_AND_PERMISSIONS, false);
?>