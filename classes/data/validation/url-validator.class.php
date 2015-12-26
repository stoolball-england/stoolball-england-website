<?php
require_once('data/validation/data-validator.class.php');

class UrlValidator extends DataValidator
{
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given string is a valid URL
	*/
	function Test($s_input, $a_keys) 
	{
		if ($s_input)
		{
			return (bool)preg_match('/^(http:\/\/|https:\/\/|mailto:|ftp:\/\/)[A-Za-z0-9.\/_?=&\-+$;%:(),@#~]+$/', $s_input);
		}
		else return true; # Empty string is valid
	}
}
?>
