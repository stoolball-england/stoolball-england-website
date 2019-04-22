<?php
require_once('data/related-item-editor.class.php');
require_once('stoolball/team.class.php');

/**
 * Aggregated editor to manage the teams playing in a stoolball tournament
 *
 */
class TeamsInTournamentEditor extends RelatedItemEditor
{
    /**
     * Creates a TeamsInTournamentEditor
     *
     * @param SiteSettings $settings
     * @param DataEditControl $controlling_editor
     * @param string $s_id
     * @param string $s_title
     * @param string[] $a_column_headings
     * @param string $csrf_token
     */
    public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id, $s_title, $a_column_headings, $csrf_token)
    {
        $this->SetDataObjectClass('Team');
        $this->SetDataObjectMethods('GetId', '', 'GetNameAndType');
        parent::__construct($settings, $controlling_editor, $s_id, $s_title, $a_column_headings, $csrf_token);
    }

    /**
     * Re-build from data posted by this control a single data object which this control is editing
     *
     * @param int $i_counter
     * @param int $i_id
     */
    protected function BuildPostedItem($i_counter=null, $i_id=null)
    {
        $s_key = $this->GetNamingPrefix() . 'Team' . $i_counter;
        $team = new Team($this->GetSettings());
        if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
        {
            $team->SetId($_POST[$s_key]);
        }
        $s_key = $this->GetNamingPrefix() . 'TeamValue' . $i_counter;
        if (isset($_POST[$s_key]))
        {
            $team->SetName($_POST[$s_key]);
        }
        
        # Infer player type from name
        if (stristr($team->GetName(), "ladies"))
        {
            $team->SetPlayerType(PlayerType::LADIES);
        } 
        else if (stristr($team->GetName(), "mixed"))
        {
            $team->SetPlayerType(PlayerType::MIXED);
        } 
        else if (stristr($team->GetName(), "Junior mixed"))
        {
            $team->SetPlayerType(PlayerType::JUNIOR_MIXED);
        } 
        else if (stristr($team->GetName(), "junior") or stristr($team->GetName(), "girls"))
        {
            $team->SetPlayerType(PlayerType::GIRLS);
        } 
        
        if ($team->GetId() or $team->GetName())
        {
            $this->DataObjects()->Add($team);
        }
        else
        {
            $this->IgnorePostedItem($i_counter);
        }

    }


    /**
     * Gets a hash of the specified team
     *
     * @param Team $data_object
     * @return string
     */
    protected function GetDataObjectHash($data_object)
    {
        return $this->GenerateHash(array($data_object->GetId()));
    }


    /**
     * Create a table row to add or edit a team
     *
     * @param object $data_object
     * @param int $i_row_count
     * @param int $i_total_rows
     */
    protected function AddItemToTable($data_object=null, $i_row_count=null, $i_total_rows=null)
    {
        /* @var $data_object Team */
        $b_has_data_object = !is_null($data_object);

        // Which team?
        if (is_null($i_row_count))
        {
            $team_control = $this->CreateTextBox($b_has_data_object ? $data_object->GetName() : '', 'TeamValue', $i_row_count, $i_total_rows);
            $team_control->AddCssClass("team");
        }
        else
        {

            $team_control = $this->CreateNonEditableText($b_has_data_object ? $data_object->GetId() : null, $b_has_data_object ? $data_object->GetName() : '', 'Team', $i_row_count, $i_total_rows);
        }

        # Add controls to table
        $this->AddRowToTable(array($team_control));
    }


    /**
     * Create validators to check a single team registration
     *
     * @param string $s_item_suffix
     */
    protected function CreateValidatorsForItem($s_item_suffix='')
    {
        require_once('data/validation/required-field-validator.class.php');
        require_once('data/validation/numeric-validator.class.php');

        $this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Team' . $s_item_suffix, 'The team identifier should be a number');
        $this->a_validators[] = new RequiredFieldValidator(array($this->GetNamingPrefix() . 'Team' . $s_item_suffix), 'Please select a team to add', ValidatorMode::AllOrNothing());
    }
}
?>