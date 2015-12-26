<?php
require_once('forum-topic.class.php');
require_once('text/bad-language-filter.class.php');
require_once('text/string-formatter.class.php');
require_once('data/data-manager.class.php');

class SubscriptionManager extends DataManager
{
	var $o_topic;
	var $a_emails; # array of emails already sent to - prevent multiple emails per person for one new message
	var $s_review_item_title;
	/**
	 * Categories for the forum
	 * @var CategoryCollection
	 */
	private $categories;

	public function SetTopic(ForumTopic $o_input)
	{
		$this->o_topic = $o_input;
	}

	public function GetTopic()
	{
		return $this->o_topic;
	}

	/**
	 * Sets the forum categories which may have been subscribed to
	 * @param CategoryCollection $categories
	 * @return void
	 */
	public function SetCategories(CategoryCollection $categories)
	{
		$this->categories = $categories;
	}

	/**
	 * Gets the forum categories which may have been subscribed to
	 * @return CategoryCollection
	 */
	public function GetCategories()
	{
		return $this->categories;
	}

	private function GetEmailAddresses($s_sql, Zend_Mail $email)
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
				case ContentType::TOPIC:
					$o_sub->SetTitle($o_row->title);
                    $category = $this->categories->GetById($o_row->topic_forum_id);
					if ($category)
					{
					    $o_sub->SetCategory($category);
                    }
					break;

				case ContentType::FORUM:
					$o_sub->SetTitle($o_row->forum_name);
					break;

				case ContentType::PAGE_COMMENTS:
					$o_sub->SetTitle($o_row->page_name);
					break;

