<?php
require_once('data/data-edit-control.class.php');
require_once 'Zend/Mail.php';

class EmailForm extends DataEditControl
{
    function __construct(SiteSettings $settings)
	{
		$this->SetDataObjectClass('Zend_Mail');
		$this->SetButtonText('Send email');

        parent::__construct($settings);
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data object this control is editing
	*/
	function BuildPostedDataObject()
	{
		$email = new Zend_Mail('UTF-8');
		if (isset($_POST['from'])) $email->setFrom($_POST['from'], html_entity_decode((isset($_POST['fromName']) and $_POST['fromName']) ? $_POST['fromName'] : $_POST['from']));
		if (isset($_POST['subject']) and $_POST['subject'])
		{
			$email->setSubject(html_entity_decode($_POST['subject']));
		}
        $body = isset($_POST['body']) ? $_POST['body'] : '';
        $email->setBodyText(html_entity_decode($body));
		$this->SetDataObject($email);
	}


	function CreateControls()
	{
		/* @var $email Zend_Mail */

		$email = $this->GetDataObject();
		if (!is_object($email)) $email = new Zend_Mail('UTF-8');

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
		
		$this->a_validators[] = new RequiredFieldValidator(array('fromName',"from","subject","body"), 'Please complete all the fields');
		$this->a_validators[] = new LengthValidator('fromName', 'Please make your name shorter', 0, 100);
		$this->a_validators[] = new PlainTextValidator('fromName', 'Please use only letters, numbers and simple punctuation in your name');
		$this->a_validators[] = new LengthValidator('from', 'Please make your email address shorter', 0, 250);
		$this->a_validators[] = new EmailValidator(array('from'), 'Please enter your real email address');
		$this->a_validators[] = new LengthValidator('subject', 'Please make your subject shorter', 0, 250);
		$this->a_validators[] = new PlainTextValidator('subject', 'Please use only letters, numbers and simple punctuation in your subject');
        $this->a_validators[] = new LengthValidator('body', 'Please make your message shorter', 0, 10000);
		$this->a_validators[] = new PlainTextValidator('body', 'Please use only letters, numbers and simple punctuation in your message');
    }
}
?>