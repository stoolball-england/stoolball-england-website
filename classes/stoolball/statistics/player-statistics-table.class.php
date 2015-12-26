<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of statistical data for players
 * @author Rick
 *
 */
class PlayerStatisticsTable extends XhtmlTable
{
	private $data;
	private $display_team;
	private $first_result;

	/**
	 * Create a PlayerStatisticsTable
	 * @param $caption
	 * @param $column_heading
	 * @param $data mixed[]
	 * @param $display_team bool
	 * @param int $first_result
	 * @return void
	 */
	public function __construct($caption, $column_heading, $data, $display_team=true, $first_result = 1)
	{
		$this->data = $data;
		$this->display_team = (bool)$display_team;
		$this->first_result = (int)$first_result;

		parent::XhtmlTable();

		# Create the table header
		$this->SetCaption($caption);
		$this->SetCssClass('statsOverall');
		$position = new XhtmlCell(true, "#");
		$position->SetCssClass("position");
		$player = new XhtmlCell(true, "Player");
		$player->SetCssClass("player");
		if ($display_team)
		{
			$team = new XhtmlCell(true, "Team");
			$team->SetCssClass("team");
		}
		$matches = new XhtmlCell(true, "Matches");
		$matches->SetCssClass("numeric");
		$aggregate = new XhtmlCell(true, $column_heading);
		$aggregate->SetCssClass("numeric");
		if ($display_team)
		{
			$header = new XhtmlRow(array($position, $player, $team, $matches, $aggregate));
		}
		else
		{
			$header = new XhtmlRow(array($position, $player, $matches, $aggregate));
		}
		$header->SetIsHeader(true);
		$this->AddRow($header);
	}

	public function OnPreRender()
	{
		$i = $this->first_result;
		$last_value = "";
		foreach ($this->data as $performance)
		{
			$current_value = $performance["statistic"];

			if ($current_value !== $last_value)
			{
				$pos = $i;
				$last_value = $current_value;
			}
			else
			{
				$pos = "=";
			}
			$i++;

			$position = new XhtmlCell(false, $pos);
			$position->SetCssClass("position");
			$player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a>");
			$player->SetCssClass("player");
			$player->AddAttribute("typeof", "schema:Person");
			$player->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $performance["player_url"]);
			if ($this->display_team)
			{
				$team = new XhtmlCell(false, $performance["team_name"]);
				$team->SetCssClass("team");
			}
			$matches = new XhtmlCell(false, $performance["total_matches"]);
			$matches->SetCssClass("numeric");
			$aggregate = new XhtmlCell(false, $current_value);
			$aggregate->SetCssClass("numeric");
			if ($this->display_team)
			{
				$row = new XhtmlRow(array($position, $player, $team, $matches, $aggregate));
			}
			else
			{
				$row = new XhtmlRow(array($position, $player, $matches, $aggregate));
			}
			$this->AddRow($row);
		}

		parent::OnPreRender();
	}
}
?>