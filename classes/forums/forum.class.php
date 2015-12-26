<?php 
require_once('category/category.class.php');
require_once('forum-topic.class.php');

class Forum extends Category
{
	var $o_settings;
	var $a_topics;

	function Forum(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;
	}

	/**
	 * Create a new forum object based on the supplied category
	 * @param SiteSettings $o_settings
	 * @param Category $o_category
	 * @return Forum
	 */
	public static function CreateFromCategory(SiteSettings $o_settings, Category $o_category)
	{
		$o_forum = new Forum($o_settings);
		$o_forum->SetId($o_category->GetId());
		return $o_forum;
	}

	function SetTopics($a_topics)
	{
		if (is_array($a_topics)) $this->a_topics = $a_topics;
	}

	function GetTopics()
	{
		return $this->a_topics;
	}

	function GetNewTopicLinkXhtml()
	{
		if ($this->GetId())
		{
			return '<a href="' . $this->o_settings->GetFolder('Forums') . 'new.php?forum=' . $this->GetId() . '" title="Start a new topic in this forum" class="forumPost">Start a new topic</a>';
		}
		else
		{
			die('No category specified for new topic link.');
			return false;
		}
	}
        
    /**
     * Gets the URI which uniquely identifies this forum
     */
    public function ForumLinkedDataUri()
    {
        return "http://" . $this->o_settings->GetDomain() . "/id/forum/" . $this->GetId();
    }
    
}
?>