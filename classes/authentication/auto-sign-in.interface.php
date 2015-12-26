<?php
/**
 * Provider which allows a user to be signed in automatically
 */
interface IAutoSignIn {
            
    /**
     * Saves the user's choice about whether to use the 'remember me' option
     */
    function SaveAutoSignIn($user_id, $enable_auto_sign_in, $reset_all_devices=false);
  
    /**
     * Gets the id of a user using an up-to-date auto-sign-in cookie if one is found
     * @return int User id if the cookie is found, null otherwise
     */
    function TryNewAutoSignIn();
    
    /**
     * Gets a user using the old auto-sign-in cookie if one is found
     * @return User if the cookie is found, null otherwise
     */
    function TryOldAutoSignIn();
}
?>