<?php
/**
 * @return PlayerType
 * @desc A type of player, defined by gender and age
 */
class PlayerType
{
	/**
	 * Gets a description of the player type
	 * @param PlayerType $type
	 * @return string
	 */
	public static function Text($type)
	{
		switch ($type)
		{
			case PlayerType::MIXED: return "Mixed";
			case PlayerType::LADIES: return "Ladies";
			case PlayerType::MEN: return "Men's";
			case PlayerType::JUNIOR_MIXED: return "Junior mixed";
			case PlayerType::GIRLS: return "Junior girls";
			case PlayerType::BOYS: return "Junior boys";
		}
	}
    
    /**
     * Converts a string representation to an integer player type
     */
    public static function Parse($type)
    {
        switch(preg_replace('/[^a-z]/',"", strtolower($type)))
        {
            case "mixed": return PlayerType::MIXED;
            case "ladies": return PlayerType::LADIES;
            case "men": return PlayerType::MEN;
            case "junior": return PlayerType::JUNIOR_MIXED;
            case "girls": return PlayerType::GIRLS;
            case "boys": return PlayerType::BOYS;
        }
    }

	/**
	 * A match involving men, women, girls and boys
	 * @return int
	 */
	const MIXED = 1;

	/**
	 * A match involving only women and girls
	 * @return int
	 */
	const LADIES = 2;

	/**
	 * A match involving only men and boys
	 * @return int
	 */
	const MEN = 3;

	/**
	 * A match involving girls and boys
	 * @return int
	 */
	const JUNIOR_MIXED = 4;

	/**
	 * A match involving only girls
	 * @return int
	 */
	const GIRLS = 5;

	/**
	 * A match involving only boys
	 * @return int
	 */
	const BOYS = 6;
}
?>