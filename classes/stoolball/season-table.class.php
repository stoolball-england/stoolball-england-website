<?php
require_once('xhtml/tables/xhtml-table.class.php');

class SeasonTable extends XhtmlTable
{
	/**
	 * Configurable sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $o_settings;

	private $a_results_missing = array();
	/**
	 * The season to display results for
	 *
	 * @var Season
	 */
	private $o_season;

	public function __construct(SiteSettings $o_settings, Season $o_season)
	{
		$this->o_settings = $o_settings;
		$this->o_season = $o_season;

		parent::__construct();

		# Add table header
		$this->SetCaption('Results table for this season');
		$this->SetCssClass('numeric league');

		$headings = array('Team', 'Played', 'Won', 'Lost', 'Tied', 'No result');
		$extra_columns = 0;

		if ($this->o_season->GetShowTableRunsScored())
		{
			$headings[] = 'Runs scored';
			$extra_columns++;
		}
		if ($this->o_season->GetShowTableRunsConceded())
		{
			$headings[] = 'Runs conceded';
			$extra_columns++;
		}
		$headings[] = 'Points';

		if ($extra_columns)
		{
			$this->SetColumnGroupSizes(array(1, 5, $extra_columns, 1));
			$a_colgroups = $this->GetColumnGroups();
			$a_colgroups[0]->SetCssClass('nonNumeric');
			$a_colgroups[1]->SetCssClass('resultCount');
			$a_colgroups[2]->SetCssClass('resultCount runs');
			$a_colgroups[3]->SetCssClass('resultCount');
		}
		else
		{
			$this->SetColumnGroupSizes(array(1, 5, 1));
			$a_colgroups = $this->GetColumnGroups();
			$a_colgroups[0]->SetCssClass('nonNumeric');
			$a_colgroups[1]->SetCssClass('resultCount');
			$a_colgroups[2]->SetCssClass('resultCount');
		}

		$o_header = new XhtmlRow($headings);
		$o_header->SetIsHeader(true);
		$this->AddRow($o_header);
	}

