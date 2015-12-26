<?php
require_once("authentication/user.class.php");

class AuditData
{
	/**
	 * Data about an audited change
	 *
     * @param int $user_id
	 * @param string $known_as
	 * @param int $i_time
	 */
	public function __construct($user_id, $known_as, $i_time)
	{
		$this->o_user = new User();
		$this->o_user->SetId($user_id);
		$this->o_user->SetName($known_as);
		$this->i_time = $i_time;
	}

	/**
	 * User recorded by the audit
	 *
	 * @var User
	 */
	private $o_user;

	/**
	 * Gets the User recorded by the audit
	 *
	 * @return User
	 */
	public function GetUser()
	{
		return $this->o_user;
	}


	/**
	 * Time of the audited action
	 *
	 * @var int
	 */
	private $i_time;

	/**
	 * Gets the time of the audited action
	 *
	 * @return int
	 */
	public function GetTime()
	{
		return $this->i_time;
	}
}
?>