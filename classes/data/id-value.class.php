<?php
class IdValue
{
	var $i_id = 0;
	var $s_value = '';
	
	/**
	* @return IdValue
	* @param $id int
	* @param $value string
	* @desc A text value identified by a numeric id
	*/
	public function __construct($id=null, $value=null)
	{
		if (!is_null($id)) $this->SetId($id);
		if (!is_null($value)) $this->SetValue($value);
	}
	
	/**
	* @return void
	* @param int $i_id
	* @desc Sets the unique database identifier for the value
	*/
	function SetId($i_id) { $this->i_id = (int)$i_id; }

	/**
	* @return int
	* @desc Gets the unique database identifier for the value
	*/
	function GetId() { return $this->i_id; }
	
	/**
	* @return void
	* @param string $s_type
	* @desc Sets the text value
	*/
	function SetValue($s_value) { $this->s_value = (string)$s_value; }

	/**
	* @return string
	* @desc Gets the text value
	*/
	function GetValue() { return $this->s_value; }
	
	/**
	* @return string
	* @desc Gets the text value
	*/
	public function __toString()
	{
		return $this->GetValue();
	}
}
?>