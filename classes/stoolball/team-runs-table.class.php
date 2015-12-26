<?php
require_once('xhtml/tables/xhtml-table.class.php');

/**
 * Displays a table of statistics about the runs scored and conceded by a team
 *
 */
class TeamRunsTable extends XhtmlTable
{
	private $matches_with_run_data;
	private $runs_scored;
	private $runs_conceded;
	private $highest_innings;
	private $lowest_innings;
	private $average_innings;

	/**
	 * Creates a TeamRunsTable
	 *
	 * @param int $matches_with_run_data
	 * @param int $runs_scored
	 * @param int $runs_conceded
	 * @param int $highest_innings
	 * @param int $lowest_innings
	 */
	public function __construct($matches_with_run_data, $runs_scored, $runs_conceded, $highest_innings, $lowest_innings, $average_innings)
	{
		parent::XhtmlTable();

		$this->matches_with_run_data = $matches_with_run_data;
		$this->runs_scored = $runs_scored;
		$this->runs_conceded = $runs_conceded;
		$this->highest_innings = $highest_innings;
		$this->lowest_innings = $lowest_innings;
		$this->average_innings = $average_innings;
	}

	public function OnPreRender()
	{
		if ($this->matches_with_run_data < 1)
		{
			$this->SetVisible(false);
			return;
		}

		# Add table of runs
		$this->SetCssClass('runTable');
		$this->SetCaption("Team runs");
		$total_header = new XhtmlCell(true, "Total");
		$total_header->SetCssClass("numeric");
		$run_header = new XhtmlRow(array(new XhtmlCell(true, "Statistic"), $total_header));
		$run_header->SetIsHeader(true);
		$this->AddRow($run_header);

		$matches_cell = new XhtmlCell(false, $this->matches_with_run_data);
		$matches_cell->SetCssClass('numeric');
		$matches_row = new XhtmlRow(array(new XhtmlCell(false, "matches with runs recorded"), $matches_cell));
		$this->AddRow($matches_row);

		if (!is_null($this->runs_scored))
		{
			$scored_cell = new XhtmlCell(false, $this->runs_scored);
			$scored_cell->SetCssClass('numeric');
			$scored_row = new XhtmlRow(array(new XhtmlCell(false, "runs scored"), $scored_cell));
			$this->AddRow($scored_row);
		}

		if (!is_null($this->runs_conceded))
		{
			$conceded_cell = new XhtmlCell(false, $this->runs_conceded);
			$conceded_cell->SetCssClass('numeric');
			$this->AddRow(new XhtmlRow(array(new XhtmlCell(false, "runs conceded"), $conceded_cell)));
		}

		if (!is_null($this->runs_scored) and !is_null($this->runs_conceded))
		{
			$run_difference = $this->runs_scored-$this->runs_conceded;
			if ($run_difference > 0) $run_difference = "+" . $run_difference;
			$difference_cell = new XhtmlCell(false, $run_difference);
			$difference_cell->SetCssClass('numeric');
			$this->AddRow(new XhtmlRow(array(new XhtmlCell(false, "run difference"), $difference_cell)));
		}

		if (!is_null($this->highest_innings))
		{
			$best_cell = new XhtmlCell(false, $this->highest_innings);
			$best_cell->SetCssClass('numeric');
			$scored_row = new XhtmlRow(array(new XhtmlCell(false, "highest innings"), $best_cell));
			$this->AddRow($scored_row);
		}

		if (!is_null($this->lowest_innings))
		{
			$worst_cell = new XhtmlCell(false, $this->lowest_innings);
			$worst_cell->SetCssClass('numeric');
			$scored_row = new XhtmlRow(array(new XhtmlCell(false, "lowest innings"), $worst_cell));
			$this->AddRow($scored_row);
		}

		if (!is_null($this->average_innings))
		{
			$average_cell = new XhtmlCell(false, round($this->average_innings, 0));
			$average_cell->SetCssClass('numeric');
			$average_row = new XhtmlRow(array(new XhtmlCell(false, "average innings"), $average_cell));
			$this->AddRow($average_row);
		}

		parent::OnPreRender();
	}
}
?>