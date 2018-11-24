<?php
require_once('xhtml/forms/xhtml-form.class.php');

class SignInForm extends XhtmlForm
{
	private $settings;
    private $authentication_manager;
    

	public function __construct(SiteSettings $settings, AuthenticationManager $authentication_manager)
	{
		$this->settings = $settings;
        $this->authentication_manager = $authentication_manager;

		parent::__construct();
	}

	function OnPreRender()
	{
		# get email address to populate form
		$s_email = '';
		if(isset($_GET['address']) and $_GET['address']) $s_email = $_GET['address'];
		else if(isset($_POST['email']) and $_POST['email']) $s_email = $_POST['email'];

		# get password to populate form
		$s_pass = (isset($_POST['password']) and $_POST['password']) ? $_POST['password'] : '';

		# remember whether to remember
		$s_remember = $this->authentication_manager->HasCookies() ? ' checked="checked"' : '';

		# work out which page to redirect to once signed in
		if (isset($_GET['page']) and $_GET['page']) $s_page_to_return_to = $_GET['page'];
		else if(isset($_POST['page']) and $_POST['page']) $s_page_to_return_to = $_POST['page'];
		# if neither of the above specified, go back to the http referer unless it's the Register or sign in page.
		else if (isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_REFERER'] and strstr($_SERVER['HTTP_REFERER'],$this->settings->GetUrl('AccountCreate')) === false and strstr($_SERVER['HTTP_REFERER'],$this->settings->GetFolder('Account')) === false)
		{
			$s_page_to_return_to = $_SERVER['HTTP_REFERER'];
		}
		else $s_page_to_return_to = $this->settings->GetClientRoot(); # home page is last resort

		# build up form xhtml
		$this->AddControl('<div class="formPart"><label for="email2" class="formLabel">Your email address</label> <div class="formControl"><input type="email" size="30" maxlength="100" autocorrect="off" autocapitalize="off" id="email2" name="email" value="' . Html::Encode($s_email) . '" /></div></div>' . "\n" .
			'<div class="formPart"><label for="password2" class="formLabel">Your password</label> <div class="formControl"><input type="password" size="30" autocorrect="off" autocapitalize="off" id="password2" name="password" value="' . Html::Encode($s_pass) . '" /></div></div>' . "\n" .
			'<div class="formPart"><div class="formControl"><label for="remember_me"><input type="checkbox" name="remember_me" id="remember_me"' . Html::Encode($s_remember) . ' /> Keep me signed in <small>(uses a cookie)</small></label></div></div>' . "\n" .
			'<input type="hidden" name="page" value="' . Html::Encode($s_page_to_return_to) . '" />' . "\n" .
			'<div><input type="submit" class="submit" value="Sign in" /></div>' . "\n" .
			'<p id="authGetPassword"><a href="/you/request-password-reset">Reset password</a></p>' . "\n" .
		'<script>' . "\n" .
			'<!--' . "\n" .
			"document.getElementById('email2').focus();\n" .
			'//-->' . "\n" .
			'</script>' . "\n");
	}
}
?>