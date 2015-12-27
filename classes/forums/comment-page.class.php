<?php
require_once ('page/stoolball-page.class.php');
require_once ('forums/topic-manager.class.php');

/**
 * Page which allows comments to be added to a page of content
 *
 */
class CommentPage extends StoolballPage
{
	private $o_review_item;
	private $o_category;
	private $o_topic;
	private $o_error_list;
	/**
	 * Manager to read and save forum topic
	 *
	 * @var TopicManager
	 */
	private $o_topic_manager;

	function OnPageInit()
	{        
		# store review type and item
		$this->o_review_item = new ReviewItem($this->GetSettings());
		if (isset($_GET['type'])) $this->o_review_item->SetType($_GET['type']);
        if (isset($_POST['type'])) $this->o_review_item->SetType($_POST['type']);
		if (isset($_GET['item'])) $this->o_review_item->SetId($_GET['item']); 
		if (isset($_POST['item'])) $this->o_review_item->SetId($_POST['item']);

        if (!$this->o_review_item->GetType() && !$this->o_review_item->GetId()) 
        {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
            exit();
        }

		# If there are existing comments, get the topic.
		# Needs to happen here so that's it's available in time for OnPostback event
		$this->o_topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());

		parent::OnPageInit();
	}

	function OnPostback()
	{
		/* @var $o_topic ForumTopic */
		/* @var $o_new_message ForumMessage */

		# new list for validation
		$this->o_error_list = new XhtmlElement('ul');
		$this->o_error_list->AddAttribute('class', 'validationSummary');

		# check required fields have some content
		if (!trim($_POST['title']))
			$this->o_error_list->AddControl(new XhtmlElement('li', 'Please give your comments a title'));
		if (!trim($_POST['message']))
			$this->o_error_list->AddControl(new XhtmlElement('li', 'Please complete your comments before clicking the "Post comments" button'));

		#if no errors found, create message
		if (!$this->o_error_list->CountControls())
		{
			$this->o_topic = $this->o_topic_manager->SaveComment($this->o_review_item, $this->GetCategories()->GetById($_POST['category_id']), $_POST['title'], $_POST['message'], $_POST['icon']);

			# update new message for later use
			$o_new_message = $this->o_topic->GetFinal();

			# send subscription emails
			require_once ('forums/subscription-manager.class.php');
			$o_subs = new SubscriptionManager($this->GetSettings(), $this->GetDataConnection());
			$o_subs->SetTopic($this->o_topic);

			$o_subs->SendCategorySubscriptions($this->GetCategories()->GetById($_POST['category_id']));
			$o_subs->SendTopicSubscriptions();
			if ($this->o_review_item->GetId())
				$o_subs->SendCommentsSubscriptions($this->o_review_item);

			# add subscription if appropriate
			if (isset($_POST['subscribe']) and $this->o_review_item->GetId())
				$o_subs->SaveSubscription($this->o_review_item->GetId(), $this->o_review_item->GetType(), AuthenticationManager::GetUser()->GetId());

			# redirect user back to the item
			$s_redirect_location = ($_POST['page']) ? str_replace('&amp;', '&', $_POST['page']) : $o_new_message->GetNavigateUrl(true, false);
			# override defaults if path has been remembered intact

			$this->Redirect($s_redirect_location);
		}
	}

	function OnLoadPageData()
	{
		/* @var $o_topic ForumTopic */

		$this->o_topic_manager->ReadReviewTopicId($this->o_review_item);
		$this->o_topic = $this->o_topic_manager->GetFirst();
		if (is_object($this->o_topic) and $this->o_topic->GetId())
		{
			# get topic info
			$this->o_topic_manager->SetReverseOrder(true);
			$this->o_topic_manager->ReadById(array($this->o_topic->GetId()));
			$this->o_topic = $this->o_topic_manager->GetFirst();
		}
		else
			$this->o_topic = new ForumTopic($this->GetSettings());

		# Finished reading/writing topic
		unset($this->o_topic_manager);
	}

	function OnPrePageLoad()
	{
		/* @var $o_review_item ReviewItem */

		# store category id
		if (isset($_POST['category_id']) and is_numeric($_POST['category_id']))
		{
			$this->o_category = $this->GetCategories()->GetById($_POST['category_id']);
		}
		else
		{
			# for new review topic, use correct category
			$this->o_category = $this->GetSettings()->GetCommentsCategory($this->GetCategories(), $this->o_review_item->GetType());
		}

		$this->SetPageTitle('Add your comments');
		$this->LoadClientScript("/scripts/tiny_mce/jquery.tinymce.js");
		$this->LoadClientScript("/scripts/tinymce.js");
	}

	function OnPageLoad()
	{
		# display page heading
		echo '<h1>Add your comments</h1>';
		$this->Render();

		# display any errors
		echo $this->o_error_list;
		$this->Render();

		# create topic
		$this->o_topic->SetCategory($this->o_category);
		$this->o_topic->SetReviewItem($this->o_review_item);

		# create form
		require_once ('forums/forum-message-form.class.php');
		$o_form = new ForumMessageForm($this->GetSettings(), AuthenticationManager::GetUser());
		$o_form->SetFormat(ForumMessageFormat::Review());
		$o_form->SetTopic($this->o_topic);
		echo $o_form->GetForm();
		$this->Render();

		require_once ('forums/forum-topic-listing.class.php');
		$o_listing = new ForumTopicListing($this->GetSettings(), $this->GetContext(), AuthenticationManager::GetUser(), $this->o_topic);
		$o_listing->SetFormat(ForumMessageFormat::ReviewReply());
		echo $o_listing->__toString();
	}

}
?>