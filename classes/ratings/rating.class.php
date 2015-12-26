<?php
class Rating
{
	var $i_rating_id;
	var $i_rating;
	var $i_weighting;
	var $i_multiplier;
	var $i_person_id;
	var $i_message_id;

	function Rating()
	{
		$this->i_weighting = 1;
		$this->i_multiplier = 1;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database identifier of the rating
	*/
	function SetRatingId($i_input)
	{
		if (is_numeric($i_input)) $this->i_rating_id = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets the unique database identifier of the rating
	*/
	function GetRatingId()
	{
		return $this->i_rating_id;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the rating - a value from 1 to 10 inclusive
	*/
	function SetRating($i_input)
	{
		if (is_numeric($i_input))
		{
			$i_input = (int)$i_input;
			if ($i_input >= 1 and $i_input <= 10) $this->i_rating = $i_input;
		}
	}

	/**
	* @return int
	* @desc Gets the rating - a value from 1 to 10 inclusive
	*/
	function GetRating()
	{
		return $this->i_rating;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets how many old ratings this new rating represents
	*/
	function SetWeighting($i_input)
	{
		if (is_numeric($i_input)) $this->i_weighting = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets how many old ratings this new rating represents
	*/
	function GetWeighting()
	{
		return $this->i_weighting;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets how many instances of a rating value this object represents
	*/
	function SetMultiplier($i_input)
	{
		if (is_numeric($i_input)) $this->i_multiplier = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets how many instances of a rating value this object represents
	*/
	function GetMultiplier()
	{
		return $this->i_multiplier;
	}

	public function SetUserId($i_input)
	{
		if (is_numeric($i_input)) $this->i_person_id = (int)$i_input;
	}

	function GetUserId()
	{
		return $this->i_person_id;
	}

	/**
	 * Sets the id of the ForumMessage with which the rating is associated
	 *
	 * @param int $i_input
	 */
	function SetMessageId($i_input)
	{
		if (is_numeric($i_input)) $this->i_message_id = (int)$i_input;
	}

	/**
	 * Gets the id of the ForumMessage with which the rating is associated
	 *
	 * @return int
	 */
	function GetMessageId()
	{
		return $this->i_message_id;
	}
}
?>