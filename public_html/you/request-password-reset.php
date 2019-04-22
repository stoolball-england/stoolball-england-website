<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('authentication/password-form.class.php');

class CurrentPage extends StoolballPage
{
	private $sent;
    private $form;
    
    public function OnPageInit()
    {
        parent::OnPageInit();
        
        $this->form = new PasswordForm($this->GetCsrfToken());
        $this->RegisterControlForValidation($this->form);
    }

	public function OnPrePageLoad()
	{
		$this->SetPageTitle('Reset password - ' . $this->GetSettings()->GetSiteName());
		$this->SetContentConstraint(StoolballPage::ConstrainText());
	}

	public function OnPostback()
	{
	    if (!$this->form->IsValid()) return;
        
		$authentication_manager = $this->GetAuthenticationManager();
		$authentication_manager->ReadByEmail($_POST['email']);

        # We're going to send an email whether they have an account or not, so as not to disclose on the website
        # whether the email address is registered.
        $greeting = "Hi there!";
        $body = '';

		if ($authentication_manager->GetCount())
		{
    		$user = $authentication_manager->GetFirst();
    		/* @var $user User */
    
    		if ($user->GetId())
    		{
    			$user->SetPasswordResetToken($authentication_manager->SavePasswordResetRequest($user->GetId()));
    
				# account found and reset request saved - send the email
				if ($user->GetName()) $greeting = "Hi " . $user->GetName() . '!';

				$body = 'If this was you, click the link below to reset your password' . "\n\n" .
				"This link will expire after 24 hours.\n\n" .
				"https://" . $this->GetSettings()->GetDomain() . $user->GetPasswordResetConfirmationUrl();

    		}
        } else {

            $body = "You don't have an account with us at this email address, but you can register for one now:\n\n" .
                    "https://" . $this->GetSettings()->GetDomain() . $this->GetSettings()->GetUrl('AccountCreate');
        }

        $body = $greeting . "\n\n" . "Someone's just asked for your " . $this->GetSettings()->GetSiteName() . ' password to be reset. ' .
                $body .
                $this->GetSettings()->GetEmailSignature() . "\n\n\n" .
                 "(If you didn't ask for this email, please ignore it.)";

        $mailer = new Swift_Mailer($this->GetSettings()->GetEmailTransport());
        $message = (new Swift_Message('Reset ' . $this->GetSettings()->GetSiteName() . ' password'))
        ->setFrom([$this->GetSettings()->GetEmailAddress() => $this->GetSettings()->GetSiteName()])
        ->setTo([$_POST['email']])
        ->setBody($body);
        $mailer->send($message);

        $this->sent = true;
	}

	public function OnPageLoad()
	{
		echo '<h1>Reset password</h1>';

		# display correct introductory para
		$s_intro = '';

        if (isset($this->sent) and $this->sent) {
            $s_intro = "<p><strong>We've sent an email to the address you entered. Please check your inbox.</strong></p>";
            
        } else {

     		$s_intro .= '<p>We can send a link to your email address which will let you reset your password.</p>';
        }
        
		echo $s_intro;

		echo $this->form;
	}        
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>