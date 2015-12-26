<?php
require_once('data/validation/data-validator.class.php');

class DateValidator extends DataValidator
{
	/**
	 * @return DateValidator
	 * @param array $a_keys
	 * @param string $s_message
	 * @param int $i_mode
	 * @desc Constructor for all validators - can be used as inherited constructor
	 */
	public function __construct($a_keys, $s_message, $i_mode=null)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);
	}

	/**
	 * @return bool
	 * @param string $s_input
	 * @param string[] field names $a_keys
	 * @desc Test whether a given string is recognised as a date
	 */
	function Test($s_input, $a_keys)
	{
		# This is not a required field validator
		if (!$s_input) return true;

		# Assume a number is a UNIX timestamp
		# Replace slashes with hyphens in submitted date because then it's treated as a British date, not American
		$date = is_numeric($s_input) ? (int)$s_input : strtotime(str_replace("/", "-", $s_input));
		return ($date !== false);
	}
}
?>