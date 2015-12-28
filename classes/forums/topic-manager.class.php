<?php
require_once('data/data-manager.class.php');
require_once('forums/forum-message.class.php');
require_once('forums/forum-topic.class.php');
require_once('http/query-string.class.php');

/**
 * Read and write forum topics
 *
 */
class TopicManager extends DataManager
{
	private $b_reverse_order = false;
	/**
	 * Categories for the forum
	 * @var CategoryCollection
	 */
	private $categories;

	/**
	 * @return TopicManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Creates a new TopicManager instance
	 */
	public function __construct(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'ForumTopic';
	}

	/**
	 * Sets the forum categories which may contain topics
	 * @param CategoryCollection $categories
	 * @return void
	 */
	public function SetCategories(CategoryCollection $categories)
	{
		$this->categories = $categories;
	}

	/**
	 * Gets the forum categories which may contain topics
	 * @return CategoryCollection
	 */
	public function GetCategories()
	{
		return $this->categories;
	}

	/**
	 * Gets whether to read messages in reverse date order
	 *
	 * @return bool
	 */
	public function GetReverseOrder()
	{
		return $this->b_reverse_order;
	}

	/**
	 * Sets whether to read messages in reverse date order
	 *
	 * @param bool $b_input
	 */
	function SetReverseOrder($b_input)
	{
		$this->b_reverse_order = (bool)$b_input;
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the forum topics matching the supplied ids
	 */
	public function ReadById($a_ids=null)
	{
		if (is_array($a_ids))
		{
			$this->ValidateNumericArray($a_ids);
			$i_how_many = count($a_ids);
			if (!$i_how_many or is_null($a_ids[0]))
			{
				# Looks like a review item with no reviews, therefore no topic to read
				return;
			}
		}

		$s_person = $this->GetSettings()->GetTable('User');
        $s_message = $this->GetSettings()->GetTable('ForumMessage');
		$s_topic = $this->GetSettings()->GetTable('ForumTopic');

		# prepare command
		$s_sql = 'SELECT ' . $s_person . '.user_id, ' . $s_person . '.known_as, ' .
		'location, signature, ' . $s_person . ".date_added AS sign_up_date, " . $s_person . '.total_messages, ' .
		$s_message . '.id, ' . $s_message . '.title, message, icon, ' . $s_message . '.topic_id, ' .
		$s_message . ".date_added AS message_date, category_id " .
		'FROM ((' . $s_message . ' INNER JOIN ' . $s_person . ' ON ' . $s_message . '.user_id = ' . $s_person . '.user_id) ' .
		'INNER JOIN ' . $s_topic . ' ON ' . $s_message . '.topic_id = ' . $s_topic . '.id) ';

		if (is_array($a_ids)) $s_sql .= 'WHERE ' . $s_message . '.topic_id IN (' . join(', ', $a_ids) . ') ';

		if ($this->GetReverseOrder())
		{
			$s_sql .=	'ORDER BY sort_override DESC, ' . $s_message . '.date_added DESC';
		}
		else
		{
			$s_sql .=	'ORDER BY sort_override, ' . $s_message . '.date_added ASC';
		}

		# get data
		$result = $this->GetDataConnection()->query($s_sql);

		# TODO: Only used for one topic, and code only handles that.
		$this->Clear();
		$o_topic = new ForumTopic($this->GetSettings());
		while($o_row = $result->fetch())
		{
			$o_topic->SetId($o_row->topic_id);

			$o_person = new User();
			$o_person->SetId($o_row->user_id);
			$o_person->SetName($o_row->known_as);
			$o_person->SetSignUpdate($o_row->sign_up_date);
			$o_person->SetLocation($o_row->location);
			$o_person->SetSignature($o_row->signature);
			$o_person->SetTotalMessages($o_row->total_messages);
			
			$o_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
			$o_message->SetId($o_row->id);
			$o_message->SetTopicId($o_row->topic_id);
			$o_message->SetTitle($o_row->title);
			$o_message->SetDate($o_row->message_date);
			$o_message->SetBody($o_row->message);
			$o_message->SetIcon($o_row->icon);
			$o_message->SetUser($o_person);

			$o_category = new Category();
			$o_category->SetId($o_row->category_id);
			$o_message->SetCategory($o_category);

			$o_topic->Add($o_message);
		}
		$this->Add($o_topic);

		$result->closeCursor();
	}

	/**
	 * Reads the latest few messages posted by a specific user
	 * @param int $user_id
	 * @return ForumMessage[]
	 */
	public function ReadMessagesByUser($user_id)
	{
		$messages = $this->GetSettings()->GetTable('ForumMessage');

		$s_sql = 'SELECT this_person_messages.topic_id, this_person_messages.id AS message_id, this_person_messages.title, topic_first_message.title AS first_message_title, ' .
		"this_person_messages.date_added AS message_date, " .
		"nsa_match.short_url " .
		'FROM (((' . $messages . ' AS this_person_messages INNER JOIN nsa_forum_topic ON this_person_messages.topic_id = nsa_forum_topic.id) ' .
		'INNER JOIN ' . $messages . ' AS topic_first_message ON nsa_forum_topic.first_message_id = topic_first_message.id) ' .
		'INNER JOIN nsa_forum_topic_link ON nsa_forum_topic.id = nsa_forum_topic_link.topic_id) ' .
		'LEFT JOIN nsa_match ON nsa_forum_topic_link.item_id = nsa_match.match_id AND nsa_forum_topic_link.item_type = ' . ContentType::STOOLBALL_MATCH . ' ' .
		'WHERE this_person_messages.user_id = ' . Sql::ProtectNumeric($user_id, false) . ' ' .
		'ORDER BY this_person_messages.date_added DESC ' .
		'LIMIT 0,11';

		$result = $this->GetDataConnection()->query($s_sql);

		$messages = array();
		while($o_row = $result->fetch())
		{
			$o_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
			$o_message->SetId($o_row->message_id);
			$o_message->SetTopicId($o_row->topic_id);
			$o_message->SetTitle($o_row->title ? $o_row->title : $o_row->first_message_title);
			$o_message->SetDate($o_row->message_date);

			$review_item = new ReviewItem($this->GetSettings());
            $review_item->SetNavigateUrl($this->o_settings->GetClientRoot() . $o_row->short_url);
			$o_message->SetReviewItem($review_item);
			
			$messages[] = $o_message;
		}
		$result->closeCursor();

		return $messages;
	}


	/**
	 * Reads the topic associated with the supplied review item, if any
	 *
	 * @param ReviewItem $o_item
	 */
	function ReadReviewTopicId(ReviewItem $o_item)
	{
		if (!$o_item->GetId() or !$o_item->GetType()) die('No item specified for comments'); # check we've got an id and type

		$s_sql = 'SELECT topic_id AS id ' .
		'FROM ' . $this->GetSettings()->GetTable('ForumTopicLink') . ' ' .
		"WHERE item_type = " . Sql::ProtectNumeric($o_item->GetType()) . " AND item_id = " . Sql::ProtectNumeric($o_item->GetId());


		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * Reads the item which the specified topic is commenting upon
	 * @param int $topic_id
	 * @return ReviewItem
	 */
	public function ReadReviewedItem($topic_id)
	{
		if (!$topic_id or !is_numeric($topic_id)) throw new Exception('No topic specified when checking for review item');

		# prepare command
		$topic_link = $this->GetSettings()->GetTable('ForumTopicLink');
		$topics = $this->GetSettings()->GetTable('ForumTopic');
		$reviewed_item = null;

		$s_sql = 'SELECT ' . $topic_link . '.item_id, ' . $topic_link . '.item_type ' .
			'FROM ' . $topics . ' INNER JOIN ' . $topic_link . ' ON ' . $topics . '.id = ' . $topic_link . '.topic_id ' .
			'WHERE ' . $topics . '.id ' . Sql::ProtectNumeric($topic_id, false, true);

		# get data
		$result = $this->GetDataConnection()->query($s_sql);

		if ($o_row = $result->fetch())
		{
			# prepare command
			$i_item_type = (int)$o_row->item_type;
			$i_item_id = $o_row->item_id;

			$result->closeCursor();

			switch ($i_item_type)
			{
				case ContentType::STOOLBALL_MATCH:
					$s_sql = "SELECT match_id AS id, match_title AS title, CONCAT('/', short_url) AS url " .
						'FROM ' . $this->GetSettings()->GetTable('Match') . ' ' .
						'WHERE match_id = ' . Sql::ProtectNumeric($i_item_id);
					break;
				default:
					$s_sql = '';
					break;
			}

			if ($s_sql)
			{
				# get data
				$result = $this->GetDataConnection()->query($s_sql);

				$reviewed_item = new ReviewItem($this->GetSettings());
				$reviewed_item->SetType($i_item_type);

				while($o_row = $result->fetch())
				{
					$reviewed_item->SetId($o_row->id);
					$reviewed_item->SetTitle($o_row->title);
                    $reviewed_item->SetNavigateUrl($o_row->url);
					if (isset($o_row->date_available)) $reviewed_item->SetDate($o_row->date_available);
				}
			}
		}
		$result->closeCursor();

		return $reviewed_item;
	}

	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		$this->Clear();
		while($o_row = $o_result->fetch())
		{
			$o_topic = new ForumTopic($this->GetSettings());
			$o_topic->SetId($o_row->id);

			if (isset($o_row->first_message_title))
			{
				$o_first_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
				if (isset($o_row->first_message_id)) $o_first_message->SetId($o_row->first_message_id);
				$o_first_message->SetTopicId($o_row->id);
				$o_first_message->SetTitle($o_row->first_message_title);
				if (isset($o_row->first_message_date)) $o_first_message->SetDate($o_row->first_message_date);
				if (isset($o_row->icon)) $o_first_message->SetIcon($o_row->icon);
				if (isset($o_row->message)) $o_first_message->SetBody($o_row->message);

				if (isset($o_row->first_person_id))
				{
					$o_first_message_person = new User();
					$o_first_message_person->SetId($o_row->first_person_id);
					$o_first_message_person->SetName($o_row->first_person_name);
					$o_first_message->SetUser($o_first_message_person);
				}

				$o_topic->Add($o_first_message);

				unset($o_first_message);
			}

			if (isset($o_row->last_message_id) and (!isset($o_row->total_messages) or $o_row->total_messages > 1))
			{
				$o_last_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
				$o_last_message->SetId($o_row->last_message_id);
				$o_last_message->SetTopicId($o_row->id);
				$o_last_message->SetTitle($o_row->last_message_title ? $o_row->last_message_title : $o_row->first_message_title);
				$o_last_message->SetDate($o_row->last_message_date);

				if (isset($o_row->last_person_id) or isset($o_row->known_as))
				{
					$o_last_message_person = new User();
					if (isset($o_row->last_person_id))
					{
						$o_last_message_person->SetId($o_row->last_person_id);
						$o_last_message_person->SetName($o_row->last_person_name);
					}
					else
					{
						$o_last_message_person->SetName($o_row->known_as);
					}
					$o_last_message->SetUser($o_last_message_person);
				}

				if (isset($o_row->total_messages))
				{
					$o_topic->Add($o_last_message, $o_row->total_messages-1);
				}
				else
				{
					$o_topic->Add($o_last_message);
				}

				unset($o_last_message);
			}

			if (isset($o_row->category))
			{
				$o_category = new Category();
				$o_category->SetName($o_row->category);
				$o_topic->SetCategory($o_category);
			}

			$this->Add($o_topic);
		}
	}


	/**
	 * Saves a new forum topic
	 *
	 * @param ForumTopic $o_topic
	 * @return ForumTopic
	 */
	public function SaveNewTopic(ForumTopic $o_topic)
	{
		/* @var $o_category Category */
		/* @var $o_result MySQLRawData */

		# Create table aliases
		$s_topic = $this->o_settings->GetTable('ForumTopic');
		$s_message = $this->o_settings->GetTable('ForumMessage');
		$s_topic_link = $this->o_settings->GetTable('ForumTopicLink');
		$s_reg = $this->o_settings->GetTable('User');

		# create new topic in db
		$o_category = $o_topic->GetCategory();
		$s_sql = 'INSERT INTO ' . $s_topic . ' SET ' .
		'category_id = ' . Sql::ProtectNumeric($o_category->GetId()) . ', ' .
		'first_message_id = null, ' .
		'last_message_id = null, ' .
		'total_messages = 0, ' .
		"status = 'open'";

		$this->Lock(array($s_topic, $s_message, $s_topic_link, $s_reg)); # BEGIN TRAN
		$o_result = $this->GetDataConnection()->query($s_sql);
		if ($this->GetDataConnection()->isError()) die('Error: failed to create topic.');

		$o_topic->SetId($this->GetDataConnection()->insertID());

		# create new message in topic
		$o_message = $o_topic->GetFinal();
		if (is_object($o_message))
		{
			# convert bool into integer
			$s_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $this->SqlString($_SERVER['REMOTE_ADDR']) : 'NULL';

			$s_sql = 'INSERT INTO ' . $s_message . ' SET ' .
			'topic_id = ' . Sql::ProtectNumeric($o_topic->GetId()) . ', ' .
			'user_id = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ', ' .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U') . ', ' .
			"icon = " . $this->SqlString($o_message->GetIcon()) . ", " .
			"title = " . $this->SqlHtmlString(ucfirst($o_message->GetTitle())) . ", " .
			"message = " . $this->SqlHtmlString($o_message->GetBody()) . ", " .
			'ip = ' . $s_ip . ', ' .
			'sort_override = 0';

			$o_result = $this->GetDataConnection()->query($s_sql);
			if ($this->GetDataConnection()->isError()) die('Failed to create message.');

			$o_message->SetId($this->GetDataConnection()->insertID());

			# update topic with details of new message
			$s_sql = 'UPDATE ' . $s_topic . ' SET ' .
			'first_message_id = ' . Sql::ProtectNumeric($o_message->GetId()) . ', ' .
			'last_message_id = ' . Sql::ProtectNumeric($o_message->GetId()) . ', ' .
			'total_messages = total_messages+1 ' .
			'WHERE id = ' . Sql::ProtectNumeric($o_topic->GetId());

			$o_result = $this->GetDataConnection()->query($s_sql);
			if ($this->GetDataConnection()->isError()) die('Failed to update topic with message details.');
		}

		# if we're dealing with a review topic, add a link record
		$o_review_item = $o_topic->GetReviewItem();
		if (is_object($o_review_item))
		{
			$s_sql = 'INSERT INTO ' . $s_topic_link . ' SET ' .
			'item_id = ' . Sql::ProtectNumeric($o_review_item->GetId()) . ', ' .
			'topic_id = ' . Sql::ProtectNumeric($o_topic->GetId()) . ', ' .
			"item_type = " . Sql::ProtectNumeric($o_review_item->GetType());


			$o_result = $this->GetDataConnection()->query($s_sql);
			if ($this->GetDataConnection()->isError()) die('Failed to associate new topic with review item.');
		}

		# increment personal message count
		$s_sql = 'UPDATE ' . $s_reg . ' SET total_messages = total_messages+1 WHERE user_id = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());
		$o_result = $this->GetDataConnection()->query($s_sql);
		if ($this->GetDataConnection()->isError()) die('Failed to update your message count.');

		# COMMIT TRAN
		$this->Unlock();

		# return updated topic
		$o_message->SetTopicId($o_topic->GetId());
		$o_topic->UpdateFinalMessage($o_message);

        $this->Clear();
        $this->Add($o_topic);

		return $o_topic;
	}


