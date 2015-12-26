<?php
require_once('data/validation/data-validator.class.php');

class UrlSegmentValidator extends DataValidator
{
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given string uses only lowercase A-Z and digits
	*/
	function Test($s_input, $a_keys) { return (bool)preg_match('/^[a-z0-9-]*$/', $s_input); }
}
?>