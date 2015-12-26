<?php
require_once('forum-topic.class.php');
require_once('data/paged-results.class.php');
require_once('forum-message-format.enum.php');
require_once('search/search-term.class.php');
require_once('search/search-highlighter.class.php');
require_once('xhtml/placeholder.class.php');

class ForumTopicListing extends Placeholder
{
	var $o_settings;
	var $o_context;
	var $o_user;
	/**
	 * The topic being displayed
	 *
	 * @var ForumTopic
	 */
	var $topic;
	var $o_navbar;
	var $i_page_size;
	var $i_format;

	function ForumTopicListing(SiteSettings $o_settings, SiteContext $o_context, User $o_user, ForumTopic $topic)
	{
		$this->o_settings = $o_settings;
		$this->o_context = $o_context;
		$this->o_user = $o_user;
		$this->topic = $topic;

		$this->SetFormat(ForumMessageFormat::Standard());
	}

	function SetNavbar($o_input)
	{
		if (is_object($o_input)) $this->o_navbar = $o_input;
	}

	function GetNavbar()
	{
		return $this->o_navbar;
	}

	function GetFormattedNavbar()
	{
		if (is_object($this->o_navbar) and is_object($this->topic) and is_object($this->topic->o_review_item))
		return $this->o_navbar->__toString();
		else
		return null;
	}

	function SetPageSize($i_input)
	{
		if (is_numeric($i_input)) $this->i_page_size = (int)$i_input;
	}

	function GetPageSize()
	{
		return $this->i_page_size;
	}

	function GetFormat()
	{
		return $this->i_format;
	}

	function SetFormat($i_input)
	{
		if (is_numeric($i_input)) $this->i_format = (int)$i_input;
	}

	function GetHeader()
	{
		if ($this->topic->GetCount()) # if no messages, write nothing
		{
			switch($this->GetFormat())
			{
				case ForumMessageFormat::ReviewReply():
					$s_messages_desc = 'comments so far';
					break;
				case ForumMessageFormat::Reply():
					$s_messages_desc = 'messages in topic';
					break;
				default:
					$s_messages_desc = '';
					break;
			}

			if ($s_messages_desc) return '<h2 class="forumTopicReview">Review ' . $s_messages_desc . ' <span class="sort-order">(Newest first)</span></h2>';
		}
	}

