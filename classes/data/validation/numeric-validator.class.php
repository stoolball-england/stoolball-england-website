<?php
require_once('data/validation/data-validator.class.php');

class NumericValidator extends DataValidator
{
	/**
	* @return NumericValidator
	* @param array $a_keys
	* @param string $s_message
	* @param int $i_mode
	* @desc Constructor for numeric validator
	*/
	function &NumericValidator($a_keys, $s_message, $i_mode=null)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);

		return $this;
	}

	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given string is numeric
	*/
	function Test($s_input, $a_keys)
	{
		return (is_numeric(trim($s_input)) or (string)trim($s_input) == '');
	}
}
?>