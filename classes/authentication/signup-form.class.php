<?php
require_once('data/data-edit-control.class.php');

class SignUpForm extends DataEditControl
{
	public function __construct(SiteSettings $o_settings)
	{
		# set up element
		$this->SetDataObjectClass('User');
		parent::__construct($o_settings);
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$o_person = new User();
		if (isset($_POST['known_as'])) $o_person->SetName($_POST['known_as']);
		if (isset($_POST['email'])) $o_person->SetEmail($_POST['email']);
		if (isset($_POST['password1'])) $o_person->SetPassword($_POST['password1']);
		if (isset($_POST['password2'])) $o_person->SetPasswordConfirmation($_POST['password2']);

		$this->SetDataObject($o_person);
	}

	/**
	 * Create and populate the controls used to edit the data object
	 *
	 */
	protected function CreateControls()
	{
		$this->SetButtonText('Register');

		$s_known_as = isset($_POST['known_as']) ? $_POST['known_as'] : '';
		$s_email = isset($_POST['email']) ? $_POST['email'] : '';
		$s_pass1 = isset($_POST['password1']) ? $_POST['password1'] : '';
		$s_pass2 = isset($_POST['password2']) ? $_POST['password2'] : '';
		$s_remember = isset($_POST['remember_me']) ? ' checked="checked"' : '';

		$this->AddControl('<div class="formPart">' . "\n" .
		'<label for="known_as" class="formLabel">Your name</label>' . "\n" .
		'<div class="formControl"><input type="text" size="25" maxlength="210" id="known_as" name="known_as" value="' . Html::Encode($s_known_as) . '" /></div>' . "\n" .
		'</div>' . "\n" .
		'<div class="formPart">' . "\n" .
		'<label for="email" class="formLabel">Your email address</label>' . "\n" .
		'<div class="formControl"><input type="text" size="25" maxlength="100" autocorrect="off" autocapitalize="off" id="email" name="email" value="' . Html::Encode($s_email) . '" /></div>' . "\n" .
		'</div>' . "\n" .
        '<p>Passwords must be at least 10 characters long. We recommend a mix of numbers, letters and punctuation. Avoid passwords you already use elsewhere.</p>' .
		'<div class="formPart">' . "\n" .
		'<label for="password1" class="formLabel">Your password</label>' . "\n" .
		'<div class="formControl"><input type="password" size="25" autocorrect="off" autocapitalize="off" id="password1" name="password1" value="' . Html::Encode($s_pass1) . '" /></div>' . "\n" .
		'</div>' . "\n" .
		'<div class="formPart">' . "\n" .
		'<label for="password2" class="formLabel">Confirm password</label>' . "\n" .
		'<div class="formControl"><input type="password" size="25" autocorrect="off" autocapitalize="off" id="password2" name="password2" value="' . Html::Encode($s_pass2) . '" /></div>' . "\n" .
		'</div>' . "\n" .
        '<label class="aural" for="dummy">Ignore this if you\'re human <input type="text" name="dummy" id="dummy" /></label>'."\n" .
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
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/email-validator.class.php');
		require_once('data/validation/spam-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/compare-validator.class.php');
		require_once('data/validation/regex-validator.class.php');

		$this->a_validators[] = new RequiredFieldValidator(array('known_as'), 'Please enter your name');
		$this->a_validators[] = new LengthValidator('known_as', 'Your name must not be longer than 210 characters', 0, 210);
		$this->a_validators[] = new RegExValidator("known_as", "Sorry that username is not available", "^[A-Za-z0-9]+[a-z][0-9]*[A-Z]{2,3}!?$", ValidatorMode::SingleField(), false);
		$this->a_validators[] = new RegExValidator("known_as", "Sorry that username is not available", "^trx[0-9]+r$", ValidatorMode::SingleField(), false);
        $this->a_validators[] = new RegExValidator("known_as", "Sorry that username is not available", "\n", ValidatorMode::SingleField(), false);
		$this->a_validators[] = new EmailValidator('email', 'Please type your real email address');
        $this->a_validators[] = new RequiredFieldValidator(array('email'), 'Please enter your email address');
		$this->a_validators[] = new LengthValidator('password1', 'Choose a password at least 10 characters long', 10, null);
        $this->a_validators[] = new RequiredFieldValidator(array('password1'), 'Please choose a password');
		$this->a_validators[] = new CompareValidator(array('password1', 'password2'), 'Please confirm your password');
		$this->a_validators[] = new SpamValidator('dummy','Please ignore the final field');
	}
}
?>