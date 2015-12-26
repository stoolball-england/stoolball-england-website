<?php
require_once 'data/validation/data-validator.class.php';
require_once 'stoolball/player.class.php';

/**
 * Checks that reserved names are not used for an ordinary player
 * @author Rick
 *
 */
class PlayerNameValidator extends DataValidator
{
	/**
	 * Creates a new PlayerNameValidator
	 * @param string[] $a_keys
	 * @param string $message
	 * @return void
	 */
	public function __construct($a_keys, $message)
	{
		parent::DataValidator($a_keys, $message, ValidatorMode::MultiField());
	}

	/**
	 * (non-PHPdoc)
	 * @see data/validation/DataValidator#Test($s_input, $a_keys)
	 */
	public function Test($a_data, $a_keys)
	{
		/* Only way to be sure of testing name against what it will be matched against is to use the code that transforms it when saving */
		$player = new Player($this->GetSiteSettings());
		$player->SetName($a_data[$a_keys[0]]);
		$player->SetPlayerRole($a_data[$a_keys[1]]);

		$comparable_name = $player->GetComparableName();

		if ($player->GetPlayerRole() == Player::PLAYER)
		{
			if ($comparable_name == "noballs") return false;
			if ($comparable_name == "wides") return false;
			if ($comparable_name == "byes") return false;
			if ($comparable_name == "bonusruns") return false;
		}
		return true;
	}
}