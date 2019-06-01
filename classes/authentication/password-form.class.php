<?php
require_once('xhtml/forms/xhtml-form.class.php');

class PasswordForm extends XhtmlForm
{
	/**
	 * Creates a new PasswordForm
	 * @param string $csrf_token
	 */
	public function __construct($csrf_token)
	{
		parent::__construct($csrf_token);
	}

	function OnPreRender()
	{
		$s_email = '';
		if (isset($_GET['address']) and $_GET['address']) $s_email = $_GET['address'];
		else if (isset($_POST['email']) and $_POST['email']) $s_email = $_POST['email'];

		$this->AddControl('<div class="formPart"><label for="email" class="formLabel">Email address:</label>
		<div class="formControl">
			<input type="text" size="30" maxlength="100" id="email" name="email" value="' . Html::Encode($s_email) . '" />
			<input type="submit" class="submit" value="Send reset link" />
		</div>
		</div>' . "\n" .
		'<div></div>' . "\n" .
		'<script>' . "\n" .
		"document.getElementById('email').focus();\n" .
		'</script>' . "\n");
	}
    
    public function CreateValidators()
    {
        require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/email-validator.class.php');
        $this->AddValidator(new RequiredFieldValidator(array('email'), 'Please enter the email address you signed up with'));
        $this->AddValidator(new EmailValidator(array('email'), 'Please enter a valid email address'));
    }
}
 ?>