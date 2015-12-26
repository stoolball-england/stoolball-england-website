<?php
require_once('page/stoolball-page.class.php');
require_once('forums/subscription-manager.class.php');
require_once('forums/topic-manager.class.php');
require_once('forums/forum.class.php');
require_once('data/paged-results.class.php');

class ForumPage extends StoolballPage
{
	private $o_newest_user;
	private $a_topics;
	private $a_stats;

	function OnLoadPageData()
	{
		# get newest user
		$authentication_manager = $this->GetAuthenticationManager();
		$this->o_newest_user = $authentication_manager->ReadNewestUser();

		# get topics in the forum
		$topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
		$topic_manager->ReadTopicsByCategory($this->GetContext()->GetCurrent()->GetId());
		$this->a_topics = $topic_manager->GetItems();

		# get stats for all forums; TODO: ought to be just child forums
		$this->a_stats = $topic_manager->ReadTotalMessages();
		unset($topic_manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		$o_current = $this->GetContext()->GetCurrent();
		$this->SetPageTitle($o_current->GetName() . " - " . $this->GetSettings()->GetSiteName());
	}

	function OnPageLoad()
	{
	    $forum = Forum::CreateFromCategory($this->GetSettings(), $this->GetContext()->GetCurrent());
        /* @Var $forum Forum */
    
    	echo '<h1>' . Html::Encode($this->GetContext()->GetCurrent()->GetName()) . '</h1>' . 
             '<div typeof="sioctypes:MessageBoard" about="' . Html::Encode($forum->ForumLinkedDataUri()) . '" property="sioc:num_threads" content="' . count($this->a_topics) . '" datatype="xsd:nonNegativeInteger">';

		# welcome message for newest user
		if ($this->o_newest_user instanceof User)
		{
			# build link to user profile
			$s_name = $this->o_newest_user->GetName();
			if ($s_name) echo new XhtmlElement('p', 'Please welcome our newest member, <a href="' . Html::Encode($this->o_newest_user->GetUserProfileUrl()) . '">' . Html::Encode($s_name) .'</a>.');
		}

        $s_text = XhtmlMarkup::ApplyCharacterEntities($this->GetContext()->GetCurrent()->GetDescription());
        $s_text = XhtmlMarkup::ApplyParagraphs($s_text);
        $s_text = XhtmlMarkup::ApplyLinks($s_text);
        $s_text = XhtmlMarkup::ApplyLists($s_text);
        $s_text = XhtmlMarkup::ApplyImages($s_text, $this->GetSettings()->GetFolder('Images'), $this->GetSettings()->GetFolder('ImagesServer'));
        $s_text = XhtmlMarkup::ApplySimpleTags($s_text);
        echo $s_text;

		?>
<p>
	Click on 'Subscribe to this forum' and we'll email you whenever someone posts a message.</p>
	<?php

	$s_navbar = $this->GetForumNavbarXhtml($forum);
	echo $s_navbar;
	echo $this->GetTopicListXhtml($forum);
	echo $s_navbar;
    echo "</div>";
	}

	function GetForumNavbarXhtml(Forum $o_forum)
	{
		/* @var $o_forum Forum */

		$s_page = urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
		$o_current = $this->GetContext()->GetCurrent();
		$o_cat = $this->GetContext()->GetByHierarchyLevel(2);
		$s_title = urlencode($o_current->GetName() . ' forum');
		$s_subscribe_link = $o_cat->GetNavigateUrl() . 'subscribe.php?type=' . ContentType::FORUM . '&amp;item=' . $o_current->GetId() . '&amp;title=' . $s_title .'&amp;page=' . $s_page;

		return '<div class="forumNavbar">' .
		$o_forum->GetNewTopicLinkXhtml() .
		'<a href="' . $s_subscribe_link . '" title="Get an email alert every time a message is posted to this forum" class="forumSubscribe">Subscribe to this forum</a>' .
		"</div>";
	}

	function GetTopicListXhtml(Forum $forum)
	{
		$o_container = new PlaceHolder();

		if (is_array($this->a_topics) and count($this->a_topics))
		{
			# set up paging based on user's preference
			$o_paging = new PagedResults();
			$o_paging->SetPageSize(AuthenticationManager::GetUser()->GetPageSize('forumtopics'), 50);
			$o_paging->SetResultsTextSingular('topic');
			$o_paging->SetResultsTextPlural('topics');
			$o_paging->SetTotalResults(count($this->a_topics));

			# trim results not needed for this page
			$this->a_topics = $o_paging->TrimRows($this->a_topics);

			# write the paging navbar
			$s_paging_bar = $o_paging->GetNavigationBar();
			$o_container->AddControl($s_paging_bar);

			$s_text = '<table class="forumTopicList"><thead><tr><th class="large">Icon</th><th>Topic</th><th>Replies</th><th>Views</th><th>Latest</th></tr></thead><tbody>' . "\n";

			foreach($this->a_topics as $topic)
			{
				/* @var $topic ForumTopic */
				
				# get messages
				$o_first_message = $topic->GetFirst();
				$o_last_message = $topic->GetFinal();

				# get people from messages
				$o_last_message_person = $o_last_message->GetUser();

				# write topic
				if (is_object($o_first_message) and is_object($o_last_message) and is_object($o_last_message_person))
				{
					$s_icon = $o_first_message->GetIconXhtml($this->GetSettings());
					if (!$s_icon) $s_icon = '&nbsp;';

					$s_text .= '<tr typeof="sioc:Thread" about="' . Html::Encode($topic->TopicLinkedDataUri()) . '" rel="sioc:has_container" rev="sioc:container_of" resource="' . Html::Encode($forum->ForumLinkedDataUri()) . '">';
					$s_text .= '<td class="icon large">' . $s_icon . '</td>';
					$s_text .= '<td class="title"><a href="' . Html::Encode($topic->GetNavigateUrl()) . '" title="View all messages in this topic">' . $o_first_message->GetFormattedTitle() . '</a></td>' .
					'<td class="status">' . $topic->GetReplyCount() . '</td>' .
					'<td class="status" about="' . Html::Encode($topic->TopicLinkedDataUri()) . '" property="sioc:num_views" datatype="xsd:nonNegativeInteger">' . $topic->GetViews() . '</td>' .
					'<td class="detail">' . $o_last_message->GetImageLinkXhtml() . 'by <a href="' . Html::Encode($o_last_message_person->GetUserProfileUrl()) . '">' . Html::Encode($o_last_message_person->GetName()) . '</a><br />' . Date::BritishDateAndTime($o_last_message->GetDate(), false, true, true) . '</td>' .
					"</tr>\n";
				}
            }

			$o_container->AddControl($s_text . '</tbody></table>');

			# write the paging navbar
			$o_container->AddControl($s_paging_bar);
		}
		else
		{
			$o_container->AddControl('<hr /><p><strong>This is a new forum. Start a new topic and be the first to post a message here!</strong></p>' . "\n\n" . '<hr />');
		}

		return $o_container;
	}
}
?>