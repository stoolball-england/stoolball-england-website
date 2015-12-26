<?php
/**
 * Enum determining what kind of stoolball match is being represented
 *
 */
class MatchType
{
	/**
	 * Gets a description of a match type
	 * @param int $type
	 * @return string
	 */
	public static function Text($type)
	{
		switch ($type)
		{
			case MatchType::LEAGUE: return 'league match';
			case MatchType::TOURNAMENT: return 'tournament';
			case MatchType::TOURNAMENT_MATCH: return 'tournament match';
			case MatchType::PRACTICE: return 'practice';
			case MatchType::FRIENDLY: return 'friendly match';
			case MatchType::CUP: return 'cup match';
		}
	}

	/**
	* @return int
	* @desc A match in a league
	*/
	const LEAGUE = 0;

	/**
	* @return int
	* @desc A tournament, which is a group of other matches
	*/
	const TOURNAMENT = 1;

	/**
	* @return int
	* @desc A match which is part of a tournament
	*/
	const TOURNAMENT_MATCH = 2;

	/**
	 * A practice session, rather than a real match
	 *
	 * @return int
	 */
	const PRACTICE = 3;

	/**
	 * A one-off friendly match
	 *
	 * @return int
	 */
	const FRIENDLY = 4;

	/**
	 * A knock-out cup match, in which the loser is usually eliminated from the competition
	 *
	 * @return int
	 */
	const CUP = 5;
}
?>