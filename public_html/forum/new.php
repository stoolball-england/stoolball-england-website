<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('forums/forum-message.class.php');
require_once('forums/forum-message-form.class.php');
require_once('forums/subscription-manager.class.php');
require_once('forums/topic-manager.class.php');

class NewTopicPage extends StoolballPage
{
	private $i_category_id;
	private $o_error_list;

	function OnPageInit()
	{
		# store forum id
		if (isset($_GET['forum']))
		{
		    $this->i_category_id = $_GET['forum'];
        }
        else if (isset($_POST['category_id']))
        {
            $this->i_category_id = $_POST['category_id'];
        }
        else {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
            exit();
        }

		parent::OnPageInit();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Start a new topic');
		$this->LoadClientScript("/scripts/tiny_mce/jquery.tinymce.js");
		$this->LoadClientScript("tinymce.js");
	}

	function OnPageLoad()
	{
		# display page heading
		echo '<h1>Start a new topic</h1>';

		# display any errors
		if (isset($this->o_error_list)) echo $this->o_error_list;

		# create topic
		$o_topic = new ForumTopic($this->GetSettings());
		$o_topic->SetCategory($this->GetCategories()->GetById($this->i_category_id));

		# create form
		$o_form = new ForumMessageForm($this->GetSettings());
		$o_form->SetFormat(ForumMessageFormat::NewTopic());
		$o_form->SetTopic($o_topic);
		echo $o_form->GetForm();

		# run template method
		parent::OnPageLoad();
	}

	# processes new topic when submitted
	function OnPostback()
	{
		# new list for validation
		$this->o_error_list = new XhtmlElement('ul');
		$this->o_error_list->AddAttribute('class', 'validationSummary');

		# check required fields have some content
		if (!trim($_POST['title'])) $this->o_error_list->AddControl(new XhtmlElement('li', 'Please give your topic a title'));
		if (!trim($_POST['message'])) $this->o_error_list->AddControl(new XhtmlElement('li', 'Please complete your message before clicking the "Post message" button'));

		if(!$this->o_error_list->CountControls()) #if no errors found, create topic
		{
			# create message
			$o_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
			$o_message->SetTitle($_POST['title']);
			$o_message->SetBody($_POST['message']);
			if (isset($_POST['icon'])) $o_message->SetIcon($_POST['icon']);

			# create topic
			$o_topic = new ForumTopic($this->GetSettings());
			$o_topic->SetCategory($this->GetCategories()->GetById($_POST['category_id']));
			$o_topic->Add($o_message);

			# write to db
			$topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
			$topic_manager->SaveNewTopic($o_topic);
			$o_topic = $topic_manager->GetFirst();

            # Update topic in search engine
            require_once ("search/lucene-search.class.php");
            $search = new LuceneSearch();
            $search->IndexTopic($o_topic, $topic_manager);
            $search->CommitChanges();
            unset($topic_manager);

			# update new message
			$o_message = $o_topic->GetFinal();

			# send subscription emails
			$o_subs = new SubscriptionManager($this->GetSettings(), $this->GetDataConnection());
			$o_subs->SetTopic($o_topic);
			$o_subs->SendCategorySubscriptions($this->GetCategories()->GetById($this->i_category_id));

			# add topic subscription if requested
			if (isset($_POST['subscribe'])) $o_subs->SaveSubscription($o_topic->GetId(), ContentType::TOPIC, AuthenticationManager::GetUser()->GetId());

			# redirect user back to the page they came from
			header('Location: ' . $o_message->GetNavigateUrl(true, false, false));
			exit();
		}
	}
}
new NewTopicPage(new StoolballSettings(), PermissionType::ForumAddTopic(), false);
?>