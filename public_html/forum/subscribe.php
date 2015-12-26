<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# include required functions
require_once('forums/subscribe-page.class.php');

# create page
new SubscribePage(new StoolballSettings(), PermissionType::PageSubscribe(), false);
?>