<?php
require_once("auto-sign-in.interface.php");
require_once("data/sql.class.php");
require_once("context/site-context.class.php");
require_once("user.class.php");

/**
 * Provides auto sign-in functionality by storing records in a MySQL database and a cookie
 */
class MySqlAutoSignIn implements IAutoSignIn {
        
    /**
     * @var MySqlConnection
     */
    private $connection;
    private $old_cookie_key;
    private $old_cookie_salt;
    
    /**
     * Creates a new instance of MySqlAutoSignIn
     * @param MySqlConnection $connection
     * @param string $old_cookie_key
     * @param string $old_cookie_salt
     */
    public function __construct(MySqlConnection $connection, $old_cookie_key, $old_cookie_salt) {
        $this->connection = $connection;
        $this->old_cookie_key = $old_cookie_key;
        $this->old_cookie_salt = $old_cookie_salt;
    }
    
    /**
     * Saves the user's choice about whether to use the 'remember me' option
     */
    public function SaveAutoSignIn($user_id, $enable_auto_sign_in, $reset_all_devices=false) {

        if ($reset_all_devices) {
            
            $this->DeleteAutoSignInToken($user_id);
        }
            
        if ($enable_auto_sign_in) 
        {
            $token_value = $this->CreateAutoSignInDetails($user_id); 
            $this->SaveAutoSignInToken($user_id, $token_value);
            $this->SaveAutoSignInCookie($token_value);
        }
        else 
        {
            $device = $this->DeleteAutoSignInCookie();
            if ($device) {
                $this->DeleteAutoSignInToken($user_id, $device);
            }
        }        
    }
    
    /**
     * Deletes the auto-sign-in token from the database
     */
    private function DeleteAutoSignInToken($user_id, $device = null)
    {
        $sql = "DELETE FROM nsa_auto_sign_in " .
               "WHERE user_id = " . Sql::ProtectNumeric($user_id, false);
               
        if (!is_null($device)) {
           $sql .= " AND device = " . Sql::ProtectNumeric($device);
        }       

        $this->connection->query('LOCK TABLES nsa_auto_sign_in WRITE');
        $this->connection->query($sql);
        $this->connection->query('UNLOCK TABLES');
    }
        
    /**
     * Creates a token, device and expiry date for auto sign in
     * @param int $user_id
     * @return array
     */
    private function CreateAutoSignInDetails($user_id)
    {
        $value = array();

        $value['token'] = $this->GenerateRandomToken();
        
        # If there's already a cookie for this device, use the same id.
        # If not, use the next available id in the database.
        if (isset($_COOKIE['user']) and is_string($_COOKIE['user'])) {
            $cookie_value = $this->ParseAutoSignInCookie($_COOKIE['user']);
            $value['device'] = $cookie_value['device'];
        } else {
                
            $sql = "SELECT IFNULL(MAX(device)+1,1) as next_device_id FROM nsa_auto_sign_in WHERE user_id = " . Sql::ProtectNumeric($user_id);
            $result = $this->connection->query($sql);
            $row = $result->fetch();
            $value['device'] = $row->next_device_id;
        }
 
        $i_one_year = 60*60*24*365;
        $value['expires'] = gmdate('U')+$i_one_year;

        return $value;
    }
    
