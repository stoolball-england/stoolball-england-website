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
		parent::DataManager($o_settings, $o_db);
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
        $s_message = $this->GetSettings()->GetTable('ForumMessage');

		# prepare command
		$s_sql = 'SELECT ' . $s_person . '.user_id, ' . $s_person . '.known_as, ' .
		'location, signature, ' . $s_person . ".date_added AS sign_up_date, " . $s_person . '.total_messages, ' .
		$s_message . '.id, ' . $s_message . '.title, message, icon, ' . $s_message . ".date_added AS message_date " .
		'FROM ' . $s_message . ' INNER JOIN ' . $s_person . ' ON ' . $s_message . '.user_id = ' . $s_person . '.user_id ' .
        'WHERE ' . $s_message . '.item_id = ' . Sql::ProtectNumeric($review_item->GetId(), false, false) . ' AND item_type = ' . Sql::ProtectNumeric($review_item->GetType(), false, false);

		if ($this->GetReverseOrder())
		{
			$s_sql .=	' ORDER BY sort_override DESC, ' . $s_message . '.date_added DESC';
		}
		else
		{
			$s_sql .=	' ORDER BY sort_override, ' . $s_message . '.date_added ASC';
		}

		# get data
		$result = $this->GetDataConnection()->query($s_sql);

		$this->Clear();
		$o_topic = new ForumTopic($this->GetSettings());
		while($o_row = $result->fetch())
		{
			$o_person = new User();
			$o_person->SetId($o_row->user_id);
			$o_person->SetName($o_row->known_as);
			$o_person->SetSignUpdate($o_row->sign_up_date);
			$o_person->SetLocation($o_row->location);
			$o_person->SetSignature($o_row->signature);
			$o_person->SetTotalMessages($o_row->total_messages);
			
			$o_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
			$o_message->SetId($o_row->id);
			$o_message->SetTitle($o_row->title);
			$o_message->SetDate($o_row->message_date);
			$o_message->SetBody($o_row->message);
			$o_message->SetIcon($o_row->icon);
			$o_message->SetUser($o_person);

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
		$s_sql = 'SELECT nsa_forum_message.id AS message_id, nsa_forum_message.title, nsa_forum_message.date_added AS message_date, ' .
		"nsa_match.short_url, nsa_match.match_title " .
		'FROM nsa_forum_message INNER JOIN nsa_match ON nsa_forum_message.item_id = nsa_match.match_id AND nsa_forum_message.item_type = ' . ContentType::STOOLBALL_MATCH . ' ' .
		'WHERE nsa_forum_message.user_id = ' . Sql::ProtectNumeric($user_id, false) . ' ' .
		'ORDER BY nsa_forum_message.date_added DESC ' .
		'LIMIT 0,11';

		$result = $this->GetDataConnection()->query($s_sql);

		$messages = array();
		while($o_row = $result->fetch())
		{
			$o_message = new ForumMessage($this->GetSettings(), AuthenticationManager::GetUser());
			$o_message->SetId($o_row->message_id);
			$o_message->SetTitle($o_row->title ? $o_row->title : $o_row->match_title);
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
	 * Saves a comment on an item
	 *
	 * @param ReviewItem $item_to_comment_on
	 * @param string $s_title
	 * @param string $s_body
	 * @param string $s_icon
	 * @return ForumMessage
	 */
	public function SaveComment(ReviewItem $item_to_comment_on, $s_title, $s_body, $s_icon)
	{
		$user = AuthenticationManager::GetUser();

		# create new message
		$o_message = new ForumMessage($this->GetSettings(), $user);
		$o_message->SetTitle($s_title);
		$o_message->SetBody($s_body);
		$o_message->SetIcon($s_icon);
		$o_message->SetReviewItem($item_to_comment_on);

		# add new message to db, either as reply or as new topic
        /* @var $o_result MySQLRawData */

        # Create table aliases
        $s_message = $this->o_settings->GetTable('ForumMessage');
        $s_reg = $this->o_settings->GetTable('User');

        $s_ip = (isset($_SERVER['REMOTE_ADDR'])) ? $this->SqlString($_SERVER['REMOTE_ADDR']) : 'NULL';

        $s_sql = 'INSERT INTO ' . $s_message . ' SET ' .
        'user_id = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ', ' .
        'date_added = ' . gmdate('U') . ', ' .
        'date_changed = ' . gmdate('U') . ', ' .
        "icon = " . $this->SqlString($o_message->GetIcon()) . ", " .
        "title = " . $this->SqlHtmlString(ucfirst($o_message->GetTitle())) . ", " .
        "message = " . $this->SqlHtmlString($o_message->GetBody()) . ", " .
        'ip = ' . $s_ip . ', ' .
        'sort_override = 0, ' .
        'item_id = ' . Sql::ProtectNumeric($item_to_comment_on->GetId()) . ', ' .
        "item_type = " . Sql::ProtectNumeric($item_to_comment_on->GetType());

        $this->Lock(array($s_message, $s_reg));

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