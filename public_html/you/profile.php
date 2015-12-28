<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('forums/topic-manager.class.php');
require_once('markup/xhtml-markup.class.php');

class CurrentPage extends StoolballPage
{
	private $user;
	private $a_messages;

	function OnPageInit()
	{
		# check we've got a profile to view
		if (isset($_GET['id']) and is_numeric($_GET['id']) and $_GET['id'])
		{
			$this->user = new User();
			$this->user->SetId($_GET['id']);
		}
	}

	function OnLoadPageData()
	{
		$authentication_manager = $this->GetAuthenticationManager();
		$authentication_manager->ReadUserById(array($this->user->GetId()));
		$this->user = $authentication_manager->GetFirst();
        
        if (!is_null($this->user)) { 
    		$topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
    		$this->a_messages = $topic_manager->ReadMessagesByUser($this->user->GetId());
    		unset($topic_manager);
        }  else 
            {
                http_response_code(404);
            }
	}

	function OnPrePageLoad()
	{
        if (!is_null($this->user)) { 
    		$this->SetPageTitle($this->user->GetName() . "'s profile at " . $this->GetSettings()->GetSiteName());
        }
        else 
        {
            $this->SetPageTitle("Page not found");
        }
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		if ($this->user instanceof User)
		{
			echo '<div typeof="sioc:UserAccount" about="' . Html::Encode($this->user->GetLinkedDataUri()) . '">';
			echo '<h1>' . Html::Encode($this->user->GetName()) . '</h1>';

			# table of personal info
			$o_table = new XhtmlElement('table');
			$o_table->SetCssClass('profile-info');
			$o_table->AddAttribute('summary', 'Personal information about ' . $this->user->GetName());

			$s_real_name = $this->user->GetRealName();
			if ($s_real_name and ($s_real_name != $this->user->GetName()))
			{
				$o_tr = new XhtmlElement('tr');
				$o_tr->AddControl(new XhtmlElement('th', 'Real name'));
				$o_tr->AddControl(new XhtmlElement('td', Html::Encode($s_real_name)));
				$o_table->AddControl($o_tr);
			}

			if ($this->user->GetSignUpDate())
			{
				$o_tr = new XhtmlElement('tr');
				$o_tr->AddControl(new XhtmlElement('th', 'Signed up'));
				$o_tr->AddControl(new XhtmlElement('td', Date::BritishDate($this->user->GetSignUpDate())));
				$o_table->AddControl($o_tr);
			}

			if ($this->user->GetGender())
			{
				$o_tr = new XhtmlElement('tr');
				$o_tr->AddControl(new XhtmlElement('th', 'Gender'));
				$cell = new XhtmlElement('td', Html::Encode(ucfirst($this->user->GetGender())));
				$cell->AddAttribute("property", "gender");
				$o_tr->AddControl($cell);
				$o_table->AddControl($o_tr);
			}

			if ($this->user->GetOccupation())
			{
				$o_tr = new XhtmlElement('tr');
				$o_tr->AddControl(new XhtmlElement('th', 'Occupation'));
				$o_tr->AddControl(new XhtmlElement('td', Html::Encode($this->user->GetOccupation())));
				$o_table->AddControl($o_tr);
			}

			if ($this->user->GetInterests())
			{
				$o_tr = new XhtmlElement('tr');
				$o_tr->AddControl(new XhtmlElement('th', 'Interests'));
				$o_tr->AddControl(new XhtmlElement('td', $this->ApplyInterestsMarkup($this->user->GetInterests())));
				$o_table->AddControl($o_tr);
			}

			if ($this->user->GetLocation())
			{
				$o_tr = new XhtmlElement('tr');
				$o_tr->AddControl(new XhtmlElement('th', 'Location'));
				$o_tr->AddControl(new XhtmlElement('td', Html::Encode($this->user->GetLocation())));
				$o_table->AddControl($o_tr);
			}

			echo $o_table;

			if(isset($this->a_messages) and is_array($this->a_messages) and count($this->a_messages))
			{
				$s_count = (count($this->a_messages) > 10) ? '10 newest of ' . $this->user->GetTotalMessages() . ' ' : '';

				echo new XhtmlElement('h2', Html::Encode($this->user->GetName() . "'s " . $s_count . 'comments'));

				$o_list = new XhtmlElement('ul');
				
				foreach($this->a_messages as $o_message)
				{
					$o_item = new XhtmlElement('li');
					$subscribed_to = $o_message->GetReviewItem()->GetTitle(); 
					if ($o_message->GetReviewItem() instanceof ReviewItem and $o_message->GetReviewItem()->GetNavigateUrl(true)) {
					   $subscribed_to = '<a typeof="sioc:Post" about="' . Html::Encode($o_message->MessageLinkedDataUri()) . '" href="' . Html::Encode($o_message->GetReviewItem()->GetNavigateUrl(true)) . '#message' . $o_message->GetId() . '">' . $subscribed_to . '</a>';
                    }
                    $o_item->AddControl($subscribed_to);
					$o_item->AddControl('<div class="detail">Posted ' . Date::BritishDateAndTime($o_message->GetDate()) . '</div>');
                    $o_item->AddAttribute("rel", "sioc:creator_of");
					$o_list->AddControl($o_item);
				}
				echo $o_list;
			}
			
			# End sioc:UserAccount entity
			echo '</div>';

			if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_USERS_AND_PERMISSIONS))
			{ 	
				$this->AddSeparator();
	
				require_once("stoolball/user-edit-panel.class.php");
				$panel = new UserEditPanel($this->GetSettings(), "this user");
				$panel->AddLink("edit this user", $this->user->GetEditUserUrl());
				echo $panel;
			}
		}
		else
		{
            require_once($_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/stoolball/section-404.php");
		}
	}

	function ApplyInterestsMarkup($text, $b_strip_tags=0)
	{
		if ($text)
		{
			$text = HTML::Encode($text);

			$text = XhtmlMarkup::ApplyCharacterEntities($text);

            require_once('email/email-address-protector.class.php');
            $protector = new EmailAddressProtector($this->GetSettings());
            $text = $protector->ApplyEmailProtection($text, AuthenticationManager::GetUser()->IsSignedIn());

			$text = XhtmlMarkup::ApplyParagraphs($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplyLists($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplyLinks($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplySimpleTags($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplySimpleXhtmlTags($text, $b_strip_tags);
			$text = XhtmlMarkup::CloseUnmatchedTags($text);
		}

		return $text;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>