    /**
     * Create a genuinely random token which can be used to authenticate time-limited requests
     */
    private function GenerateRandomToken() 
    {
        return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB)));
    }
    
    /**
     * Saves an auto-sign-in token to the database, and returns the new token
     * @param int $user_id
     */
    private function SaveAutoSignInToken($user_id, array $token_value)
    {
        $sql = "INSERT INTO nsa_auto_sign_in SET
        user_id = " . Sql::ProtectNumeric($user_id, false) . ",
        token = " . Sql::ProtectString($this->connection, $token_value['token']) . ",
        device = " . Sql::ProtectNumeric($token_value['device']) . ",
        expires = " . Sql::ProtectNumeric($token_value['expires']) . " 
        ON DUPLICATE KEY UPDATE
        token = " . Sql::ProtectString($this->connection, $token_value['token']) . ",
        expires = " . Sql::ProtectNumeric($token_value['expires']);

        $this->connection->query('LOCK TABLES nsa_auto_sign_in WRITE');
        $this->connection->query($sql);
        $this->connection->query('UNLOCK TABLES');
    }
    
    /**
     * Saves the provided data into a cookie
     *
     * @param array $token_value
     */
    private function SaveAutoSignInCookie(array $token_value)
    {
        if (!headers_sent())
        {
            $this->DeleteAutoSignInCookie();
    
            setcookie('user', $token_value['device'] . ";" . $token_value['token'], $token_value['expires'], '/', "", true, true);
        }
    }
    
    /**
     * Add an instruction to the HTTP Response to remove all cookies for this site
     * @return int device
     */
    private function DeleteAutoSignInCookie()
    {
        $device = false;
                
        # Delete old and new auto-sign-in cookies
        if (isset($_COOKIE['user']) and is_string($_COOKIE['user'])) {
        
            $value = $this->ParseAutoSignInCookie($_COOKIE['user']);
            $device = $value['device'];
        
            setcookie('user', '', gmdate('U'), '/');
        }
        
        if (isset($_COOKIE['user_3uNGNNLT'])) {
            setcookie('user_3uNGNNLT', '', gmdate('U'), '/');
        }
        
        return $device;
    }
    
    /**
     * Parses the device and token from an auto sign-in cookie
     * @param string $cookie_value
     * @return array
     */
    private function ParseAutoSignInCookie($cookie_value) 
    {
         # In wp-settings.php WordPress runs add_magic_quotes() over every value in $_COOKIE. 
        # Need to undo that otherwise it's not the value we put there. 
        if (SiteContext::IsWordPress()) {
         
            $cookie_value = stripslashes($cookie_value);
        }
        
        $value = array();
        $separator = strpos($cookie_value, ';');
        if ($separator and strlen($cookie_value) > $separator+1) {
            $value['device'] = (int)substr($cookie_value, 0, $separator);
            $value['token'] = substr($cookie_value, $separator+1);
        }
        return $value;
    }
  
    /**
     * Gets the id of a user using an up-to-date auto-sign-in cookie if one is found
     * @return int User id if the cookie is found, null otherwise
     */
    public function TryNewAutoSignIn()
    {      
        if (isset($_COOKIE['user']) and is_string($_COOKIE['user']) and $_COOKIE['user'])
        {
            $cookie = $this->ParseAutoSignInCookie($_COOKIE['user']);
            
            # Don't assume 'user' cookie was set by this site. Could be hacker value.
            if (isset($cookie['device']) and $cookie['device'] and isset($cookie['token']) and $cookie['token']) {
                
                $sql = "SELECT COUNT(user_id) AS total, user_id FROM nsa_auto_sign_in 
                WHERE device = " . Sql::ProtectNumeric($cookie['device']) . " 
                AND token = " . Sql::ProtectString($this->connection, $cookie['token']) . "
                AND expires >= " . gmdate('U'); 
                
                $result = $this->connection->query($sql);
                $row = $result->fetch();
                if ($row and $row->total == 1) {
                    return (int)$row->user_id;
                }
            }
                 
        }        
        
        return null;
    }
 
        
    /**
     * Gets a user using the old auto-sign-in cookie if one is found
     * @return User if the cookie is found, null otherwise
     */
    public function TryOldAutoSignIn()
    {
        # Try to read encrypted cookie
        if (isset($_COOKIE['user_3uNGNNLT']) and $_COOKIE['user_3uNGNNLT'])
        {
            # In wp-settings.php WordPress runs add_magic_quotes() over every value in $_COOKIE. Need to undo that
            # otherwise it's not the value we put there and, when we decrypt the wrong value, it's gibberish
            if (SiteContext::IsWordPress()) $_COOKIE['user_3uNGNNLT'] = stripslashes($_COOKIE['user_3uNGNNLT']);

            $user = $this->DecryptOldAutoSignInCookie($_COOKIE['user_3uNGNNLT']);
            if ($user->GetEmail() and $user->GetPassword())
            {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Extracts data encrypted in cookie
     *
     * @param string $encrypted_cookie
     * @return User
     */
    private function DecryptOldAutoSignInCookie($encrypted_cookie)
    {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $decrypted_text = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->old_cookie_key, $encrypted_cookie, MCRYPT_MODE_ECB, $iv);
        $len = strlen($decrypted_text);

        $user = new User();

        $pos = strpos($decrypted_text, $this->old_cookie_salt);
        if ($pos)
        {
            # Get username
            $user->SetEmail(substr($decrypted_text, 0, $pos));

            # Get password hash
            $pos = $pos+strlen($this->old_cookie_salt);
            if ($len >= $pos+32) # 32 is length of an MD5 hash
            {
                $user->SetPassword(substr($decrypted_text, $pos, 32));
            }
        }

        return $user;
    }
}
?>