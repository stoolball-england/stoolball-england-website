<?php
require_once('data/data-manager.class.php');
require_once('ratings/rating.class.php');
require_once('ratings/rated-item.class.php');
require_once('data/content-type.enum.php');

class RatingManager extends DataManager
{
	/**
	* @return RatingManager
	* @param SiteSettings $o_settings
	* @param MySqlConnection $o_db
	* @desc Read and write ratings
	*/
	function &RatingManager(&$o_settings, &$o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'Rating';
		return $this;
	}


	/**
	* @access public
	* @return void
	* @param int[] $a_ids
	* @desc Read from the db the objects matching the supplied ids, or all objects
	*/
	function ReadById($a_ids=null)
	{
		die('RatingManager::ReadById not implemented');
	}


	/**
	* @access public
	* @return void
	* @param int[] $a_related_item_ids
	* @param ContentType $i_relation_type
	* @desc Read from the db the objects matching the supplied related item id and type
	*/
	function ReadByRelatedItemId($a_related_item_ids, $i_relation_type)
	{
		# check parameters
		if (!is_array($a_related_item_ids) or !is_numeric($i_relation_type)) die('Invalid arguments when getting ratings');
		$i_count = count($a_related_item_ids);
		for ($i = 0; $i < $i_count; $i++) $a_related_item_ids[$i] = Sql::ProtectNumeric($a_related_item_ids[$i]);

		# build query
		$s_rating = $this->o_settings->GetTable('Rating');

		$s_sql = 'SELECT id, rating, weighting, count(rating) AS multiplier ' .
		'FROM ' . $s_rating . ' ' .
		'WHERE item_id IN (' . join(', ', $a_related_item_ids) . ") AND item_type = " . Sql::ProtectNumeric($i_relation_type) . " " .
		'GROUP BY rating, weighting';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into object
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}


	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		while($o_row = $o_result->fetch())
		{
			$o_rating = new Rating();
			$o_rating->SetRatingId($o_row->id);
			$o_rating->SetRating($o_row->rating);
			$o_rating->SetWeighting($o_row->weighting);
			$o_rating->SetMultiplier($o_row->multiplier);
			$this->Add($o_rating);
			unset($o_rating);
		}
	}


	/**
	* @return int
	* @param RatedItem $o_object
	* @desc Save the supplied object to the database, and return the id
	*/
	function Save($o_object)
	{
		/* @var $o_rating Rating */
		/* @var $o_result MySQLRawData*/
		if (!$o_object instanceof RatedItem) die('Unable to save rating');

		$o_rating = &$o_object->GetNewRating();

		if (is_object($o_rating))
		{
			$s_rating = $this->o_settings->GetTable('Rating');

			# Update an existing rating, identified by its RatingId
			if ($o_rating->GetRatingId() and $o_rating->GetRating())
			{
				$s_sql = 'UPDATE ' . $s_rating . ' SET ' .
				'date_changed = ' . gmdate('U') . ', ' .
				'rating = ' . Sql::ProtectNumeric($o_rating->GetRating()) . ', ' .
				'message_id = ' . Sql::ProtectNumeric($o_rating->GetMessageId(), true) . ' ' .
				'WHERE id = ' . Sql::ProtectNumeric($o_rating->GetRatingId());

				$this->Lock(array($s_rating));
				$this->GetDataConnection()->query($s_sql);
				$this->Unlock();

				return $o_rating->GetRatingId();
			}


			# Or add a new rating to the db
			if ($o_rating->GetRating() and $o_object->GetId() and $o_object->GetType())
			{
				$s_sql = 'INSERT INTO ' . $s_rating . ' SET ' .
				'date_added = ' . gmdate('U') . ', ' .
				'date_changed = ' . gmdate('U') . ', ' .
				'item_id = ' . Sql::ProtectNumeric($o_object->GetId()) . ', ' .
				"item_type = " . Sql::ProtectNumeric($o_object->GetType()) . ", " .
				'user_id = ' . Sql::ProtectNumeric($o_rating->GetUserId(), true) . ', ' .
				'rating = ' . Sql::ProtectNumeric($o_rating->GetRating()) . ', ' .
				'message_id = ' . Sql::ProtectNumeric($o_rating->GetMessageId(), true);

				$this->Lock(array($s_rating));
				$o_result = $this->GetDataConnection()->query($s_sql);
				$i_rating_id = $this->GetDataConnection()->insertID();
				$this->Unlock();

				return $i_rating_id;
			}
		}
		else return 0;
	}

	/**
	* @access public
	* @return void
	* @param int[] $a_ids
	* @desc Delete from the db the objects matching the supplied ids
	*/
	function Delete($a_ids)
	{
		die('RatingManager::Delete not implemented');
	}
}
?>