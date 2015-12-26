<?php
require_once('data/validation/data-validator.class.php');

class RequiredFieldValidator extends DataValidator
{
	/**
	 * @return bool
	 * @param string $s_input
	* @param string[] field names $a_keys
	 * @desc Test whether a given string is empty
	*/
	function Test($s_input, $a_keys) { return (bool)strlen(trim($s_input)); }
}
?>