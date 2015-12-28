<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('forums/forum-topic.class.php');
require_once('forums/subscription.class.php');
require_once('forums/subscription-manager.class.php');
require_once('forums/subscription-grid.class.php');

class SubscriptionManagerPage extends StoolballPage
{
	private $a_subs;

	function OnLoadPageData()
	{
		$sub_manager = new SubscriptionManager($this->GetSettings(), $this->GetDataConnection());

		# process delete request
		if ((isset($_GET['delete']) and is_numeric($_GET['delete'])) and (isset($_GET['type']) and is_numeric($_GET['type'])))
		{
			$sub_manager->DeleteSubscription($_GET['delete'], $_GET['type'], AuthenticationManager::GetUser()->GetId());
		}

		# get subscription data
		$sub_manager->ReadSubscriptionsForUser(AuthenticationManager::GetUser()->GetId());
		$this->a_subs = $sub_manager->GetItems();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Email alerts for ' . AuthenticationManager::GetUser()->GetName());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
		echo '<p>You can subscribe to emails for ' . Html::Encode($this->GetSettings()->GetSiteName()) . '\'s match comments. ' .
                'Once you have subscribed, as soon as anyone else adds their comments,  you\'ll get an email.</p>';
       

		# display tables of subscriptions
		echo new SubscriptionGrid($this->GetSettings(), $this->a_subs);

		echo '<form method="get" action="' . Html::Encode($this->GetSettings()->GetUrl('AccountEdit')) . '"><div>' .
		'<input type="submit" class="submit" value="Done" title="Return to your edit profile options page" />' .
		'</div></form>';
	}
}
new SubscriptionManagerPage(new StoolballSettings(), PermissionType::PageSubscribe(), false);
?>