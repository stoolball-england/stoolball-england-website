<?php
require_once('xhtml/forms/xhtml-select.class.php');

/**
 * Dropdown list of seasons, sorted by year and competition
 *
 */
class SeasonSelect extends XhtmlSelect
{
	private $a_seasons = array();

	/**
	 * Adds the seasons to the dropdown list
	 *
	 * @param Season[] $a_seasons
	 */
	public function AddSeasons($a_seasons)
	{
		if (is_array($a_seasons))
		{
			foreach ($a_seasons as $o_obj)
			{
				if ($o_obj instanceof Season)
				{
					$this->a_seasons[] = $o_obj;
				}
			}
		}

		$this->SortSeasons();
		$this->GroupOptions();
		$this->Reselect();

	}

	/**
	 *  Sort seasons by date, then competition
	 *
	 */
	private function SortSeasons()
	{
		$a_sort_control = array();
		$a_subsort_control = array();
		$a_subsubsort_control = array();
		foreach ($this->a_seasons as $o_season)
		{
			$a_sort_control[] = $o_season->GetStartYear();
			$a_subsort_control[] = $o_season->GetEndYear();
			$a_subsubsort_control[] = $o_season->GetCompetitionName();
		}
		array_multisort($a_sort_control, SORT_DESC, $a_subsort_control, SORT_DESC, $a_subsubsort_control, SORT_ASC, $this->a_seasons);
	}

	/**
	 * Sorts seasons into option groups and adds them to list
	 *
	 */
	private function GroupOptions()
	{
		# Group seasons by year and add to list
		$i_start_year = 0;
		$i_end_year = 0;
		$group = null;
		foreach ($this->a_seasons as $o_season)
		{
			/* @var $o_season Season  */
			if ($o_season->GetStartYear() != $i_start_year or $o_season->GetEndYear() != $i_end_year)
			{
				$group = $o_season->GetYears();
				$i_start_year = $o_season->GetStartYear();
				$i_end_year = $o_season->GetEndYear();
			}
			$competition_name = $o_season->GetCompetitionName();
			$o_opt = new XhtmlOption(substr($competition_name, 0, strlen($competition_name)-7), $o_season->GetId()); # remove " season" from end because the dropdown's so wide
			$o_opt->SetGroupName($group);
			$this->AddControl($o_opt);
		}
	}

	/**
	 * Reselect season if posted
	 *
	 */
	private function Reselect()
	{
		if (!$this->HasSelected() and isset($_POST[$this->GetXhtmlId()]) and (int)$_POST[$this->GetXhtmlId()]) $this->SelectOption((int)$_POST[$this->GetXhtmlId()]);
	}
}
?>