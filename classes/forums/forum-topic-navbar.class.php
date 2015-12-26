<?php
require_once('xhtml/xhtml-element.class.php');
require_once('context/site-settings.class.php');
require_once('forums/forum-topic.class.php');

class ForumTopicNavbar extends XhtmlElement
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	var $o_settings;
	/**
	 * The situation of the current request
	 *
	 * @var SiteContext
	 */
	var $o_context;
	/**
	 * Forum topic being viewed
	 *
	 * @var ForumTopic
	 */
	var $o_topic;
	var $o_review_item;

	function ForumTopicNavbar(SiteSettings $o_settings, SiteContext $o_context, ForumTopic $o_topic, $o_review_item)
	{
		$this->o_settings = $o_settings;
		$this->o_context = $o_context;
		$this->o_topic = $o_topic;
		$this->o_review_item = $o_review_item;

		parent::XhtmlElement('div');
		$this->SetCssClass('forumNavbar');
	}

	function OnPreRender()
	{
		$s_reply_link = $this->o_settings->GetFolder('Forums') . 'reply.php?topic=' . $this->o_topic->GetId();

		if (isset($this->o_review_item) and $this->o_review_item instanceof RatedItem)
		{
			$s_reply_link .= '&amp;type=' . $this->o_review_item->GetType() . '&amp;item=' . $this->o_review_item->GetId();
		}

		$o_reply_link = new XhtmlElement('a', 'Reply');
		$o_reply_link->AddAttribute('href', $s_reply_link);
		$o_reply_link->SetCssClass('forumPost');
		$this->AddControl($o_reply_link);

		$s_page = urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
		$s_title = urlencode($this->o_topic->GetTitle());
		$o_cat = $this->o_context->GetByHierarchyLevel(2);

		$o_sub_link = new XhtmlElement('a', 'Subscribe to this topic');
		$o_sub_link->AddAttribute('href', $o_cat->GetNavigateUrl() . 'subscribe.php?type=' . ContentType::TOPIC . '&amp;item=' . $this->o_topic->GetId() . '&amp;title=' . $s_title .'&amp;page=' . $s_page);
		$o_sub_link->AddAttribute('title', 'Get an email alert every time there is a reply to this topic');
		$o_sub_link->SetCssClass('forumSubscribe');
		$this->AddControl($o_sub_link);
	}
}
?>