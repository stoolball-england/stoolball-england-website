<?php
require_once("permission-collection.class.php");

/**
 * A security role used to assign permissions to a user
 */
class Role
{
	private $role_id;
	private $role_name;
    private $permissions;

	/**
	 * Create a new Role
	 * @param $role_id int
	 * @param $role_name string
	 */
	public function __construct($role_id = "", $role_name = "")
	{
		$this->setRoleId($role_id);
		$this->setRoleName($role_name);
        $this->permissions = new PermissionCollection();
	}

	/**
	 * Sets the identifier for the role
	 * @param $role_id int
	 */
	public function setRoleId($role_id)
	{
		$this->role_id = (int)$role_id;
	}

	/**
	 * Gets the identifier for the role
	 */
	public function getRoleId()
	{
		return $this->role_id;
	}

	/**
	 * Sets the display name for the role
	 * @param $role_name string
	 */
	public function setRoleName($role_name)
	{
		$this->role_name = (string)$role_name;
	}

	/**
	 * Gets the display name for the role
	 */
	public function getRoleName()
	{
		return $this->role_name;
	}

	/**
	 * Gets the display name for the role
	 */
	public function __toString()
	{
		return $this->getRoleName();
	}

  
    /**
     * Gets the security permissions assigned to the role
     * @return PermissionCollection
     */
    public function Permissions() 
    {
        return $this->permissions;
    }
}
?>