<?php
require_once('forum-message.class.php');
require_once('subscription.class.php');
require_once('text/bad-language-filter.class.php');
require_once('text/string-formatter.class.php');
require_once('data/data-manager.class.php');

class SubscriptionManager extends DataManager
{
	var $a_emails; # array of emails already sent to - prevent multiple emails per person for one new message
	var $s_review_item_title;

	private function GetEmailAddresses($s_sql, Swift_Message $email)
	{
		$a_send = null;

		if ($s_sql)
		{
			$result = $this->GetDataConnection()->query($s_sql);

			while($o_row = $result->fetch())
			{
				# first up, if this is a review item, get the title
				if (isset($o_row->title)) $this->s_review_item_title = $o_row->title;

				# check if person in the previous subscriptions
				if (!is_array($this->a_emails) or !in_array($o_row->email, $this->a_emails))
				{
					#...add to email list
					$a_send[] = $o_row->email;

					# ... add also to list of people sent, to exclude from further emails
					$this->a_emails[] = $o_row->email;
				}
			}

			$result->closeCursor();

			if (is_array($a_send))
			{
				foreach ($a_send as $address) $email->addBcc($address);
			}
		}

		return is_array($a_send);
	}

	/**
	 * (non-PHPdoc)
	 * @see data/DataManager#BuildItems($o_result)
	 */
	public function BuildItems(MySqlRawData $result)
	{
		$this->Clear();
		while($o_row = $result->fetch())
		{
			$o_sub = new Subscription($this->GetSettings());
			$o_sub->SetType($o_row->item_type);
			$o_sub->SetSubscribeDate($o_row->date_changed);
			$o_sub->SetSubscribedItemId($o_row->item_id);

			switch($o_sub->GetType())
			{
				case ContentType::STOOLBALL_MATCH:
					$o_sub->SetTitle($o_row->match_title);
					$o_sub->SetContentDate(Date::BritishDate($o_row->start_time));
                    $o_sub->SetSubscribedItemUrl($o_row->short_url);
					break;

			}

			$this->Add($o_sub);
		}
	}

	function GetHeader()
	{
		return '"*** DO NOT REPLY TO THIS EMAIL ALERT. USE THE LINK BELOW. ***' . "\n\n" .
		'Hi there!' . "\n\n";
	}

	function GetFooter()
	{
		$s_footer = $this->GetSettings()->GetEmailSignature();
		$s_footer .= "\n\nIf you don't want to get an email like this again, you can unsubscribe\non the email alerts page at https://" . $this->GetSettings()->GetDomain() . $this->GetSettings()->GetUrl('AccountEdit');
		return $s_footer;
	}

	function SendCommentsSubscriptions(ReviewItem $review_item, ForumMessage $message)
	{
		# get all subscriptions for this item
		if(AuthenticationManager::GetUser()->IsSignedIn() and $review_item->GetId())
		{
			$s_person = $this->GetSettings()->GetTable('User');
			$s_sub = $this->GetSettings()->GetTable('EmailSubscription');

			# join to item's table to get the title, regardless of message title
			$s_sql = '';

			switch ($review_item->GetType())
			{
				case ContentType::STOOLBALL_MATCH:
					$matches = $this->GetSettings()->GetTable('Match');
					$s_sql = "SELECT $matches.match_title AS title, $s_person.email
					FROM ($s_person INNER JOIN $s_sub ON $s_person.user_id = $s_sub.user_id AND $s_sub.item_type = " . ContentType::STOOLBALL_MATCH . ")
					INNER JOIN $matches ON $s_sub.item_id = $matches.match_id AND $s_sub.item_type = " . ContentType::STOOLBALL_MATCH . "
					WHERE $s_sub.item_id = " . Sql::ProtectNumeric($review_item->GetId()) . " AND $s_person.user_id <> " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());
					break;
			}
			if ($s_sql)
			{
				# if there's at least one person, build email
				$email = new Swift_Message();
				if ($this->GetEmailAddresses($s_sql, $email))
				{
					$o_filter = new BadLanguageFilter();
					$s_title = $o_filter->Filter($this->s_review_item_title);
					unset($o_filter);

					$s_title = StringFormatter::PlainText($s_title);

					# send the email
					$mailer = new Swift_Mailer($this->GetSettings()->GetEmailTransport());
					$email->setFrom([$this->GetSettings()->GetSubscriptionEmailFrom() => $this->GetSettings()->GetSubscriptionEmailFrom()])
					->setTo([$this->GetSettings()->GetSubscriptionEmailTo()])
					->setSubject("Email alert: '" . $s_title . "'")
					->setBody($this->GetHeader() .
					trim(AuthenticationManager::GetUser()->GetName()) . ' has just commented on a page at ' . $this->GetSettings()->GetSiteName() . ' for which you subscribed to an email alert.' . "\n\n" .
					"The page is called '" . $s_title . "' - here's an excerpt of the new comments:\n\n" . $message->GetExcerpt() . "\n\n" .
					'View the new comments at' . "\n" .$review_item->GetNavigateUrl() . '#message' . $message->GetId() .
					$this->GetFooter());
					$mailer->send($email);
				}
			}
		}
	}

