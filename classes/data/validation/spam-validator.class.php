<?php
require_once('data/validation/data-validator.class.php');

class SpamValidator extends DataValidator
{

	/**
	 * @return bool
	 * @param string $s_input
	* @param string[] field names $a_keys
	 * @desc Check that a field has been left empty. Only spam robots fill in every field.
	*/
	function Test($s_input, $a_keys) { return !(bool)strlen(trim($s_input)); }
}
?>