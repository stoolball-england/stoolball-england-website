<?php
require_once('data/content-type.enum.php');

/**
 * A content item which can have comments associated with it
 *
 */
class ReviewItem
{
	var $i_id;
	var $i_type;
	var $s_title;
	var $o_settings;
	var $o_categories;
	var $s_date;
	var $s_navigate_url;

	function ReviewItem(SiteSettings $o_settings, $o_categories=null)
	{
		$this->o_settings = $o_settings;
		$this->o_categories = $o_categories;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database identifier of the item being reviewed
	*/
	function SetId($i_input)
	{
		if (is_numeric($i_input)) $this->i_id = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets the unique database identifier of the item being reviewed
	*/
	function GetId()
	{
		return $this->i_id;
	}

	/**
	* @return void
	* @param ContentType $i_input
	* @desc Sets the type of item being reviewed
	*/
	function SetType($i_input)
	{
		if (is_numeric($i_input)) $this->i_type = (int)$i_input;
	}

	/**
	* @return ContentType
	* @desc Gets the type of item being reviewed
	*/
	function GetType()
	{
		return $this->i_type;
	}

	/**
	 * Sets the title of the item associated with the comments
	 *
	 * @param string $s_input
	 */
	function SetTitle($s_input)
	{
		if (is_string($s_input)) $this->s_title = $s_input;
	}

	/**
	 * Gets the title of the item associated with the comments
	 *
	 * @return  string
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
		# Prefer a simple getter
		if ($this->s_navigate_url) return $this->s_navigate_url;

		# Support tightly-coupled legacy approach
		$s_root = '';
		if (!$b_relative)
		{
			$s_root = 'http://' . $this->o_settings->GetDomain();
		}

		switch ($this->GetType())
		{
			case ContentType::PAGE_COMMENTS:
				$o_category = $this->o_categories->GetById($this->GetId());
				return $s_root . $o_category->GetNavigateUrl();
				break;

			case ContentType::STOOLBALL_MATCH:
				return $s_root . $this->o_settings->GetUrl('Match') . $this->GetId();
				break;

		}
	}
}
?>