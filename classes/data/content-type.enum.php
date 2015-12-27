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
			case ContentType::UNKNOWN: return 'l';
			case ContentType::PAGE_COMMENTS: return 'page';
			case ContentType::FORUM: return 'category';
			case ContentType::STOOLBALL_MATCH: return 'match';
		}
	}

	/**
	 * @return int
	 * @desc Unknown content type
	 */
	const UNKNOWN = 0;

	/**
	 * @return int
	 * @desc Comments on a page of content
	 */
	const PAGE_COMMENTS = 3;

	/**
	 * @return int
	 * @desc A forum allowing visitors to post comments
	 */
	const FORUM = 4;

	/**
	 * @return int
	 * @desc A stoolball match
	 */
	const STOOLBALL_MATCH = 3002;
}
?>