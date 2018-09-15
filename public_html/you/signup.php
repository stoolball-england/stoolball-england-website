<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('authentication/signup-form.class.php');

class SignUpPage extends StoolballPage
{
	private $form;
	private $new_user;

	function OnPageInit()
	{
		# New edit control
		$this->form = new SignUpForm($this->GetSettings());
		$this->RegisterControlForValidation($this->form);
	}

	function OnPostback()
	{
		/* @var $new_user User */

		# get object
		$this->new_user = $this->form->GetDataObject();
		$new_user = $this->new_user;

		# save data if valid
		if($this->IsValid())
		{
			# see if the email address is in the db already
			$authentication_manager = $this->GetAuthenticationManager();
			$authentication_manager->ReadByEmail($new_user->GetEmail());

			if ($authentication_manager->GetCount())
			{
				# all email addresses have a registered password - is it this one?
				$valid_user = $authentication_manager->ValidateUser($new_user->GetEmail(), $new_user->GetPassword());

				if ($valid_user)
				{
					# email registered with this password - redirect to sign in
					$this->Redirect("index.php?action=wrongscreen");
				}
				else
				{
					# Email registered but not with this password - but don't tell the website user as that would
					# reveal whether the email is already registered with the site and worth trying to hack the account.
		            # Instead send email telling them to use password reset.
                    require_once 'Zend/Mail.php';
                    $o_email = new Zend_Mail('UTF-8');
                    $o_email->addTo($new_user->GetEmail());
                    $o_email->setSubject('Your ' . $this->GetSettings()->GetSiteName() . ' registration details');
                    $o_email->setFrom($this->GetSettings()->GetEmailAddress(), $this->GetSettings()->GetSiteName());

                    $o_email->setBodyText('Hi ' . $new_user->GetName() . "!\n\n" .
                            "You're already registered with " . $this->GetSettings()->GetSiteName() . ". If you've forgotten your password you can reset it at:\n\n" .
                            "https://" . $this->GetSettings()->GetDomain() . "/you/request-password-reset" .
                            $this->GetSettings()->GetEmailSignature() . "\n\n" .
                            '(We sent you this email because someone signed up to ' . $this->GetSettings()->GetSiteName() . "\n" .
                            'using the email address ' . $new_user->GetEmail() . ". If it wasn't you just ignore this \n" .
                            "email, and the account won't be activated.)");

                    $email_success = true;
                    try
                    {
                        $o_email->send();
                    }
                    catch (Zend_Mail_Transport_Exception $e)
                    {
                        $email_success = false;
                    }

                    $s_email_status = $email_success ? '' : '&email=no';				
					$this->Redirect($this->GetSettings()->GetUrl('AccountActivate') . '?action=request' . $s_email_status);
				}
			}
			else
			{
				# email not in db, so sign up a new user
				$new_user = $authentication_manager->RegisterUser($new_user);

				# add activation request
				$s_hash = $authentication_manager->SaveRequest($new_user->GetId());

				# send email requesting activation - validates email address
				$email_success = $authentication_manager->SendActivationEmail($new_user, $s_hash);

				# redirect to sign-in page
				$s_email_status = $email_success ? '' : '&email=no';
				$this->Redirect($this->GetSettings()->GetUrl('AccountActivate') . '?action=request' . $s_email_status);
			}
		}
	}


	function OnPrePageLoad()
	{
		$this->SetPageTitle('Register with ' . $this->GetSettings()->GetSiteName());
        $this->SetContentConstraint(StoolballPage::ConstrainText());
	}

	function OnPageLoad()
	{
		echo '<h1>' . Html::Encode($this->GetPageTitle()) . '</h1>';

		$new_user = (is_object($this->new_user)) ? $this->new_user : new User();
		$this->form->SetDataObject($new_user);
		echo $this->form;
	}
}
new SignUpPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>