<?php
require_once('data/data-edit-control.class.php');

class ResetPasswordForm extends DataEditControl
{
	private $o_person;

	public function __construct(SiteSettings $settings, User $o_person)
	{
		$this->o_person = $o_person;
		$this->SetDataObjectClass('User');
        $this->SetButtonText('Reset password');

		parent::__construct($settings);
	}

	/**
	 * (non-PHPdoc)
	 * @see data/DataEditControl#BuildPostedDataObject()
	 */
	protected function BuildPostedDataObject()
	{
		$o_person = AuthenticationManager::GetUser();
		$o_person->SetRequestedPassword($_POST['password2']);

		$this->SetDataObject($o_person);
	}

	/**
	 * (non-PHPdoc)
	 * @see data/DataEditControl#CreateControls()
	 */
	protected function CreateControls()
	{
		$this->AddControl(
		'<p>Passwords must be at least 10 characters long. We recommend a mix of numbers, letters and punctuation. Avoid passwords you already use elsewhere.</p>' .
		'<div class="formPart">' . "\n" .
		'<label for="password2" class="formLabel">New password</label>' . "\n" .
		'<div class="formControl"><input type="password" size="25" name="password2" id="password2" value="" autocorrect="off" autocapitalize="off" /></div>' . "\n" .
		'</div>' . "\n" .
		'<div class="formPart">' . "\n" .
		'<label for="password3" class="formLabel">Confirm new password</label>' . "\n" .
		'<div class="formControl"><input type="password" size="25" name="password3" id="password3" value="" autocorrect="off" autocapitalize="off" /></div>' . "\n" .
		'</div>' . "\n" .

		'<script>' . "\n" .
		"document.getElementById('password').focus();\n" .
		'</script>' . "\n");
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/compare-validator.class.php');
		require_once('data/validation/length-validator.class.php');

		$this->AddValidator(new RequiredFieldValidator('password2', 'Please enter your new password'));
		$this->AddValidator(new LengthValidator('password2', 'Choose a password at least 10 characters long', 10, null));
		$this->AddValidator(new CompareValidator(array('password2', 'password3'), 'Please confirm your new password'));
	}
}
?>