	/**
	 * Reads all the things the given user has subscribed to notifications for
	 * @param int $user_id
	 * @return void
	 */
	public function ReadSubscriptionsForUser($user_id)
	{
		$s_sub = $this->GetSettings()->GetTable('EmailSubscription');
		$s_match = $this->GetSettings()->GetTable('Match');

		$s_sql = 'SELECT ' . $s_sub . '.item_id, ' . $s_sub . '.item_type, ' . $s_sub . ".date_changed, " . 
		          $s_match . '.match_title, ' . $s_match . '.start_time, ' . $s_match . ".short_url" .
                ' FROM ' . $s_sub . ' ' .
                ' LEFT JOIN ' . $s_match . ' ON ' . $s_sub . '.item_id = ' . $s_match . '.match_id AND ' . $s_sub . ".item_type = " . ContentType::STOOLBALL_MATCH .
		        ' WHERE ' . $s_sub . '.user_id = ' .  Sql::ProtectNumeric($user_id, false) .
			    ' ORDER BY ' . $s_sub . '.item_type';

		$result = $this->GetDataConnection()->query($s_sql);
		$this->BuildItems($result);
		$result->closeCursor();
	}

	/**
	 * Saves a new subscription
	 * @param int $item_subscribed_to_id
	 * @param int $content_type
	 * @param int $user_id
	 * @return void
	 */
	public function SaveSubscription($item_subscribed_to_id, $content_type, $user_id)
	{
		if ($item_subscribed_to_id and $user_id and $content_type)
		{
			$s_sql = 'REPLACE INTO ' . $this->GetSettings()->GetTable('EmailSubscription') . ' SET ' .
			'date_changed = ' . gmdate('U') . ', ' .
			'item_id = ' . Sql::ProtectNumeric($item_subscribed_to_id, false) . ', ' .
			'user_id = ' . Sql::ProtectNumeric($user_id, false) . ', ' .
			"item_type = " . Sql::ProtectNumeric($content_type, false);

			$this->Lock($this->GetSettings()->GetTable('EmailSubscription'));
			$this->GetDataConnection()->query($s_sql);
			$this->Unlock();
		}
	}

	/**
	 * Deletes a subscription for the current user
	 * @param int $item_subscribed_to_id
	 * @param int $content_type
	 * @param int $user_id
	 * @return void
	 */
	public function DeleteSubscription($item_subscribed_to_id, $content_type, $user_id)
	{
		$s_sub = $this->GetSettings()->GetTable('EmailSubscription');
		$s_sql = 'DELETE FROM ' . $s_sub . ' WHERE ' .
				'item_id = ' . Sql::ProtectNumeric($item_subscribed_to_id, false) . ' ' .
				'AND user_id = ' . Sql::ProtectNumeric($user_id, false) . ' ' .
				"AND item_type = " . Sql::ProtectNumeric($content_type, false);

		$this->GetDataConnection()->query($s_sql);

	}
}
?>