	/**
	 * Saves a new message to an existing forum topic
	 *
	 * @param ForumTopic $o_topic
	 * @return ForumTopic
	 */
	public function SaveReply(ForumTopic $o_topic)
	{
		/* @var $o_result MySQLRawData */

		# Create table aliases
		$s_topic = $this->o_settings->GetTable('ForumTopic');
		$s_message = $this->o_settings->GetTable('ForumMessage');
		$s_reg = $this->o_settings->GetTable('User');

		# get message to add
		$o_message = $o_topic->GetFinal();

		if (is_object($o_message))
		{
			# convert bool into integer
			$s_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $this->SqlString($_SERVER['REMOTE_ADDR']) : 'NULL';

			$s_sql = 'INSERT INTO ' . $s_message . ' SET ' .
			'topic_id = ' . Sql::ProtectNumeric($o_topic->GetId()) . ', ' .
			'user_id = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ', ' .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U') . ', ' .
			"icon = " . $this->SqlString($o_message->GetIcon()) . ", " .
			"title = " . $this->SqlHtmlString(ucfirst($o_message->GetTitle())) . ", " .
			"message = " . $this->SqlHtmlString($o_message->GetBody()) . ", " .
			'ip = ' . $s_ip . ', ' .
			'sort_override = 0';

			$this->Lock(array($s_topic, $s_message, $s_reg));

			$o_result = $this->GetDataConnection()->query($s_sql);
			if ($this->GetDataConnection()->isError()) die('Failed to create message.');

			$o_message->SetId($this->GetDataConnection()->insertID());

			# update topic with details of new message
			$s_sql = 'UPDATE ' . $s_topic . ' SET ' .
			'last_message_id = ' . Sql::ProtectNumeric($o_message->GetId()) . ', ' .
			'total_messages = total_messages+1 ' .
			'WHERE id = ' . Sql::ProtectNumeric($o_topic->GetId());

			$o_result = $this->GetDataConnection()->query($s_sql);
			if ($this->GetDataConnection()->isError()) die('Failed to update topic with message details.');

			# increment personal message count
			$s_sql = 'UPDATE ' . $s_reg . ' SET total_messages = total_messages+1 WHERE user_id = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());

			$o_result = $this->GetDataConnection()->query($s_sql);
			if ($this->GetDataConnection()->isError()) die('Failed to update your message count.');

			# release db
			$this->Unlock();

			# return updated topic
			if (!$o_message->GetTopicId()) $o_message->SetTopicId($o_topic->GetId());
			$o_topic->UpdateFinalMessage($o_message);

            $this->Clear();
            $this->Add($o_topic);
		}

		return $o_topic;
	}


	/**
	 * Saves a comment on an item
	 *
	 * @param ReviewItem $item_to_comment_on
	 * @param Category $category
	 * @param string $s_title
	 * @param string $s_body
	 * @param string $s_icon
	 * @return ForumTopic
	 */
	public function SaveComment($item_to_comment_on, $category, $s_title, $s_body, $s_icon)
	{
		$user = AuthenticationManager::GetUser();

		# create new message
		$o_new_message = new ForumMessage($this->GetSettings(), $user);
		$o_new_message->SetTitle($s_title);
		$o_new_message->SetBody($s_body);
		$o_new_message->SetIcon($s_icon);

		# create topic
		$this->ReadReviewTopicId($item_to_comment_on);
		$topic = $this->GetFirst();
		if (!is_object($topic)) $topic = new ForumTopic($this->GetSettings());
		$topic->SetCategory($category);
		$topic->SetReviewItem($item_to_comment_on);
		$topic->Add($o_new_message);

		# add new message to db, either as reply or as new topic
		if ($topic->GetId())
        {
            $this->SaveReply($topic);
        }
        else
        {
            $this->SaveNewTopic($topic);
        }
		$topic = $this->GetFirst();

		return $topic;
	}
}
?>