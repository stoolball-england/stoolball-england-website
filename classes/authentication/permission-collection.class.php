<?php 
require_once("permission-type.enum.php");

class PermissionCollection
{
    private $permissions = array();
  
    /**
     * Add a permission to the set granted to the user
     * @param int $permission
     * @param string $resource_uri
     * @return void
     */
    public function AddPermission($permission, $resource_uri = null)
    {
        # Ensure we have an array for the permission type
        if (!array_key_exists($permission, $this->permissions))
        {
            $this->permissions[$permission] = array();
        }

        # If this is a global permission, store a token representing that
        if (!$resource_uri)
        {
            $resource_uri = PermissionType::GLOBAL_PERMISSION_SCOPE;
        }

        # Check this permission is not already granted, then grant it
        if (!in_array($resource_uri, $this->permissions[$permission], true))
        {
            $this->permissions[$permission][$resource_uri] = true;
        }
    }

    /**
     * Removes a permission from the set granted to the user
     * @param int $permission
     * @param string $resource_uri
     * @return void
     */
    public function RemovePermission($permission, $resource_uri = null)
    {
        # If no permissions of this type have been granted, do nothing
        if (!array_key_exists($permission, $this->permissions))
        {
            return ;
        }
        
        # If this is a global permission, look for a global permissions token
        if (!$resource_uri)
        {
            $resource_uri = PermissionType::GLOBAL_PERMISSION_SCOPE;
        }

        # Look for this permission and remove it
        if (in_array($resource_uri, $this->permissions[$permission], true))
        {
            unset($this->permissions[$permission][$resource_uri]);
        }
        
        # If there are no more permissions of this type, remove the type
        if (!count($this->permissions[$permission]))
        {  
            unset($this->permissions[$permission]);
        }
    }

    /**
     * Gets whether the person has the specified permission
     * @return bool
     * @param int $permission
     * @param string $resource_uri
     */
    public function HasPermission($permission, $resource_uri = null)
    {
        # If this is a global permission, look for a global permissions token
        if (!$resource_uri)
        {
            $resource_uri = PermissionType::GLOBAL_PERMISSION_SCOPE;
        }
        
        # Check for the permission type, and either the specific permission or global permission
        return (array_key_exists($permission, $this->permissions) and 
                (array_key_exists($resource_uri, $this->permissions[$permission]) or array_key_exists(PermissionType::GLOBAL_PERMISSION_SCOPE, $this->permissions[$permission])));
    }
    
    /**
     * Returns an array where the index is the permission type, and the value is an array where the indices are scopes
     */
    public function ToArray() 
    {
        return $this->permissions;
    }  
}
?>