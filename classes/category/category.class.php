<?php
require_once('media/has-media.interface.php');

class Category implements IHasMedia
{
	var $i_id;
	var $s_name;
	var $i_parent_id;
	var $s_url;
	var $s_navigate_url;
	var $s_description;
	var $b_show_at_site_root;
	var $s_site;
	var $i_hierarchy_level;
	var $i_sort_override;


	/**
	 * Gets a ContentType identifying this as a category
	 *
	 * @return ContentType
	 */
	public function GetContentType() { return ContentType::CATEGORY; }

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database identifier of the category
	*/
	function SetId($i_input)
	{
		if (is_numeric($i_input)) $this->i_id = (int)$i_input;
	}

	/**
	* @return unknown
	* @desc Gets the unique database identifier of the category
	*/
	function GetId()
	{
		return $this->i_id;
	}

	function SetName($s_input)
	{
		if (is_string($s_input)) $this->s_name = $s_input;
	}

	/**
	* @return string
	* @desc Get the display name of the Category
	*/
	function GetName()
	{
		return $this->s_name;
	}

	/**
	* @return string
	* @desc Get the display name of the Category
	*/
	public function __toString()
	{
		return $this->GetName();
	}

	function SetParentId($i_input)
	{
		if (is_numeric($i_input)) $this->i_parent_id = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets the unique id of the parent category in the category hierarchy
	*/
	function GetParentId()
	{
		return $this->i_parent_id;
	}

	function HasParent()
	{
		return (bool)$this->GetParentId();
	}

	function SetUrl($s_input)
	{
		if (is_string($s_input)) $this->s_url = $s_input;
	}

	/**
	* @return string
	* @desc Gets the url segment of the category
	*/
	function GetUrl()
	{
		return $this->s_url;
	}

	/**
	* @return void
	* @param string $s_input
	* @desc Gets the URL for the category home page
	*/
	function SetNavigateUrl($s_input)
	{
		if (is_string($s_input)) $this->s_navigate_url = $s_input;
	}

	/**
	* @return string
	* @desc Gets the URL for the category home page
	*/
	function GetNavigateUrl()
	{
		return $this->s_navigate_url;
	}

	/**
	* @return string
	* @desc Gets the URL to edit the category
	*/
	public function GetEditCategoryUrl()
	{
		return "/yesnosorry/categoryedit.php?item=" . $this->GetId();
	}

	/**
	* @return string
	* @desc Gets the URL to delete the category
	*/
	public function GetDeleteCategoryUrl()
	{
		return "/yesnosorry/categorydelete.php?item=" . $this->GetId();
	}

	function SetDescription($s_input)
	{
		if (is_string($s_input)) $this->s_description = $s_input;
	}

	/**
	* @return string
	* @desc Get a short summary of the Category
	*/
	function GetDescription()
	{
		return $this->s_description;
	}

	function SetShowAtSiteRoot($b_input)
	{
		$this->b_show_at_site_root = (bool)$b_input;
	}

	function GetShowAtSiteRoot()
	{
		return $this->b_show_at_site_root;
	}

	function SetSite($s_input)
	{
		if (is_string($s_input)) $this->s_site = $s_input;
	}

	function GetSite()
	{
		return $this->s_site;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Set how deeply nested is this Category within the category hierarchy
	*/
	function SetHierarchyLevel($i_input)
	{
		if (is_numeric($i_input)) $this->i_hierarchy_level = (int)$i_input;
	}

	/**
	* @return int
	* @desc Get how deeply nested is this Category within the category hierarchy
	*/
	function GetHierarchyLevel()
	{
		return $this->i_hierarchy_level;
	}

	function SetSortOverride($i_input)
	{
		if (is_numeric($i_input)) $this->i_sort_override = (int)$i_input;
	}

	function GetSortOverride()
	{
		return $this->i_sort_override;
	}
}
?>