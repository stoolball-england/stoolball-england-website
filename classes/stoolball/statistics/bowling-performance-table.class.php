<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of best individual bowling performances
 * @author Rick
 *
 */
class BowlingPerformanceTable extends XhtmlTable
{
	private $bowling_data;
	private $first_result;
	private $max_results;
	private $display_team;

	/**
	 * Create a BowlingPerformanceTable
	 * @param $bowling_data Bowling[]
	 * @param $display_team bool
	 * @param int $first_result
	 * @param int $max_results
	 * @return void
	 */
	public function __construct($bowling_data, $display_team=true, $first_result = 1, $max_results = null)
	{
		$this->bowling_data = $bowling_data;
		$this->display_team = (bool)$display_team;
		$this->first_result = (int)$first_result;
		if (!is_null($max_results)) $this->max_results = (int)$max_results;

		parent::XhtmlTable();

		# Create the table header
		$this->SetCaption(is_null($max_results) ? "All bowling performances, best first" : "Best bowling performances");
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
		$opposition = new XhtmlCell(true, "Opposition");
		$opposition->SetCssClass("team");
		$when = new XhtmlCell(true, "When");
		$when->SetCssClass("date");
		$wickets = new XhtmlCell(true, $display_team ? '<abbr title="Wickets">Wkts</abbr>' : "Wickets");
		$wickets->SetCssClass("numeric");
		$runs = new XhtmlCell(true, "Runs");
		$runs->SetCssClass("numeric");
		if ($display_team)
		{
			$best_bowling_header = new XhtmlRow(array($position, $player, $team, $opposition, $when, $wickets, $runs));
		}
		else
		{
			$best_bowling_header = new XhtmlRow(array($position, $player, $opposition, $when, $wickets, $runs));
		}
		$best_bowling_header->SetIsHeader(true);
		$this->AddRow($best_bowling_header);
	}

	public function OnPreRender()
	{
		$i = $this->first_result;
		$last_value = "";
		foreach ($this->bowling_data as $bowling)
		{
			$current_value = $bowling["wickets"] . "/". $bowling["runs_conceded"];

			if ($current_value != $last_value)
			{
				# If 11th value is not same as 10th, stop. This can happen because DB query sees selects all performances with the same number of wickets as the tenth.
				if (!is_null($this->max_results) and $i > $this->max_results) break;

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
			$player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $bowling["player_url"] . '">' . $bowling["player_name"] . "</a>");
			$player->SetCssClass("player");
			$player->AddAttribute("typeof", "schema:Person");
			$player->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $bowling["player_url"]);
			if ($this->display_team)
			{
				$team = new XhtmlCell(false, $bowling["team_name"]);
				$team->SetCssClass("team");
			}
			$opposition = new XhtmlCell(false, $bowling["opposition_name"]);
			$opposition->SetCssClass("team");
			$date = new XhtmlCell(false, Date::BritishDate($bowling["match_time"], false, true, $this->display_team));
			$date->SetCssClass("date");
			$wickets = new XhtmlCell(false, $bowling["wickets"]);
			$wickets->SetCssClass("numeric");
			$runs = new XhtmlCell(false, $bowling["runs_conceded"]);
			$runs->SetCssClass("numeric");
			if ($this->display_team)
			{
				$row = new XhtmlRow(array($position, $player, $team, $opposition, $date, $wickets, $runs));
			}
			else
			{
				$row = new XhtmlRow(array($position, $player, $opposition, $date, $wickets, $runs));
			}
			$this->AddRow($row);
		}

		parent::OnPreRender();
	}
}
?>