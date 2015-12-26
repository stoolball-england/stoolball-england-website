<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('data/paged-results.class.php');
require_once('collection-builder.class.php');
require_once('ratings/rated-item.class.php');
require_once('forums/forum-topic-listing.class.php');
require_once('forums/topic-manager.class.php');
require_once('forums/subscription-manager.class.php');
require_once('forums/forum-topic-navbar.class.php');
require_once('stoolball/user-edit-panel.class.php');

class TopicPage extends StoolballPage
{
	private $i_topic_id;
	
    /**
     * @var ForumTopic
     */
	private $topic;
    private $is_admin;

	function OnSiteInit()
	{
		# ensure a topic is specified
		if (!isset($_GET['topic']) or !is_numeric($_GET['topic']) or !$_GET['topic'])
		{
			$this->Redirect();
		}
		else
		{
			$this->i_topic_id = intval($_GET['topic']);
		}

		# run template method
		parent::OnSiteInit();
	}

	function OnLoadPageData()
	{
		# get topic info
		$topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
		$topic_manager->ReadById(array($this->i_topic_id));

		$this->topic = $topic_manager->GetFirst();

		# get related item if this is a review
		$topic_manager->SetCategories($this->GetCategories());
        $reviewed = $topic_manager->ReadReviewedItem($this->i_topic_id);
		if ($reviewed)
        {
          $this->topic->SetReviewItem($reviewed);  
        } 

		# increment the count of views of this topic
		$topic_manager->IncrementViews($this->topic);
		unset($topic_manager);
	}

	function OnPrePageLoad()
	{
		/* @var $message ForumMessage */

		$message = $this->topic->GetFirst();
		if ($message instanceof ForumMessage)
		{
			$this->SetPageTitle($message->GetFilteredTitle() . ' - ' . $this->GetSettings()->GetSiteName() . ' forum');
			$this->SetPageDescription($message->GetExcerpt());
		}
        
        $this->is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_GROUNDS);
        if ($this->is_admin)
        {
            $this->SetContentConstraint(StoolballPage::ConstrainColumns());
        } 
	}

	function OnPageLoad()
	{
		# set the heading of the page
		$o_message = $this->topic->GetFirst();
		if ($o_message instanceof ForumMessage)
		{
			echo new XhtmlElement('h1', $o_message->GetFilteredTitle());
		}

		# add link of reviewed item, if there is one
		echo $this->FormatReviewItem();

		# build and display navbar
		$o_navbar = new ForumTopicNavbar($this->GetSettings(), $this->GetContext(), $this->topic, $this->topic->GetReviewItem());
		echo $o_navbar;

		$o_listing = new ForumTopicListing($this->GetSettings(), $this->GetContext(), AuthenticationManager::GetUser(), $this->topic);
		$o_listing->SetPageSize(30);
		echo $o_listing;

		# display navbar again at bottom
		echo $o_navbar;
        
        if ($this->is_admin) 
        {
            $this->AddSeparator();

            $panel = new UserEditPanel($this->GetSettings(), 'this topic');
            $panel->AddLink("delete this topic", $this->topic->GetDeleteTopicUrl());
            echo $panel;
        }
	}

	# a topic can have an item which it is reviewing - this writes a link to it
	function FormatReviewItem()
	{
		$s_text = '';

		if ($this->topic->GetReviewItem() instanceof RatedItem)
		{
			switch ($this->topic->GetReviewItem()->GetType())
			{
				default:
					$s_text = 'commenting on <a href="' . Html::Encode($this->topic->GetReviewItem()->GetNavigateUrl()) . '">' . Html::Encode($this->topic->GetReviewItem()->GetTitle()) . '</a>';
					break;
			}

			if ($s_text) $s_text = '<div class="forumRelatedItem">This topic is ' . $s_text . '</div>' . "\n\n";
		}

		return $s_text;
	}
}
new TopicPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
