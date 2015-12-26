<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class ActivationPage extends StoolballPage
{
	private $b_success = false;

	function OnLoadPageData()
	{
		# if activation code specified, try to activate
		if (isset($_GET['c']) and isset($_GET['p']) and is_numeric($_GET['p']))
		{
			$o_activator = $this->GetAuthenticationManager();
			$this->b_success = $o_activator->Activate($_GET['p'], $_GET['c']);

			# expire old activation requests while we're here
			$o_activator->ExpireRequests();

			unset($o_activator);

			# if activation succeeded...
			if ($this->b_success)
			{
				# get registered details
				$s_sql = 'SELECT known_as, email FROM ' . $this->GetSettings()->GetTable('User') . ' ' .
					"WHERE user_id = " . Sql::ProtectNumeric($_GET['p']);

				$s_email = '';
				$s_name = '';

				$o_data = $this->GetDataConnection()->query($s_sql);
				$o_row = $o_data->fetch();

				if (is_object($o_row))
				{
					$s_email = $o_row->email;
					$s_name = $o_row->known_as;

					# send email with details
					require_once 'Zend/Mail.php';
					$o_email = new Zend_Mail('UTF-8');
					$o_email->addTo($s_email);
					$o_email->setSubject('Your ' . $this->GetSettings()->GetSiteName() . ' registration details');
					$o_email->setFrom($this->GetSettings()->GetEmailAddress(), $this->GetSettings()->GetSiteName());

					$o_email->setBodyText('Hi ' . $s_name . "!\n\n" .
							'Thanks for registering with ' . $this->GetSettings()->GetSiteName() . '.' . "\n\n" .
							"You can sign in at https://" . $this->GetSettings()->GetDomain() . $this->GetSettings()->GetFolder('Account') . "\n\n" .
							"Please keep this email as a reminder of the email address you signed up with.\n\n" .
							'You can change your details at any time by signing in to ' . $this->GetSettings()->GetSiteName() . "\n" .
							"and editing your profile. You can also reset your password if you lose it.\n\n" .
							'We hope to see you back at ' . $this->GetSettings()->GetSiteName() . ' soon!' .
					$this->GetSettings()->GetEmailSignature() . "\n\n" .
							'(We sent you this email because someone signed up to ' . $this->GetSettings()->GetSiteName() . "\n" .
							'using the email address ' . $s_email . ". If it wasn't you, please contact us\n" .
							'via our website and ask for your address to be removed.)');

					$b_email_result = true;
					try
					{
						$o_email->send();
					}
					catch (Zend_Mail_Transport_Exception $e)
					{
						$b_email_result = false;
					}
				}
				else $b_email_result = false;

				# ensure user is not signed in as someone else, then show a welcome message below
				$this->GetAuthenticationManager()->SignOut();
			}

		}
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Confirm your registration');
	}

	function OnPageLoad()
	{
		# display intro
		if (isset($_GET['action']) and $_GET['action'] == 'request')
		{
			$o_message = new XhtmlElement('div');
			$o_message->SetCssClass('activateMessage');

			if(isset($_GET['email']) and $_GET['email'] == 'no')
			{
				echo '<h1>Confirm your registration</h1>';

				$s_message = 'There was a <strong>problem sending you an email</strong>.';
				$s_message .= "You need to get an email to register with " . Html::Encode($this->GetSettings()->GetSiteName()) . '. ' .
					'Please check your email address and try again.';
				$o_message->AddControl(new XhtmlElement('p', $s_message));
			}
			else
			{
				echo '<h1>Check your email to confirm your registration</h1>';

				$o_message->AddControl(new XhtmlElement('p', 'Thanks for registering with ' . Html::Encode($this->GetSettings()->GetSiteName()) . '.'));
				$s_message = "We've sent you an email. Please check your inbox now, and click on the link in the email to confirm your registration.";
				$o_message->AddControl(new XhtmlElement('p', $s_message));
			}

			echo $o_message;
		}
		else
		{
			if ($this->b_success) {
                ?>
                <h1>Confirmation successful</h1>
                <p>Welcome to <?php echo Html::Encode($this->GetSettings()->GetSiteName()); ?>!</p>
                <p>We've activated your account, and sent you an email confirming your sign in details.</p>
          
                <p><strong>Please <a href="/you">sign in</a> using your email address and your new password.</strong></p>
           
                <?php
                
			} else {
				echo new XhtmlElement('h1', 'Confirmation failed');
				echo new XhtmlElement('p', 'Sorry, your registration for ' . Html::Encode($this->GetSettings()->GetSiteName()) . ' could not be confirmed.');
				echo new XhtmlElement('p', 'Please check that you used the exact address in the email you received, or try to <a href="' . Html::Encode($this->GetSettings()->GetUrl('AccountCreate')) . '">register again</a>.');
			}
		}

	}
}
new ActivationPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>