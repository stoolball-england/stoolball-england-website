<?php
require_once('data/content-type.enum.php');
require_once('forum-topic.class.php');
require_once('xhtml/forms/xhtml-form.class.php');

class ForumMessageForm extends XhtmlForm
{
	function GetForm()
	{
		$s_text = '<form action="' . $_SERVER['REQUEST_URI'] . '#last-message" method="post" id="forumMessageForm">' . "\n";

		$s_text .= '<h2><label for="message">Add your comment</label></h2>' . "\n" .
		'<textarea name="message" id="message"></textarea><p class="radioButtonList">' .
		'<label for="subscribe"><input type="checkbox"  name="subscribe" id="subscribe" value="1"';
		if (($_SERVER['REQUEST_METHOD'] != 'POST') or (isset($_POST['subscribe']) and $_POST['subscribe'])) $s_text .= ' checked="checked"';
		$s_text .= ' /> Send an email alert whenever anyone adds a comment</label></p>' . "\n" .
		'<input type="submit" class="submit" value="Post comment" name="action" />' . "\n" .
		'</form>';

		return $s_text;
	}
}
?>