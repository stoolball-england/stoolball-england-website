<?php
require_once('data/data-manager.class.php');
require_once('forums/forum-message.class.php');
require_once('forums/forum-topic.class.php');
require_once('http/query-string.class.php');
require_once('forums/review-item.class.php');

/**
 * Read and write forum topics
 *
 */
class TopicManager extends DataManager
{
	private $b_reverse_order = false;

	/**
	 * @return TopicManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Creates a new TopicManager instance
	 */
	public function __construct(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::__construct($o_settings, $o_db);
		$this->s_item_class = 'ForumTopic';
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
	 * @desc Read from the db the comments for the supplied review item
	 */
	public function ReadCommentsForReviewItem(ReviewItem $review_item)
	{
		$s_person = $this->GetSettings()->GetTable('User');

		# prepare command
		$s_sql = 'SELECT nsa_user.user_id, nsa_user.known_as, ' .
		"nsa_user.date_added AS sign_up_date, nsa_user.total_messages, " .
		"nsa_forum_message.id, nsa_forum_message.message, nsa_forum_message.date_added AS message_date " .
		'FROM nsa_forum_message LEFT JOIN nsa_user ON nsa_forum_message.user_id = nsa_user.user_id ' .
        'WHERE nsa_forum_message.item_id = ' . Sql::ProtectNumeric($review_item->GetId(), false, false) . ' AND item_type = ' . Sql::ProtectNumeric($review_item->GetType(), false, false);

		if ($this->GetReverseOrder())
		{
			$s_sql .=	' ORDER BY sort_override DESC, nsa_forum_message.date_added DESC';
		}
		else
		{
			$s_sql .=	' ORDER BY sort_override, nsa_forum_message.date_added ASC';
		}

		# get data
		$result = $this->GetDataConnection()->query($s_sql);

		$this->Clear();
		$o_topic = new ForumTopic($this->GetSettings());
		while($o_row = $result->fetch())
		{
			$o_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());

			if (isset($o_row->user_id)) {
				$o_person = new User();
				$o_person->SetId($o_row->user_id);
				$o_person->SetName($o_row->known_as);
				$o_person->SetSignUpDate($o_row->sign_up_date);
				$o_person->SetTotalMessages($o_row->total_messages);
				$o_message->SetUser($o_person);
			}
			
			$o_message->SetId($o_row->id);
			$o_message->SetDate($o_row->message_date);
			$o_message->SetBody($o_row->message);
            $o_message->SetReviewItem($review_item);

			$o_topic->Add($o_message);
		}
		$this->Add($o_topic);

		$result->closeCursor();
	}

	/**
	 * Saves a comment on an item
	 *
	 * @param ReviewItem $item_to_comment_on
	 * @param string $s_body
	 * @return ForumMessage
	 */
	public function SaveComment(ReviewItem $item_to_comment_on, $s_body)
	{
		$user = AuthenticationManager::GetUser();

		# create new message
		$o_message = new ForumMessage($this->GetSettings(), $user);
		$o_message->SetBody($s_body);
		$o_message->SetReviewItem($item_to_comment_on);

		# add new message to db, either as reply or as new topic
        /* @var $o_result MySQLRawData */

        # Create table aliases
        $s_reg = $this->o_settings->GetTable('User');

        $s_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $this->SqlString($_SERVER['REMOTE_ADDR']) : 'NULL';

        $s_sql = 'INSERT INTO nsa_forum_message SET ' .
        'user_id = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ', ' .
        'date_added = ' . gmdate('U') . ', ' .
        'date_changed = ' . gmdate('U') . ', ' .
        "message = " . $this->SqlHtmlString($o_message->GetBody()) . ", " .
        'ip = ' . $s_ip . ', ' .
        'sort_override = 0, ' .
        'item_id = ' . Sql::ProtectNumeric($item_to_comment_on->GetId()) . ', ' .
        "item_type = " . Sql::ProtectNumeric($item_to_comment_on->GetType());

        $this->Lock(array('nsa_forum_message', $s_reg));

        $o_result = $this->GetDataConnection()->query($s_sql);
        if ($this->GetDataConnection()->isError()) die('Failed to create message.');

        $o_message->SetId($this->GetDataConnection()->insertID());

        # increment personal message count
        $user_id = Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());
        $s_sql = "UPDATE nsa_user SET total_messages = (SELECT COUNT(id) FROM nsa_forum_message WHERE user_id = $user_id) WHERE user_id = $user_id";
        $o_result = $this->GetDataConnection()->query($s_sql);
        if ($this->GetDataConnection()->isError()) die('Failed to update your message count.');

        # release db
        $this->Unlock();

		return $o_message;
	}
}
?>