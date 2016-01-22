<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of best individual batting scores
 * @author Rick
 *
 */
class BattingInningsTable extends XhtmlTable
{
	private $batting_data;
	private $display_team;
	private $first_result;
	private $max_results;

	/**
	 * Create a BattingInningsTable
	 * @param $batting_data Batting[]
	 * @param $display_team bool
	 * @param int $first_result
	 * @param int $max_results
	 * @return void
	 */
	public function __construct($batting_data, $display_team=true, $first_result = 1, $max_results = null)
	{
		$this->batting_data = $batting_data;
		$this->display_team = (bool)$display_team;
		$this->first_result = (int)$first_result;
		if (!is_null($max_results)) $this->max_results = (int)$max_results;

		parent::XhtmlTable();

		# Create the table header
		$this->SetCaption(is_null($max_results) ? "All individual scores, highest first" : "Highest individual scores");
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
		$runs = new XhtmlCell(true, "Runs");
		$runs->SetCssClass("numeric");
        $balls = new XhtmlCell(true, "Balls");
        $balls->SetCssClass("numeric");
		if ($display_team)
		{
			$best_batting_header = new XhtmlRow(array($position, $player, $team, $opposition, $when, $runs, $balls));
		}
		else
		{
			$best_batting_header = new XhtmlRow(array($position, $player, $opposition, $when, $runs, $balls));
		}
		$best_batting_header->SetIsHeader(true);
		$this->AddRow($best_batting_header);
	}

	public function OnPreRender()
	{
		$i = $this->first_result;
		$last_value = "";
		foreach ($this->batting_data as $batting)
		{
			$current_value = $batting["runs_scored"];
			$not_out = ($batting["how_out"] == Batting::NOT_OUT or $batting["how_out"] == Batting::RETIRED or $batting["how_out"] == Batting::RETIRED_HURT);
			if ($not_out)
			{
				$current_value .= "*";
			}

			if ($current_value != $last_value)
			{
				# If 11th value is not same as 10th, stop. This can happen because DB query sees 40* and 40 as equal, but they're not.
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
			$player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $batting["player_url"] . '">' . $batting["player_name"] . "</a>");
			$player->SetCssClass("player");
			$player->AddAttribute("typeof", "schema:Person");
			$player->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $batting["player_url"]);
			if ($this->display_team)
			{
				$team = new XhtmlCell(false, $batting["team_name"]);
				$team->SetCssClass("team");
			}
			$opposition = new XhtmlCell(false, $batting["opposition_name"]);
			$opposition->SetCssClass("team");
			$date = new XhtmlCell(false, Date::BritishDate($batting["match_time"], false, true, $this->display_team));
			$date->SetCssClass("date");
			$runs = new XhtmlCell(false, $current_value);
			$runs->SetCssClass("numeric");
			if (!$not_out) $runs->AddCssClass("out");
            $balls_faced = is_null($batting["balls_faced"]) ? "&#8211;" : '<span class="balls-faced">' . $batting["balls_faced"] . '</span>';
            $balls = new XhtmlCell(false, $balls_faced);
            $balls->SetCssClass("numeric");

			if ($this->display_team)
			{
				$row = new XhtmlRow(array($position, $player, $team, $opposition, $date, $runs, $balls));
			}
			else
			{
				$row = new XhtmlRow(array($position, $player, $opposition, $date, $runs, $balls));
			}
			$this->AddRow($row);
		}

		parent::OnPreRender();
	}
}
?>