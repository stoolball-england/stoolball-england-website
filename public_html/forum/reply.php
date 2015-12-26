<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('page/stoolball-page.class.php');
require_once ('forums/forum-message-form.class.php');
require_once ('forums/topic-manager.class.php');
require_once ('forums/forum-topic-listing.class.php');
require_once ('forums/subscription-manager.class.php');
require_once ('forums/review-item.class.php');

class ReplyPage extends StoolballPage
{
    private $i_topic_id;
    private $i_review_type;
    private $o_topic;
    private $o_error_list;

    function OnPageInit()
    {
        # store topic id
        if (isset($_GET['topic']) and $_GET['topic'])
            $this->i_topic_id = $_GET['topic'];
        else if (isset($_POST['topic_id']) and $_POST['topic_id'])
            $this->i_topic_id = $_POST['topic_id'];

        # store review type
        if (isset($_POST['type']) and $_POST['type'])
            $this->i_review_type = $_POST['type'];
        else if (isset($_GET['type']) and $_GET['type'])
            $this->i_review_type = $_GET['type'];

        parent::OnPageInit();
    }

    function OnLoadPageData()
    {
        # get topic info
        $o_topic_reader = new TopicManager($this->GetSettings(), $this->GetDataConnection());
        $o_topic_reader->SetReverseOrder(true);
        $o_topic_reader->ReadById(array($this->i_topic_id));

        $this->o_topic = $o_topic_reader->GetFirst();
        unset($o_topic_reader);
    }

    function OnPrePageLoad()
    {
        $this->SetPageTitle('Reply to topic');
        $this->LoadClientScript("/scripts/tiny_mce/jquery.tinymce.js");
        $this->LoadClientScript("/scripts/tinymce.js");
    }

    function OnPageLoad()
    {
        # display page heading
        echo '<h1>Reply to topic</h1>';

        # display any errors
        if (isset($this->o_error_list))
            echo $this->o_error_list;

        # create review item
        $o_review_item = new ReviewItem($this->GetSettings());
        $o_review_item->SetType($this->i_review_type);
        $this->o_topic->SetReviewItem($o_review_item);

        # create form
        $o_form = new ForumMessageForm($this->GetSettings());
        $o_form->SetFormat(ForumMessageFormat::Reply());
        $o_form->SetTopic($this->o_topic);
        echo $o_form->GetForm();

        $o_listing = new ForumTopicListing($this->GetSettings(), $this->GetContext(), AuthenticationManager::GetUser(), $this->o_topic);
        $o_listing->SetFormat(ForumMessageFormat::Reply());
        echo $o_listing;

        # run template method
        parent::OnPageLoad();
    }

    # processes new message when submitted
    function OnPostback()
    {
        # new list for validation
        $this->o_error_list = new XhtmlElement('ul');
        $this->o_error_list->AddAttribute('class', 'validationSummary');

        # check required fields have some content
        if (!trim($_POST['message']))
            $this->o_error_list->AddControl(new XhtmlElement('li', 'Please complete your message before clicking the "Post message" button'));

        # if no errors found, create message
        if (!$this->o_error_list->CountControls())
        {
            # create first message
            $o_first_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
            $o_first_message->SetTitle($_POST['topic_title']);

            # create new message
            $o_new_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
            $o_new_message->SetTitle($_POST['title']);
            $o_new_message->SetBody($_POST['message']);
            if (isset($_POST['icon']))
                $o_new_message->SetIcon($_POST['icon']);

            # create topic
            $o_topic = new ForumTopic($this->GetSettings());
            $o_topic->SetId($this->i_topic_id);
            $o_topic->Add($o_first_message);
            $o_topic->Add($o_new_message);

            # check for double-posting
            if (!AuthenticationManager::GetUser()->MatchLastMessage($_POST['message']))
            {
                $topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
                $topic_manager->SaveReply($o_topic);
                $o_topic = $topic_manager->GetFirst();
                
                # Update topic in search engine
                require_once ("search/lucene-search.class.php");
                $search = new LuceneSearch();
                $search->DeleteDocumentById("topic" . $o_topic->GetId());
                $search->IndexTopic($o_topic, $topic_manager);
                $search->CommitChanges();
                unset($topic_manager);

                # prevent double-posting
                AuthenticationManager::GetUser()->SetLastMessage($_POST['message']);

                # update new message
                $o_new_message = $o_topic->GetFinal();

                # send subscription emails
                $o_subs = new SubscriptionManager($this->GetSettings(), $this->GetDataConnection());
                $o_subs->SetTopic($o_topic);

                $o_subs->SendCategorySubscriptions($this->GetCategories()->GetById($_POST['category_id']));
                $o_subs->SendTopicSubscriptions();

                if ($this->i_review_type)
                {
                    $o_review_item = new ReviewItem($this->GetSettings());
                    $o_review_item->SetType($this->i_review_type);
                    $o_review_item->SetId($_POST['item']);
                    $o_subs->SendCommentsSubscriptions($o_review_item);
                }

                # add topic subscription if requested
                if (isset($_POST['subscribe']) and $_POST['subscribe'])
                    $o_subs->SaveSubscription($o_topic->GetId(), ContentType::TOPIC, AuthenticationManager::GetUser()->GetId());
            }

            # TODO: If post same message twice by going to reply form twice and
            # entering same text (rather than refreshing)
            # result is "else" case here, redirects to new message with no id,
            # which redirects to forum home
            # Else case should instead redirect to most recent post in topic
            # (whatever it is - likely to be this)

            # redirect user back to the page they came from
            header('Location: ' . $o_new_message->GetNavigateUrl(true, false, false));
            exit();
        }
    }

}

new ReplyPage(new StoolballSettings(), PermissionType::ForumAddMessage(), false);
?>