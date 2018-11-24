<?php 
require_once('collection.class.php');
require_once('forum-message.class.php');
require_once('review-item.class.php');

class ForumTopic extends Collection
{
	var $o_settings;
	var $o_review_item;
	
	function __construct(SiteSettings $o_settings, $a_items=null)
	{
		parent::__construct($a_items);
		$this->o_settings = $o_settings;
		$this->s_item_class = 'ForumMessage';
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
			# add to array, optionally allowing index to be specified
			if ($i_pos != null and is_numeric($i_pos)) $this->a_items[$i_pos] = $o_input;
			else $this->a_items[] = $o_input;
		}
	}

	/**
	* @return void
	* @param ReviewItem $o_input
	* @desc Sets the item being reviewed
	*/
	function SetReviewItem(ReviewItem $o_input)
	{
		$this->o_review_item = $o_input;
        
        foreach ($this->a_items as $message){
            $message->SetReviewItem($o_input);
        }
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
        if (!$this->o_review_item instanceof ReviewItem) {
            throw new Exception("You must set the review item to get the linked data ID");
        }
        return $this->o_review_item->GetLinkedDataUri() . '/comments';
    }
}
?>