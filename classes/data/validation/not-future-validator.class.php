<?php
require_once('data/validation/data-validator.class.php');

class NotFutureValidator extends DataValidator
{
	/**
	 * @return NotFutureValidator
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
	 * @desc Test whether a given string is a date that is not in the future
	 */
	function Test($s_input, $a_keys)
	{
		# Assume a number is a UNIX timestamp
		# Replace slashes with hyphens in submitted date because then it's treated as a British date, not American
		$date = is_numeric($s_input) ? (int)$s_input : strtotime(str_replace("/", "-", $s_input));

		# This is not a date validator
		if ($date === false) return true;

		$i_utc_now = gmdate('U');
		$i_utc_today = gmmktime(23, 59, 59, gmdate('m', $i_utc_now), gmdate('d', $i_utc_now), gmdate('Y', $i_utc_now));
		return ($date <= gmdate("U", $i_utc_today));
	}
}
?>