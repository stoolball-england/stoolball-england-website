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
		<ul>
		<?php

		foreach ($this->a_people as $o_person)
		{
			if ($o_person instanceof User)
			{
				$o_link = new XhtmlElement('a', Html::Encode($o_person->GetName()));
				$o_link->AddAttribute('href', 'personedit.php?item=' . $o_person->GetId());
				echo "<li>" . $o_link . "</li>";
			}
		}

		?>
		</ul>
		<?php
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_USERS_AND_PERMISSIONS, false);
?>