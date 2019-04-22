<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('page/stoolball-page.class.php');
require_once ('email/email-form.class.php');
require_once ('email/email-address.class.php');
require_once('email/email-address-protector.class.php');

class CurrentPage extends StoolballPage
{
	private $form;
	private $send_attempted = false;
	private $send_succeeded;
	private $address;
	private $address_display;
	private $valid;
	private $throttled;

	function OnPageInit()
	{
		parent::OnPageInit();

		$this->form = new EmailForm($this->GetSettings(), $this->GetCsrfToken());
		$this->RegisterControlForValidation($this->form);

		$this->valid = (isset($_GET['to']) and is_string($_GET['to']) and isset($_GET['tag']) and is_string($_GET['tag']));
        if ($this->valid)
        {
            $protector = new EmailAddressProtector($this->GetSettings());
            $this->address = $protector->DecryptProtectedEmail($_GET['to'], $_GET['tag']);
            $email = new EmailAddress($this->address);
            $this->valid = $email->IsValid();
        } 
        if ($this->valid)
        {
            $this->address_display = HTML::Encode(substr($this->address, 0, strpos($this->address, "@"))) . "@&#8230; (protected address)";
        } 
        else
		{
			header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
		}

	}
	
	/**
	 * For postbacks, allow session writes beyond the usual point so that 'email_form_throttle' can be updated
	 */
	protected function SessionWriteClosing()
	{
		return !$this->IsPostback();
	}

	function OnPostback()
	{
		/* @var $email Swift_Message */
		$email = $this->form->GetDataObject();

        # Throttles sending rate to deter spam
        if (isset($_SESSION['email_form_throttle']))
        {
            $last_sent = $_SESSION['email_form_throttle'];
            $allow_after = $last_sent + 10;
            if (time() <= $allow_after)
            {
                $this->throttled = true;
                
                # Use a validator to display the message, because that ensures the submitted data is repopulated in the form
                require_once('data/validation/required-field-validator.class.php');
                $fail = new RequiredFieldValidator(array("this_validator_will_fail"), "You've sent another email too quickly. We limit the speed you can send them, to help protect you against spam. Try again now.");
                $fail->SetValidIfNotFound(false);
                $this->form->AddValidator($fail);
                return;
            }
        }

		# send email if valid
		if ($this->IsValid() and $this->valid)
		{
			$email->setTo([$this->address]);

			$mailer = new Swift_Mailer($this->GetSettings()->GetEmailTransport());
			$this->send_succeeded = $mailer->send($email);
			$_SESSION['email_form_throttle'] = time();
		}

		# Safe to end session writes now
		session_write_close();
	}

	function OnPrePageLoad()
	{
		if ($this->throttled)
		{
			$this->SetPageTitle("Hang on a minute&#8230;");
		}
		else if ($this->valid)
		{
			$this->SetPageTitle("Email $this->address_display");
			$this->SetPageDescription("Send an email to $this->address_display about stoolball");
		}
		else
		{
			$this->SetPageTitle("Oops, that doesn't work");
		}
		$this->SetContentConstraint($this->ConstrainText());
        ?>
        <meta name="robots" content="noindex" />
        <?php 
	}

	function OnPageLoad()
	{
		if ($this->throttled)
		{
			echo "<h1>Hang on a minute&#8230;</h1>" . 
			     $this->form;
		}
		else if ($this->valid)
		{
			echo new XhtmlElement('h1', "Email $this->address_display");
			if ($this->send_attempted)
			{
				echo $this->send_succeeded ? '<p>Thank you. Your email has been sent.</p>' : '<p>Sorry, there was a problem sending your email. Please try again later.</p>';
			}
			else
			{
		        echo $this->form;
			}
		}
		else
		{
			echo "<h1>Oops, that doesn't work</h1>
			      <p>Sorry, we're not sure who you're trying to email. If you got here by clicking a link on our website, please let us know where it was and we'll fix it.</p>";
		}
	}

}

new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>