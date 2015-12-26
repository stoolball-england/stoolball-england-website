<?php
/**
 * Enum representing the result of an attempt to sign in
 *
 */
class SignInResult
{
	/**
	 * SignInResult is a static class
	 *
	 */
	private function __construct(){}

	/**
	 * The username and password did not match a registered account
	 *
	 * @return int
	 */
	public static function NotFound() { return 0; }

	/**
	 * The username and password matched a registered account, but the account has not been activated
	 *
	 * @return int
	 */
	public static function NotActivated() { return 1; }

	/**
	 * The username and password matched an account that has been disabled
	 *
	 * @return int
	 */
	public static function AccountDisabled() { return 2; }

	/**
	 * The username and password matched an active account and the user is signed in
	 *
	 * @return int
	 */
	public static function Success() { return 3; }
}
?>