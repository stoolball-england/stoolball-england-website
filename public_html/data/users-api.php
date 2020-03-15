<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('data/date.class.php');

class CurrentPage extends Page
{
	public function OnPageInit()
	{
		# Check that the request comes from an allowed origin
		$allowedOrigins = $this->GetSettings()->GetCorsAllowedOrigins();
		if (isset($_SERVER["HTTP_ORIGIN"]) && !in_array($_SERVER["HTTP_ORIGIN"], $allowedOrigins, true)) {
			exit();
		}

		# This is a JavaScript file
		if (!headers_sent()) {
			header("Content-Type: text/javascript; charset=utf-8");
			if (isset($_SERVER["HTTP_ORIGIN"])) {
				header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
			}
		}

		# Require an API key to include personal contact details to avoid spam bots picking them up
        $api_keys = $this->GetSettings()->GetApiKeys();
        $valid_key = false;
        if (!isset($_GET['key']) or !in_array($_GET['key'], $api_keys)) 
        {
            exit();
		}
	}

	public function OnLoadPageData()
	{
		# Read all users to be migrated
		$auth = $this->GetAuthenticationManager();
		$auth->FilterByActivated(true);
		$auth->FilterByDisabled(false);
		$auth->FilterByMinimumSignIns(1);
		$auth->ReadUserById();
		$firstUser = true;
		?>[<?php
		foreach ($auth as $user)
		{
			/* @var $user User */
			if ($firstUser) {
				$firstUser = false;
			} else {
				?>,<?php
			}
?>{"userId":<?php echo $user->GetId();?>,"email":"<?php 
	echo $user->GetEmail() ?>","name":"<?php
	echo $user->GetName() ?>","totalLogins":<?php
	echo $user->GetTotalSignIns() ?>,"lastLogin":"<?php
	echo Date::Microformat($user->GetLastSignInDate()) ?>","dateCreated":"<?php
	echo Date::Microformat($user->GetSignUpDate()) ?>","dateUpdated":"<?php
	echo Date::Microformat($user->GetDateChanged()) ?>","roles":[<?php
	$firstRole = true;
	foreach ($user->Roles() as $role) {
		if ($firstRole) {
			$firstRole = false;
		} else {
			?>,<?php
		}
		echo $role->GetRoleId();
	}
	?>]}<?php
		}
		?>]<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>