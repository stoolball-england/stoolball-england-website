<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('email/contact-form.class.php');

class CurrentPage extends StoolballPage
{
	var $o_form;
	var $b_send_attempted = false;
	var $b_send_succeeded;

	function OnPageInit()
	{
		parent::OnPageInit();

		$a_reasons = array($this->GetSettings()->GetWebEditorEmail() => "It's about this website",
		$this->GetSettings()->GetEnquiriesEmail() => "I have a question about stoolball or Stoolball England",
		$this->GetSettings()->GetSalesEmail() => "I want to buy something");

		$this->o_form = new ContactForm($this->GetSettings(), $a_reasons, $this->GetCsrfToken());
		$this->RegisterControlForValidation($this->o_form);
	}

	function OnPostback()
	{
		/* @var $o_email Swift_Message */
		$o_email = $this->o_form->GetDataObject();

		# send email if valid
		if($this->IsValid())
		{
			$this->b_send_attempted = true;
			
			$mailer = new Swift_Mailer($this->GetSettings()->GetEmailTransport());
			$this->b_send_succeeded = $mailer->send($o_email);
		}
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Contact us');
		$this->SetPageDescription("Phone or email us about anything to do with stoolball, or contact us on Facebook or Twitter.");
		$this->SetContentConstraint($this->ConstrainText());
        ?>
        <meta name="robots" content="noindex" />
        <?php 
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', "Contact us");

		$o_message = new XhtmlElement('p');
		if ($this->b_send_attempted)
		{
			$o_message->AddControl($this->b_send_succeeded ? 'Thank you. Your email has been sent.' : 'Sorry, there was a problem sending your email. Please try again later.');
			echo $o_message;
		}
		else
		{
			$o_message->AddControl('You can also contact us via <a href="http://Facebook.com/stoolball">Facebook</a> or <a href="http://twitter.com/stoolball">Twitter</a>.');
			echo $o_message;
		}

		echo $this->o_form;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>