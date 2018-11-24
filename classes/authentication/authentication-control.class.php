<?php
require_once ('xhtml/xhtml-element.class.php');
require_once ('xhtml/xhtml-anchor.class.php');

class AuthenticationControl extends XhtmlElement
{
	/* @var SiteSettings */
	private $o_settings;

	/* @var User */
	private $o_user;

	function __construct(SiteSettings $o_settings, User $o_user)
	{
		parent::__construct('div');
		$this->o_settings = $o_settings;
		$this->o_user = $o_user;
	}

	function OnPreRender()
	{
		if ($this->o_user->IsSignedIn())
		{
			# Show username
			$this->AddControl(new XhtmlElement('p', 'Welcome ' . $this->o_user->GetName(), "large"));
            
            $token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB)));
            $_SESSION['csrf_token'] = $token;
            $html = '<form method="post" class="sign-out screen" action="/you/sign-out"><div>
            <input type="hidden" name="action" value="signout" />
            <input type="hidden" name="securitytoken" value="' . Html::Encode($token) . '" />
            <input type="submit" value="Sign out" /></div></form>';
            $this->AddControl($html);

			# Build edit profile link
			$o_profile = new XhtmlAnchor('Edit profile', $this->o_settings->GetUrl('AccountEdit'));
			$o_profile->AddAttribute('accesskey', '0');
			$o_profile->SetCssClass('editProfile screen');
			$this->AddControl($o_profile);
		}
		else
		{
		    # Build sign in link
			$o_sign_in = new XhtmlAnchor('Sign in', $this->o_settings->GetFolder('Account'));
			$o_sign_in->AddAttribute('accesskey', '0');
			$o_sign_in->SetCssClass('signIn');
			$this->AddControl($o_sign_in);

			# Build register link
			$register = new XhtmlAnchor('Register', $this->o_settings->GetUrl('AccountCreate'));
			$this->AddControl($register);
		}
	}

}
?>