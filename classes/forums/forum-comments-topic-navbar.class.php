<?php 
require_once('xhtml/xhtml-element.class.php');
require_once('context/site-settings.class.php');
require_once('forums/forum-topic.class.php');
require_once('text/string-formatter.class.php');

class ForumCommentsTopicNavbar extends XhtmlElement
{
    /**
     * Forum topic being viewed
     *
     * @var ForumTopic
     */
    var $o_topic;
    var $o_review_item;

    public function __construct(ForumTopic $o_topic, $o_review_item)
    {
        $this->o_topic = $o_topic;
        $this->o_review_item = $o_review_item;

        parent::XhtmlElement('div');
        $this->SetCssClass('forumNavbar');
    }

	function OnPreRender()
	{
		/* @var $o_top_level Category */
		$i_topic_id = $this->o_topic->GetId();
		$this->o_review_item = $this->o_topic->GetReviewItem();
		$s_suggested_title = urlencode(StringFormatter::PlainText(trim($this->o_review_item->GetTitle())));
		$i_item_id = $this->o_review_item->GetId();
		$i_item_type = $this->o_review_item->GetType();
		
		$s_page = urlencode($_SERVER['REQUEST_URI']);
		$s_subscribe_link = '/play/subscribe.php?type=' . $i_item_type . '&amp;item=' . $i_item_id . '&amp;title=' . $s_suggested_title .'&amp;page=' . $s_page;
		$s_subscribe_title = 'Get an email alert every time there are new comments on this page';
		$s_review_link = '/play/comment.php?type=' . $i_item_type . '&amp;item=' . $i_item_id . '&amp;title=' . $s_suggested_title .'&amp;page=' . $s_page;
		if (isset($_GET['cid'])) 
		{
			$s_subscribe_link .= '&amp;cid=' . $_GET['cid'];
			$s_review_link .= '&amp;cid=' . $_GET['cid'];
		}
		
		if ($i_item_id and is_integer(intval($i_item_id)))
		{
			if ($i_topic_id and is_integer(intval($i_topic_id))) # if there are already some messages
			{
				$this->AddControl('<a href="' . $s_review_link . '" class="forumPost">Add your comments</a>' .
					'<a href="' . $s_subscribe_link . '" title="' . $s_subscribe_title . '" class="forumSubscribe">Subscribe to this page</a>');
			}
			else # if this would be the first message
			{
				$this->SetCssClass('');
				$this->AddControl('<div class="forumPost"><a href="' . $s_review_link . '">Be the first to add your comments!</a></div>' .
					'<div class="forumSubscribe"><a href="' . $s_subscribe_link . '" title="' . $s_subscribe_title . '">Subscribe to this page</a></div>');
			}
		}
		else
		{
			throw new Exception('No item specified for review navbar.');
			return false;
		}
	}
}
?>