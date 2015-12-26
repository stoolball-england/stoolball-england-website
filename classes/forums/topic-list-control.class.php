<?php
require_once('xhtml/xhtml-element.class.php');

class TopicListControl extends XhtmlElement
{
	var $a_topics;
	var $b_show_forum;

	function TopicListControl($a_topics=null)
	{
		parent::XhtmlElement('ul');
		$this->a_topics = (is_array($a_topics)) ? $a_topics : array();
		$this->b_show_forum = true;
	}

	/**
	* @return void
	* @param bool $b_show
	* @desc Sets whether to include the name of the forum with each topic
	*/
	function SetShowForum($b_show)
	{
		$this->b_show_forum = (bool)$b_show;
	}

	/**
	* @return bool $b_show
	* @desc Gets whether to include the name of the forum with each topic
	*/
	function GetShowForum()
	{
		return $this->b_show_forum;
	}

	function OnPreRender()
	{
		/* @var $o_topic ForumTopic */

		foreach($this->a_topics as $o_topic)
		{
			if ($o_topic instanceof ForumTopic)
			{
				$o_message = $o_topic->GetFinal();
				$o_person = $o_message->GetUser();
				$o_category = $o_topic->GetCategory();

				$s_text = $o_message->GetLinkXhtml() . ' by ' . $o_person->GetName();
				if ($this->b_show_forum) $s_text .= ' in <strong>' . $o_category->GetName() . '</strong>';
				$s_text .= ', ' . Date::BritishDateAndTime($o_message->GetDate(), false, true, true);

				$o_li = new XhtmlElement('li', $s_text);
				$this->AddControl($o_li);
			}
		}
	}
}
?>