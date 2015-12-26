<?php
require_once 'data/validation/data-validator.class.php';
require_once 'stoolball/team.class.php';

/**
 * Checks that team names do not generate duplicate comparable names
 * @author Rick
 *
 */
class TeamNameValidator extends DataValidator
{
    /**
     * Creates a new TeamNameValidator
     * @param string[] $a_keys
     * @param string $message
     * @return void
     */
    public function __construct($a_keys, $message)
    {   
        parent::DataValidator($a_keys, $message, ValidatorMode::MultiField());
    }

    /**
     * Gets whether the validator requires a SiteSettings object to work
     * @return bool
     */
    public function RequiresSettings() { return true; }

    /**
     * Gets whether the validator requires a data connection to work
     * @return bool
     */
    public function RequiresDataConnection() { return true; }

    /**
     * (non-PHPdoc)
     * @see data/validation/DataValidator#Test($s_input, $a_keys)
     */
    public function Test($a_data, $a_keys)
    {
        /* Only way to be sure of testing name against what it will be matched against is to use the code that transforms it when saving */
        $team = new Team($this->GetSiteSettings());
        $team->SetName($a_data[$a_keys[1]]);
        $team->SetPlayerType($a_data[$a_keys[2]]);

        $team_manager = new TeamManager($this->GetSiteSettings(),$this->GetDataConnection());
        $team = $team_manager->MatchExistingTeam($team);
        unset($team_manager);
        
        $current_id = isset($a_data[$a_keys[0]]) ? $a_data[$a_keys[0]] : null;
        return ((!$team->GetId()) or ($team->GetId() == $current_id));
    }
}