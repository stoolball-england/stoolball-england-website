<?php
require_once('data/validation/data-validator.class.php');

class MutuallyExclusiveValidator extends DataValidator
{
	private $default_values;

	/**
	 * Creates a MutuallyExclusiveValidator
	 * @param string[] $field_names
	 * @param string $message
	 * @param string[] $default_values
	 * @param int $i_mode
	 * @return void
	 */
	public function __construct($field_names, $message, $default_values, $i_mode=null)
	{
		parent::__construct($field_names, $message, $i_mode);
		$this->default_values = (is_array($default_values)) ? $default_values : array();
	}

	/**
	 * @return bool
	 * @param string $s_input
	* @param string[] field names $a_keys
	 * @desc Test whether only one of a set of fields has been completed
	*/
	function Test($s_input, $a_keys)
	{
		$selected = 0;
		$total_fields = count($a_keys);
		for ($i = 0; $i < $total_fields; $i++)
		{
			# Get possible default values for this field, ensuring they're in an array
			$default_value = isset($this->default_values[$i]) ? $this->default_values[$i] : array();
			if (!is_array($default_value)) $default_value = array($default_value);

			# Is the data posted and different from everything in the default array?
			if (isset($this->a_data[$a_keys[$i]]) and !in_array(trim($this->a_data[$a_keys[$i]]), $default_value)) $selected++;

			if ($selected > 1) break;
		}
		return ($selected <= 1);
	}
}
?>