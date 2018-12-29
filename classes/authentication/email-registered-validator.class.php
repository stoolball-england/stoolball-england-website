<?php
require_once('data/validation/data-validator.class.php');

class EmailRegisteredValidator extends DataValidator
{
	/**
	 * @return DataValidator
	 * @param array $a_keys
	 * @param string $s_message
	 * @desc Creates a new ShortUrlValidator
	 */
	public function __construct($a_keys)
	{
		parent::__construct($a_keys, 'The email address you chose, {0}, is already in use. Please chose a different one.');
	}

	/**
	 * Gets whether the validator requires a SiteSettings object to work
	 * @return bool
	 */
	public function RequiresSettings() { return true; }

	/**
	 * Gets whether the validator requires a data connection to work
	 * @return bool
	 */
	public function RequiresDataConnection() { return true; }

	/**
	 * @return bool
	 * @param string $s_input
	 * @param string[] field names $a_keys
	 * @desc Test whether a short URL is already taken by another page
	 */
	public function Test($s_input, $a_keys)
	{
		$this->SetMessage(str_replace('{0}', htmlspecialchars($s_input), $this->GetMessage()));
		
		require_once('authentication-manager.class.php');

		$manager = new AuthenticationManager($this->GetSiteSettings(), $this->GetDataConnection());
		$taken = $manager->IsEmailRegistered($s_input);
		unset($manager);

		return !$taken;
	}
}
?>