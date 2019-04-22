<?php
require_once('data/data-edit-control.class.php');

class EssentialInfoForm extends DataEditControl
{
	private $user;
    private $authentication_manager;
    private $suppress_email_message = false;

	/**
	 * Creates a new EssentialInfoForm
	 * @param SiteSettings $settings
	 * @param User $user
	 * @param AuthenticationManager $authentication_manager
	 * @param string $csrf_token
	 */
	public function __construct(SiteSettings $settings, User $user, AuthenticationManager $authentication_manager, $csrf_token)
	{
		$this->user = $user;
        $this->authentication_manager = $authentication_manager;
		$this->SetDataObjectClass('User');

		parent::__construct($settings, $csrf_token);
	}

	/**
	 * (non-PHPdoc)
	 * @see data/DataEditControl#BuildPostedDataObject()
	 */
	protected function BuildPostedDataObject()
	{
		# Prepare swear filter
		require_once('text/bad-language-filter.class.php');
		$o_filter = new BadLanguageFilter();

		# Build object
		$user = AuthenticationManager::GetUser();
		$user->SetName($o_filter->Filter($_POST['known_as']));
		$user->SetFirstName($o_filter->Filter($_POST['first_name']));
		$user->SetLastName($o_filter->Filter($_POST['last_name']));
		$user->SetRequestedEmail($_POST['email']);
		$user->SetPassword($_POST['password1']);
		$user->SetRequestedPassword($_POST['password2']);
		$user->SetAutoSignIn(isset($_POST['remember_me']));

		$this->SetDataObject($user);
	}
    
    /**
     * Allow the host page to suppress the message about waiting to change the email address
     * @param bool $suppress
     */
    public function SuppressEmailChangeMessage($suppress) {
        $this->suppress_email_message = (bool)$suppress;
    }

	/**
	 * (non-PHPdoc)
	 * @see data/DataEditControl#CreateControls()
	 */
	protected function CreateControls()
	{
		$s_known_as = isset($_POST['known_as']) ? $_POST['known_as'] : $this->user->GetName();
		$s_first_name = isset($_POST['first_name']) ? $_POST['first_name'] : $this->user->GetFirstName();
		$s_last_name = isset($_POST['last_name']) ? $_POST['last_name'] : $this->user->GetLastName();
		$s_email = isset($_POST['email']) ? $_POST['email'] : $this->user->GetEmail();
		$s_remember = ((isset($_POST['remember_me']) and $_POST['remember_me']) or $this->authentication_manager->HasCookies()) ? ' checked="checked"' : '';

		$this->AddControl('<fieldset>' . "\n" .
		'<legend>Your name</legend>' . "\n" .
		'<div class="formPart">' . "\n" .
		'<label for="known_as" class="formLabel">Nickname</label>' . "\n" .
		'<div class="formControl"><input type="text" size="50" maxlength="255" name="known_as" id="known_as" value="' . Html::Encode($s_known_as) . '" /></div>' . "\n" .
		'</div>' . "\n" .
		'<div class="formPart">' . "\n" .
		'<label for="first_name" class="formLabel">First name</label>' . "\n" .
		'<div class="formControl"><input type="text" size="50" maxlength="100" name="first_name" id="first_name" value="' . Html::Encode($s_first_name) . '" /></div>' . "\n" .
		'</div>' . "\n" .
		'<div class="formPart">' . "\n" .
		'<label for="last_name" class="formLabel">Last name</label>' . "\n" .
		'<div class="formControl"><input type="text" size="50" maxlength="100" name="last_name" id="last_name" value="' . Html::Encode($s_last_name) . '" /></div>' . "\n" .
		'</div>' . "\n" . 
		'</fieldset>' . "\n" .

        '<fieldset>' . "\n" .
        '<legend>Change your email address</legend>' . 
        '<div class="formPart">' . "\n" .
        '<label for="email" class="formLabel">Email address</label>' . "\n" .
        '<div class="formControl"><input type="email" size="50" maxlength="100" name="email" id="email" autocorrect="off" autocapitalize="off" value="' . Html::Encode($s_email) . '" /></div>' . "\n" .
        '</div>');
        
        if ($this->user->GetRequestedEmail() && $this->user->GetEmail() != $this->user->GetRequestedEmail() and $this->user->GetPassword() and !$this->suppress_email_message)
        {
            $this->AddControl("<p>Waiting to change email address to <strong>" . Html::Encode($this->user->GetRequestedEmail()) . "</strong>. Please check your email.</p>");
        }

        $this->AddControl('</fieldset>' . "\n" .
        '<fieldset>' . "\n" .
        '<legend>Change your password</legend>' . "\n" .
        '<p>Passwords must be at least 10 characters long. We recommend a mix of numbers, letters and punctuation. Avoid passwords you already use elsewhere.</p>' .
		'<div class="formPart">' . "\n" .
		'<label for="password2" class="formLabel">New password</label>' . "\n" .
		'<div class="formControl"><input type="password" size="25" name="password2" id="password2" value="" autocorrect="off" autocapitalize="off" /></div>' . "\n" .
		'</div>' . "\n" .
		'<div class="formPart">' . "\n" .
		'<label for="password3" class="formLabel">Confirm new password</label>' . "\n" .
		'<div class="formControl"><input type="password" size="25" name="password3" id="password3" value="" autocorrect="off" autocapitalize="off" /></div>' . "\n" .
		'</div>' . "\n" .
		'</fieldset>' . "\n" .

        '<fieldset>' . "\n" .
        '<legend>Sign in automatically</legend>' . "\n" .
		'<div class="radioButtonList"><label for="remember_me"><input type="checkbox" name="remember_me" id="remember_me" value="1"' . $s_remember . ' /> Keep me signed in <small>(uses a cookie)</small></label></div>' . "\n" .
        '</fieldset>' . "\n" .

        '<fieldset>' . "\n" .
        '<legend>Confirm your current password</legend>' . "\n" .
        '<p>To change your password or email address, or to sign in automatically, please enter your current password.</p>' .
        '<div class="formPart">' . "\n" .
        '<label for="password1" class="formLabel">Current password</label>' . "\n" .
        '<div class="formControl"><input type="password" size="25" maxlength="12" name="password1" id="password1" value="" autocorrect="off" autocapitalize="off" /></div>' . "\n" .
        '</div>' . "\n" .
        '</fieldset>' . "\n" .

		'<script>' . "\n" .
		'<!--' . "\n" .
		"document.getElementById('known_as').focus();\n" .
		'//-->' . "\n" .
		'</script>' . "\n");
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/email-validator.class.php');
		require_once('authentication/email-registered-validator.class.php');
		require_once('data/validation/compare-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/requires-other-fields-validator.class.php');

		$this->AddValidator(new RequiredFieldValidator('known_as', 'Please enter a nickname'));
		$this->AddValidator(new RequiredFieldValidator('email', 'Please enter your email address'));
		$this->AddValidator(new EmailValidator('email', 'Please enter a valid email address'));
		$this->AddValidator(new EmailRegisteredValidator('email', $this->authentication_manager));
		$this->AddValidator(new LengthValidator('password2', 'Choose a password at least 10 characters long', 10, null));
		$this->AddValidator(new RequiresOtherFieldsValidator(array('password2', 'password1'), 'Please fill in all three password fields to change your password'));
		$this->AddValidator(new CompareValidator(array('password2', 'password3'), 'Please confirm your new password'));
		if (!$this->authentication_manager->HasCookies())
		{
			$this->AddValidator(new RequiresOtherFieldsValidator(array('remember_me', 'password1'), "Please enter your current password to enable 'Keep me signed in'."));
		}
	}
}
?>