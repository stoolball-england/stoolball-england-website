<?php
require_once('data/content-type.enum.php');

class RatedItem
{
	/**
	 * Configurable sitewide settings
	 *
	 * @var SiteSettings
	 */
	var $o_settings;
	var $i_id;
	var $i_category_id;
	private $i_type;
	var $s_title;
	private $s_date;
    var $a_ratings;
	var $f_average_rating;
	var $i_total_ratings;
	var $i_old_ratings;
	var $o_new_rating;
	var $o_categories;
	private $s_navigate_url;

	function RatedItem(SiteSettings $o_settings, $o_categories=null)
	{
		$this->o_settings = $o_settings;
		$this->o_categories = $o_categories;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database identifier or the item being rated
	*/
	function SetId($i_input)
	{
		if (is_numeric($i_input)) $this->i_id = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets the unique database identifier of the item being rated
	*/
	function GetId()
	{
		return $this->i_id;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database identifier of the item's category
	*/
	function SetCategoryId($i_input)
	{
		if (is_numeric($i_input)) $this->i_category_id = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets the unique database identifier of the item's category
	*/
	function GetCategoryId()
	{
		return $this->i_category_id;
	}

	/**
	* @return void
	* @param int $i_type
	* @desc Sets the ContentType of the item being rated
	*/
	function SetType($i_type)
	{
		$this->i_type = (int)$i_type;
	}

	/**
	* @return int
	* @desc Gets the type of the item being rated
	*/
	function GetType()
	{
		return $this->i_type;
	}

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the name or title of the item being rated
	*/
	function SetTitle($s_input)
	{
		if (is_string($s_input)) $this->s_title = $s_input;
	}

	/**
	* @return string
	* @desc Gets the name or title of the item being rated
	*/
	function GetTitle()
	{
		return $this->s_title;
	}


	function SetDate($s_input)
	{
		if (is_string($s_input)) $this->s_date = $s_input;
	}

	function GetDate()
	{
		return $this->s_date;
	}

	/**
	* @return void
	* @param Rating[] $a_input
	* @desc Sets the ratings for the item
	*/
	function SetRatings($a_input)
	{
		if (is_array($a_input))
		{
			$this->a_ratings = $a_input;
			$this->GenerateRatingStats();
		}
	}

	/**
	* @return Rating[]
	* @desc Gets the ratings for the item
	*/
	function GetRatings()
	{
		return $this->a_ratings;
	}

	/**
	* @return void
	* @param float $f_input
	* @desc Sets the average of all prior ratings, rounded to one decimal place
	*/
	function SetAverageRating($f_input)
	{
		if (is_numeric($f_input)) $this->f_average_rating = (float)$f_input;
	}

	/**
	* @return float
	* @desc Gets the average of all prior ratings, rounded to one decimal place
	*/
	function GetAverageRating()
	{
		return $this->f_average_rating;
	}

	function SetTotalRatings($i_input)
	{
		if (is_numeric($i_input)) $this->i_total_ratings = (int)$i_input;
	}

	function GetTotalRatings()
	{
		return $this->i_total_ratings;
	}

	function SetOldRatings($i_input)
	{
		if (is_numeric($i_input)) $this->i_old_ratings = (int)$i_input;
	}

	function GetOldRatings()
	{
		return $this->i_old_ratings;
	}

	function SetNewRating($o_input)
	{
		if (is_object($o_input)) $this->o_new_rating = $o_input;
	}

	function GetNewRating()
	{
		return $this->o_new_rating;
	}

	function GenerateRatingStats()
	{
		if(is_array($this->a_ratings))
		{
			$i_count = 0; # how many ratings have there been?
			$i_total = 0; # what do the ratings add up to?
			$i_old = 0; # how many old ratings from when only totals were kept?

			foreach($this->a_ratings as $o_rating)
			{
				# add value of this rating to totals
				$i_count += ($o_rating->GetWeighting() * $o_rating->GetMultiplier());
				$i_total += ($o_rating->GetRating() * $o_rating->GetWeighting() * $o_rating->GetMultiplier());

				# higher weightings indicate ratings transferred from before registration required
				# not 100% accurate because some transfers has weighting of 1, but doesn't matter
				if ($o_rating->GetWeighting() > 1)	$i_old += $o_rating->GetWeighting();
			}

			# get average rating to 1 decimal place (avoiding divide by 0 error)
			$i_average = $i_count ? round($i_total / $i_count,1) : 0;

			# store results
			$this->SetAverageRating($i_average);
			$this->SetTotalRatings($i_count);
			$this->SetOldRatings($i_old);
		}
	}


	/**
	 * Sets the URL to view the item associated with the comments
	 *
	 * @param string $s_input
	 */
	function SetNavigateUrl($s_input)
	{
		$this->s_navigate_url = trim((string)$s_input);
	}


	/**
	 * Gets the URL to view the item associated with the comments
	 *
	 * @param bool $b_relative
	 */
	function GetNavigateUrl($b_relative=true)
	{
		return $this->s_navigate_url;
	}

	function GetMessageUrl()
	{
		# get details of rating to pass across
		$o_rating = $this->GetNewRating();
		if (is_object($o_rating))
		{
			$i_rating_id = $o_rating->GetRatingId();
			$i_rating = $o_rating->GetRating();
		}

		#  which folder?
		$s_folder = $this->o_settings->GetClientRoot();

		# build url
		$s_url = $s_folder . 'comment.php?type=' . $this->GetType() . '&amp;item=' . $this->GetId() . '&amp;cid=' . $this->GetCategoryId() . '&amp;title=' . urlencode($this->GetTitle()) . '&amp;rating=';
		if (isset($i_rating)) $s_url .= $i_rating;

		$s_url .= '&amp;ratingid=';
		if (isset($i_rating_id)) $s_url .= $i_rating_id;

		$s_page = '';
		if (isset($_POST['page'])) $s_page = urlencode($_POST['page']);
		else if (isset($_GET['page'])) $s_page = urlencode($_GET['page']);
		if ($s_page) $s_url .= '&amp;page=' . $s_page;

		return $s_url;
	}

	function GetBreakdownUrl()
	{
		$s_folder = '';

		$s_navigate_url = $s_folder . 'rating.php?id=' . $this->GetId() . '&amp;type=' . $this->GetType() . '&amp;cid=' . $this->GetCategoryId() . '&amp;page=' . urlencode($_SERVER['REQUEST_URI']);
		return $s_navigate_url;
	}
}
?>