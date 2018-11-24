<?php
/**
 * Table showing best performances for a given dataset
 * @author Rick
 *
 */
class StatisticsHighlightTable extends Placeholder
{
	/**
	 * Creates a StatisticsHighlightTable
	 * @param Batting[] $best_batting
	 * @param Batting[] $most_runs
	 * @param Bowling[] $best_bowling
	 * @param Bowling[] $most_wickets
	 * @param array[Player,int] $most_catches
	 * @param string $description
	 * @return void
	 */
	public function __construct($best_batting, $most_runs, $best_bowling, $most_wickets, $most_catches, $description)
	{
		parent::__construct();

		$best_batting_count = count($best_batting);
		$best_bowling_count = count($best_bowling);
		$most_runs_count = count($most_runs);
		$most_wickets_count = count($most_wickets);
		$most_catches_count = count($most_catches);
		$has_player_stats = ($best_batting_count or $best_bowling_count or $most_runs_count or $most_wickets_count or $most_catches_count);

		if (!$has_player_stats) return;

		# Show top players
		$this->AddControl('<table class="playerSummary large"><thead><tr><th colspan="2" class="scope">' . $description . '</th></tr><tr><th>Player</th><th class="total">Total</th></tr></thead><tbody>');

		$i = 1;
		$best = "";
		$list_open = false;
		$row_open = false;
		foreach ($best_batting as $performance)
		{
			$count = $performance["runs_scored"];
			$not_out = ($performance["how_out"] == Batting::NOT_OUT or $performance["how_out"] == Batting::RETIRED or $performance["how_out"] == Batting::RETIRED_HURT);
			if ($not_out) $count .= "*";

			if ($best and $best != $count)
			{
				$best_batting_count--;
			}
			else
			{
				if ($i == 1)
				{
					$this->AddControl('<tr><td>');
					$row_open = true;
				}
				if ($i == 1 and $best_batting_count > 1)
				{
					$this->AddControl("<ul>");
					$list_open = true;
				}

				if ($best_batting_count > 1) $this->AddControl("<li>");
				$this->AddControl('<span typeof="schema:Person" about="http://www.stoolball.org.uk/id/player' . $performance["player_url"] . '"><a property="schema:name" rel="schema:url" href="' . $performance["player_url"]. '">' . $performance["player_name"]. "</a></span>");
				if ($best_batting_count > 1) $this->AddControl("</li>");
				$best = $count;
			}

			if ($i >= $best_batting_count and $row_open)
			{
				if ($list_open) $this->AddControl("</ul>");
				$this->AddControl("</td><td class=\"stat\">best batting<span class=\"stat\">$best</span></td></tr>\n");
				$row_open = false;
			}

			$i++;
		}

		$i = 1;
		foreach ($most_runs as $performance)
		{
			$count = $performance["statistic"];
			$runs = ($count == 1) ? "run" : "runs";
			if ($i == 1) $this->AddControl('<tr><td>');
			if ($i == 1 and $most_runs_count > 1) $this->AddControl("<ul>");
			if ($most_runs_count > 1) $this->AddControl("<li>");
			$this->AddControl('<span typeof="schema:Person" about="http://www.stoolball.org.uk/id/player' . $performance["player_url"] . '"><a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a></span>");
			if ($most_runs_count > 1) $this->AddControl("</li>");
			if ($i == $most_runs_count and $most_runs_count > 1) $this->AddControl("</ul>");
			if ($i == $most_runs_count) $this->AddControl("</td><td class=\"stat\"><span class=\"stat\">$count</span> $runs</td></tr>\n");
			$i++;
		}

		$i = 1;
		$best = "";
		$list_open = false;
		foreach ($best_bowling as $performance)
		{
			$count = $performance["wickets"] . "/" . $performance["runs_conceded"];

			if ($best and $best != $count)
			{
				$best_bowling_count--;
			}
			else
			{
				if ($i == 1)
				{
					$this->AddControl('<tr><td>');
					$row_open = true;
				}
				if ($i == 1 and $best_bowling_count > 1)
				{
					$this->AddControl("<ul>");
					$list_open = true;
				}
				if ($best_bowling_count > 1) $this->AddControl("<li>");
				$this->AddControl('<span typeof="schema:Person" about="http://www.stoolball.org.uk/id/player' . $performance["player_url"] . '"><a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a></span>");
				if ($best_bowling_count > 1) $this->AddControl("</li>");
				$best = $count;
			}

			if ($i >= $best_bowling_count and $row_open)
			{
				if ($list_open) $this->AddControl("</ul>");
				$this->AddControl("</td><td class=\"stat\">best bowling<span class=\"stat\">$best</span></td></tr>\n");
				$row_open = false;
			}

			$i++;
		}

		$i = 1;
		foreach ($most_wickets as $performance)
		{
			$count = $performance["statistic"];
			$wicket = ($count == 1) ? "wicket" : "wickets";
			if ($i == 1) $this->AddControl('<tr><td>');
			if ($i == 1 and $most_wickets_count > 1) $this->AddControl("<ul>");
			if ($most_wickets_count > 1) $this->AddControl("<li>");
			$this->AddControl('<span typeof="schema:Person" about="http://www.stoolball.org.uk/id/player' . $performance["player_url"] . '"><a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a></span>");
			if ($most_wickets_count > 1) $this->AddControl("</li>");
			if ($i == $most_wickets_count and $most_wickets_count > 1) $this->AddControl("</ul>");
			if ($i == $most_wickets_count) $this->AddControl("</td><td class=\"stat\"><span class=\"stat\">$count</span> $wicket</td></tr>\n");
			$i++;
		}

		$i = 1;
		foreach ($most_catches as $performance)
		{
			$count = $performance["statistic"];
			$catch = ($count == 1) ? "catch" : "catches";
			if ($i == 1) $this->AddControl('<tr><td>');
			if ($i == 1 and $most_catches_count > 1) $this->AddControl("<ul>");
			if ($most_catches_count > 1) $this->AddControl("<li>");
			$this->AddControl('<span typeof="schema:Person" about="http://www.stoolball.org.uk/id/player' . $performance["player_url"] . '"><a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a></span>");
			if ($most_catches_count > 1) $this->AddControl("</li>");
			if ($i == $most_catches_count and $most_catches_count > 1) $this->AddControl("</ul>");
			if ($i == $most_catches_count) $this->AddControl("</td><td class=\"stat\"><span class=\"stat\">$count</span> $catch</td></tr>\n");
			$i++;
		}

		$this->AddControl("</tbody></table>");
	}
}