	function GetFormattedMessages()
	{
		/* @var $o_person User */
		/* @var $o_message ForumMessage */

		$s_text = '';

		switch($this->GetFormat())
		{
			case ForumMessageFormat::Standard() or ForumMessageFormat::Reply():
				$s_message_header = 'Message';
				break;
			case ForumMessageFormat::ReviewReply():
				$s_message_header = 'Comments';
				break;
		}

		$a_messages = $this->topic->GetItems();

		if (is_array($a_messages))
		{
			if ($this->GetPageSize() != null)
			{
				# set up paging based on user's preference
				$o_paging = new PagedResults();
				$o_paging->SetPageSize($this->o_user->GetPageSize('forummessages'), $this->GetPageSize());
				$o_paging->SetResultsTextSingular('message');
				$o_paging->SetResultsTextPlural('messages');
				$o_paging->SetTotalResults($this->topic->GetCount());
				if (isset($_GET['message']) and $_GET['message'] and !(isset($_GET['page']) and $_GET['page'])) $o_paging->SetCurrentPage($o_paging->GetPageForId($_GET['message'], $a_messages));

				# trim results not needed for this page
			# 	$a_messages = $o_paging->TrimRows($a_messages);

				# write the paging navbar
				$s_text = $o_paging->GetNavigationBar();
			}

			$s_text .= '<div class="forumTopic" typeof="sioc:Thread" about="' . $this->topic->TopicLinkedDataUri() . '">' . "\n";
            
            $message_type = $this->topic->GetReviewItem() ? "Comment" : "BoardPost";

			$b_alternate = true;

			$position_in_topic = isset($o_paging) ? $o_paging->GetFirstResultOnPage() : 1;
            $total_messages = count($a_messages);
            $first_message = ($position_in_topic-1);
            $last_message = isset($o_paging) ? $o_paging->GetFinalResultOnPage()-1 : $total_messages-1;
            
            for ($i = $first_message; $i<=$last_message; $i++)
			{
				/* @var $message ForumMessage */
				$message = $a_messages[$i];
                
				# clean up previous
				$s_rating = '';

				# get person
				$o_person = $message->GetUser();

				# prepare rating
				if ($message->GetRating()) $s_rating = '<div class="rating">Rating: ' . $message->GetRating() . '/10</div>';

				# highlight search terms
				if (isset($_GET['hi']) and $_GET['hi'])
				{
					$o_term = new SearchTerm($_GET['hi']);
					$message->SetTitle(SearchHighlighter::Highlight($o_term->GetTerms(), $message->GetTitle()));
					$message->SetBody(SearchHighlighter::Highlight($o_term->GetTerms(), $message->GetBody()));
				}

                # Declare relationship between messages.
                $reply_of = ($i > 0) ? ' rel="sioc:reply_of" resource="' . $a_messages[$i-1]->MessageLinkedDataUri() . '"' : "";
                $has_reply = ($i<($total_messages-1)) ? ' rel="sioc:has_reply" resource="' . $a_messages[$i+1]->MessageLinkedDataUri() . '"' : "";

				# start message row
				$s_text .= '<hr /><div class="forumMessage';
				if ($b_alternate) $s_text .= " forumAltMessage";
				$s_text .= '" id="message' . $message->GetId() . '" typeof="sioctypes:' . $message_type . '" about="' . $message->MessageLinkedDataUri() . '"' . 
				            ' rel="sioc:has_container" rev="sioc:container_of" resource="' . $this->topic->TopicLinkedDataUri() . '">' . 
				            '<h2 class="aural" about="' . $message->MessageLinkedDataUri() . '"' . $reply_of . '><span about="' . $message->MessageLinkedDataUri() . '"' . $has_reply . '>Message ' . $position_in_topic . '</span></h2>';
				$position_in_topic++;

                # message title
                if($message->GetTitle() or $message->GetIconXhtml($this->o_settings)) $s_text .= '<h3 class="small">' . $message->GetIconXhtml($this->o_settings) . $message->GetFormattedTitle() . '</h3>' . "\n";

                # add profile
                $s_text .= '<div class="profile';
                if ($b_alternate) $s_text .= " altProfile";
                $s_text .= '"><p about="' . $message->MessageLinkedDataUri() . '"><span class="small">Posted by </span>' . 
                '<span rel="sioc:has_creator" rev="sioc:creator_of"><a typeof="sioc:UserAccount" about="' . $o_person->GetLinkedDataUri() . '" href="' . $o_person->GetUserProfileUrl() . '">' . $o_person->GetName() . '</a></span>';
                if ($o_person->GetLocation()) $s_text .= '<span class="small">, ' . $o_person->GetLocation() . "</span>";
                if ($o_person->Permissions()->HasPermission(PermissionType::MANAGE_FORUMS)) $s_text .= ' <strong class="forumRole">(Moderator)</strong>';
                $s_text .= '<span class="small" property="dcterms:created" content="' . Date::Microformat($message->GetDate()) . '"> at ' . Date::BritishDateAndTime($message->GetDate(), false, true, true) . "</span>";
                $s_text .= '</p>';
                $s_text .= '<ul class="large"><li>Posted: ' . Date::BritishDateAndTime($message->GetDate(), false, true, true) . '</li>' .
                    '<li>Signed up: ' . Date::MonthAndYear($o_person->GetSignUpDate()) . "</li>\n" .
                    '<li>Total messages: ' . $o_person->GetTotalMessages() . "</li>\n";
                if ($o_person->GetLocation()) $s_text .= '<li>Location: ' . $o_person->GetLocation() . '</li>' . "\n";
                $s_text .= '</ul>';
                $s_text .= '</div>';

                # add the message
                $s_text .= '<div about="' . $message->MessageLinkedDataUri() . '" rel="awol:content" class="message';
                if ($b_alternate) $s_text .= " altMessage";
                $s_text .= '"><div typeof="awol:Content"><meta property="awol:type" content="text/html" /><div property="awol:body">' . $s_rating;
                if($message->GetTitle() or $message->GetIconXhtml($this->o_settings))
                {
                    $title = $message->GetTitle() ? '<span about="' . $message->MessageLinkedDataUri() . '" property="dcterms:title">' . $message->GetFormattedTitle() . '</span>' : "";
                  $s_text .= '<h3 class="large">' . $message->GetIconXhtml($this->o_settings) . $title . '</h3>' . "\n";  
                } 
                $s_text .= $message->GetFormattedBody($this->o_settings);
                $s_text .= "</div></div>";
                
				# signature
				if ($o_person->GetSignature())
				{                    
					$signature = htmlentities($o_person->GetSignature(), ENT_QUOTES, "UTF-8", false);

					$signature = XhtmlMarkup::ApplyCharacterEntities($signature);

                    require_once('email/email-address-protector.class.php');
                    $protector = new EmailAddressProtector($this->o_settings);
                	$signature = $protector->ApplyEmailProtection($signature, $this->o_user->IsSignedIn());

					$signature = XhtmlMarkup::ApplyParagraphs($signature, false);
					$signature = XhtmlMarkup::ApplyLists($signature, false);
					$signature = XhtmlMarkup::ApplyLinks($signature, false);
					$signature = XhtmlMarkup::ApplySimpleTags($signature, false);
					$signature = XhtmlMarkup::ApplySimpleXhtmlTags($signature, false);
					$signature = XhtmlMarkup::CloseUnmatchedTags($signature);

					$s_text .= '<div class="signature">' . "\n" . $signature . '</div>' . "\n";
				}

				$s_text .= '</div></div>' . "\n";

				$b_alternate = !$b_alternate;
			}

			$s_text .= '<hr /></div>' . "\n\n";

			if ($this->GetPageSize() != null)
			{
				# write the paging navbar
				$s_text .= $o_paging->GetNavigationBar();
			}

			return $s_text;
		}
	}

	function GetFooter()
	{
		return'';  # method to be overriden
	}

	function OnPreRender()
	{
		$this->AddControl($this->GetHeader());

		# write one navbar, whether there are comments yet or not
		$this->AddControl($this->GetFormattedNavbar());

		# if there are comments, carry on writing
		if ($this->topic->GetCount())
		{
			$this->AddControl($this->GetFormattedMessages());
			$this->AddControl($this->GetFormattedNavbar());
		}

		$this->AddControl($this->GetFooter());

	}
}
?>