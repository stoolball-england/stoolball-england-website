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
			case ContentType::TOPIC: return 'topic';
			case ContentType::FORUM: return 'category';
			case ContentType::IMAGE: return 'image';
			case ContentType::CATEGORY: return 'contentcategory';
			case ContentType::STOOLBALL_MATCH: return 'match';
			case ContentType::STOOLBALL_TEAM: return 'team';
			case ContentType::STOOLBALL_SEASON: return 'season';
			case ContentType::STOOLBALL_COMPETITION: return 'competition';
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
	 * @desc A topic in a forum
	 */
	const TOPIC = 5;

	/**
	 * @return int
	 * @desc An image in GIF, JPEG or PNG format
	 */
	const IMAGE = 7;

	/**
	 * @return int
	 * @desc A category of content
	 */
	const CATEGORY = 9;

	/**
	 * @return int
	 * @desc A season of a stoolball competition
	 */
	const STOOLBALL_SEASON = 3000;

	/**
	 * @return int
	 * @desc A stoolball match
	 */
	const STOOLBALL_MATCH = 3002;

	/**
	 * @return int
	 * @desc A stoolball team
	 */
	const STOOLBALL_TEAM = 3003;

	/**
	 * @return int
	 * @desc A stoolball competition
	 */
	const STOOLBALL_COMPETITION = 3004;
}
?>