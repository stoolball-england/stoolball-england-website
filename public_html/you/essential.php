<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('authentication/essential-info-form.class.php');

class CurrentPage extends StoolballPage
{
	private $error;
	private $form;
	private $show_changed_email_message = false;

	function OnPageInit()
	{
		$this->form = new EssentialInfoForm($this->GetSettings(), AuthenticationManager::GetUser(), $this->GetAuthenticationManager(), $this->GetCsrfToken());
		$this->RegisterControlForValidation($this->form);
	}

	/**
	 * For postbacks, allow session writes beyond the usual point so that SaveToSession() can update the current user
	 */
	protected function SessionWriteClosing()
	{
		return !$this->IsPostback();
	}

	function OnPostback()
	{
		if ($this->IsValid())
		{
			/* @var $o_person User */
			$o_person = $this->form->GetDataObject();

			# Save name
			$authentication = $this->GetAuthenticationManager();
			$success = $authentication->SaveUser($o_person);

			if (!$success)
			{
				$this->error = new XhtmlElement('p', 'There was a problem saving your name. Please try again.', 'validationSummary');
				return;
			}

			# successful name change - now update session
			$authentication->SaveToSession($o_person);

			# Safe to end session writes now
			session_write_close();
            
            $old_password_valid = false;
            if ($o_person->GetPassword()) {
                
                $old_password_valid = $authentication->ValidateUser($o_person->GetEmail(), $o_person->GetPassword());
            }

			# save password if new and old password both provided and are different
            if ($o_person->GetPassword()) {

    			if ($o_person->GetRequestedPassword() and $o_person->GetPassword() != $o_person->GetRequestedPassword())
    			{
       				if (!$old_password_valid or !$authentication->SavePassword($o_person))
    				{
    					$this->error = new XhtmlElement('p', 'Your password could not be changed. Please re-enter your current password and try again.', 'validationSummary');
    					return;
    				}
                    
                    # if password changed, clear auto sign in for all devices, but reset for this device 
                    if ($authentication->GetAutoSignInProvider() instanceof IAutoSignIn) {
                        $authentication->GetAutoSignInProvider()->SaveAutoSignIn($o_person->GetId(), $o_person->GetAutoSignIn(), true);
                    }
    			} else {
    
                    if ($authentication->GetAutoSignInProvider() instanceof IAutoSignIn) {
                        $authentication->GetAutoSignInProvider()->SaveAutoSignIn($o_person->GetId(), $o_person->GetAutoSignIn());
                    }
                }
            }
            
			# save email request if address changed
			if ($o_person->GetEmail() != $o_person->GetRequestedEmail())
			{
               if ($o_person->GetPassword() and $old_password_valid) {
 
    				if (!($hash = $authentication->SaveRequestedEmail($o_person)))
    				{
    					$this->error = new XhtmlElement('p', 'There was a problem saving your email address. Please try again.', 'validationSummary');
    					return;
    				}
    
    				# request saved to db, now email the new address for confirmation
					$o_person->SetRequestedEmailHash($hash);
					$authentication->SendChangeEmailAddressEmail($o_person);
    				$this->show_changed_email_message = true;
               } else {

                    $this->form->SuppressEmailChangeMessage(true);
                    $this->error = new XhtmlElement('p', 'Please confirm your current password to change your email address.', 'validationSummary');
               }
				# avoid the redirect, this page needs to display a message
				return;
			}

			# success - redirect to edit profile home
			$this->Redirect($this->GetSettings()->GetUrl('AccountEdit'));
		}
		else
		{
			# Safe to end session writes now
			session_write_close();
		}
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Essential information for ' . AuthenticationManager::GetUser()->GetName());
		$this->SetContentConstraint($this->ConstrainText());
	}

	function OnPageLoad()
	{
		if ($this->show_changed_email_message)
		{
			?>
<h1>Just one more thing&#8230;</h1>
<p>There's one more step before we update your email address.</p>
<p>Please check your email inbox, and within the next few minutes you should see something from us asking you to confirm your email address. 
    Click on the link in the email, and we'll update your account.</p>
			<?php
			echo '<p><a href="' . Html::Encode($this->GetSettings()->GetUrl('AccountEdit')) . '">Back to your profile</a></p>';
		}
		else
		{
			echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

			if (!is_null($this->error))
			{
				echo $this->error;
			}

			echo $this->form;
		}
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::EditPersonalInfo(), false);
?>