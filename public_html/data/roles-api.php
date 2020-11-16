<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');

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
				header("Access-Control-Allow-Headers: x-requested-with");
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
		# Read all roles to be migrated
		$auth = $this->GetAuthenticationManager();
		$firstRole = true;
		?>[<?php
		foreach ($auth->ReadRoles() as $role) {
			
			$role = $auth->ReadRoleById($role->getRoleId());
			
			if ($firstRole) {
				$firstRole = false;
			} else {
				?>,<?php
			}
	?>{"roleId":<?php echo $role->getRoleId();?>,"name":"<?php echo $role->getRoleName() ?>","permissions":[<?php
		$firstPermission = true;
        $permissions = $role->Permissions()->ToArray();
        foreach ($permissions as $permission => $scopes) {
			if ($firstPermission) {
				$firstPermission = false;
			} else {
				?>,<?php
			}
			?>{"permissionId":<?php echo $permission ?>,"scopes":[<?php 
				$firstScope = true;

				foreach ($scopes as $scope => $ignore_value) {
					if ($firstScope) {
						$firstScope = false;
					} else {
						?>,<?php
					}
					?>"<?php echo $scope; ?>"<?php
				}
			?>]}<?php
		}
		?>]}<?php
		}
		?>]<?php

		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>