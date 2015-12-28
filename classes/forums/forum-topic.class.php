<?php 
require_once('collection.class.php');
require_once('forum-message.class.php');
require_once('review-item.class.php');

class ForumTopic extends Collection
{
	var $o_settings;
	var $i_topic_id;
	var $o_review_item;
	var $o_category;
	
	function ForumTopic(SiteSettings $o_settings, $a_items=null)
	{
		parent::Collection($a_items);
		$this->o_settings = $o_settings;
		$this->s_item_class = 'ForumMessage';
	}
	
	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database id of the topic
	*/
	function SetId($i_input)
	{
		if (is_numeric($i_input)) $this->i_topic_id = (int)$i_input;
	}
	
	function GetId()
	{
		return $this->i_topic_id;
	}

	# override base method to force use of Add() method
	function SetItems($a_input)
	{
		if (is_array($a_input)) 
		{
			foreach($a_input as $o_message) $this->Add($o_message);
		}
	}
	
	# override base method
	# always pass $o_input by value - passing by reference causes topic page to display multiple copies of one message
	function Add($o_input, $i_pos=null)
	{
		if ($o_input instanceof $this->s_item_class)
		{
			 # assign details of this topic
			if ($this->GetId()) $o_input->SetTopicId($this->GetId());

			if ($this->GetTitle()) $o_input->SetTopicTitle($this->GetTitle());
			else $o_input->SetTopicTitle($o_input->GetTitle());

			# add to array, optionally allowing index to be specified
			if ($i_pos != null and is_numeric($i_pos)) $this->a_items[$i_pos] = $o_input;
			else $this->a_items[] = $o_input;
		}
	}
	
	# TODO: this method has a bug if you assign specific index numbers using Add()
	function UpdateFinalMessage(ForumMessage $o_input)
	{
		$this->a_items[count($this->a_items)-1] = $o_input;
	}

	/**
	* @return string
	* @desc Gets the title of the forum message
	*/
	function GetTitle()
	{
		$o_message = $this->GetFirst();
		return is_object($o_message) ? $o_message->GetTitle() : null;
	}
	
	/**
	* @return string
	* @desc Gets the title of the forum topic with swear filtering applied
	*/
	function GetFilteredTitle()
	{
		$o_message = $this->GetFirst();
		return is_object($o_message) ? $o_message->GetFilteredTitle() : null;
	}

	/**
	 * Sets the category (forum) the topic is in
	 * @param Category $o_input
	 * @return void
	 */
	public function SetCategory(Category $o_input)
	{
		$this->o_category = $o_input;
	}
	
	/**
	 * Gets the category (forum) the topic is in
	 * @return Category
	 */
	public function GetCategory()
	{
		# internal property may be set if it's a new topic which doesn't yet have any messages
		if ($this->o_category instanceof Category) 
		{
			return $this->o_category;
		}
		else if (count($this->a_items) > 0 and $this->a_items[0] instanceof ForumMessage) 
		{
			#otherwise we can get the category from any of the message objects
			return $this->a_items[0]->GetCategory();
		}
		else return null;
	}

	/**
	* @return void
	* @param ReviewItem $o_input
	* @desc Sets the item being reviewed
	*/
	function SetReviewItem(ReviewItem $o_input)
	{
		$this->o_review_item = $o_input;
	}
	
	/**
	* @return ReviewItem
	* @desc Gets the item being reviewed
	*/
	function GetReviewItem()
	{
		return $this->o_review_item;
	}

	/**
	* @return int
	* @desc Gets count of messages in the topic (even if not all have been retrieved)
	*/
	function GetCount()
	{
		if (is_array($this->a_items) and count($this->a_items)) return max(array_keys($this->a_items))+1;
		else return false;
	}

    /**
     * Gets the URI which uniquely identifies this topic
     */
    public function TopicLinkedDataUri()
    {
        return "http://" . $this->o_settings->GetDomain() . "/id/forum/topic/" . $this->GetId();
    }
}
?>