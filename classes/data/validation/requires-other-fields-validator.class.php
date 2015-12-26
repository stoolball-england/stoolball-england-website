<?php
require_once('data/validation/data-validator.class.php');

class RequiresOtherFieldsValidator extends DataValidator
{
	private $requires_values;
	/**
	* @return RequiresOtherFieldsValidator
	* @param array Validated field followed by other required fields $a_keys
	* @param string $s_message
	* @param array(array()) Acceptable values in required fields $requires_values
	* @desc If the first given field has data, require the subsequent fields
	*/
	public function __construct($a_keys, $s_message, $requires_values=null)
	{
		parent::DataValidator($a_keys, $s_message, ValidatorMode::MultiField());
		$this->requires_values = is_array($requires_values) ? $requires_values : array();
	}
	/**
	* @return bool
	* @param array $a_data
	* @param string[] field names $a_keys
	* @desc Test whether first field is set and, if so, whether second field is not empty
	*/
	function Test($a_data, $a_keys)
	{
		# If we've insufficient data or the first field is empty, validator doesn't apply so return true
		if (count($a_data) < 1 or count($a_keys) < 2 or !isset($a_data[$a_keys[0]]) or !$a_data[$a_keys[0]])
		{
			return true;
		}
		else
		{
			# Otherwise ensure all dependent fields are completed
			$len = count($a_keys);
			for ($i = 1; $i < $len; $i++)
			{
				if (!isset($a_data[$a_keys[$i]]) or strlen(trim($a_data[$a_keys[$i]])) == 0) return false;

				# Specific values array doesn't have the field to validate, so index is one lower
				if (array_key_exists($i-1, $this->requires_values))
				{
					# Should be an array of allowed values, so check actual value is one of them
					if (!in_array($a_data[$a_keys[$i]], $this->requires_values[$i-1])) return false;
				}
			}

			return true;
		}
	}
}
?>