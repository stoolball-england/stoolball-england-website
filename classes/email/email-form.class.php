<?php
require_once('data/data-edit-control.class.php');
require_once('email/email-message.class.php');

class EmailForm extends DataEditControl
{
	/**
	 * Creates a new EmailForm
	 * @param SiteSettings $settings
	 * @param string $csrf_token
	 */
    public function __construct(SiteSettings $settings, $csrf_token)
	{
		$this->SetDataObjectClass('EmailMessage');
		$this->SetButtonText('Send email');

        parent::__construct($settings, $csrf_token);
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data object this control is editing
	*/
	function BuildPostedDataObject()
	{
		$email = new EmailMessage();
		
		if (isset($_POST['from']) and is_string($_POST['from'])) {
	        $fromName = html_entity_decode((isset($_POST['fromName']) and is_string($_POST['fromName']) and $_POST['fromName']) ? $_POST['fromName'] : $_POST['from']);
			$email->SetFromAddress($_POST['from']);
			$email->SetFromName($fromName);
        }
		if (isset($_POST['subject']) and is_string($_POST['subject']) and $_POST['subject'])
		{
			$email->SetSubject(html_entity_decode($_POST['subject']));
		}
        $body = (isset($_POST['body']) and is_string($_POST['body'])) ? $_POST['body'] : '';
        $email->SetBody(html_entity_decode($body));
		$this->SetDataObject($email);
	}


	function CreateControls()
	{
		# Your details
        $name = new TextBox('fromName', '', $this->IsValidSubmit());
		$name->SetMaxLength(100);
		$name_part = new FormPart('Your name', $name);
		$this->AddControl($name_part);

		$from = new TextBox('from', '', $this->IsValidSubmit());
		$from->AddAttribute("type", "email");
		$from->SetMaxLength(250);
		$from_part = new FormPart('Your email address', $from);
		$this->AddControl($from_part);

		# Your email
        $subj = new TextBox('subject', '', $this->IsValidSubmit());
		$subj->SetMaxLength(250);
		$subj_part = new FormPart('Subject', $subj);
		$this->AddControl($subj_part);

		$body = new TextBox('body', '', $this->IsValidSubmit());
		$body->SetMode(TextBoxMode::MultiLine());
		$body_part = new FormPart('Your message', $body);
        $this->AddControl($body_part);
    }


	/**
	* @return void
	* @desc Create DataValidator objects to validate the edit control
	*/
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/plain-text-validator.class.php');
		require_once('data/validation/email-validator.class.php');
		
		$this->AddValidator(new RequiredFieldValidator(array('fromName',"from","subject","body"), 'Please complete all the fields'));
		$this->AddValidator(new LengthValidator('fromName', 'Please make your name shorter', 0, 100));
		$this->AddValidator(new PlainTextValidator('fromName', 'Please use only letters, numbers and simple punctuation in your name'));
		$this->AddValidator(new LengthValidator('from', 'Please make your email address shorter', 0, 250));
		$this->AddValidator(new EmailValidator(array('from'), 'Please enter your real email address'));
		$this->AddValidator(new LengthValidator('subject', 'Please make your subject shorter', 0, 250));
		$this->AddValidator(new PlainTextValidator('subject', 'Please use only letters, numbers and simple punctuation in your subject'));
        $this->AddValidator(new LengthValidator('body', 'Please make your message shorter', 0, 10000));
		$this->AddValidator(new PlainTextValidator('body', 'Please use only letters, numbers and simple punctuation in your message'));
    }
}
?>