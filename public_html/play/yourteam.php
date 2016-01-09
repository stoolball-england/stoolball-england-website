<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('xhtml/forms/xhtml-form.class.php');
require_once('xhtml/forms/textbox.class.php');
require_once('xhtml/forms/radio-button.class.php');
require_once('xhtml/forms/button.class.php');

class CurrentPage extends StoolballPage
{
	var $form;
	var $b_success;

	function OnPageInit()
	{
		parent::OnPageInit();

		# Set up form, which must exist to be validated
		$this->form = new XhtmlForm();

		$fs1 = new XhtmlElement('fieldset', new XhtmlElement('legend', 'Your team name'));
		$this->form->AddControl($fs1);

		$o_team = new TextBox('team', isset($_POST['team']) ? $_POST['team'] : '');
		$o_team->SetMaxLength(200);
		$o_team_part = new FormPart('Team name', $o_team);
		$o_team_part->SetIsRequired(true);
		$fs1->AddControl($o_team_part);

		$o_club = new TextBox('club', isset($_POST['club']) ? $_POST['club'] : '');
		$o_club->SetMaxLength(200);
		$o_club_part = new FormPart('Club (if different)', $o_club);
		$fs1->AddControl($o_club_part);

		$fs2 = new XhtmlElement('fieldset', new XhtmlElement('legend', 'Where and when you play'));
		$this->form->AddControl($fs2);

		$o_ground = new TextBox('ground', isset($_POST['ground']) ? $_POST['ground'] : '');
		$o_ground->SetMode(TextBoxMode::MultiLine());
		$o_ground_part = new FormPart('Address of playing field', $o_ground);
		$o_ground_part->SetIsRequired(true);
		$fs2->AddControl($o_ground_part);

		$o_prac = new TextBox('pracNight', isset($_POST['pracNight']) ? $_POST['pracNight'] : '');
		$o_prac->SetMaxLength(50);
		$o_prac_part = new FormPart('Practice night', $o_prac);
		$fs2->AddControl($o_prac_part);

		$o_match = new TextBox('matchNight', isset($_POST['matchNight']) ? $_POST['matchNight'] : '');
		$o_match->SetMaxLength(100);
		$o_match_part = new FormPart('Match nights', $o_match);
		$fs2->AddControl($o_match_part);

		$o_league = new TextBox('leagues', isset($_POST['leagues']) ? $_POST['leagues'] : '');
		$o_league->SetMode(TextBoxMode::MultiLine());
		$o_league_part = new FormPart('League(s) or friendlies you play in', $o_league);
		$fs2->AddControl($o_league_part);

		$fs3 = new XhtmlElement('fieldset', new XhtmlElement('legend', 'Contact details for Stoolball England to use'));
		$this->form->AddControl($fs3);

		$o_contact = new TextBox('contact', isset($_POST['contact']) ? $_POST['contact'] : '');
		$o_contact->SetMaxLength(150);
		$o_contact_part = new FormPart('Contact name', $o_contact);
		$o_contact_part->SetIsRequired(true);
		$fs3->AddControl($o_contact_part);

		$o_contact_addr = new TextBox('address', isset($_POST['address']) ? $_POST['address'] : '');
		$o_contact_addr->SetMode(TextBoxMode::MultiLine());
		$o_contact_addr_part = new FormPart('Contact address', $o_contact_addr);
		$fs3->AddControl($o_contact_addr_part);

		$o_contact_phone = new TextBox('contactPhone', isset($_POST['contactPhone']) ? $_POST['contactPhone'] : '');
		$o_contact_phone->SetMaxLength(50);
		$o_contact_phone_part = new FormPart('Contact phone number', $o_contact_phone);
		$fs3->AddControl($o_contact_phone_part);

		$o_contact_e = new TextBox('email', isset($_POST['email']) ? $_POST['email'] : '');
		$o_contact_e_part = new FormPart('Contact email', $o_contact_e);
		$o_contact_e_part->SetIsRequired(true);
		$fs3->AddControl($o_contact_e_part);

		$fs4 = new XhtmlElement('fieldset', new XhtmlElement('legend', 'Contact details for the website'), '#publicContact');
		$fs4->SetCssClass("radioButtonList");
		$this->form->AddControl($fs4);

		$public1 = new RadioButton('publicAbove', 'public', 'Same as above', 'Same', $this->IsPostback() ? (isset($_POST['public']) and $_POST['public'] == 'Same') : true);
		$public3 = new RadioButton('publicDiff', 'public', 'Display different contact details on the website', 'Different', $this->IsPostback() ? (isset($_POST['public']) and $_POST['public'] == 'Different') : false);
		$public2 = new RadioButton('publicNone', 'public', 'Don\'t display any contact details (not recommended)', 'None', $this->IsPostback() ? (isset($_POST['public']) and $_POST['public'] == 'None') : false);
		$fs4->AddControl($public1);
		$fs4->AddControl($public3);
		$fs4->AddControl($public2);

		$public_contact = new TextBox('publicContact', isset($_POST['publicContact']) ? $_POST['publicContact'] : '');
		$public_contact->SetMode(TextBoxMode::MultiLine());
		$public_contact_part = new FormPart('Contact details for the website', $public_contact);
		$public_contact_part->SetXhtmlId('publicContactPart');
		$fs4->AddControl($public_contact_part);

		$fs5 = new XhtmlElement('fieldset', new XhtmlElement('legend', 'Anything else you\'d like on your team\'s page'));
		$this->form->AddControl($fs5);

		$o_notes = new TextBox('notes', isset($_POST['notes']) ? $_POST['notes'] : '');
		$o_notes->SetMode(TextBoxMode::MultiLine());
		$o_notes_part = new FormPart('Other details', $o_notes);
		$fs5->AddControl($o_notes_part);

		$o_buttons = new XhtmlElement('div');
		$o_buttons->SetCssClass('buttonGroup');
		$o_submit = new Button('send', 'Send email');
		$o_buttons->AddControl($o_submit);
		$this->form->AddControl($o_buttons);

		# Set up validation
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/email-validator.class.php');

		$a_validators = array();
		$a_validators[] = new RequiredFieldValidator(array('team'), 'A team name is required');
		$a_validators[] = new LengthValidator(array('team'), 'Your team name is too long', 0, 200);
		$a_validators[] = new LengthValidator(array('club'), 'Your club name is too long', 0, 200);
		$a_validators[] = new RequiredFieldValidator(array('ground'), 'A playing field address is required');
		$a_validators[] = new LengthValidator(array('ground'), 'The playing field address is too long', 0, 2000);
		$a_validators[] = new RequiredFieldValidator(array('contact'), 'A contact name for the team is required');
		$a_validators[] = new LengthValidator(array('pracNight'), 'Practice night must be 50 characters or less', 0, 50);
		$a_validators[] = new LengthValidator(array('matchNight'), 'Match nights must be 100 characters or less', 0, 100);
		$a_validators[] = new LengthValidator(array('leagues'), 'Your league or friendly group(s) must be 2000 characters or less', 0, 2000);
		$a_validators[] = new LengthValidator(array('contact'), 'Your contact name is too long', 0, 150);
		$a_validators[] = new LengthValidator(array('address'), 'The contact address is too long', 0, 2000);
		$a_validators[] = new LengthValidator(array('contactPhone'), 'The contact phone number is too long', 0, 50);
		$a_validators[] = new RequiredFieldValidator(array('email'), 'A contact email for the team is required');
		$a_validators[] = new EmailValidator('email', 'Please enter a valid email address');
		$a_validators[] = new LengthValidator('publicContact', 'You\'ve put too much in the "website contact details" box. Please make the text shorter.', 0, 10000);
		$a_validators[] = new LengthValidator('notes', 'You\'ve put too much in the "other details" box. Please make the text shorter.', 0, 10000);

		foreach ($a_validators as $o_v)
		{
			$this->form->AddValidator($o_v);
			unset($o_v);
		}
		$this->RegisterControlForValidation($this->form);

	}

