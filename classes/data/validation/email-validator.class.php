<?php
require_once('data/validation/data-validator.class.php');
require_once('email/email-address.class.php');

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
		$o_email_address = new EmailAddress($s_input);
		return $o_email_address->IsValid(); 
		
		/*		require_once 'Zend/Validate/EmailAddress.php';
		$validator = new Zend_Validate_EmailAddress();
		$validator->setValidateMx(false);
		return $validator->isValid($s_input);*/
	}
}
?>