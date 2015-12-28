<?php
require_once('data/data-manager.class.php');
require_once('authentication/user.class.php');
require_once('authentication/sign-in-result.enum.php');

class AuthenticationManager extends DataManager
{
    /**
     * @var IAutoSignIn
     */
    private $auto_sign_in;
	private $i_permission_required_for_page;

	/**
	 * @return AuthenticationManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @param PermissionType $i_permission_required
	 * @desc Read and write activation requests
	 */
	public function __construct($o_settings, $o_db, $i_permission_required=null)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'User';

		# If a permission is supplied this is the main authentication manager for the page,
		# otherwise it's just an ordinary data manager class.
		if (!is_null($i_permission_required))
		{
			# is authentication required for this page
			$this->i_permission_required_for_page = $i_permission_required;

			# start/resume a session
			# "viewas" parameter is a way of passing PHP session id in an AJAX request
			if (isset($_POST["viewas"])) {
				session_id($_POST["viewas"]);
			}
			if (!headers_sent()) {
			    session_start();
            }

			# remember this request, so that it's possible to detect a refresh
			$this->SaveRequestHash();
		}
	}
    
    /**
     * Sets the provider used to handle signing in automatically
     */
    public function SetAutoSignInProvider(IAutoSignIn $auto_sign_in) {
        $this->auto_sign_in = $auto_sign_in;
    }
    
    /**
     * Gets the provider used to handle signing in automatically
     */
    public function GetAutoSignInProvider() {
        return $this->auto_sign_in;
    }

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the users matching the supplied ids
	 */
	public function ReadUserById($a_ids=null)
	{
		# build query
		$user = $this->GetSettings()->GetTable('User');
        $user_role = $this->GetSettings()->GetTable("UserRole");
        $role = $this->GetSettings()->GetTable("Role");

		$sql = "SELECT $user.user_id, $user.known_as, $user.name_first, $user.name_last, $user.email, 
		      gender, occupation, interests, location, date_added AS sign_up_date, total_messages, disabled,  
		      $role.role_id, $role.role
    		  FROM $user LEFT JOIN $user_role ON $user.user_id = $user_role.user_id 
		      LEFT JOIN $role ON $user_role.role_id = $role.role_id ";

		# limit to specific ids, if specified
		if (is_array($a_ids)) $sql .= "WHERE $user.user_id IN (" . join(', ', $a_ids) . ') ';

		# sort results
		$sql .= "ORDER BY $user.known_as ASC, $user.name_last ASC, $user.name_first ASC";

		# run query
		$o_result = $this->GetDataConnection()->query($sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @param string $email
	 * @desc Reads the person matching the supplied email
	 */
	public function ReadByEmail($email)
	{
		# build query
		$s_person = $this->GetSettings()->GetTable('User');

		$s_sql = 'SELECT ' . $s_person . '.user_id, ' . $s_person . '.known_as, ' . $s_person . '.email ' .
			'FROM (' . $s_person . ') ' .
			"WHERE email = " . Sql::ProtectString($this->GetDataConnection(), $email);

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}
    
    /**
     * Reads a user with a matching reset token, if it was requested in the last 24 hours 
     * @param string $reset_token A UUID
     * @return User 
     */
    public function ReadUserByPasswordResetToken($reset_token) {

        $user = $this->GetSettings()->GetTable('User');
        $one_day_ago = gmdate('U') - (60 * 60 * 24);
        

        $sql = 'SELECT user_id, known_as ' .
            'FROM (' . $user . ') ' .
            'WHERE password_reset_request_date >= ' . $one_day_ago . 
            ' AND password_reset_token = ' . Sql::ProtectString($this->GetDataConnection(), $reset_token);

        $result = $this->GetDataConnection()->query($sql);
        $this->BuildItems($result);
        $result->closeCursor();
        unset($result);
        
        return $this->GetFirst();
    }

	/**
	 * Registers, but doesn't activate, a user account
	 * @param User $user
	 * @return User
	 */
	public function RegisterUser(User $user)
	{
		$s_sql = 'INSERT INTO nsa_user SET '  .
				"known_as = " . Sql::ProtectString($this->GetDataConnection(), $user->GetName()) . ", " .
				"name_sort = " . Sql::ProtectString($this->GetDataConnection(), $user->GetSortName()) . ", " .
				"email = " . Sql::ProtectString($this->GetDataConnection(), $user->GetEmail()) . ", " .
				'activated = 0, ' .
				'date_added = ' . gmdate('U') . ', ' .
				'date_changed = ' . gmdate('U');

		$this->Lock(array("nsa_user"));
		$result = $this->GetDataConnection()->query($s_sql);
		$user->SetId($this->GetDataConnection()->insertID());
        $this->SavePassword($user);
		$this->Unlock();

		return $user;
	}

	/**
	 * @return string
	 * @param int $i_person_id
	 * @desc Add a new user account request to be activated by email, and return an activation code
	 */
	function SaveRequest($i_person_id)
	{
		if (!is_numeric($i_person_id)) throw new Exception('Unable to add activation request');

		$token = $this->GenerateRandomToken();

		$activation = $this->o_settings->GetTable('Activation');
		$i_person_id = Sql::ProtectNumeric($i_person_id);

		# clear out any previous account request for this user
		$this->GetDataConnection()->query("DELETE FROM $activation WHERE item_id = $i_person_id");

		# add a new account request
		$s_sql = 'INSERT INTO ' . $activation . ' SET ' .
		'item_id = ' . $i_person_id . ', ' .
		'request_time = ' . gmdate('U') . ', ' .
		"hash = " . Sql::ProtectString($this->GetDataConnection(), $token);

		$this->GetDataConnection()->query($s_sql);

		return $token;
	}

	/**
	 * Sends an email to the user with a link to activate their account
	 * @param User $account
	 * @param string hash returned by a call to SaveRequest() $activation_hash
	 * @return bool
	 */
	public function SendActivationEmail(User $account, $activation_hash)
	{
		# send email requesting activation - validates email address
		require_once 'Zend/Mail.php';
		$email = new Zend_Mail('UTF-8');
		$email->addTo($account->GetEmail());
		$email->setSubject($this->GetSettings()->GetSiteName() . ' - please confirm your registration');
		$email->setFrom($this->GetSettings()->GetEmailAddress(), $this->GetSettings()->GetSiteName());

		$s_greeting = $account->GetName() ? $account->GetName() : 'there';
		$s_confirm_url = 'https://' . $this->GetSettings()->GetDomain() . $this->GetSettings()->GetUrl('AccountActivate') . '?p=' . $account->GetId() . '&c=' . urlencode($activation_hash);
        $body = 'Hi ' . $s_greeting . "!\n\n" .
				'Please confirm your request to register with ' . $this->GetSettings()->GetSiteName() . "\n" .
				"by clicking on the following link, or copying it into your browser: \n\n" .
		          $s_confirm_url . "\n\n" .
		          trim($this->GetSettings()->GetEmailSignature()) . "\n\n" .
                            '(We sent you this email because someone signed up to ' . $this->GetSettings()->GetSiteName() . "\n" .
                            'using the email address ' . $account->GetEmail() . ". If it wasn't you just ignore this email,\n" .
                            "and the account won't be activated.)";
        $email->setBodyText($body);

		try
		{
			$email->send();
                        
			return true;
		}
		catch (Zend_Mail_Transport_Exception $e)
		{
			return false;
		}
	}

    /**
     * ID of the role which represents permissions for an ordinary registered user
     */
    const REGISTERED_USER_ROLE = 1;

	/**
	 * @return bool
	 * @param int $i_person_id
	 * @param string $s_hash
	 * @desc Complete an activation request matching the specified hash code
	 */
	public function Activate($i_person_id, $s_hash)
	{
		# check parameters
		if (!is_numeric($i_person_id)) return false;
		if (!is_string($s_hash)) return false;

		# get the activation request
		$s_sql = 'SELECT COUNT(item_id) AS total FROM ' . $this->o_settings->GetTable('Activation') . ' ' .
		"WHERE hash = " . Sql::ProtectString($this->GetDataConnection(), $s_hash) . " AND item_id = " . Sql::ProtectNumeric($i_person_id);

		$o_data = $this->GetDataConnection()->query($s_sql);
        $o_row = $o_data->fetch();

		# succeeded if exactly one record retrieved
		if ($o_row and $o_row->total == 1)
		{
            # activate the person record
			$s_sql = 'UPDATE ' . $this->o_settings->GetTable('User') . ' SET ' .
			'activated = 1 ' .
			'WHERE user_id = ' . $i_person_id;

			$o_data = $this->GetDataConnection()->query($s_sql);

			# succeeded if exactly one record activated
			if ($this->GetDataConnection()->GetAffectedRows() == 1)
			{
				# remove the activation request
				$s_sql = 'DELETE FROM ' . $this->o_settings->GetTable('Activation') . ' ' .
				"WHERE hash = " . Sql::ProtectString($this->GetDataConnection(), $s_hash) . " AND item_id = " . Sql::ProtectNumeric($i_person_id);

				$this->GetDataConnection()->query($s_sql);

				# add into signed-up-user role
				$this->AddUserToRole($i_person_id, AuthenticationManager::REGISTERED_USER_ROLE);

				return true;
			}
			else return false;
		}
		else return false;
	}

	/**
	 * @return void
	 * @param int $i_person_id
	 * @param int $i_role_id
	 * @desc Add the specified person to the specified role
	 */
	public function AddUserToRole($i_person_id, $i_role_id)
	{
		# check parameters
		if (!is_numeric($i_person_id)) die('Unable to add person to role');
		if (!is_numeric($i_role_id)) die('Unable to add person to role');

		# build query
		$s_sql = 'INSERT INTO ' . $this->o_settings->GetTable('UserRole') . ' SET ' .
			'user_id = ' . Sql::ProtectNumeric($i_person_id) . ', ' .
			'role_id = ' . Sql::ProtectNumeric($i_role_id);

		# run query
		$this->GetDataConnection()->query($s_sql);
	}
    
    /**
     * Reads all the security roles
     * @return Role[]
     */
    public function ReadRoles() 
    {
        require_once("authentication/role.class.php");
        $role = $this->o_settings->GetTable('Role');
        $result = $this->GetDataConnection()->query("SELECT role_id, role FROM $role ORDER BY role");
        
        $roles = array();
        while ($row = $result->fetch()) 
        {
            $roles[] = new Role($row->role_id, $row->role);
        }
        $result->closeCursor();
        return $roles;
    }

    /**
     * Reads a security role matching the supplied ID
     * @param $id int
     */
    public function ReadRoleById($id) 
    {
        require_once("authentication/role.class.php");
        $role = $this->o_settings->GetTable('Role');
        $permission = $this->o_settings->GetTable('PermissionRoleLink');
        
        $sql = "SELECT $role.role_id, $role.role, $permission.permission_id, $permission.resource_uri
                FROM $role LEFT JOIN $permission ON $role.role_id = $permission.role_id
                WHERE $role.role_id = " . Sql::ProtectNumeric($id, false) . " 
                ORDER BY $role.role";
        $result = $this->GetDataConnection()->query($sql);
        
        $role = null;
        while ($row = $result->fetch()) 
        {
            if (is_null($role))
            { 
                $role = new Role($row->role_id, $row->role);
            }
            if (!is_null($row->permission_id))
            {
                $role->Permissions()->AddPermission($row->permission_id, $row->resource_uri);
            } 
         }
        $result->closeCursor();
        return $role;
    }

    /**
     * Saves a security role
     * @param $role Role
     */
    public function SaveRole(Role $role)
    {
        $roles = $this->GetSettings()->GetTable('Role');
        $permissions_table = $this->GetSettings()->GetTable('PermissionRoleLink');

        # if no id, it's a new object; otherwise update the object
        if ($role->getRoleId())
        {
            $sql = "UPDATE $roles SET 
                role = " . Sql::ProtectString($this->GetDataConnection(), $role->getRoleName()) . " 
                WHERE role_id = " . Sql::ProtectNumeric($role->getRoleId());

            $this->LoggedQuery($sql);
        
            # Remove existing permissions
            $sql = "DELETE FROM $permissions_table WHERE role_id = " . Sql::ProtectNumeric($role->getRoleId());
            $this->LoggedQuery($sql);
        }
        else
        {
            $sql = "INSERT INTO $roles SET role = " . Sql::ProtectString($this->GetDataConnection(), $role->getRoleName());

            $this->LoggedQuery($sql);

            $role->setRoleId($this->GetDataConnection()->insertID());
        }
                
        # Add replacement permissions
        $role_id = Sql::ProtectNumeric($role->getRoleId());
        $permissions = $role->Permissions()->ToArray();
        foreach ($permissions as $permission => $scopes) 
        {
            foreach ($scopes as $scope => $ignore_value) 
            {
                $resource_uri = ($scope == PermissionType::GLOBAL_PERMISSION_SCOPE) ? "NULL" : Sql::ProtectString($this->GetDataConnection(), $scope);
                
                $sql = "INSERT INTO $permissions_table SET 
                    permission_id = " . Sql::ProtectNumeric($permission) . ",
                    resource_uri = $resource_uri, 
                    role_id = $role_id";

                $this->LoggedQuery($sql);
            }
        }
        
    }


    /**
     * Deletes a security role matching the supplied ID
     * @param $id int
     */
    public function DeleteRole($id) 
    {
        if (!is_numeric($id))
        {
            throw new Exception("Role ID not specified");
        } 
        
        $role = $this->o_settings->GetTable('Role');
        $permission = $this->o_settings->GetTable('PermissionRoleLink');
                
        $role_id = Sql::ProtectNumeric($id, false);
        
        $sql = "UPDATE nsa_team SET owner_role_id = NULL WHERE owner_role_id = $role_id";
        $this->GetDataConnection()->query($sql);
        
        $sql = "DELETE FROM $permission WHERE role_id = $role_id";
        $this->GetDataConnection()->query($sql);
        
        $sql = "DELETE FROM nsa_user_role WHERE role_id = $role_id";
        $this->GetDataConnection()->query($sql);
        
        $sql = "DELETE FROM $role WHERE role_id = $role_id";
        $this->GetDataConnection()->query($sql);
    }

	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $result
	 */
	protected function BuildItems(MySqlRawData $result)
	{
		$this->Clear();

		# use CollectionBuilder to handle duplicates
		$user_builder = new CollectionBuilder();
        $roles = new CollectionBuilder();
        $role_class_loaded = false;
		$user = null;

		while($row = $result->fetch())
		{
			# check whether this is a new person
			if (!$user_builder->IsDone($row->user_id))
			{
				# store any exisiting person
				if ($user != null)
                {
                    $this->Add($user);
                    $roles->Reset();
                }

				# create the new person
				$user = new User();
				$user->SetId($row->user_id);
				$user->SetName($row->known_as);
				if (isset($row->name_first)) $user->SetFirstName($row->name_first);
				if (isset($row->name_last)) $user->SetLastName($row->name_last);
				if (isset($row->email)) $user->SetEmail($row->email);
				if (isset($row->gender)) $user->SetGender($row->gender);
				if (isset($row->occupation)) $user->SetOccupation($row->occupation);
				if (isset($row->interests)) $user->SetInterests($row->interests);
				if (isset($row->location)) $user->SetLocation($row->location);
				if (isset($row->sign_up_date)) $user->SetSignUpDate($row->sign_up_date);
				if (isset($row->total_messages)) $user->SetTotalMessages($row->total_messages);
                
                if (isset($row->disabled)) 
                {
                    $user->SetAccountDisabled($row->disabled);
                }
            }
             # Add security roles
            if (isset($row->role_id) and !$roles->IsDone($row->role_id))
            {
                if (!$role_class_loaded)
                {
                    require_once("authentication/role.class.php");
                    $role_class_loaded = true;
                } 
                $role = new Role($row->role_id, $row->role);
                $user->Roles()->Add($role);
            }
		}
		# store final person
		if ($user != null) $this->Add($user);
	}

	/**
	 * @return void
	 * @desc Expire unactivated activation requests after 30 days
	 */
	function ExpireRequests()
	{
		# expire requests after 30 days
		# don't worry about time zones - a few hours don't matter
		$i_30_days_ago = gmdate('U') - (60 * 60 * 24 * 30);

		$activation = $this->GetSettings()->GetTable('Activation');
		$registered = $this->GetSettings()->GetTable('User');

		$sql = "SELECT item_id FROM $activation WHERE request_time <= " . Sql::ProtectNumeric($i_30_days_ago);
		$result = $this->GetDataConnection()->query($sql);

		# Delete the person request for each expired activation request, because otherwise they can't have
		# another go at Registering using the same email address
		$this->Lock(array($activation, $registered));
		while ($row = $result->fetch())
		{
			$this->GetDataConnection()->query("DELETE FROM $registered WHERE user_id = " . $row->item_id);
			$this->GetDataConnection()->query("DELETE FROM $activation WHERE item_id = " . $row->item_id);
		}
		$this->Unlock();
	}

	/**
	 * Saves a hash identifying the current request, to be used to decide whether it is a refresh
	 *
	 */
	private function SaveRequestHash()
	{
		$_SESSION['prev_request_hash'] = isset($_SESSION['request_hash']) ? $_SESSION['request_hash'] : '';
		$s_request_sig = $_SERVER['REQUEST_URI'] . '#GET#' . implode(array_keys($_GET)) . $this->ImplodeArray($_GET) . '#POST#' . implode(array_keys($_POST)) . $this->ImplodeArray($_POST) . '#COOKIE#' . implode(array_keys($_COOKIE)) . $this->ImplodeArray($_COOKIE);
		if (isset($_SERVER['HTTP_USER_AGENT'])) $s_request_sig .= $_SERVER['HTTP_USER_AGENT'];
		$_SESSION['request_hash'] = md5($s_request_sig);
	}

	/**
	 * Convert array values to string, but cope with values which are themselves arrays
	 *
	 * @param array $implode_this
	 * @return string
	 */
	private function ImplodeArray($implode_this)
	{
		$copy = array();
		foreach ($implode_this as $value)
		{
			if (is_array($value))
			{
				$copy[] = $this->ImplodeArray($value);
			}
			else
			{
				$copy[] = $value;
			}
		}
		return implode($copy);
	}

	/**
	 * Gets whether the current request is a refresh or re-post of the last
	 *
	 * @return bool
	 */
	public function UserHasRefreshed()
	{
		return $_SESSION['prev_request_hash'] == $_SESSION['request_hash'];
	}

	function HasPermissionForPage($o_user)
	{
		/* @var $o_user User */
        if (!$o_user instanceof User) return false;
		else return $o_user->Permissions()->HasPermission($this->i_permission_required_for_page);
	}

	/**
	 * If authentication is required, get the user to sign in
	 *
	 * @param string $s_return_page
	 * @return void
	 **/
	function GetPermission($s_return_page='')
	{
		# allow page other than current to come back to
		if (!$s_return_page)
		{
			$s_return_page = $_SERVER['REQUEST_URI'];
		}

		header('Location: ' . $this->o_settings->GetFolder('Account') . '?action=required&page=' . urlencode(str_replace('action=signout','',$s_return_page)));
		exit(); # shouldn't get here
	}

	/**
	 * Checks whether an email and password combination is valid
	 * @param string $username
	 * @param string $password
     * @param bool $password_already_hashed
	 * @return int user id or false
	 */
	public function ValidateUser($username, $password, $password_already_hashed=false)
	{
        $valid = false;
        if (is_string($username) and is_string($password))
        {
            $sql = "SELECT salt, password_md5, password_hash, user_id
            FROM nsa_user WHERE email = " . Sql::ProtectString($this->GetDataConnection(), $username);
            $result = $this->GetDataConnection()->query($sql);
            $row = $result->fetch();
            
            if ($row)
            {
                # Check for password hashed by PHP
                if ($row->password_hash) {
                        
                    $valid = password_verify($password, $row->password_hash);     
                    if ($valid) {
                            
                        # If PHP has updated its hashing algorithm, update the saved hash
                        if (password_needs_rehash($row->password_hash, PASSWORD_DEFAULT)) {

                             $password_change_user = new User();
                             $password_change_user->SetId($row->user_id);
                             $password_change_user->SetRequestedPassword($password);
                             $this->SavePassword($password_change_user);
                        }
                    }
            
              
                }
                
                # Check for old password hash
                else if ($row->password_md5) {
                    if (!$password_already_hashed)
                    {
                        $submitted_password_hashed = md5($password . $row->salt);
                    }
                    else {
                        $submitted_password_hashed = $password;
                    } 
                    
                    if ($submitted_password_hashed === $row->password_md5) {
                                
                        $valid = true;               
                                        
                        # If using old password storage, re-store it using up-to-date method 
                        if ($row->password_md5 && !$password_already_hashed) {
                            $password_change_user = new User();
                            $password_change_user->SetId($row->user_id);
                            $password_change_user->SetRequestedPassword($password);
                            $this->SavePassword($password_change_user);
                        }                 
                    }
                }

                if ($valid) {
                    $valid = $row->user_id;
                }
             }
 
            $result->closeCursor();
        } 

        return $valid;
    }

    /**
     * When a user's credentials have been validated, get more info on that user
     * @param int $user_id
     * @return User
     */
    public function ReadDataForValidUser($user_id) 
    {
        $user = null;
            
        $sql = "SELECT user_id, known_as, name_first, name_last, email, 
        disabled, activated, requested_email
        FROM nsa_user WHERE user_id = " . Sql::ProtectNumeric($user_id);
        $result = $this->GetDataConnection()->query($sql);
        $row = $result->fetch();
        
        if ($row)
        {
            $user = new User();
            $user->SetId($row->user_id);
            $user->SetName($row->known_as);
            $user->SetFirstName($row->name_first);
            $user->SetLastName($row->name_last);
            $user->SetEmail($row->email);
            $user->SetRequestedEmail($row->requested_email);
            $user->SetAccountActivated($row->activated);
            $user->SetAccountDisabled($row->disabled);
        }
        
        return $user;
    }

	/**
	 * Attempt to sign in to the website using the supplied username and password
	 *
	 * @param string $username
	 * @param string $password
	 * @param bool $enable_auto_sign_in
	 * @param bool $password_already_hashed
	 * @return SignInResult
	 */
	public function SignIn($username, $password, $enable_auto_sign_in=false, $password_already_hashed=false)
	{
        $user_id = $this->ValidateUser($username, $password, $password_already_hashed);    

		if (!$user_id) 
		{
		    return SignInResult::NotFound();
        }
        
        $user = $this->ReadDataForValidUser($user_id);
        $sign_in_result = $this->SignInValidUser($user, $enable_auto_sign_in);

		return $sign_in_result;
	}
    
    /**
     * Once a user has been validated, do everything needed to sign them in
     * @param $user User
     * @param bool $enable_auto_sign_in
     * @return SignInResult
     */
    public function SignInValidUser(User $user, $enable_auto_sign_in) 
    {
        # Bail out if user account not activated
        # (if there's no role it's because I've tried to activate the account by flipping
        #  the activation field, but I haven't added the account to the "Signed in user" role)
        if (!$user->GetAccountActivated())
        {
            return SignInResult::NotActivated();
        }

        # bail out if user account has been disabled
        if ($user->GetAccountDisabled())
        {
            if ($this->auto_sign_in instanceof IAutoSignIn) {
                $this->SaveAutoSignIn($user->GetId(), false);
            }
            return SignInResult::AccountDisabled();
        }

        # Elevation of privilege, so regenerate session id to guard against session fixation attack
        if (!headers_sent())
        {
           session_regenerate_id(false);
        }

        $this->SaveToSession($user);
        
        $this->LoadUserPermissions();

        $this->Lock(array("nsa_user"));

        # update stats in db...
        $sql = 'UPDATE nsa_user SET ' .
        'sign_in_count = sign_in_count+1, ' .
        'last_signed_in = ' . gmdate('U') . ' ' .
        'WHERE user_id = ' . $user->GetId();

        $this->GetDataConnection()->query($sql);
        $this->Unlock();
        
        # process remember me option
        if ($this->auto_sign_in instanceof IAutoSignIn) {
            $this->auto_sign_in->SaveAutoSignIn($user->GetId(), $enable_auto_sign_in);
        }
        
        return SignInResult::Success();
    }

    /**
     * Read the current user's permissions from the database, and update the current user
     */
    public function LoadUserPermissions() 
    {
        $user = AuthenticationManager::GetUser();
        
        $sql = "SELECT pr.permission_id, pr.resource_uri
        FROM nsa_user_role AS ur  
        LEFT OUTER JOIN nsa_role AS role ON ur.role_id = role.role_id 
        LEFT OUTER JOIN nsa_permission_role AS pr ON role.role_id = pr.role_id 
        WHERE user_id = " . Sql::ProtectNumeric($user->GetId(), false);

        $result = $this->GetDataConnection()->query($sql);
        while ($row = $result->fetch())
        {
            $user->Permissions()->AddPermission($row->permission_id, $row->resource_uri);
        }
    
        $this->SaveToSession($user);
    }


	/**
	 * Check whether a request to sign out has been submitted, and process it
     * @return bool true if a sign out was requested, false otherwise
	 *
	 */
	public function SignOutIfRequested()
	{
		if (isset($_POST['action']) and $_POST['action'] == 'signout' 
		and isset($_SESSION['user']) and $_SESSION['user']
        and isset($_POST['securitytoken']) and $_POST['securitytoken']
        and isset($_SESSION['csrf_token']) and $_SESSION['csrf_token']
        and $_SESSION['csrf_token'] == $_POST['securitytoken'])
        {
            $this->SignOut();
            return true;
        }
        return false;
	}

	/**
	 * Ends the current session and deletes any cookies which would result in an automatic sign-in
	 *
	 */
	public function SignOut()
	{
		$user = $this->GetUser();
        if ($this->auto_sign_in instanceof IAutoSignIn) {
            $this->auto_sign_in->SaveAutoSignIn($user->GetId(), false);
        }
		
		session_destroy();
		session_start();
		
		$this->EnsureUser();
	}

	/**
	 * Ensures there is a user object available with view permissions even if no user is signed in
	 *
	 * @return void
	 */
	public static function EnsureUser()
	{
		if (!isset($_SESSION['user'])) $_SESSION['user'] = new User();
		$_SESSION['user']->Permissions()->AddPermission(PermissionType::ViewPage());
	}


	public function SaveToSession(User $o_person)
	{
		$_SESSION['user'] = $o_person;
		$this->o_user = $_SESSION['user'];
	}
    
	/**
	 * Checks whether the current request contains authentication cookies
	 *
	 * @return bool
	 */
	public function HasCookies()
	{
	    # Look for new auto-sign-in cookie
		if (isset($_COOKIE['user']) and $_COOKIE['user']) {
		    return true;
        }
        
        # Look for old auto-sign-in cookie
        if (isset($_COOKIE['user_3uNGNNLT']) and $_COOKIE['user_3uNGNNLT']) {
            return true;
        }
        
        # None found
		return false;
	}
    
    /**
     * Create a genuinely random token which can be used to authenticate time-limited requests
     */
    private function GenerateRandomToken() 
    {
        return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB)));
    }
 
    /**
     * Checks the cookies supplied with the current request for a saved account and, if available, signs in
     *
     */
    public function SignInIfRemembered()
    {
        # If there's a user object in session already, don't try to override it with an automatic sign in
        if (isset($_SESSION['user']) and $_SESSION['user']) return;

        # If they're trying to sign out, don't sign them back in again
        if (isset($_GET['action']) and $_GET['action'] == 'signout') return;

        # Approach to this feature has changed. Try new approach, and fall back to old.
        # Note: Last cookies from old approach will expire 1 Feb 2016, when this code can be deleted.
        if (!$this->TryNewAutoSignIn()) {
            $this->TryOldAutoSignIn();
        }
    }
  
    /**
     * Signs in using an up-to-date auto-sign-in cookie if one is found
     * @return bool true if the cookie is found, false otherwise
     */
    private function TryNewAutoSignIn()
    {      
        if ($this->auto_sign_in instanceof IAutoSignIn) {
            $user_id = $this->auto_sign_in->TryNewAutoSignIn();
            if (!is_null($user_id)) {
                $user = $this->ReadDataForValidUser($user_id);
                $this->SignInValidUser($user, true);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Signs in using the old auto-sign-in cookie if one is found
     * @return bool true if the cookie is found, false otherwise
     */
    private function TryOldAutoSignIn()
    {
        if ($this->auto_sign_in instanceof IAutoSignIn) {
            $user = $this->auto_sign_in->TryOldAutoSignIn();
            if ($user instanceof User) {
                $this->SignIn($user->GetEmail(), $user->GetPassword(), true, true);
                return true;
            }
            return true;
        }

        return false;
    }
    
	/**
	 * Gets the current user of the website
	 *
	 * @return User
	 */
	public static function GetUser()
	{
		return $_SESSION['user'];
	}

	/**
	 * Checks whether an email address is already registered by anyone other than the current user
	 * @param string $email
	 * @return bool
	 */
	public function IsEmailRegistered($email)
	{
		$taken = false;

		$s_sql = 'SELECT user_id FROM ' . $this->GetSettings()->GetTable('User') . " WHERE email = " . Sql::ProtectString($this->GetDataConnection(), $email);

		$current_user_id = AuthenticationManager::GetUser()->GetId();
		if ($current_user_id) $s_sql .= " AND user_id <> " . Sql::ProtectNumeric($current_user_id, false);

		$result = $this->GetDataConnection()->query($s_sql);

		if ($result->fetch())
		{
			$taken = true;
		}
		$result->closeCursor();

		return $taken;
	}

    /**
     * Records a request to reset a password, and a token which will allow the request for a limited period
     * @param int $user_id The user account for which the reset was requested
     * @return string token
     */
    public function SavePasswordResetRequest($user_id) 
    {
        $token = $this->GenerateRandomToken();

        $registered = $this->GetSettings()->GetTable('User');
        $sql = "UPDATE $registered SET " .
                'password_reset_request_date = ' . gmdate('U') . ', ' .
                "password_reset_token = " . Sql::ProtectString($this->GetDataConnection(), $token) . " " .
                "WHERE user_id = " . Sql::ProtectNumeric($user_id, false);

        $this->Lock(array($registered));
        $this->GetDataConnection()->query($sql);
        $this->Unlock();  
        
        return $token;      
    }
    
	/**
	 * Saves the user's requested password, optionally checking if their correct current password is given
	 * @param User $user
	 * @return bool
	 */
	public function SavePassword(User $user)
	{
		$registered = $this->GetSettings()->GetTable('User');
        $success = false;

        $new_password_hashed = password_hash($user->GetRequestedPassword(), PASSWORD_DEFAULT);

        $sql = "UPDATE $registered SET " .
                'date_changed = ' . gmdate('U') . ', ' .
                "salt = NULL, " .
                "password_md5 = NULL, " .
                'password_reset_request_date = NULL, ' .
                "password_reset_token = NULL, " .
                "password_hash = " . Sql::ProtectString($this->GetDataConnection(), $new_password_hashed) . " " .
                "WHERE user_id = " . Sql::ProtectNumeric($user->GetId(), false);       

        $this->Lock(array($registered));
        $result = $this->GetDataConnection()->query($sql);
        $success = (!$this->GetDataConnection()->isError() and $this->GetDataConnection()->GetAffectedRows() == 1);
        $this->Unlock();
        
		return $success;
	}

	/**
	 * Saves an email address the user would like to user as their username, awaiting confirmation of ownership
	 * @param User $user
	 * @return Confirmation hash, or false
	 */
	public function SaveRequestedEmail(User $user)
	{
		$token = $this->GenerateRandomToken();

		$sql = "UPDATE nsa_user SET" .
				" requested_email = " . Sql::ProtectString($this->GetDataConnection(), $user->GetRequestedEmail()) . ", " .
				" requested_email_hash = " . Sql::ProtectString($this->GetDataConnection(), $token) .
				" WHERE user_id = " . Sql::ProtectNumeric($user->GetId(), false);
		$this->Lock("nsa_user");
		$result = $this->GetDataConnection()->query($sql);
		$this->Unlock();

		return $this->GetDataConnection()->isError() ? false : $token;
	}

	/**
	 * Sends an email to the user with a link to confirm a change of email address
	 * @param User $account
	 * @param string hash returned by a call to SaveRequestedEmail() $confirmation_hash
	 * @return bool
	 */
	public function SendChangeEmailAddressEmail(User $account, $confirmation_hash)
	{
		# send email requesting confirmation - validates email address
		require_once 'Zend/Mail.php';
		$o_email = new Zend_Mail('UTF-8');
		$o_email->addTo($account->GetRequestedEmail());
		$o_email->setSubject($this->GetSettings()->GetSiteName() . ' - please confirm your email address');
		$o_email->setFrom($this->GetSettings()->GetEmailAddress(), $this->GetSettings()->GetSiteName());

		$s_greeting = $account->GetName() ? $account->GetName() : 'there';
		$s_confirm_url = 'https://' . $this->GetSettings()->GetDomain() . $this->GetSettings()->GetUrl('AccountConfirmEmail') . 
		'?p=' . $account->GetId() . '&c=' . urlencode($confirmation_hash);

		$o_email->setBodyText('Hi ' . $s_greeting . "!\n\n" .
		wordwrap("Please confirm you'd like to use this email address, " . $account->GetRequestedEmail() . ", to sign in to " .
		$this->GetSettings()->GetSiteName() . " by clicking on the following link, or copying it into your web browser: ", 72, "\n") .
					"\n\n" . $s_confirm_url .
		$this->GetSettings()->GetEmailSignature() . "\n\n" .
		wordwrap('(You are receiving this email because a request to register this email address was made on the ' .
		$this->GetSettings()->GetSiteName() . " website. If you did not request this email, please ignore it. " .
					"You will not get any more emails.)", 72, "\n"));

		try
		{
			$o_email->send();
			return true;
		}
		catch (Zend_Mail_Transport_Exception $e)
		{
			return false;
		}
	}


	/**
	 * @return bool
	 * @param int $i_person_id
	 * @param string $s_hash
	 * @desc Confirm a request to change an email address by matching the specified hash code
	 */
	public function ConfirmEmail($i_person_id, $s_hash)
	{
		# check parameters
		if (!is_numeric($i_person_id)) return false;
		if (!is_string($s_hash)) return false;

		$registered = $this->GetSettings()->GetTable('User');
		$i_person_id = Sql::ProtectNumeric($i_person_id);

		# get the requested email address
		$s_sql = "SELECT COUNT(requested_email) AS total, requested_email FROM $registered " .
		"WHERE requested_email_hash = " . Sql::ProtectString($this->GetDataConnection(), $s_hash) . " AND user_id = " . $i_person_id;

		$result = $this->GetDataConnection()->query($s_sql);

		# succeeded if exactly one record retrieved
        $o_row = $result->fetch();
		if (!$o_row or $o_row->total != 1)
		{
			$result->closeCursor();
			return false;
		}

		$requested_email = $o_row->requested_email;

		# start transaction
		$this->Lock(array($registered));

		# check the new email address isn't already in use
		$s_sql = "SELECT user_id FROM $registered WHERE email = " . Sql::ProtectString($this->GetDataConnection(), $requested_email);
		$result = $this->GetDataConnection()->query($s_sql);

		# succeeded if no records retrieved
		if ($result->fetch())
		{
			$result->closeCursor();
			$this->Unlock();
			return false;
		}

		# update the user's registered email address
		$s_sql = "UPDATE $registered SET " .
				'email = ' . Sql::ProtectString($this->GetDataConnection(), $requested_email) . ", " .
				"date_changed = " . gmdate('U') . " " .
				'WHERE user_id = ' . $i_person_id;

		$result = $this->GetDataConnection()->query($s_sql);

		# succeeded if exactly one record affected
		if ($this->GetDataConnection()->GetAffectedRows() != 1)
		{
			$this->Unlock();
			return false;
		}

		# remove the request to change email
		$s_sql = "UPDATE $registered SET requested_email = NULL, requested_email_hash = NULL " .
		"WHERE requested_email_hash = " . Sql::ProtectString($this->GetDataConnection(), $s_hash) . " AND user_id = " . $i_person_id;

		$result = $this->GetDataConnection()->query($s_sql);

		# succeeded if exactly one record affected
		if ($this->GetDataConnection()->GetAffectedRows() != 1)
		{
			$this->Unlock();
			return false;
		}

		# end transaction
		$this->Unlock();

		return true;
	}

	/**
	 * @return int or false
	 * @param User $user
	 * @desc Save the supplied object to the database, and return the id
	 */
	public function SaveUser($user)
	{
		# check parameters
		if (!$user instanceof User) throw new Exception('Unable to save person');

		# build query
		$table = $this->GetSettings()->GetTable('User');

		$this->Lock($table);

		$s_sql = "UPDATE $table SET " .
			"known_as = " . Sql::ProtectString($this->GetDataConnection(), $user->GetName()) . ", " .
			"name_first = " . Sql::ProtectString($this->GetDataConnection(), $user->GetFirstName()) . ", " .
			"name_last = " . Sql::ProtectString($this->GetDataConnection(), $user->GetLastName()) . ", " .
			"name_sort = " . Sql::ProtectString($this->GetDataConnection(), $user->GetSortName()) . ", " .
			"email = " . Sql::ProtectString($this->GetDataConnection(), $user->GetEmail()) . ", " .
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE user_id = ' . Sql::ProtectNumeric($user->GetId());

		$result = $this->GetDataConnection()->query($s_sql);

		$this->Unlock();

		$success = !$this->GetDataConnection()->isError();

		return ($success) ? $user->GetId() : false;
	}

    /**
     * Replace the security permissions currently assigned to a user with those in the supplied User object
     * @param $user User
     */
    public function SaveUserSecurity(User $user) 
    {
        $user_table = $this->GetSettings()->GetTable("User");
        $roles = $this->GetSettings()->GetTable("UserRole");
        $user_id = Sql::ProtectNumeric($user->GetId(), false, false);
        
        # First update main user table
        $sql = "UPDATE $user_table SET disabled = " . Sql::ProtectBool($user->GetAccountDisabled()) . " WHERE user_id = $user_id";
        $this->GetDataConnection()->query($sql);
        
        # Remove existing roles
        $sql = "DELETE FROM $roles WHERE user_id = " . $user_id;
        $this->GetDataConnection()->query($sql);
        
        # Add replacement roles
        foreach ($user->Roles() as $role) 
        {
            $this->AddUserToRole($user->GetId(), $role->GetRoleId());
        }
    }

	/**
	 * Saves personal information about a user
	 * @param User $user
	 * @return void
	 */
	public function SavePersonalInfo(User $user)
	{
		# Prepare filter
		require_once('text/bad-language-filter.class.php');
		$language = new BadLanguageFilter();

		$users = $this->GetSettings()->GetTable('User');

		$s_sql = 'UPDATE ' . $users . ' SET ' .
					'date_changed = ' . gmdate('U') . ', ' .
					"gender = " . ($user->GetGender() ? $this->SqlString($user->GetGender()) : "NULL") . ", " .
					"occupation = " . $this->SqlString($language->Filter($user->GetOccupation())) . ", " .
					"interests = " . $this->SqlHtmlString($language->Filter($user->GetInterests())) . ", " .
					"location = " . $this->SqlString($language->Filter($user->GetLocation())) . " " .
					'WHERE user_id = ' . Sql::ProtectNumeric($user->GetId(), false);

		$this->Lock(array($users));
		$this->GetDataConnection()->query($s_sql);
		$this->Unlock();
	}
}
?>