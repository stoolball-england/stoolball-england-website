<?php
require_once('data/validation/data-validator.class.php');
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\RFCValidation;

class EmailValidator extends DataValidator
{
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given email address looks valid
	*/
	function Test($s_input, $a_keys) 
	{
		if (!$s_input) return true; // this isn't a RequiredFieldValidator
		
		$lexer =  new EmailLexer();
		$validator = new RFCValidation();
		$is_valid = $validator->isValid($s_input, $lexer);
		return $is_valid;
	}
}
?>