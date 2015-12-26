<?php 
class CollectionBuilder
{
	var $a_monitored_values = array();
	
	/**
	* @return bool
	* @param mixed $v_value
	* @desc Check whether the specified identifier has already been processed and recorded
	*/
	function IsDone($v_value)
	{
		# see if this value is in the array
		$b_done = in_array($v_value, $this->a_monitored_values);

		# if not done, add it to the list for next time
		if (!$b_done) $this->a_monitored_values[] = $v_value;
	
		return $b_done;
	} 
	
	/**
	* @return void
	* @desc Remove all the exisiting values from the CollectionBuilder, ready for re-use
	*/
	function Reset()
	{
		$this->a_monitored_values = array();
	}
}
?>
