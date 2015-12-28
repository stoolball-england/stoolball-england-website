<?php
require_once('forum-topic.class.php');
require_once('forum-message-format.enum.php');
require_once('search/search-term.class.php');
require_once('search/search-highlighter.class.php');
require_once('xhtml/placeholder.class.php');

class ForumTopicListing extends Placeholder
{
	var $o_settings;
	var $o_user;
	/**
	 * The topic being displayed
	 *
	 * @var ForumTopic
	 */
	var $topic;
	var $o_navbar;
	var $i_format;

	function ForumTopicListing(SiteSettings $o_settings, User $o_user, ForumTopic $topic)
	{
		$this->o_settings = $o_settings;
		$this->o_user = $o_user;
		$this->topic = $topic;
        $this->SetFormat(ForumMessageFormat::Review());
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
		if ($this->GetFormat() == ForumMessageFormat::ReviewReply())
		{
			if ($this->topic->GetCount()) # if no messages, write nothing)
			{
				return '<h2 class="forumTopicReview">Review comments so far <span class="sort-order">(newest first)</span></h2>';
			}
            return '';
		}
        else if ($this->GetFormat() == ForumMessageFormat::Review()) {
            return '<div id="comments-topic">' . "\n" .
            '<h2>Add your comments</h2>';
        }
	}

	function GetFormattedMessages()
	{
		/* @var $o_person User */
		/* @var $o_message ForumMessage */

		$s_text = '';

		$a_messages = $this->topic->GetItems();

		if (is_array($a_messages))
		{
			$s_text .= '<div class="forumTopic" typeof="sioc:Thread" about="' . $this->topic->TopicLinkedDataUri() . '">' . "\n";
            
            $message_type = $this->topic->GetReviewItem() ? "Comment" : "BoardPost";

			$b_alternate = true;

			$position_in_topic = 1;
            $total_messages = count($a_messages);
            $first_message = ($position_in_topic-1);
            $last_message = $total_messages-1;
            
            for ($i = $first_message; $i<=$last_message; $i++)
			{
				/* @var $message ForumMessage */
				$message = $a_messages[$i];
                
				# get person
				$o_person = $message->GetUser();

				# highlight search terms
				if (isset($_GET['hi']) and $_GET['hi'])
				{
					$o_term = new SearchTerm($_GET['hi']);
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

                # add profile
                $s_text .= '<div class="profile';
                if ($b_alternate) $s_text .= " altProfile";
                $s_text .= '"><p about="' . $message->MessageLinkedDataUri() . '"><span class="small">Posted by </span>' . 
                '<span rel="sioc:has_creator" rev="sioc:creator_of"><a typeof="sioc:UserAccount" about="' . $o_person->GetLinkedDataUri() . '" href="' . $o_person->GetUserProfileUrl() . '">' . $o_person->GetName() . '</a></span>';
                if ($o_person->GetLocation()) $s_text .= '<span class="small">, ' . $o_person->GetLocation() . "</span>";
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
                $s_text .= '"><div typeof="awol:Content"><meta property="awol:type" content="text/html" /><div property="awol:body">';
                $s_text .= $message->GetFormattedBody($this->o_settings);
                $s_text .= "</div></div>";
                
				$s_text .= '</div></div>' . "\n";

				$b_alternate = !$b_alternate;
			}

			$s_text .= '<hr /></div>' . "\n\n";

			return $s_text;
		}
	}

	function GetFooter()
	{
	    if ($this->GetFormat() == ForumMessageFormat::Review()) {
	        return '</div>';
	    }
		return'';
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