<?php
require_once('http/query-string.class.php');

class PagedResults
{
	private  $i_current_page = 1;
	private  $i_total_pages = 1;
	private  $i_total_results;
	private  $i_page_size = 10;
	private  $s_results_text_singular = 'item';
	private  $s_results_text_plural = 'items';
	private  $s_page_name;
	private  $s_query_string;

	function PagedResults()
	{
		# set current page from query string
		$this->UpdateCurrentPage();
		$this->SetPageName($_SERVER['PHP_SELF']);
		$this->SetQueryString($_SERVER['QUERY_STRING']);
	}

	function SetPageName($s_input)
	{
		if (is_string($s_input)) $this->s_page_name = $s_input;
	}

	function GetPageName()
	{
		return $this->s_page_name;
	}

	function SetQueryString($s_input)
	{
		if (is_string($s_input)) $this->s_query_string = QueryStringBuilder::RemoveParameter('page', $s_input);
	}

	function GetQueryString()
	{
		return $this->s_query_string;
	}

	function SetCurrentPage($i_input)
	{
		if (is_numeric($i_input)) $this->i_current_page = (int)$i_input;
	}

	function GetCurrentPage()
	{
		return $this->i_current_page;
	}

	function SetTotalPages($i_input)
	{
		if (is_numeric($i_input)) $this->i_total_pages = (int)$i_input;
	}

	function GetTotalPages()
	{
		return $this->i_total_pages;
	}

	function SetTotalResults($i_input)
	{
		if (is_numeric($i_input))
		{
			$this->i_total_results = (int)$i_input;
			$this->UpdateTotalPages();
		}
	}

	function GetTotalResults()
	{
		return $this->i_total_results;
	}

	function SetPageSize($i_input, $i_default=null)
	{
		if (is_numeric($i_input) and $i_input > 0)
		{
			$this->i_page_size = (int)$i_input;
		}
		else if (is_numeric($i_default))
		{
			$this->i_page_size = (int)$i_default;
		}
	}

	function GetPageSize()
	{
		return $this->i_page_size;
	}

	/**
	 * Get the URL for a page of results
	 * @param int $page_number
	 */
	private function GetPageUrl($page_number)
	{
		if ($page_number > 1)
		{
			return $this->GetPageName() . $this->GetQueryString() . 'page=' . $page_number;
		}
		else
		{
			$url = $this->GetPageName() . $this->GetQueryString();
			return substr($url, 0, strlen($url)-1);
		}
	}

	function SetResultsTextSingular($s_input)
	{
		if (is_string($s_input)) $this->s_results_text_singular = $s_input;
	}

	function GetResultsTextSingular()
	{
		return $this->s_results_text_singular;
	}

	function SetResultsTextPlural($s_input)
	{
		if (is_string($s_input)) $this->s_results_text_plural = $s_input;
	}

	function GetResultsTextPlural()
	{
		return $this->s_results_text_plural;
	}

	function GetPageInContext()
	{
		return 'Page ' . $this->GetCurrentPage() . ' of ' . $this->GetTotalPages();
	}

	function GetResultsInContext()
	{
		$i_first_result = $this->GetFirstResultOnPage();
		$i_final_result = $this->GetFinalResultOnPage();

		if ($this->GetTotalResults() == 0)
		{
			$s_text = '0 ' . $this->GetResultsTextPlural();
		}
		else if ($this->GetTotalResults() == 1)
		{
			$s_text = '1 ' . $this->GetResultsTextSingular();
		}
		else if ($this->GetTotalPages() == 1)
		{
			$s_text = $this->GetTotalResults() . ' ' . $this->GetResultsTextPlural();
		}
		else if ($i_first_result == $this->GetTotalResults())
		{
			$s_text = ucfirst($this->GetResultsTextSingular()) . ' ' . $i_first_result . ' of ' . $this->GetTotalResults() . ' ' . $this->GetResultsTextPlural();
		}
		else
		{
			$s_text = $i_first_result . '&#8212;' . $i_final_result . ' of ' . $this->GetTotalResults() . ' ' . $this->GetResultsTextPlural();
		}

		return $s_text;
	}

	/**
	 * Gets the position in the full resultset of the first result on the current page
	 *
	 * @return int
	 */
	function GetFirstResultOnPage()
	{
		return ($this->GetCurrentPage() * $this->GetPageSize()) - ($this->GetPageSize() - 1);
	}

