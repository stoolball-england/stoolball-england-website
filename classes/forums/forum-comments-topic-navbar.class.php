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
    private $o_topic;
    
    /**
     * @var AuthenticationManager
     */
    private $authentication_manager;

    public function __construct(ForumTopic $o_topic, AuthenticationManager $authentication_manager)
    {
        $this->o_topic = $o_topic;
        $this->authentication_manager = $authentication_manager;

        parent::XhtmlElement('div');
        $this->SetCssClass('forumNavbar');
    }

	function OnPreRender()
	{
		/* @var $o_top_level Category */
		$review_item = $this->o_topic->GetReviewItem();
		$s_suggested_title = urlencode(StringFormatter::PlainText(trim($review_item->GetTitle())));
		
		$s_page = urlencode($_SERVER['REQUEST_URI']);
		$s_subscribe_link = '/play/subscribe.php?type=' . $review_item->GetType() . '&amp;item=' . $review_item->GetId() . '&amp;title=' . $s_suggested_title .'&amp;page=' . $s_page;
		$s_subscribe_title = 'Get an email alert every time there are new comments on this page';
		
		$this->AddControl('<div class="forumSubscribe"><a href="' . $s_subscribe_link . '" title="' . $s_subscribe_title . '">Subscribe to comments</a></div>');

        if (!$this->authentication_manager->GetUser()->Permissions()->HasPermission(PermissionType::ForumAddMessage()))
        {   
            $add = $this->o_topic->GetCount() ? 'Add your comments' : 'Be the first to add your comments!';
            $this->AddControl('<div class="forumPost"><a href="' . Html::Encode($this->authentication_manager->GetPermissionUrl()) . urlencode('#forumMessageForm') . '">' . $add . '</a></div>');

        } 
	}
}
?>