<?php
require_once('data/validation/data-validator.class.php');

class LengthValidator extends DataValidator
{
	var $i_minlength;
	var $i_maxlength;

	/**
	 * @return LengthValidator
	 * @param array $a_keys
	 * @param string $s_message
	 * @param int $i_minlength Minimum number of characters, below which the field(s) will not validate
	 * @param int $i_maxlength Maximum number of characters, above which the field(s) will not validate
	 * @param int $i_mode
	 * @desc Constructor for all validators - can be used as inherited constructor
	 */
	function LengthValidator($a_keys, $s_message, $i_minlength, $i_maxlength, $i_mode=null)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);
		$this->i_minlength = (int)$i_minlength;
		$this->i_maxlength = ($i_maxlength == null) ? 10000000 : (int)$i_maxlength;
	}
	/**
	 * @return bool
	 * @param string $s_input
	 * @param string[] field names $a_keys
	 * @desc Test whether a given string less than or equal to a given length
	 */
	function Test($s_input, $a_keys)
	{
		$i_length = strlen($s_input);
		if ($i_length == 0) return true; # special case, this is not a required field validator
		return (($i_length >= $this->i_minlength) and ($i_length <= $this->i_maxlength));
	}
}
?>