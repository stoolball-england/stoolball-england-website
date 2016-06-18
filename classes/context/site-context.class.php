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

	protected function GetSiteContext(CategoryCollection $categories_to_search)
	{
        # Prefer the actual URL requested and only fall back to the script name, since mod_rewrite can introduce some big differences
        $this->FindCurrentCategoriesFromUrl($_SERVER['REQUEST_URI'], $categories_to_search);
        if (!$this->o_current_categories->GetCount()) {
            $this->FindCurrentCategoriesFromUrl($_SERVER['PHP_SELF'], $categories_to_search);
        }

		# get ancestors of current category
		while($o_parent = $categories_to_search->GetParent($this->o_current_categories->GetFirst()))
		{
			if ($o_parent != false)
			{
				$this->o_current_categories->Insert($o_parent);
			}
			else break;
		}
	}
    
    private function FindCurrentCategoriesFromUrl($url, CategoryCollection $categories_to_search) {

        # Strip any querystring
        $pos = strpos($url, '?');
        if ($pos) $url = substr($url, 0, $pos);

        # If there's a filename, strip it
        if (strpos($url, ".php")) $url = substr($url, 0, strpos($url, basename($url)));

        # Strip any surrounding slashes
        $url = trim($url, '/');

        # If there's more than one folder, loop from the deepest to the highest looking for one which matches a category
        $folders = explode('/', $url);
        if (is_array($folders))
        {
            for ($i = count($folders)-1; $i >=0; $i--)
            {
                $category = $categories_to_search->GetByUrl($folders[$i]);
                if ($category != false)
                {
                    $this->o_current_categories->Add($category);
                    break;
                }
            }
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