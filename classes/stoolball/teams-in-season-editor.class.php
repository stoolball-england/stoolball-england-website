<?php
require_once('data/related-item-editor.class.php');
require_once('stoolball/team-in-season.class.php');
require_once('stoolball/season.class.php');

/**
 * Aggregated editor to manage the teams playing in a stoolball season
 *
 */
class TeamsInSeasonEditor extends RelatedItemEditor
{
	/**
	 * The teams which might be in the season
	 *
	 * @var Team[]
	 */
	private $a_teams;

	/**
	 * Creates a TeamsInSeasonEditor
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
		$this->SetDataObjectClass('TeamInSeason');
		$this->SetDataObjectMethods('GetTeamId', '', 'GetTeamName');
		parent::__construct($settings, $controlling_editor, $s_id, $s_title, $a_column_headings, $csrf_token);

		# initialise arrays
		$this->a_teams = array();
	}

	/**
	 * Sets the teams which might be in the season
	 *
	 * @param Team[] $a_teams
	 */
	public function SetTeams($a_teams)
	{
		if (is_array($a_teams)) $this->a_teams = $a_teams;
	}

	/**
	 * Gets the teams which might be in the season
	 *
	 * @return Team[]
	 */
	public function GetTeams()
	{
		return $this->a_teams;
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
		$o_team = null;
		$o_team = new Team($this->GetSettings());
		if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$o_team->SetId($_POST[$s_key]);
		}
		$s_key = $this->GetNamingPrefix() . 'TeamValue' . $i_counter;
		if (isset($_POST[$s_key]))
		{
			$o_team->SetName($_POST[$s_key]);
		}
		
		$s_key = $this->GetNamingPrefix() . 'WithdrawnLeague' . $i_counter;
		$b_withdrawn_league = (isset($_POST[$s_key]) and $_POST[$s_key] == '1');

		if ($o_team->GetId() or $b_withdrawn_league)
		{
			$team_in_season = new TeamInSeason($o_team, null, $b_withdrawn_league);
			$this->DataObjects()->Add($team_in_season);
		}
		else
		{
			$this->IgnorePostedItem($i_counter);
		}

	}


	/**
	 * Gets a hash of the specified team registration
	 *
	 * @param TeamInSeason $data_object
	 * @return string
	 */
	protected function GetDataObjectHash($data_object)
	{
		return $this->GenerateHash(array($data_object->GetTeam()->GetId()));
	}


	/**
	 * Create a table row to add or edit a team registration
	 *
	 * @param object $data_object
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 */
	protected function AddItemToTable($data_object=null, $i_row_count=null, $i_total_rows=null)
	{
		/* @var $data_object TeamInSeason */
		$b_has_data_object = !is_null($data_object);

		// Which team?
		if (is_null($i_row_count))
		{
			$team = new XhtmlSelect();
			foreach ($this->a_teams as $o_team)
			{
				// For each team, check that it's not already selected before offering it as a choice
				$is_current_team = false;
				foreach ($this->DataObjects() as $team_in_season) 
				{
					/* @var $team_in_season TeamInSeason */
					if ($team_in_season->GetTeamId() == $o_team->GetId()) 
					{
						$is_current_team = true;
						break; 
					}
				}
				
				if (!$is_current_team) $team->AddControl(new XhtmlOption($o_team->GetName(), $o_team->GetId()));
			}
			$team_control = $this->ConfigureSelect($team, $b_has_data_object ? $data_object->GetTeam()->GetId() : '', 'Team', $i_row_count, $i_total_rows, true);
		}
		else
		{
			if (!$data_object->GetTeamName())
			{
				foreach ($this->a_teams as $o_team)
				{
					if ($o_team->GetId() == $data_object->GetTeamId())
					{
						$data_object->SetTeam($o_team);
						break;
					}
				}
			}
			$team_control = $this->CreateNonEditableText($b_has_data_object ? $data_object->GetTeamId() : null, $b_has_data_object ? $data_object->GetTeamName() : '', 'Team', $i_row_count, $i_total_rows);
		}

		// Withdrawn from league?
		$withdrawn_league = $this->CreateCheckbox($b_has_data_object ? $data_object->GetWithdrawnFromLeague() : '', 'WithdrawnLeague', $i_row_count, $i_total_rows);

		# Add controls to table
		$this->AddRowToTable(array($team_control, $withdrawn_league));
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