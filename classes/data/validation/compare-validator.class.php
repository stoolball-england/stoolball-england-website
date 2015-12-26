<?php
require_once('data/validation/data-validator.class.php');

class CompareValidator extends DataValidator
{
	/**
	* @return CompareValidator
	* @param array $a_keys
	* @param string $s_message
	* @desc Test whether given fields are the same
	*/
	function CompareValidator($a_keys, $s_message)
	{
		parent::DataValidator($a_keys, $s_message, ValidatorMode::MultiField());
	}
	/**
	* @return bool
	* @param array $a_data
	* @param string[] field names $a_keys
	* @desc Test whether data in first two fields of $a_keys matches
	*/
	function Test($a_data, $a_keys) 
	{
		# TODO: could loop through $a_keys looking for everything to match?
		if (count($a_data) >= 2 and count($a_keys) >= 2 and isset($a_data[$a_keys[0]]) and isset($a_data[$a_keys[1]]))
		{
			return ($a_data[$a_keys[0]] === $a_data[$a_keys[1]]); 
		}
		else return false;
	}
}
?>