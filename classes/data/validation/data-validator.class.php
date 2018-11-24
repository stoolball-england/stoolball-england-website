<?php
require_once('data/validation/validator-mode.enum.php');

class DataValidator
{
	var $i_mode;
	var $a_keys;
	var $s_message;
	var $a_data;
	private $b_valid_if_not_found = true;
	private $b_valid;
	private $settings;
	private $data_connection;

	/**
	 * @return DataValidator
	 * @param array $a_keys
	 * @param string $s_message
	 * @param int $i_mode
	 * @desc Abstract constructor for all validators - can be used as inherited constructor
	 */
	function __construct($a_keys, $s_message, $i_mode=null)
	{
		# validators act by default on one field; can act on a combination
		if (!is_numeric($i_mode)) $this->i_mode = ValidatorMode::SingleField();
		$this->i_mode = $i_mode;

		# overload supports a single field or an array of fields as first parameter
		if (is_array($a_keys)) $this->a_keys = $a_keys; else $this->a_keys = array($a_keys);

		# store message
		$this->s_message = $s_message;

		# default to validating POST data
		$this->a_data = $_POST;
	}

	/**
	 * @return bool
	 * @param string data to validate $s_input
	* @param string[] field names $a_keys
	 * @desc Test the supplied data for validity
	 */
	function Test($s_input, $a_keys) { return false; /* abstract function */ }

	/**
	 * Sets whether the validator passes if the field to be validated is not found (SingleField mode only)
	 *
	 * @param bool $b_valid
	 */
	public function SetValidIfNotFound($b_valid)
	{
		$this->b_valid_if_not_found = (bool)$b_valid;
	}

	/**
	 * Gets whether the validator passes if the field to be validated is not found (SingleField mode only)
	 *
	 * @return bool
	 */
	public function GetValidIfNotFound()
	{
		return $this->b_valid_if_not_found;
	}

	/**
	 * @return void
	 * @param $s_message string
	 * @desc Set the message to be displayed if data is not valid
	 */
	function SetMessage($s_message) { $this->s_message = (string)$s_message; }

	/**
	 * @return string
	 * @desc Get the message to be displayed if data is not valid
	 */
	function GetMessage() { return $this->s_message; }

	/**
	 * Gets whether the validator requires a SiteSettings object to work
	 * @return bool
	 */
	public function RequiresSettings() { return false; }

	/**
	 * Gets whether the validator requires a data connection to work
	 * @return bool
	 */
	public function RequiresDataConnection() { return false; }

	/**
	 * Adds site settings for the validator to use
	 * @param SiteSettings $settings
	 * @return void
	 */
	public function SetSiteSettings(SiteSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Adds a data connection for the validator to use
	 * @param MySqlConnection $data_connection
	 * @return void
	 */
	public function SetDataConnection(MySqlConnection $data_connection)
	{
		$this->data_connection = $data_connection;
	}

	/**
	 * Gets site settings used for validation
	 * @return SiteSettings
	 */
	public function GetSiteSettings() { return $this->settings; }

	/**
	 * Gets the data connection used for validation
	 * @return MySqlConnection
	 */
	public function GetDataConnection() { return $this->data_connection; }

	/**
	 * @return bool
	 * @desc Check whether the field(s) are valid
	 */
	function IsValid()
	{
		// Cache validation so that multiple calls to IsValid don't process again
		if (isset($this->b_valid)) return $this->b_valid;

		if (is_array($this->a_keys) and is_array($this->a_data))
		{
			if ($this->i_mode == ValidatorMode::SingleField())
			{
				if (!isset($this->a_data[$this->a_keys[0]]))
				{
					$this->b_valid = $this->b_valid_if_not_found;
				}
				else
				{
					$this->b_valid = $this->Test($this->a_data[$this->a_keys[0]], $this->a_keys);
				}
			}
			elseif ($this->i_mode == ValidatorMode::AnyField())
			{
				$b_valid = false;

				foreach($this->a_keys as $s_key)
				{
					$b_valid = ($b_valid || (isset($this->a_data[$s_key]) and $this->Test($this->a_data[$s_key],$this->a_keys)));
				}

				$this->b_valid = $b_valid;
			}
			elseif ($this->i_mode == ValidatorMode::AllFields())
			{
				$b_valid = true;

				foreach($this->a_keys as $s_key)
				{
					$b_valid = ($b_valid and (isset($this->a_data[$s_key]) and $this->Test($this->a_data[$s_key],$this->a_keys)));
				}

				$this->b_valid = $b_valid;
			}
			elseif ($this->i_mode == ValidatorMode::AllOrNothing())
			{
				$b_valid = true;
				$b_expected = (isset($this->a_data[$this->a_keys[0]]) and $this->Test($this->a_data[$this->a_keys[0]],$this->a_keys));

				$i_fields = count($this->a_keys);
				if ($i_fields > 1)
				{
					for($i = 1; $i < $i_fields; $i++)
					{
						if ((isset($this->a_data[$this->a_keys[$i]]) and ($this->Test($this->a_data[$this->a_keys[$i]], $this->a_keys))) != $b_expected) $b_valid = false;
					}
				}

				$this->b_valid = $b_valid;
			}
			elseif ($this->i_mode == ValidatorMode::MultiField())
			{
				$this->b_valid = $this->Test($this->a_data, $this->a_keys);
			}
		}
		else $this->b_valid = false;

		return $this->b_valid;
	}
}
?>