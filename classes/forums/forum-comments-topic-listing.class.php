<?php
require_once('forum-topic-listing.class.php');
require_once('forum-comments-topic-navbar.class.php');
require_once('forum-message-format.enum.php');

class ForumCommentsTopicListing extends ForumTopicListing
{
	var $s_intro;

	function ForumCommentsTopicListing(SiteSettings $o_settings, SiteContext $o_context, User $o_user, ForumTopic $o_topic)
	{
		parent::ForumTopicListing($o_settings, $o_context, $o_user, $o_topic);

		$this->SetNavbar(new ForumCommentsTopicNavbar($o_context, $o_topic, null));
	}

	function SetIntro($s_text)
	{
		$this->s_intro = (string)$s_text;
	}

	function GetIntro() { return $this->s_intro; }

	function GetHeader()
	{
		# overrides standard header

		$s_text = '<div id="comments-topic">' . "\n" .
			'<h2>Add your comments</h2>';

		if ($this->s_intro)
		{
			$s_text .= '<p>' . $this->s_intro . '</p>';
		}

		return $s_text;
	}

	function GetFooter()
	{
		return '</div>';
	}
}
?>