	function OnPreRender()
	{
		/* @var $o_team Team */
		/* @var $o_match Match */

		$a_data_to_bind = array();
		$i_played = 1;
		$i_won = 2;
		$i_lost = 3;
		$i_tie = 4;
		$i_noresult = 5;
		$i_runs_for = 6;
		$i_runs_against = 7;
		$i_points = 8;

		# Build an array of teams, and initiate an array of data for each team
		foreach ($this->o_season->GetTeams() as $o_team)
		{
			if ($o_team instanceof Team and !is_object($this->o_season->TeamsWithdrawnFromLeague()->GetItemByProperty('GetId', $o_team->GetId())))
			{
				$a_team_data = array();
				$a_team_data[0] = new XhtmlAnchor(htmlentities($o_team->GetName(), ENT_QUOTES, "UTF-8", false), $o_team->GetNavigateUrl());
				$a_team_data[$i_played] = 0;
				$a_team_data[$i_won] = 0;
				$a_team_data[$i_lost] = 0;
				$a_team_data[$i_tie] = 0;
				$a_team_data[$i_noresult] = 0;
				if ($this->o_season->GetShowTableRunsScored()) $a_team_data[$i_runs_for] = 0;
				if ($this->o_season->GetShowTableRunsConceded()) $a_team_data[$i_runs_against] = 0;
				$a_team_data[$i_points] = 0;
				$a_data_to_bind[$o_team->GetId()] = &$a_team_data;
				unset($a_team_data);
			}
		}

		# Look at matches to build data for each team
		foreach ($this->o_season->GetMatches() as $o_match)
		{
			# Discount matches in the future
			if ($o_match->GetStartTime() >= gmdate('U')) break;

			# Discount non-league matches
			if ($o_match->GetMatchType() != MatchType::LEAGUE) continue;

			# Discount postponed matches
			if ($o_match->Result()->GetResultType() == MatchResult::POSTPONED) continue;

			# Discount matches where a team has withdrawn from the league
			if (is_object($this->o_season->TeamsWithdrawnFromLeague()->GetItemByProperty('GetId', $o_match->GetHomeTeamId()))) continue;
			if (is_object($this->o_season->TeamsWithdrawnFromLeague()->GetItemByProperty('GetId', $o_match->GetAwayTeamId()))) continue;

			# Make a note of missing results, to excuse inaccuracies
			if ($o_match->Result()->GetResultType() == MatchResult::UNKNOWN)
			{
				$this->a_results_missing[] = '<a href="' . Html::Encode($o_match->GetNavigateUrl()) . '">' .
				        Html::Encode($o_match->GetTitle()) . '</a> &#8211; ' . Html::Encode($o_match->GetStartTimeFormatted());
				continue;
			}

			# Home team
			$i_home = $o_match->GetHomeTeamId();

			if (array_key_exists($i_home, $a_data_to_bind))
			{
				$a_data_to_bind[$i_home][$i_played]++;
				if ($o_match->Result()->GetIsHomeWin()) $a_data_to_bind[$i_home][$i_won]++;
				else if ($o_match->Result()->GetIsAwayWin()) $a_data_to_bind[$i_home][$i_lost]++;
				else if ($o_match->Result()->GetIsEqualResult()) $a_data_to_bind[$i_home][$i_tie]++;
				else if ($o_match->Result()->GetIsNoResult()) $a_data_to_bind[$i_home][$i_noresult]++;
				else $a_data_to_bind[$i_home][$i_played]--; // safeguard - shouldn't get here
				if ($this->o_season->GetShowTableRunsScored()) $a_data_to_bind[$i_home][$i_runs_for] = ($a_data_to_bind[$i_home][$i_runs_for] + $o_match->Result()->GetHomeRuns());
				if ($this->o_season->GetShowTableRunsConceded()) $a_data_to_bind[$i_home][$i_runs_against] = ($a_data_to_bind[$i_home][$i_runs_against] + $o_match->Result()->GetAwayRuns());
				$a_data_to_bind[$i_home][$i_points] = ($a_data_to_bind[$i_home][$i_points] + $o_match->Result()->GetHomePoints());

			}

			# Away team
			$i_away = $o_match->GetAwayTeamId();

			if (array_key_exists($i_away, $a_data_to_bind))
			{
				$a_data_to_bind[$i_away][$i_played]++;
				if ($o_match->Result()->GetIsHomeWin()) $a_data_to_bind[$i_away][$i_lost]++;
				else if ($o_match->Result()->GetIsAwayWin()) $a_data_to_bind[$i_away][$i_won]++;
				else if ($o_match->Result()->GetIsEqualResult()) $a_data_to_bind[$i_away][$i_tie]++;
				else if ($o_match->Result()->GetIsNoResult()) $a_data_to_bind[$i_away][$i_noresult]++;
				else $a_data_to_bind[$i_away][$i_played]--;  // safeguard - shouldn't get here
				if ($this->o_season->GetShowTableRunsScored()) $a_data_to_bind[$i_away][$i_runs_for] = ($a_data_to_bind[$i_away][$i_runs_for] + $o_match->Result()->GetAwayRuns());
				if ($this->o_season->GetShowTableRunsConceded()) $a_data_to_bind[$i_away][$i_runs_against] = ($a_data_to_bind[$i_away][$i_runs_against] + $o_match->Result()->GetHomeRuns());
				$a_data_to_bind[$i_away][$i_points] = ($a_data_to_bind[$i_away][$i_points] + $o_match->Result()->GetAwayPoints());
			}
		}

		# Apply points adjustments
		foreach ($this->o_season->PointsAdjustments()->GetItems() as $o_point)
		{
			/* @var $o_point PointsAdjustment */
			$a_data_to_bind[$o_point->GetTeam()->GetId()][$i_points] += $o_point->GetPoints();
		}

		# Sort the teams so that the highest points come first
		$a_control_array = array();
		foreach($a_data_to_bind as $a_team_data) $a_control_array[] = $a_team_data[$i_points];

		$a_control_subarray = array();
		foreach ($a_data_to_bind as $a_team_data) $a_control_subarray[] = $a_team_data[$i_played];

		array_multisort($a_control_array, SORT_DESC, $a_control_subarray, SORT_DESC, $a_data_to_bind);

		# Display the data
		$this->BindArray($a_data_to_bind);

		# Add withdrawn teams at the end of the table
		foreach ($this->o_season->TeamsWithdrawnFromLeague() as $team)
		{
			/* @var $team Team */
			$withdrawn_row = new XhtmlRow(array(new XhtmlAnchor(htmlentities($team->GetName(), ENT_QUOTES, "UTF-8", false), $team->GetNavigateUrl()), 'Withdrew from league'));
			$withdrawn_row->SetCssClass('withdrawn');
			$this->AddRow($withdrawn_row);
		}

		parent::OnPreRender();
	}

	/**
	 * Generates the XHTML for the control
	 *
	 * @return string
	 */
	public function __toString()
	{
		$s_xhtml = parent::__toString();

		if ($this->o_season->PointsAdjustments()->GetCount())
		{
			$s_xhtml .= '<p>The results table includes the following points adjustments:</p><ul>';
			$this->o_season->PointsAdjustments()->SortByProperty('GetDate');
			foreach ($this->o_season->PointsAdjustments() as $o_point)
			{
				/* @var $o_point PointsAdjustment */

				# Convert sign to positive the non-maths way
				$i_positive_points = (int)str_replace('-', '', $o_point->GetPoints());
				$s_points = ($i_positive_points > 1) ? ' points' : ' point';
				$s_reason = ($o_point->GetReason()) ? ' for ' . $o_point->GetReason() : '';

				# Display a message about each adjustment
				if ($o_point->GetPoints() > 0)
				{
					$s_xhtml .= '<li>' . htmlentities($i_positive_points . $s_points . ' awarded to ' . $o_point->GetTeam()->GetName() . $s_reason, ENT_QUOTES, "UTF-8", false) . '</li>';
				}
				elseif ($o_point->GetPoints() < 0)
				{
					$s_xhtml .= '<li>' . htmlentities($i_positive_points . $s_points . ' deducted from ' . $o_point->GetTeam()->GetName() . $s_reason, ENT_QUOTES, "UTF-8", false) . '</li>';
				}
			}
			$s_xhtml .= '</ul>';
		}

		if (count($this->a_results_missing))
		{
			$s_xhtml .= '<p class="resultPending">Waiting for results from:</p><ul class="resultPending"><li>' . implode('</li><li>', $this->a_results_missing) . '</li></ul>';
		}
		return $s_xhtml;
	}
}
?>