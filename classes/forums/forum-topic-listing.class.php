<?php
require_once('forum-topic.class.php');
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

	function __construct(SiteSettings $o_settings, User $o_user, ForumTopic $topic)
	{
		$this->o_settings = $o_settings;
		$this->o_user = $o_user;
		$this->topic = $topic;
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
				$s_text .= '"><p about="' . $message->MessageLinkedDataUri() . '"><span class="small">Posted by </span>';
				if (is_null($o_person)) {
					$s_text .= "<del>Account deleted</del>";
				} else {
					$s_text .= '<span rel="sioc:has_creator" rev="sioc:creator_of"><span typeof="sioc:UserAccount" about="' . $o_person->GetLinkedDataUri() . '">' . $o_person->GetName() . '</span></span>';
				}
                $s_text .= '<span class="small" property="dcterms:created" content="' . Date::Microformat($message->GetDate()) . '"> at ' . Date::BritishDateAndTime($message->GetDate(), false, true, true) . "</span>";
                $s_text .= '</p>';
				$s_text .= '<ul class="large"><li>Posted: ' . Date::BritishDateAndTime($message->GetDate(), false, true, true) . '</li>';
				if (!is_null($o_person)) {
					$s_text .= '<li>Signed up: ' . Date::MonthAndYear($o_person->GetSignUpDate()) . "</li>\n" .
					'<li>Total messages: ' . $o_person->GetTotalMessages() . "</li>\n";
				}
                $s_text .= '</ul>';
                $s_text .= '</div>';

                # add the message
                $s_text .= '<div about="' . $message->MessageLinkedDataUri() . '" rel="awol:content" class="message';
                if ($b_alternate) $s_text .= " altMessage";
                if ($i == $last_message) $s_text .= '" id="last-message';
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

	function OnPreRender()
	{
		if ($this->topic->GetCount())
		{
			$this->AddControl($this->GetFormattedMessages());
		}
	}
}
?>