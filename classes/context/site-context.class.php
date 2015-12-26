<?php
class SiteContext
{
	var $o_current_categories;

	public function __construct(CategoryCollection $o_categories)
	{
		require_once('category/category-collection.class.php'); # not required if only static methods are in use
		$this->o_current_categories = new CategoryCollection();
		$this->GetSiteContext($o_categories);
	}

	protected function GetSiteContext(CategoryCollection $o_categories)
	{
		# for WordPress get the actual URL requested rather than the script name, since mod_rewrite can introduce some big differences
		$url = SiteContext::IsWordPress() ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];

		# Strip any querystring
		$pos = strpos($url, '?');
		if ($pos) $url = substr($url, 0, $pos);

		# If there's a filename, strip it
		if (strpos($url, ".php")) $url = substr($url, 0, strpos($url, basename($url)));

		# Strip any surrounding slashes
		$url = trim($url, '/');

		# If there's more than one folder, loop from the deepest to the highest looking for one which matches a category
		$a_folders = explode('/', $url);
		if (is_array($a_folders))
		{
			for ($i = count($a_folders)-1; $i >=0; $i--)
			{
				$o_category = $o_categories->GetByUrl($a_folders[$i]);
				if ($o_category != false)
				{
					$this->o_current_categories->Add($o_category);
					break;
				}
			}
		}

		# get ancestors of current category
		while($o_parent = $o_categories->GetParent($this->o_current_categories->GetFirst()))
		{
			if ($o_parent != false)
			{
				$this->o_current_categories->Insert($o_parent);
			}
			else break;
		}
	}

	/**
	 * @return Category
	 * @desc Gets the category of the current request
	 */
	function GetCurrent()
	{
		$o_current = $this->o_current_categories->GetFinal();
		return $o_current;
	}

	# get the parent of the current category
	function GetParent()
	{
		return $this->o_current_categories->GetParent($this->o_current_categories->GetFinal());
	}

	/**
	 * Gets the category at a specific level in the current hierarchy
	 *
	 * @param int $i_level
	 * @return Category
	 */
	function GetByHierarchyLevel($i_level)
	{
		if (is_numeric($i_level))
		{
			$i_index = ((int)$i_level)-1;
			if ($i_index < $this->o_current_categories->GetCount())
			{
				$a_categories = $this->o_current_categories->GetItems();
				return $a_categories[$i_index];
			}
		}

		return false;
	}

	/**
	 * @return int
	 * @desc Gets the depth of the current category in the category hierarchy
	 */
	function GetDepth()
	{
		return $this->o_current_categories instanceof CategoryCollection ? $this->o_current_categories->GetCount() : null;
	}

	/**
	 * Gets whether the site is the development version, rather than the public one
	 *
	 * @return bool
	 */
	public static function IsDevelopment()
	{
		return ($_SERVER['SERVER_NAME'] == 'stoolball.local');
	}

	/**
	 * Gets whether the current request is being handled by WordPress
	 *
	 * @return bool
	 */
	public static function IsWordPress()
	{
		return function_exists('wp');
	}
}
?>