				case ContentType::STOOLBALL_MATCH:
					$o_sub->SetTitle($o_row->match_title);
					$o_sub->SetContentDate(Date::BritishDate($o_row->start_time));
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
		$s_footer .= "\n\nIf you don't want to get an email like this again, you can unsubscribe\non the email alerts page at http://" . $this->GetSettings()->GetDomain() . $this->GetSettings()->GetUrl('AccountEdit');
		return $s_footer;
	}

	function SendCategorySubscriptions(Category $o_category)
	{
		if (is_object($this->o_topic) and AuthenticationManager::GetUser()->IsSignedIn())
		{
			$s_person = $this->GetSettings()->GetTable('User');
			$s_sub = $this->GetSettings()->GetTable('EmailSubscription');

			# query db for all of this person's category subscriptions
			$s_sql = 'SELECT ' . $s_person . '.email ' .
			'FROM ' . $s_person . ' INNER JOIN ' . $s_sub . ' ON ' . $s_person . '.user_id = ' . $s_sub . '.user_id AND ' . $s_sub . ".item_type = " . ContentType::FORUM . " " .
			'WHERE ' . $s_sub . '.item_id = ' . Sql::ProtectNumeric($o_category->GetId()) . ' AND ' . $s_person . '.user_id <> ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());

			# if there's at least one person, build email
			require_once 'Zend/Mail.php';
			$email = new Zend_Mail('UTF-8');
			if ($this->GetEmailAddresses($s_sql, $email))
			{
				$o_message = $this->o_topic->GetFinal();

				# send the email
				$email->addTo($this->GetSettings()->GetSubscriptionEmailTo());
				$email->setFrom($this->GetSettings()->GetSubscriptionEmailFrom(), $this->GetSettings()->GetSubscriptionEmailFrom());
				$email->setSubject("Email alert: " . $o_category->GetName());
				$email->setBodyText($this->GetHeader() .
				trim(AuthenticationManager::GetUser()->GetName()) . " has just posted a message in '" . $this->GetSettings()->GetSiteName() . ' ' . $o_category->GetName() . "', for which you subscribed to an email alert.\n\n" .
				"The topic is called '" . StringFormatter::PlainText($this->o_topic->GetFilteredTitle()) . "' - here's an excerpt of the new message:\n\n" . $o_message->GetExcerpt() . "\n\n" .
				"The topic is located at\n" . $o_message->GetNavigateUrl(true, false) . $this->GetFooter());

				try
				{
					$email->send();
				}
				catch (Zend_Mail_Transport_Exception $e)
				{
					# Do nothing - email not that important so, if it fails, fail silently rather than raising a fatal error
				}
			}
		}
	}

	function SendTopicSubscriptions()
	{
		if(is_object($this->o_topic) and AuthenticationManager::GetUser()->IsSignedIn())
		{
			$s_person = $this->GetSettings()->GetTable('User');
			$s_sub = $this->GetSettings()->GetTable('EmailSubscription');

			$s_sql = 'SELECT ' . $s_person . '.user_id, email ' .
			'FROM ' . $s_person . ' INNER JOIN ' . $s_sub . ' ON ' . $s_person . '.user_id = ' . $s_sub . '.user_id AND ' . $s_sub . ".item_type = " . ContentType::TOPIC . " " .
			'WHERE ' . $s_sub . '.item_id = ' . Sql::ProtectNumeric($this->o_topic->GetId()) . ' AND ' . $s_person . '.user_id <> ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());

			# if there's at least one person, build email
			require_once 'Zend/Mail.php';
			$email = new Zend_Mail('UTF-8');
			if ($this->GetEmailAddresses($s_sql, $email))
			{
				$o_message = $this->o_topic->GetFinal();

				# send the email
				$s_title = StringFormatter::PlainText($this->o_topic->GetFilteredTitle());
				$email->addTo($this->GetSettings()->GetSubscriptionEmailTo());
				$email->setFrom($this->GetSettings()->GetSubscriptionEmailFrom(), $this->GetSettings()->GetSubscriptionEmailFrom());
				$email->setSubject("Email alert: '" . $s_title . "'");

				$email->setBodyText($this->GetHeader() .
				trim(AuthenticationManager::GetUser()->GetName()) . ' has just replied to a topic in the ' . $this->GetSettings()->GetSiteName() . ' forum for which you subscribed to an email alert.' . "\n\n" .
				"The topic is called '" . $s_title . "' - here's an excerpt of the new reply:\n\n" . $o_message->GetExcerpt() . "\n\n" .
				"The topic is located at\n" . $o_message->GetNavigateUrl(true, false) . $this->GetFooter());

				try
				{
					$email->send();
				}
				catch (Zend_Mail_Transport_Exception $e)
				{
					# Do nothing - email not that important so, if it fails, fail silently rather than raising a fatal error
				}

			}
		}
	}

	function SendCommentsSubscriptions(ReviewItem $review_item)
	{
		# get all subscriptions for this item
		if(is_object($this->o_topic) and AuthenticationManager::GetUser()->IsSignedIn() and $review_item->GetId())
		{
			$s_person = $this->GetSettings()->GetTable('User');
			$s_sub = $this->GetSettings()->GetTable('EmailSubscription');

			# join to item's table to get the title, regardless of message title
			$s_sql = '';

			switch ($review_item->GetType())
			{
				case ContentType::PAGE_COMMENTS:
					$s_cat = $this->GetSettings()->GetTable('Category');
					$s_sql = 'SELECT ' . $s_cat . '.name AS title, ' . $s_person . '.email ' .
					'FROM (' . $s_person . ' INNER JOIN ' . $s_sub . ' ON ' . $s_person . '.user_id = ' . $s_sub . '.user_id AND ' . $s_sub . ".item_type = " . ContentType::PAGE_COMMENTS . ") " .
					'INNER JOIN ' . $s_cat . ' ON ' . $s_sub . '.item_id = ' . $s_cat. '.id AND ' . $s_sub . ".item_type = " . ContentType::PAGE_COMMENTS . " " .
					'WHERE ' . $s_sub . '.item_id = ' . Sql::ProtectNumeric($review_item->GetId()) . ' AND ' . $s_person . '.user_id <> ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());
					break;
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
				require_once 'Zend/Mail.php';
				$email = new Zend_Mail('UTF-8');
				if ($this->GetEmailAddresses($s_sql, $email))
				{
					$o_filter = new BadLanguageFilter();
					$s_title = $o_filter->Filter($this->s_review_item_title);
					unset($o_filter);

					$s_title = StringFormatter::PlainText($s_title);

					$o_message = $this->o_topic->GetFinal();

					# send the email
					$email->addTo($this->GetSettings()->GetSubscriptionEmailTo());
					$email->setFrom($this->GetSettings()->GetSubscriptionEmailFrom(), $this->GetSettings()->GetSubscriptionEmailFrom());
					$email->setSubject("Email alert: '" . $s_title . "'");

					$email->setBodyText($this->GetHeader() .
					trim(AuthenticationManager::GetUser()->GetName()) . ' has just commented on a page at ' . $this->GetSettings()->GetSiteName() . ' for which you subscribed to an email alert.' . "\n\n" .
					"The page is called '" . $s_title . "' - here's an excerpt of the new comments:\n\n" . $o_message->GetExcerpt() . "\n\n" .
					'View the new comments at' . "\n" .$review_item->GetNavigateUrl(false) . '#message' . $o_message->GetId() .
					$this->GetFooter());

					try
					{
						$email->send();
					}
					catch (Zend_Mail_Transport_Exception $e)
					{
						# Do nothing - email not that important so, if it fails, fail silently rather than raising a fatal error
					}

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
		$s_topic = $this->GetSettings()->GetTable('ForumTopic');
		$s_message = $this->GetSettings()->GetTable('ForumMessage');
		$s_cat = $this->GetSettings()->GetTable('Category');
		$s_match = $this->GetSettings()->GetTable('Match');

		$s_sql = 'SELECT ' . $s_sub . '.item_id, ' . $s_sub . '.item_type, ' . $s_sub . ".date_changed";

		if ($this->GetSettings()->HasContent(ContentType::FORUM))
		{
			$s_sql .= ', ' . $s_message . '.title, ' . $s_cat . '.id AS topic_forum_id, ' .
				's_forums.name AS forum_name';
		}
		if ($this->GetSettings()->HasContent(ContentType::PAGE_COMMENTS))
		{
			$s_sql .= ', s_pages.name AS page_name';
		}
		if ($this->GetSettings()->HasContent(ContentType::STOOLBALL_MATCH))
		{
			$s_sql .= ', ' . $s_match . '.match_title, ' . $s_match . '.start_time';
		}

		$s_sql .= ' FROM (((((((' . $s_sub;

		$s_sql .= ($this->GetSettings()->HasContent(ContentType::FORUM))
		?	' LEFT JOIN ' . $s_topic . ' ON ' . $s_sub . '.item_id = ' . $s_topic . '.id AND ' . $s_sub . ".item_type = " . ContentType::TOPIC . ") " .
				' LEFT JOIN ' . $s_message . ' ON ' . $s_topic . '.first_message_id = ' . $s_message . '.id) ' .
				' LEFT JOIN ' . $s_cat . ' ON ' . $s_topic . '.category_id = ' . $s_cat . '.id) ' .
				' LEFT JOIN ' . $s_cat . ' AS s_forums ON ' . $s_sub . '.item_id = s_forums.id AND ' . $s_sub . ".item_type = " . ContentType::FORUM . ") "
				:	')))) ';

				$s_sql .= ') ';

				$s_sql .= ($this->GetSettings()->HasContent(ContentType::PAGE_COMMENTS))
				?	' LEFT JOIN ' . $s_cat . ' AS s_pages ON ' . $s_sub . '.item_id = s_pages.id AND ' . $s_sub . ".item_type = " . ContentType::PAGE_COMMENTS . ") "
				:	') ';

				$s_sql .= ') ';

				if ($this->GetSettings()->HasContent(ContentType::STOOLBALL_MATCH))
				{
					$s_sql .= ' LEFT JOIN ' . $s_match . ' ON ' . $s_sub . '.item_id = ' . $s_match . '.match_id AND ' . $s_sub . ".item_type = " . ContentType::STOOLBALL_MATCH . " " ;
				}
				else $s_sql .= ' ';

				$s_sql .= ' WHERE ' . $s_sub . '.user_id = ' .  Sql::ProtectNumeric($user_id, false) .
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