	/**
	 * Gets the position in the full resultset of the final result on the current page
	 *
	 * @return int
	 */
	function GetFinalResultOnPage()
	{
		$i_final_result = ($this->GetCurrentPage() * $this->GetPageSize());
        if ($this->GetCurrentPage() == $this->GetTotalPages()) $i_final_result = $this->GetTotalResults();
        return $i_final_result;
	}

	function UpdateCurrentPage()
	{
		if (isset($_GET['page']) and $_GET['page'] and is_numeric($_GET['page']))
		{
			$this->SetCurrentPage($_GET['page']);
		}
		else
		{
			$this->SetCurrentPage(1);
		}
	}

	function UpdateTotalPages()
	{
		$i_leftover = ($this->GetTotalResults() % $this->GetPageSize());

		if ($i_leftover > 0)
		{
			$i_pages = ((($this->GetTotalResults() - $i_leftover) / $this->GetPageSize()) + 1);
		}
		else
		{
			$i_pages = $this->GetTotalResults()/$this->GetPageSize();
		}

		$this->SetTotalPages(ceil($i_pages));

		# now check that the current page exists
		if ($this->GetCurrentPage() > $this->GetTotalPages()) $this->SetCurrentPage($this->GetTotalPages());
	}

	function TrimRows($a_results)
	{
		if (is_array($a_results))
		{
			# work out which rows we need for this page
			$i_first_result_pos = (($this->GetCurrentPage() * $this->GetPageSize()) - ($this->GetPageSize() - 1)) -1;
			$i_final_result_pos = ($this->GetCurrentPage() * $this->GetPageSize()) -1;
			if ($this->GetCurrentPage() == $this->GetTotalPages()) $i_final_result_pos = $this->GetTotalResults()-1;

			# loop through and delete the ones we don't need
			# loop backwards because otherwise deleting rows messes up the count using $i_count
			for ($i_count = count($a_results)-1; $i_count >= 0; $i_count--)
			{
				if (($i_count < $i_first_result_pos) or ($i_count > $i_final_result_pos)) unset($a_results[$i_count]);
			}
		}

		return $a_results;
	}

	/**
	 * Gets a link to the previous page, if appropriate
	 */
	public function GetPreviousLink()
	{
		if ($this->GetCurrentPage() > 1)
		{
			return '<a href="' . $this->GetPageUrl($this->GetCurrentPage() - 1) . '" title="Go to the previous page of these ' . $this->GetResultsTextPlural() . '">&lt; Prev</a> ';
		}
	}

	/**
	 * Gets a link to the next page, if appropriate
	 */
	public function GetNextLink()
	{
		if ($this->GetCurrentPage() < $this->GetTotalPages())
		{
			return ' <a href="' . $this->GetPageName() . $this->GetQueryString() . 'page=' . ($this->GetCurrentPage() + 1) . '" title="Go to the next page of these ' . $this->GetResultsTextPlural() . '">Next &gt;</a>';
		}
	}

