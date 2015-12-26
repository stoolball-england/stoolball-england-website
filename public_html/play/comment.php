<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# include required functions
require_once('forums/comment-page.class.php');

# create page
new CommentPage(new StoolballSettings(), PermissionType::ForumAddMessage(), false);
?>