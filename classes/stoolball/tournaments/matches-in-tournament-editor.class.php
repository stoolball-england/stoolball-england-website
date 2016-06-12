<?php
require_once('data/related-item-editor.class.php');
require_once('stoolball/match.class.php');

/**
 * Aggregated editor to manage the matches in a stoolball tournament
 *
 */
class MatchesInTournamentEditor extends RelatedItemEditor
{
    private $max_sort_order = 0;
    
    /**
     * Creates a MatchesInTournamentEditor
     *
     * @param SiteSettings $settings
     * @param DataEditControl $controlling_editor
     * @param string $s_id
     * @param string $s_title
     * @param string[] $a_column_headings
     */
    public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id, $s_title)
    {
        $this->SetDataObjectClass('Match');
        $this->SetDataObjectMethods('GetId', '', 'GetTitle');
        parent::__construct($settings, $controlling_editor, $s_id, $s_title, array('Order','Teams'));
        $this->teams = array();
    }

    /**
     * Re-build from data posted by this control a single data object which this control is editing
     *
     * @param int $i_counter
     * @param int $i_id
     */
    protected function BuildPostedItem($i_counter=null, $i_id=null)
    {
        $match = new Match($this->GetSettings());
        $match->SetMatchType(MatchType::TOURNAMENT_MATCH);
        
        $key = $this->GetNamingPrefix() . 'MatchId' . $i_counter;
        if (isset($_POST[$key]) and is_numeric($_POST[$key]))
        {
            $match->SetId($_POST[$key]);
        }
        
        $key = $this->GetNamingPrefix() . 'MatchOrder' . $i_counter;
        if (isset($_POST[$key]) and is_numeric($_POST[$key]))
        {
            $match->SetOrderInTournament($_POST[$key]);
        }
        
        $key = $this->GetNamingPrefix() . 'MatchIdValue' . $i_counter;
        if (isset($_POST[$key]))
        {
            $match->SetTitle($_POST[$key]);
        }
        
        $key = $this->GetNamingPrefix() . 'HomeTeamId' . $i_counter;
        if (isset($_POST[$key]) and $_POST[$key])
        {
            $team = new Team($this->GetSettings());
            $team->SetId($_POST[$key]);
            $match->SetHomeTeam($team);
        }

        $key = $this->GetNamingPrefix() . 'AwayTeamId' . $i_counter;
        if (isset($_POST[$key]) and $_POST[$key])
        {
            $team = new Team($this->GetSettings());
            $team->SetId($_POST[$key]);
            $match->SetAwayTeam($team);
        }

        if ($match->GetId() or ($match->GetHomeTeamId() and $match->GetAwayTeamId()))
        {
            $this->DataObjects()->Add($match);
        }
        else
        {
            $this->IgnorePostedItem($i_counter);
        }
    }

    /**
     * The teams in the tournament
     *
     * @var Team[]
     */
    private $teams;

    /**
     * Sets the teams in the tournament
     *
     * @param Team[] $teams
     */
    public function SetTeams($teams)
    {
        if (is_array($teams)) $this->teams = $teams;
    }

    /**
     * Gets the teams in the tournament
     *
     * @return Team[]
     */
    public function GetTeams()
    {
        return $this->teams;
    }


    /**
     * Gets a hash of the specified match
     *
     * @param Match $data_object
     * @return string
     */
    protected function GetDataObjectHash($data_object)
    {
        return $this->GenerateHash(array($data_object->GetId()));
    }


    /**
     * Create a table row to add or edit a match
     *
     * @param object $data_object
     * @param int $i_row_count
     * @param int $i_total_rows
     */
    protected function AddItemToTable($data_object=null, $i_row_count=null, $i_total_rows=null)
    {
        /* @var $data_object Match */
        $b_has_data_object = !is_null($data_object);

        // Which match?
        if (is_null($i_row_count))
        {
            $order = $this->CreateTextBox(null, 'MatchOrder', null, $i_total_rows);
            $order->AddAttribute("type", "number");
            $order->SetCssClass("numeric");

            $this->max_sort_order++;
            $order->SetText($this->max_sort_order);           
            
            $home_control = $this->AddTeamList('HomeTeamId', $b_has_data_object ? $data_object->GetHomeTeamId() : '', $i_total_rows);
            $away_control = $this->AddTeamList('AwayTeamId', $b_has_data_object ? $data_object->GetAwayTeamId() : '', $i_total_rows);
            
            $match_control = new Placeholder();
            $match_control->AddControl($home_control);
            $match_control->AddControl(" v ");
            $match_control->AddControl($away_control);

            $this->AddRowToTable(array($order, $match_control));
        }
        else
        {
            # Display the match title. When the add button is used, we need to get the team names for the new match because they weren't posted.
            if ($b_has_data_object) {
                $this->EnsureTeamName($data_object->GetHomeTeam());  
                $this->EnsureTeamName($data_object->GetAwayTeam());   
            }
            
            
            $match_order = $b_has_data_object ? $data_object->GetOrderInTournament() : null;
            if (is_null($match_order)) {
                $match_order = $this->max_sort_order +1;
            }
            if ($match_order > $this->max_sort_order) {
                $this->max_sort_order = $match_order;
            }
            $order = $this->CreateTextBox($match_order, 'MatchOrder', $i_row_count, $i_total_rows);
            $order->AddAttribute("type", "number");
            $order->SetCssClass("numeric");
            
            $match_control = $this->CreateNonEditableText($b_has_data_object ? $data_object->GetId() : null, $b_has_data_object ? $data_object->GetTitle() : '', 'MatchId', $i_row_count, $i_total_rows);
            
            # If the add button is used, remember the team ids until the final save is requested 
            $home_team_id = $this->CreateTextBox($data_object->GetHomeTeamId(), 'HomeTeamId', $i_row_count, $i_total_rows);
            $home_team_id->SetMode(TextBoxMode::Hidden());
            $away_team_id = $this->CreateTextBox($data_object->GetAwayTeamId(), 'AwayTeamId', $i_row_count, $i_total_rows);
            $away_team_id->SetMode(TextBoxMode::Hidden());
            
            $container = new Placeholder();
            $container->SetControls(array($match_control, $home_team_id, $away_team_id));
                                    
            $this->AddRowToTable(array($order, $container));
        }
    }

    private function AddTeamList($list_id, $value, $i_total_rows) {
        $list = new XhtmlSelect();
        foreach ($this->teams as $team)
        {
            $list->AddControl(new XhtmlOption($team->GetName(), $team->GetId()));
        }
        return $this->ConfigureSelect($list, $value, $list_id, null, $i_total_rows, true);        
    }

    private function EnsureTeamName($team=null) {
            
        if (!$team instanceof Team) return;
            
        if (!$team->GetName()) {
            foreach ($this->teams as $possible_team) {
                if ($team->GetId() === $possible_team->GetId()) {
                    $team->SetName($possible_team->GetName());
                    return;
                }
            }
        }
    }

    /**
     * Create validators to check a single match
     *
     * @param string $s_item_suffix
     */
    protected function CreateValidatorsForItem($s_item_suffix='')
    {
        require_once('data/validation/required-field-validator.class.php');
        require_once('data/validation/numeric-validator.class.php');

        $this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'MatchId' . $s_item_suffix, 'The match identifier should be a number');
        $this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'MatchOrder' . $s_item_suffix, 'The match order should be a number');
        $this->a_validators[] = new RequiredFieldValidator(array($this->GetNamingPrefix() . 'HomeTeamId' . $s_item_suffix, $this->GetNamingPrefix() . 'AwayTeamId' . $s_item_suffix), 'Please select two teams to add a match', ValidatorMode::AllOrNothing());
    }
}
?>