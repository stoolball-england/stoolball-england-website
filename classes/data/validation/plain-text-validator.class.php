<?php
require_once('data/validation/data-validator.class.php');

class PlainTextValidator extends DataValidator
{
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given string is plain text only
	*/
	function Test($s_input, $a_keys) { return (bool)preg_match('/^[A-Za-z0-9\'\[\]\-\(\)!£\$€%&*#@;:\/?\\\.~{}=+_^|,\n\r\t ]*$/', $s_input); }
}
?>