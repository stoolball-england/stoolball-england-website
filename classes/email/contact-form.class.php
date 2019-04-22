<?php
require_once('data/data-edit-control.class.php');

class ContactForm extends DataEditControl
{
	private $a_addresses;
	private $a_addresses_md5;

	/**
	 * Creates a new ContactForm
	 * @param SiteSettings $settings
	 * @param array $a_addresses
	 * @param string $csrf_token
	 */
	public function __construct($settings, $a_addresses, $csrf_token)
	{
		if (!is_array($a_addresses) or !count($a_addresses)) throw new Exception('Must supply at least one contact address for the email form');

		# set up element
		$this->SetDataObjectClass('Swift_Message');
		$this->SetButtonText('Send email');

		$this->a_addresses = $a_addresses;
		$this->a_addresses_md5 = array();
		foreach ($this->a_addresses as $s_addr => $s_text) $this->a_addresses_md5[md5($s_addr)] = $s_addr;

		parent::__construct($settings, $csrf_token);
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data object this control is editing
	*/
	function BuildPostedDataObject()
	{
		$o_email = new Swift_Message();
		if (isset($_POST['to']) and key_exists($_POST['to'], $this->a_addresses_md5)) $o_email->setTo([$this->a_addresses_md5[$_POST['to']]]);
		if (isset($_POST['from'])) $o_email->setFrom([$_POST['from'] => (isset($_POST['fromName']) and $_POST['fromName']) ? $_POST['fromName'] : $_POST['from']]);
		if (isset($_POST['subject']) and $_POST['subject'])
		{
			$o_email->setSubject($_POST['subject']);
		}
		else
		{
			$o_email->setSubject($this->GetSettings()->GetSiteName() . ' contact form');
		}
		$body = isset($_POST['body']) ? $_POST['body'] : '';
		if (isset($_POST['reply'])) $body .= "\n\n(The sender of this message has asked for a reply.)";
		$o_email->setBody($body);
		if (isset($_POST['cc'])) $o_email->setCC([$_POST['from']]);
		$this->SetDataObject($o_email);
	}


	function CreateControls()
	{
        $this->AddCssClass('legacy-form');
		
		# Who to send to
		$i_address_count = count($this->a_addresses);
		if ($i_address_count > 1)
		{
			$o_who = new XhtmlElement('fieldset', new XhtmlElement('legend', 'Why are you contacting us?'));
			$o_who->SetCssClass('radioButtonList');
			$i = 0;
			foreach($this->a_addresses as $s_addr => $s_reason)
			{
				$o_radio = new XhtmlElement('input');
				$o_radio->SetEmpty(true);
				$o_radio->AddAttribute('type', 'radio');
				$o_radio->AddAttribute('name', 'to');
				$o_radio->AddAttribute('value', md5($s_addr));
				$o_radio->SetXhtmlId('to' . $i);
				if ($this->IsPostback())
				{
					if(isset($_POST['to']) and $_POST['to'] == md5($s_addr)) $o_radio->AddAttribute('checked', 'checked');
				}
				else
				{
					if (!$i) $o_radio->AddAttribute('checked', 'checked');
				}

				$o_label = new XhtmlElement('label', $o_radio);
				$o_label->AddAttribute('for', $o_radio->GetXhtmlId());
				$o_label->AddControl($s_reason);
				$o_who->AddControl($o_label);
				$i++;
			}
			$this->AddControl($o_who);
		}



		# Your details
		$o_details = new XhtmlElement('fieldset');
		$o_details->AddControl(new XhtmlElement('legend', 'Your details'));
		$this->AddControl($o_details);

		$o_name = new TextBox('fromName', '', $this->IsValidSubmit());
		$o_name->SetMaxLength(100);
		$o_name_part = new FormPart('Name', $o_name);
		$o_details->AddControl($o_name_part);

		$o_from = new TextBox('from', '', $this->IsValidSubmit());
		$o_from->AddAttribute("type", "email");
		$o_from->SetMaxLength(250);
		$o_from_part = new FormPart('Email address', $o_from);
		$o_from_part->SetIsRequired(true);
		$o_details->AddControl($o_from_part);

		# Your email
		$o_message = new XhtmlElement('fieldset');
		$o_message->AddControl(new XhtmlElement('legend', 'Your email'));
		$this->AddControl($o_message);

		$o_subj = new TextBox('subject', '', $this->IsValidSubmit());
		$o_subj->SetMaxLength(250);
		$o_subj_part = new FormPart('Subject', $o_subj);
		$o_message->AddControl($o_subj_part);

		$o_body = new TextBox('body', '', $this->IsValidSubmit());
		$o_body->SetMode(TextBoxMode::MultiLine());
		$o_body_part = new FormPart('Your message', $o_body);
		$o_body_part->SetIsRequired(true);
		$o_message->AddControl($o_body_part);

		# Options
		$o_opt = new XhtmlElement('fieldset', null, "radioButtonList");
		$o_opt->AddControl(new XhtmlElement('legend', 'Options', "aural"));
		$this->AddControl($o_opt);

		$o_opt->AddControl(new CheckBox('cc', 'Send me a copy', 1, false, $this->IsValidSubmit()));
		$o_opt->AddControl(new CheckBox('reply', "I'd like a reply", 1, true, $this->IsValidSubmit()));
	}


	/**
	* @return void
	* @desc Create DataValidator objects to validate the edit control
	*/
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/email-validator.class.php');
		require_once('data/validation/requires-other-fields-validator.class.php');

		$this->AddValidator(new RequiredFieldValidator(array('to'), 'Please say why you\'re contacting us'));
		$this->AddValidator(new LengthValidator('fromName', 'Please make your name shorter', 0, 100));
		$this->AddValidator(new RequiredFieldValidator('from', 'Please include your email address'));
		$this->AddValidator(new LengthValidator('from', 'Please make your email address shorter', 0, 250));
		$this->AddValidator(new EmailValidator(array('from'), 'Please enter your real email address'));
		$this->AddValidator(new LengthValidator('subject', 'Please make your subject shorter', 0, 250));
		$this->AddValidator(new RequiredFieldValidator(array('body'), 'Type your message before clicking <cite>Send email</cite>'));
		$this->AddValidator(new LengthValidator('body', 'Please make your message shorter', 0, 10000));
		$this->AddValidator(new RequiresOtherFieldsValidator(array('cc', 'from'), 'If you want a copy of the email, please tell us your email address'));
	}
}
?>