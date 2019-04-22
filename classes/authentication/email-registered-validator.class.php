<?php
require_once('data/validation/data-validator.class.php');
require_once('authentication-manager.class.php');

class EmailRegisteredValidator extends DataValidator
{
	private $authentication_manager;

	/**
	 * @return DataValidator
	 * Creates a new EmailRegisteredValidator
	 */
	public function __construct($a_keys, AuthenticationManager $authentication_manager)
	{
		$this->authentication_manager = $authentication_manager;
		parent::__construct($a_keys, 'The email address you chose, {0}, is already in use. Please choose a different one.');
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
		$taken = $this->authentication_manager->IsEmailRegistered($s_input);
		return !$taken;
	}
}
?>