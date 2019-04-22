<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('authentication/signin-form.class.php');
require_once('email/email-address.class.php');

class CurrentPage extends StoolballPage
{
	private $o_error_list;
	
	/**
	 * For postbacks, allow session writes beyond the usual point so that SignIn() can be called
	 */
	protected function SessionWriteClosing()
	{
		return !$this->IsPostback();
	}

	public function OnPrePageLoad()
	{
		$this->SetPageTitle('Sign in to ' . $this->GetSettings()->GetSiteName());
		$this->SetContentConstraint($this->ConstrainText());
	}

	function OnPostback()
	{
		# new list for validation
		$this->o_error_list = new XhtmlElement('ul');
		$this->o_error_list->AddAttribute('class', 'validationSummary');

		# check we've got email
		if ((isset($_POST['email']) and !trim($_POST['email'])) or !isset($_POST['email'])) 
		{
		    $this->o_error_list->AddControl(new XhtmlElement('li', 'Please enter your email address'));
        }

		# check for request to resend activation email
		if (isset($_POST['resend']) and !$this->o_error_list->CountControls())
		{
			# Get the person's name and id. Only checking email at this point creates the possibility that someone could
			# fake this request for another user, but the worst they can do is send a new activation request to that other
			# user; they can't gain any information themselves or disable anyone's account. Don't try to check password because
			# browser security means we can't be sure it'll be repopulated and reposted.
			$authentication = $this->GetAuthenticationManager();
			$authentication->ReadByEmail($_POST['email']);
			$account = $authentication->GetFirst();

			if (is_object($account))
			{
				# send a new email
				$s_hash = $authentication->SaveRequest($account->GetId());
				$email_success = $authentication->SendActivationEmail($account, $s_hash);

				# redirect to activation message
				$s_email_status = $email_success ? '' : '&email=no';
				$this->Redirect($this->GetSettings()->GetUrl('AccountActivate') . '?action=request&name=' . urlencode($account->GetName()) . '&address=' . urlencode($account->GetEmail()) . $s_email_status);
			}
		}

		# check we've got password
		if ((isset($_POST['password']) and !trim($_POST['password'])) or !isset($_POST['password'])) 
		{
		    $this->o_error_list->AddControl(new XhtmlElement('li', 'Please enter your password'));
        }

		# no message so form OK
		if(!$this->o_error_list->CountControls())
		{
			# try to sign in
			$sign_in_result = $this->GetAuthenticationManager()->SignIn($_POST['email'], $_POST['password'], isset($_POST['remember_me']));
			switch ($sign_in_result)
			{
				case SignInResult::Success():
					if (isset($_POST['page'])) 
					{
					    header('Location: ' . str_replace('&amp;', '&', str_replace('&amp;', '&', $_POST['page']))); 
				    }
				    else 
				    {
				        header('location: ' . $this->GetSettings()->GetClientRoot());
                    }
					exit();
				case SignInResult::AccountDisabled():
					$this->o_error_list->AddControl(new XhtmlElement('li', 'Sorry, your account has been disabled due to misuse.'));
					break;
				case SignInResult::NotActivated():
					$not_activated = new XhtmlElement('li', 'You need to activate your account. Check your email inbox.');
					$not_activated->AddControl('<input type="submit" name="resend" value="Send a new email" class="inlineButton" />');
					$this->o_error_list->AddControl($not_activated);
					break;
				case SignInResult::NotFound():
					$this->o_error_list->AddControl(new XhtmlElement('li', 'You tried to sign in with an incorrect email address and/or password. Please sign in again.'));
					break;
			}
		}

		# Safe to end session writes now
		session_write_close();
	}

	public function DisplayIntro()
	{
		# build welcome message
		$s_welcome = '';
		if(isset($_GET['action']) and $_GET['action'] == 'wrongscreen')
		{
			$s_welcome .= "You've already registered using the email address and password you just entered. Please sign in.";
		}
		else if(isset($_GET['action']) and $_GET['action'] == 'required')
		{
		    if (AuthenticationManager::GetUser()->IsSignedIn())
            {
                $s_welcome .= '<strong>You do not have permission to use this feature.</strong></p>' .
                    '<p>Please sign in with a different account.';
            }
            else 
            {
                $s_welcome .= '<strong>Please sign in to use this feature.</strong></p>' .
                '<p>If you have not already registered, please <a href="' . Html::Encode($this->GetSettings()->GetUrl('AccountCreate')) . '">register now</a>.';
            }
		}
		else if (AuthenticationManager::GetUser()->IsSignedIn())
		{
			$s_signed_in_as = AuthenticationManager::GetUser()->GetName();
			?>
<p><strong>You're already signed in as <?php echo Html::Encode($s_signed_in_as) ?>.</strong></p>
			<?php
		}
		else
		{
			$s_welcome = '<p>If you haven\'t already registered, please <a href="' . Html::Encode($this->GetSettings()->GetUrl('AccountCreate')) . '">register now</a>.';
		}
		echo new XhtmlElement('p', $s_welcome);
	}

	public function DisplayForm()
	{
		# display form, with the error control included because it may contain an action button
		$form = new SignInForm($this->GetSettings(), $this->GetAuthenticationManager(), $this->GetCsrfToken());
		$form->SetControls(array_unshift($form->GetControls(), $this->o_error_list));
        echo $form;
	}

	function OnPageLoad()
	{
		echo '<h1>Sign in</h1>';
		$this->DisplayIntro();
		$this->DisplayForm();
	}

}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>