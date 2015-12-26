<?php
require_once('data/validation/data-validator.class.php');

class NumericRangeValidator extends DataValidator
{
	private $i_min;
	private $i_max;

	/**
	* @return NumericRangeValidator
	* @param array $a_keys
	* @param string $s_message
	* @param int $i_min Minimum value, below which the field(s) will not validate
	* @param int $i_max Maximum value, above which the field(s) will not validate
	* @param int $i_mode
	* @desc Constructor for all validators - can be used as inherited constructor
	*/
	function __construct($a_keys, $s_message, $i_min, $i_max, $i_mode=null)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);
		$this->i_min = is_null($i_min) ? null : (float)$i_min;
		$this->i_max = is_null($i_max) ? null : (float)$i_max;
	}
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given value is within the specified range
	*/
	function Test($s_input, $a_keys)
	{
		if (!strlen(trim($s_input))) return true; # this isn't a required field validator
		if (!is_numeric($s_input)) return true; # this isn't a NumericValidator

		$i_value = (float)$s_input;
		return ((is_null($this->i_min) or $i_value >= $this->i_min) and (is_null($this->i_max) or $i_value <= $this->i_max));
	}
}
?>