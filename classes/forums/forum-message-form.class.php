<?php
require_once('data/content-type.enum.php');
require_once('forum-topic.class.php');
require_once('forum-message-format.enum.php');
require_once('xhtml/forms/xhtml-form.class.php');

class ForumMessageForm extends XhtmlForm
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	var $o_settings;
	var $s_message_text = 'message';
	var $i_format;
	var $o_topic;

	function __construct(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;
	}

	function SetMessageText($s_input)
	{
		if (is_string($s_input)) $this->s_message_text = $s_input;
	}

	function GetMessageText()
	{
		return $this->s_message_text;
	}

	function GetFormat()
	{
		return $this->i_format;
	}

	function SetFormat($i_input)
	{
		if (is_numeric($i_input)) $this->i_format = (int)$i_input;
	}

	function SetTopic(ForumTopic $o_input)
	{
		$this->o_topic = $o_input;
	}

	function GetTopic()
	{
		return $this->o_topic;
	}

	function GetForm()
	{
		$o_review_item = $this->o_topic->GetReviewItem();

		$s_topic_name = 'comments';
		switch ($o_review_item->GetType())
		{
			case ContentType::STOOLBALL_MATCH:
				$s_post_pod_text = 'comments on this match';
		}
		$this->SetMessageText('comments');

		$s_text = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" id="forumMessageForm">' . "\n" .
		'<input type="hidden" name="format" id="format" value="' . $this->GetFormat() . '" />' . "\n" .
		'<input type="hidden" name="page" id="page" value="';
		if (isset($_POST['page']) and $_POST['page'])
		{
			$s_text .= $_POST['page'];
		}
		else if (isset($_GET['page']) and $_GET['page'])
		{
			$s_text .= $_GET['page'];
		}
		else if (isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_REFERER'])
		{
			$s_text .= htmlentities($_SERVER['HTTP_REFERER']);
		}
		$s_text .= '" />' . "\n";

		if ($o_review_item instanceof ReviewItem)
		{
			$s_text .= '<input type="hidden" name="type" id="type" value="' . $o_review_item->GetType() . '" />' . "\n" .
			'<input type="hidden" name="item" id="item" value="';
			if (isset($_GET['item']) and $_GET['item']) $s_text .= $_GET['item']; else if (isset($_POST['item']) and $_POST['item']) $s_text .= $_POST['item'];
			$s_text .= '" />' . "\n";
		}

		$s_text .= '<p><label for="title">Title of your ' . $s_topic_name;

		if($this->GetFormat() == ForumMessageFormat::ReviewReply())
		{
			$s_text .= ' <small>(optional)</small>';
		}

		$s_text .= '</label><input type="text" id="title" name="title" maxlength="200" value="';
		if (isset($_POST['title']) and $_POST['title']) $s_text .= stripslashes($_POST['title']);
		else if (isset($_GET['title']) and $_GET['title']) $s_text .= stripslashes($_GET['title']);
		$s_text .= '" /></p>' . "\n";

		$s_text .= '<label for="message" class="aural">Your ' . $this->GetMessageText() . '</label>' . "\n" .
		'<textarea name="message" id="message">';
		if (isset($_POST['message']))	$s_text .= stripslashes($_POST['message']);
		$s_text .= '</textarea><p class="radioButtonList">' .
		'<label for="subscribe"><input type="checkbox"  name="subscribe" id="subscribe" value="1"';
		if (($_SERVER['REQUEST_METHOD'] != 'POST') or (isset($_POST['subscribe']) and $_POST['subscribe'])) $s_text .= ' checked="checked"';
		$s_text .= ' /> Send an email alert whenever anyone ' . $s_post_pod_text . '</label></p>' . "\n" .
		'<input type="submit" class="submit" value="Post ' . $this->GetMessageText() . '" name="action" title="Click here to post your ' . $this->GetMessageText() . '" />' . "\n" .
		'</form>' . "\n\n" .
		'<script type="text/javascript">' . "\n" .
		"document.getElementById('title').focus();\n" .
		'</script>' . "\n\n";

		return $s_text;
	}
}
?>