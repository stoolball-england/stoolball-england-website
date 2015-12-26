<?php 
class ForumStats
{
	var $i_total_topics;
	var $i_total_messages;
	
	function SetTotalTopics($i_input)
	{
		if (is_numeric($i_input)) $this->i_total_topics = (int)$i_input;
	}
	
	function GetTotalTopics()
	{
		return $this->i_total_topics;
	}

	function SetTotalMessages($i_input)
	{
		if (is_numeric($i_input)) $this->i_total_messages = (int)$i_input;
	}
	
	function GetTotalMessages()
	{
		return $this->i_total_messages;
	}
}
?>