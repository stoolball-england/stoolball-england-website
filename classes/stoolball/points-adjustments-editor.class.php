<?php
require_once('data/related-item-editor.class.php');
require_once('stoolball/points-adjustment.class.php');

/**
 * Aggregated editor to manage league points adjustments for a season
 *
 */
class PointsAdjustmentsEditor extends RelatedItemEditor
{
	/**
	 * The teams which can be awarded or deducted points
	 *
	 * @var Team[]
	 */
	private $a_teams;

	/**
	 * Creates a PointsAdjustmentsEditor
	 *
	 * @param SiteSettings $settings
	 * @param DataEditControl $controlling_editor
	 * @param string $s_id
	 * @param string $s_title
	 * @param string[] $a_column_headings
	 */
	public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id, $s_title, $a_column_headings)
	{
		$this->SetDataObjectClass('PointsAdjustment');
		$this->SetDataObjectMethods('GetId', 'GetDate', 'GetDate');
		parent::__construct($settings, $controlling_editor, $s_id, $s_title, $a_column_headings);

		# initialise arrays
		$this->a_teams = array();
	}

	/**
	 * Sets the teams which can be awarded or deducted points
	 *
	 * @param Team[] $a_teams
	 */
	public function SetTeams($a_teams)
	{
		if (is_array($a_teams)) $this->a_teams = $a_teams;
	}

	/**
	 * Gets the teams which can be awarded or deducted points
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
		$s_key = $this->GetNamingPrefix() . 'Points' . $i_counter;
		$s_award_key = $this->GetNamingPrefix() . 'Awarded' . $i_counter;
		$i_points = ((int)(isset($_POST[$s_key]) and is_numeric($_POST[$s_key])) ? $_POST[$s_key] : 0);
		if (isset($_POST[$s_award_key]) and $_POST[$s_award_key] == '2')
		{
			$i_points = $i_points - ($i_points*2);
		}

		$s_key = $this->GetNamingPrefix() . 'PointsTeam' . $i_counter;
		$o_team = null;
		$o_team = new Team($this->GetSettings());
		if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$o_team->SetId($_POST[$s_key]);
		}

		$s_key = $this->GetNamingPrefix() . 'Reason' . $i_counter;
		$s_reason = (isset($_POST[$s_key]) and strlen($_POST[$s_key]) <= 200) ? $_POST[$s_key] : '';
		
		if ($i_points != 0 or $o_team->GetId() or $s_reason)
		{
			$o_adjust = new PointsAdjustment($i_id, $i_points, $o_team, $s_reason);
			$i_date = $this->BuildPostedItemModifiedDate($i_counter, $o_adjust);
			$o_adjust->SetDate($i_date);
			$this->DataObjects()->Add($o_adjust);
		}
		else
		{
			$this->IgnorePostedItem($i_counter);
		}

	}


	/**
	 * Gets a hash of the specified points adjustment
	 *
	 * @param PointsAdjustment $data_object
	 * @return string
	 */
	protected function GetDataObjectHash($data_object)
	{
		return $this->GenerateHash(array($data_object->GetPoints(), $data_object->GetTeam()->GetId(), $data_object->GetReason()));
	}


	/**
	 * Create a table row to add or edit a points adjustment
	 *
	 * @param object $data_object
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 */
	protected function AddItemToTable($data_object=null, $i_row_count=null, $i_total_rows=null)
	{		
		/* @var $data_object PointsAdjustment */
		$b_has_data_object = !is_null($data_object);
		
		// Number of points
		$i_positive_points = $b_has_data_object ? (int)str_replace('-', '', $data_object->GetPoints()) : null;
		$o_point_value = $this->CreateTextBox($i_positive_points, 'Points', $i_row_count, $i_total_rows, true);
		$o_point_value->AddAttribute('class', trim($o_point_value->GetAttribute('class') . ' numeric pointsBox'));

		// Awarded or deducted?
		$award = new XhtmlSelect();
		$award->AddControl(new XhtmlOption('Awarded', 1));
		$award->AddControl(new XhtmlOption('Deducted', 2));
		$o_points_award = $this->ConfigureSelect($award, $b_has_data_object ? $data_object->GetPoints() > 0 ? 1 : 2 : '', 'Awarded', $i_row_count, $i_total_rows, true, 1);

		// For which team?
		$team = new XhtmlSelect();
		foreach ($this->a_teams as $o_team) $team->AddControl(new XhtmlOption($o_team->GetName(), $o_team->GetId()));
		$o_points_team = $this->ConfigureSelect($team, $b_has_data_object ? $data_object->GetTeam()->GetId() : '', 'PointsTeam', $i_row_count, $i_total_rows, true); 

		// For what reason?
		$o_points_reason = $this->CreateTextBox($b_has_data_object ? $data_object->GetReason() : '', 'Reason', $i_row_count, $i_total_rows, true);
		$o_points_reason->SetMaxLength(200);

		# Add controls to table
		$this->AddRowToTable(array($o_point_value, $o_points_award, $o_points_team, $o_points_reason));
	}


	/**
	 * Create validators to check a single points adjustment
	 *
	 * @param string $s_item_suffix
	 */
	protected function CreateValidatorsForItem($s_item_suffix='')
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/plain-text-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/numeric-range-validator.class.php');

		$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Points' . $s_item_suffix, 'The points adjustment must be a number. For example, \'2\', not \'two\'');
		$this->a_validators[] = new NumericRangeValidator($this->GetNamingPrefix() . 'Points' . $s_item_suffix, 'The points awarded or deducted must be one or more', 1, null);
		$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Awarded' . $s_item_suffix, 'The points adjustment must be awarded or deducted');
		$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'PointsTeam' . $s_item_suffix, 'The team identifier for the points adjustment should be a number');
		$this->a_validators[] = new PlainTextValidator($this->GetNamingPrefix() . 'Reason' . $s_item_suffix, 'Please use only letters, numbers and simple punctuation in the reason for the points adjustment');
		$this->a_validators[] = new LengthValidator($this->GetNamingPrefix() . 'Reason' . $s_item_suffix, 'The reason for the points adjustment should be 200 characters or fewer', 0, 200);
		$this->a_validators[] = new RequiredFieldValidator(array($this->GetNamingPrefix() . 'Points' . $s_item_suffix, $this->GetNamingPrefix() . 'PointsTeam' . $s_item_suffix, $this->GetNamingPrefix() . 'Reason' . $s_item_suffix), 'Please complete all fields to add a points adjustment', ValidatorMode::AllOrNothing());
	}
}
?>