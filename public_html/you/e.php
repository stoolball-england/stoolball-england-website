<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class EmailPage extends StoolballPage
{
	private $b_success = false;

	/**
	 * Allow session writes beyond the usual point so that SignInValidUser() can be called
	 */
	protected function SessionWriteClosing()
	{
		return false;
	}

	function OnLoadPageData()
	{
		# if confirmation code specified, try to update email
		if (!isset($_GET['c']) or !isset($_GET['p']) or !is_numeric($_GET['p'])) return;

		$authentication = $this->GetAuthenticationManager();
		$this->b_success = $authentication->ConfirmEmail($_GET['p'], $_GET['c']);
		if ($this->b_success)
		{
			# if they're genuine, be nice and sign them in

			/* @var $user User */
            $user = $authentication->ReadDataForValidUser(array($_GET['p']));
            $authentication->SignInValidUser($user, $authentication->HasCookies());
		}
		unset($authentication);
		
		# Safe to end session writes now
		session_write_close();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle($this->b_success ? 'Confirmation successful' : 'Confirmation failed');
	}

	function OnPageLoad()
	{
		if ($this->b_success)
		{
			?>
<h1>Confirmation successful</h1>
<p>Thank you for confirming your email address. Your profile has been updated.</p>
<p>When you next sign in to <?php echo Html::Encode($this->GetSettings()->GetSiteName()); ?> you'll
need to use your new email address, <strong><?php echo Html::Encode(AuthenticationManager::GetUser()->GetEmail()); ?></strong>, along with your existing password.</p>
<p><a href="<?php echo Html::Encode($this->GetSettings()->GetUrl('AccountEdit')) ?>">Back to your profile</a></p>
			<?php
		}
		else
		{
			?>
<h1>Confirmation failed</h1>
<p>Sorry, your request to change your registered email address could not be confirmed.</p>
<p>Please check that you used the exact address in the email you received, or try to <a href="<?php echo Html::Encode($this->GetSettings()->GetUrl('AccountEssential')) ?>">change your email address</a> again.</p>
			<?php
		}

	}
}
new EmailPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>