	function OnPostback()
	{
		if ($this->IsValid())
		{
			# send the email
			require_once 'Zend/Mail.php';

			$email = new Zend_Mail('UTF-8');
			$email->addTo($this->GetSettings()->GetWebEditorEmail());
			$email->setFrom($_POST['email'], $_POST['email']);
			$email->setSubject('Stoolball team details');
			$s_body = 'Team name: ' . $_POST['team'] . "\n\n" .
			'Club: ' . $_POST['club'] . "\n\n" .
			'Ground: ' . $_POST['ground'] . "\n\n" .
			'Practice night: ' . $_POST['pracNight'] . "\n\n" .
			'Match nights: ' . $_POST['matchNight'] . "\n\n" .
			'Leagues: ' . $_POST['leagues'] . "\n\n" .
			'Contact name: ' . $_POST['contact'] . "\n\n" .
			'Address: ' . $_POST['address'] . "\n\n" .
			'Phone: ' . $_POST['contactPhone'] . "\n\n" .
			'Email: ' . $_POST['email'] . "\n\n" .
			'Permission for contact details: ' . $_POST['public'] . "\n\n" .
			'Website contact details (if different): ' . $_POST['publicContact'] . "\n\n" .
			'Notes: ' . $_POST['notes'];
			$email->setBodyText(html_entity_decode($s_body));

			$this->b_success = true;
			try
			{
				$email->send();
			}
			catch (Zend_Mail_Transport_Exception $e)
			{
				$this->b_success = false;
			}
		}
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle('Tell us about your team');
		$this->SetPageDescription("Ask us to add your stoolball team if it's not listed, or update your page if it is.");
		$this->LoadClientScript('yourteam-3.js', true);
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

        # Display welcome message and form
		if (!$this->IsPostback() or !$this->IsValid())
		{
			$intro = <<<INTRO
				If your team's information on this website isn't up-to-date, or if your team isn't here at all, let us know.
				If you're looking for the Stoolball England Registration Form, this is it.
INTRO;

			echo new XhtmlElement('p', $intro);

			echo $this->form;

		}

		# Or display sending error
		else if ($this->IsPostback() and $this->IsValid() and !$this->b_success)
		{
			$intro = <<<INTRO
			There was a problem emailing your form. Please try again later.
INTRO;

			echo new XhtmlElement('p', $intro);

			echo $this->form;
		}

		# Or display thank you
		else if ($this->IsPostback() and $this->IsValid() and $this->b_success)
		{
			$intro = <<<INTRO
			Thank you. Your form has been emailed to us and we'll update this website soon.
INTRO;

			echo new XhtmlElement('p', $intro);

			echo new XhtmlElement('p', new XhtmlAnchor('See all stoolball teams', '/teams/'));
		}
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>