<?php
class Subscription
{
	var $o_settings;
	var $i_type;
	var $o_category;
	var $i_subscribed_item_id;
	var $s_title;
	var $s_content_date;
	var $i_subscribe_date;
    private $subscribed_item_url;

	function Subscription(SiteSettings $o_settings)
	{
		$this->o_settings = &$o_settings;
	}

	function SetType($i_input)
	{
		if (is_numeric($i_input)) $this->i_type = (int)$i_input;
	}

	function GetType()
	{
		return $this->i_type;
	}

	function SetCategory(Category $o_input)
	{
		$this->o_category = $o_input;
	}

	function GetCategory()
	{
		return $this->o_category;
	}

	function SetSubscribedItemId($i_input)
	{
		if (is_numeric($i_input)) $this->i_subscribed_item_id = (int)$i_input;
	}

	function GetSubscribedItemId()
	{
		return $this->i_subscribed_item_id;
	}
    
    public function SetSubscribedItemUrl($url) {
        $this->subscribed_item_url = $url;
    }
    
    public function GetSubscribedItemUrl() {
        return $this->subscribed_item_url;
    }

	function SetTitle($s_input)
	{
		if (is_string($s_input)) $this->s_title = $s_input;
	}

	function GetTitle()
	{
		return $this->s_title;
	}

	function SetContentDate($s_input)
	{
		if (is_string($s_input)) $this->s_content_date = $s_input;
	}

	function GetContentDate()
	{
		return $this->s_content_date;
	}

	function SetSubscribeDate($i_input)
	{
		if (is_numeric($i_input)) $this->i_subscribe_date = (int)$i_input;
	}

	function GetSubscribeDate()
	{
		return $this->i_subscribe_date;
	}


}
?>