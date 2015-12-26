<?php
require_once ('data/validation/data-validator.class.php');

class RegExValidator extends DataValidator
{
	var $s_pattern;
	private $valid_if_match;

	/**
	 * @return RegExValidator
	 * @param array $a_keys
	 * @param string $s_message
	 * @param string $s_pattern The regular expression to match
	 * @param int $i_mode
	 * @param bool $valid_if_match
	 * @desc Validate fields' values against a regular expression
	 */
	function __construct($a_keys, $s_message, $s_pattern, $i_mode = null, $valid_if_match = true)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);
		$this->s_pattern = (string)$s_pattern;
		$this->valid_if_match = (bool)$valid_if_match;
	}

	/**
	 * @return bool
	 * @param string $s_input
	 * @param string[] field names $a_keys
	 * @desc Test whether a given string matches a regular expression
	 */
	function Test($s_input, $a_keys)
	{
		if ($this->valid_if_match)
		{
			return (bool)preg_match('/' . $this->s_pattern . '/', $s_input);
		}
		else
		{
			return !(bool)preg_match('/' . $this->s_pattern . '/', $s_input);
		}
	}

}
?>