	function GetPages()
	{
		$i_upper_1 = 0;
		$i_lower_2 = $this->GetCurrentPage();
		$i_lower_spread = $this->GetCurrentPage() - 1;
		$i_upper_spread = $this->GetTotalPages() - $this->GetCurrentPage();
		$b_show_upper_ellipses = false;
		$b_show_lower_ellipses = false;
		$s_nav_links = '';

		# don't bother with page navigation if there's only one page
		if ($this->GetTotalPages() > 1)
		{
            # calculate ellipses
			# calculate lower
			if (($this->GetCurrentPage() - 1) > 1) $i_upper_1 = $this->GetCurrentPage() - 1;

			if ($i_lower_spread < 3)
			{
				$i_lower_1 = $i_upper_1;
			}
			else
			{
				if ($i_lower_spread > 5)
				{
					$i_lower_1 = $this->GetCurrentPage() - 4;
					$b_show_lower_ellipses = true;
				}
				else
				{
					$i_lower_1 = $this->GetCurrentPage() - ($i_lower_spread - 1);
				}
			}
			# calculate upper
			if (($this->GetCurrentPage() + 1) < $this->GetTotalPages())
			{
				$i_lower_2 = $this->GetCurrentPage() + 1;
			}
			if ($i_upper_spread < 3)
			{
				$i_upper_2 = $i_lower_2;
			}
			else
			{
				if ($i_upper_spread > 5)
				{
					$i_upper_2 = $this->GetCurrentPage() + 4;
					$b_show_upper_ellipses = true;
				}
				else
				{
					$i_upper_2 = $this->GetCurrentPage() + ($i_upper_spread - 1);
				}
			}

			# render Page 1 link
			if ($this->GetCurrentPage() == 1) $s_nav_links .= '1 ';
			else $s_nav_links .= '<a href="' . $this->GetPageUrl(1) . '" title="Go to the first page of these ' . $this->GetResultsTextPlural() . '">1</a> ';

			# render Lower Ellipses
			if ($i_lower_1 > 0)
			{
				if ($b_show_lower_ellipses) $s_nav_links .= '&#8230; ';

				for ($i_count = $i_lower_1; $i_count <= $i_upper_1; $i_count++)	$s_nav_links .= '<a href="' . $this->GetPageName() . $this->GetQueryString() . 'page=' . $i_count . '" title="Go to page ' . $i_count . ' of these ' . $this->GetResultsTextPlural() . '">' . $i_count . '</a> ';
			}

			# render Current Page link
			if (($this->GetCurrentPage() > 1) and ($this->GetCurrentPage() != $this->GetTotalPages())) $s_nav_links .= $this->GetCurrentPage() . ' ';

			# render Upper Ellipses
			if (($i_lower_2 > 0) and ($i_upper_2 != $this->GetCurrentPage()))
			{
				for ($i_count = $i_lower_2; $i_count <= $i_upper_2; $i_count++) $s_nav_links .= '<a href="' . $this->GetPageName() . $this->GetQueryString() . 'page=' . $i_count . '" title="Go to page ' . $i_count . ' of these ' . $this->GetResultsTextPlural() . '">' . $i_count . '</a> ';

				if ($b_show_upper_ellipses) $s_nav_links .= "&#8230; ";
			}
			# render Last Page link
			if ($this->GetCurrentPage() == $this->GetTotalPages()) $s_nav_links .= $this->GetTotalPages();
			else $s_nav_links .= '<a href="' . $this->GetPageName() . $this->GetQueryString() . 'page=' . $this->GetTotalPages() . '" title="Go to the final page of these ' . $this->GetResultsTextPlural() . '">' . $this->GetTotalPages() . '</a>';

			# Only show the page links when there's plenty of room
			if ($s_nav_links) $s_nav_links = '<span class="numbers">' . $s_nav_links . '</span>'; 
			
			# render next and previous link
			$s_nav_links = $this->GetPreviousLink() . $s_nav_links . $this->GetNextLink();
		}

		return $s_nav_links;

	}

	function GetUnstyledNavigationBar()
	{
		$s_text = '<div class="pageContext">' . "\n" .
		$this->GetResultsInContext() .
			'</div>' . "\n";

        $s_text .= '<div class="pages">' . $this->GetPages() . '</div>' . "\n";

		return $s_text;
	}

	function GetNavigationBar()
	{
		$s_text = '<div class="paging">' . "\n" .
		$this->GetUnstyledNavigationBar() .
			'</div>' . "\n";

		return $s_text;
	}

	function GetPageForId($i_item_id, $a_items)
	{
		$i_page = 0;
		if (is_numeric($i_item_id) and is_array($a_items))
		{
			# make sure we're dealing with right datatype
			$i_item_id = (int)$i_item_id;

			# first, get the index from the array
			$i_index = 0;
			for ($i_count = 0; $i_count < count($a_items); $i_count++)
			{
				if (is_array($a_items[$i_count])) $i_this_id = (int)$a_items[$i_count]['id'];
				elseif (is_object($a_items[$i_count])) $i_this_id = (int)$a_items[$i_count]->GetId();

				if ($i_this_id == $i_item_id)
				{
					$i_index = $i_count;
					break;
				}
			}

			# now find the page for that index
			$i_index++; # zero-based

			$i_page = ceil($i_index / $this->GetPageSize());
		}

		if (intval($i_page) == 0) $i_page = 1;

		return $i_page;
	}
}
?>