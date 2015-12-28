<?php
require_once('page/stoolball-page.class.php');
require_once('forums/subscription-manager.class.php');
require_once('review-item.class.php');

/**
 * Page which processes an email subscription request
 *
 */
class SubscribePage extends StoolballPage
{
	/**
	 * The item being subscribed to
	 *
	 * @var ReviewItem
	 */
	private $o_review_item;

	function OnSiteInit()
	{
		parent::OnSiteInit();

		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->InvalidRequest();
		if (!isset($_GET['type']) or !is_numeric($_GET['type'])) $this->InvalidRequest();
	}

	function OnPageInit()
	{
		$this->o_review_item = new ReviewItem($this->GetSettings());
		$this->o_review_item->SetId($_GET['item']);
		$this->o_review_item->SetType($_GET['type']);
		if (isset($_GET['title'])) $this->o_review_item->SetTitle($_GET['title']); // data already sanitised in OnSiteInit
		if (isset($_GET['page']))
		{
			$this->o_review_item->SetNavigateUrl($_GET['page']);
		}
		elseif (isset($_SERVER['HTTP_REFERER']))
		{
			$this->o_review_item->SetNavigateUrl($_SERVER['HTTP_REFERER']);
		}

		parent::OnPageInit();
	}

	/**
	 * If invalid request detected, do not attempt to subscribe
	 *
	 */
	function InvalidRequest()
	{
		header('Location: ' . $this->GetSettings()->GetClientRoot());
		exit();
	}

	function OnLoadPageData()
	{
		# Add subscription
		$o_subs = new SubscriptionManager($this->GetSettings(), $this->GetDataConnection());
		$o_subs->SaveSubscription($this->o_review_item->GetId(), $this->o_review_item->GetType(), AuthenticationManager::GetUser()->GetId());
		unset($o_subs);
	}

	function OnPrePageLoad()
	{
		$this->SetContentCssClass('subscribeConfirm');
		$this->SetPageTitle('Subscribed to ' . ($this->o_review_item->GetTitle() ? $this->o_review_item->GetTitle() : ' page'));
	}

	function OnPageLoad()
	{
		echo '<h1>Subscribed to ' . Html::Encode($this->o_review_item->GetTitle() ? $this->o_review_item->GetTitle() : ' page') . '</h1>';

		# Confirm subscription to user
		$b_title = (bool)$this->o_review_item->GetTitle();
		echo new XhtmlElement('p', 'You have subscribed to ' . Html::Encode($b_title ? "'" . $this->o_review_item->GetTitle() . "'" : 'the page you selected') . '. You will get an email alert every time someone adds a comment.');
		echo new XhtmlElement('p', 'If you want to stop getting these email alerts, sign in to ' . Html::Encode($this->GetSettings()->GetSiteName()) . ' and delete your subscription from the email alerts page.');

		# Links suggesting what to do next
		$o_whatnext = new XhtmlElement('ul');
		if ($this->o_review_item->GetNavigateUrl())
		{
			$o_whatnext->AddControl(new XhtmlElement('li', new XhtmlAnchor('Go back to ' . Html::Encode($b_title ? $this->o_review_item->GetTitle() : 'the page you came from'), $this->o_review_item->GetNavigateUrl())));
		}
		$o_whatnext->AddControl(new XhtmlElement('li', new XhtmlAnchor("Email alerts", $this->GetSettings()->GetUrl('EmailAlerts'))));
		echo $o_whatnext;

	}

}
?>