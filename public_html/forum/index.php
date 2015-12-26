<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# include required functions
require_once('forums/forum-page.class.php');

new ForumPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>