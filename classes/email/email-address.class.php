<?php 
class EmailAddress
{
	private $s_email;

	public function __construct($s_email='')
	{
		$this->SetEmail($s_email);
	}

	public function SetEmail($s_input)
	{
		$this->s_email = trim((string)$s_input);
	}

	/**
	 * Gets the email address
	 *
	 * @return string
	 */
	public function GetEmail()
	{
		return $this->s_email;
	}

	/**
	* @return bool
	* @desc Test whether the email address matches a valid format
	*/
	public function IsValid()
	{
		$s_not_valid =  "^\t\n\r ";
		return ((strlen($this->GetEmail()) <= 300) and (preg_match("/^[" . $s_not_valid . "]+@[" . $s_not_valid . "]+\.[" . $s_not_valid . "]+/i", $this->GetEmail())));
	}
}
?>