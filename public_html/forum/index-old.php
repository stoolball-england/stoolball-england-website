<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('page/stoolball-page.class.php');
require_once ('forums/forum.class.php');
require_once ('forums/topic-manager.class.php');
require_once ('markup/xhtml-markup.class.php');
require_once ('xhtml/xhtml-image.class.php');

# create page
class CurrentPage extends StoolballPage
{
	private $o_newest_user;
	private $a_forums;
	private $a_stats;

	function OnLoadPageData()
	{
		# get newest user
		$authentication_manager = $this->GetAuthenticationManager();
		$this->o_newest_user = $authentication_manager->ReadNewestUser();

		# get stats for all forums
		$o_topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
		$this->a_stats = $o_topic_manager->ReadTotalMessages();

		# get ids of forum categories
		$a_category_ids = $this->GetCategories()->GetDescendantIds($this->GetContext());

		# create forum obj for each category with recent messages
		foreach ($a_category_ids as $i_id)
		{
			# not the current category
			$o_category = $this->GetContext()->GetCurrent();
			if ($i_id != $o_category->GetId())
			{
				# create forum object
				$o_forum = new Forum($this->GetSettings());
				$o_forum->SetId($i_id);
				$o_topic_manager->ReadRecent(6, array($i_id));
				$o_forum->SetTopics($o_topic_manager->GetItems());
				$this->a_forums[] = $o_forum;
			}
		}
		unset($o_topic_manager);
	}

	function OnPrePageLoad()
	{
		$o_category = $this->GetContext()->GetCurrent();
		if ($o_category instanceof Category)
			$this->SetPageTitle($o_category->GetName());
	}

	function OnPageLoad()
	{
		# category intro
		$category = $this->GetContext()->GetCurrent();
		if ($category->GetName())
			echo '<h1>' . HTML::Encode($category->GetName()) . '</h1>' . "\n\n";

        # welcome message for newest user
        if ($this->o_newest_user instanceof User)
        {
            # build link to user profile
            $s_name = $this->o_newest_user->GetName();
            if ($s_name) echo new XhtmlElement('p', 'Please welcome our newest member, <a href="' . HTML::Encode($this->o_newest_user->GetUserProfileUrl()) . '">' . HTML::Encode($s_name) .'</a>.');
        }

		# send results to browser while next bit processes
		$this->Render();

		# get formatted result for each forum
		if (is_array($this->a_forums))
			foreach ($this->a_forums as $o_forum)
				echo $this->FormatForum($o_forum);
	}

	function FormatForum($o_forum)
	{
		# get the category as well
		$o_category = $this->GetCategories()->GetById($o_forum->GetId());

		# get stats
		$s_stats = 'Be the first to post here!';
		if (is_array($this->a_stats) and isset($this->a_stats[$o_forum->GetId()]))
		{
			$o_forum_stats = $this->a_stats[$o_forum->GetId()];
			if (is_object($o_forum_stats))
				$s_stats = $o_forum_stats->GetTotalMessages() . ' messages in ' . $o_forum_stats->GetTotalTopics() . ' topics';
			unset($o_forum_stats);
		}

		$s_page = urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
		$o_forum_category = $this->GetCategories()->GetById($o_forum->GetId());
		$o_cat = $this->GetContext()->GetByHierarchyLevel(2);
		$s_title = urlencode($o_forum_category->GetName() . ' forum');
		$s_subscribe_link = $o_cat->GetNavigateUrl() . 'subscribe.php?type=' . ContentType::FORUM . '&amp;item=' . $o_forum->GetId() . '&amp;title=' . $s_title . '&amp;page=' . $s_page;

		$s_category_link = $o_category->GetNavigateUrl();

		$s_text = '<div class="forumOverview"><div class="forumSummary">' . '<div class="forumLatest">' . '<h2><a href="' . HTML::Encode($s_category_link) . '">' . HTML::Encode($o_category->GetName()) . '</a></h2>' . 
		          '<div class="forumStats"><div><p>' . HTML::Encode($s_stats) . '</p></div></div>' . '<p>' . Html::Encode($o_category->GetDescription()) . '</p>';

		$a_topics = $o_forum->GetTopics();
		if (is_array($a_topics) and count($a_topics))
		{
			$s_text .= '<h3>Latest messages</h3><ul>';

			foreach ($a_topics as $o_topic)
			{
				$o_message = $o_topic->GetFinal();
				$o_person = $o_message->GetUser();
				$s_text .= '<li>' . $o_message->GetLinkXhtml() . ' by ' . Html::Encode($o_person->GetName()) . ', ' . Date::BritishDateAndTime($o_message->GetDate()) . '</li>';
			}

			$s_text .= '</ul>';
		}

		$s_text .= '</div>' . '</div>' . '<div class="forumAction">' . $o_forum->GetNewTopicLinkXhtml() . '<a href="' . $s_subscribe_link . '" title="Get an email alert when a message is posted to the ' . Html::Encode($o_category->GetName()) . ' forum">Subscribe to forum</a></div></div>';

		return $s_text;
	}

}

new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>