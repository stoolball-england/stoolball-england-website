<?php
require_once ('xhtml/xhtml-element.class.php');
require_once ('xhtml/xhtml-anchor.class.php');

class AuthenticationControl extends XhtmlElement
{
	/* @var SiteSettings */
	private $settings;

	/* @var User */
	private $user;

	private $csrf_token;

	public function __construct(SiteSettings $settings, User $user, $csrf_token)
	{
		parent::__construct('div');
		$this->settings = $settings;
		$this->user = $user;
		$this->csrf_token = $csrf_token;
	}

	function OnPreRender()
	{
		if ($this->user->IsSignedIn())
		{
			# Show username
			$this->AddControl(new XhtmlElement('p', 'Welcome ' . $this->user->GetName(), "large"));
            
            $html = '<form method="post" class="sign-out screen" action="/you/sign-out"><div>
            <input type="hidden" name="action" value="signout" />
            <input type="hidden" name="securitytoken" value="' . Html::Encode($this->csrf_token) . '" />
            <input type="submit" value="Sign out" /></div></form>';
            $this->AddControl($html);

			# Build edit profile link
			$o_profile = new XhtmlAnchor('Edit profile', $this->settings->GetUrl('AccountEdit'));
			$o_profile->AddAttribute('accesskey', '0');
			$o_profile->SetCssClass('editProfile screen');
			$this->AddControl($o_profile);
		}
		else
		{
		    # Build sign in link
			$o_sign_in = new XhtmlAnchor('Sign in', $this->settings->GetFolder('Account'));
			$o_sign_in->AddAttribute('accesskey', '0');
			$o_sign_in->SetCssClass('signIn');
			$this->AddControl($o_sign_in);

			# Build register link
			$register = new XhtmlAnchor('Register', $this->settings->GetUrl('AccountCreate'));
			$this->AddControl($register);
		}
	}

}
?>