<?php
/**
 * Enum used to represent a type of content as a numeric value
 *
 */
class ContentType
{
	/**
	 * Gets the description of a content type
	 * @param int $type
	 * @return string
	 */
	public static function Text($type)
	{
		switch($type)
		{
			case ContentType::STOOLBALL_MATCH: return 'match';
		}
	}

	/**
	 * @return int
	 * @desc A stoolball match
	 */
	const STOOLBALL_MATCH = 3002;
}
?>