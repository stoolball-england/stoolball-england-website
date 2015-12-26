<?php
require_once('data/data-manager.class.php');
require_once('category/category.class.php');

class CategoryManager extends DataManager
{
	/**
	* @return CategoryManager
	* @param SiteSettings $o_settings
	* @param MySqlConnection $o_db
	* @desc Read and write Categories
	*/
	public function __construct(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'Category';
	}

	/**
	* @access public
	* @return void
	* @param int[] $a_ids
	* @desc Read from the db the Categories matching the supplied ids, or all Categories
	*/
	function ReadById($a_ids=null)
	{
		# build query
		$s_category = $this->o_settings->GetTable('Category');

		$s_sql = 'SELECT id, name, short_intro, parent, code, sort_override, navigate_url, hierarchy_level ' .
		'FROM ' . $s_category . ' ';

		# limit to specific category, if specified
		$where = '';
		if (is_array($a_ids)) $where = $this->SqlAddCondition($where, 'id IN (' . join(', ', $a_ids) . ')');
		$s_sql = $this->SqlAddWhereClause($s_sql, $where);

		# sort
		$s_sql .= 'ORDER BY hierarchy_sort ASC, sort_override ASC, name ASC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Category objects
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		while($o_row = $o_result->fetch())
		{
			# create the new category
			$o_category = new Category();
			$o_category->SetId($o_row->id);
			$o_category->SetName($o_row->name);
			$o_category->SetDescription($o_row->short_intro);
			$o_category->SetParentId((int)$o_row->parent);
			$o_category->SetUrl($o_row->code);
		    $o_category->SetSortOverride($o_row->sort_override);
			$o_category->SetNavigateUrl($o_row->navigate_url);
			$o_category->SetHierarchyLevel($o_row->hierarchy_level);
			$this->Add($o_category);
		}
	}


	/**
	* @return int
	* @param Category $o_category
	* @desc Save the supplied Category to the database, and return the id
	*/
	function Save($o_category)
	{
		# check parameters
		if (!$o_category instanceof Category) die('Unable to save category');

		# build query

		# if no id, it's a new category; otherwise update the category
		if ($o_category->GetId())
		{
			$s_sql = 'UPDATE ' . $this->GetSettings()->GetTable('Category') . ' SET ' .
			'parent = ' . Sql::ProtectNumeric($o_category->GetParentId(), true) . ', ' .
			"name = " . Sql::ProtectString($this->GetDataConnection(), $o_category->GetName()) . ", " .
			"short_intro = " . Sql::ProtectString($this->GetDataConnection(), $o_category->GetDescription()) . ", " .
			"code = " . Sql::ProtectString($this->GetDataConnection(), $o_category->GetUrl()) . ", " .
			'sort_override = ' . Sql::ProtectNumeric($o_category->GetSortOverride()) . ', ' .
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE id = ' . Sql::ProtectNumeric($o_category->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);
		}
		else
		{
			$s_sql = 'INSERT INTO ' . $this->GetSettings()->GetTable('Category') . ' SET ' .
			'parent = ' . Sql::ProtectNumeric($o_category->GetParentId(), true) . ', ' .
			"name = " . Sql::ProtectString($this->GetDataConnection(), $o_category->GetName()) . ", " .
			"short_intro = " . Sql::ProtectString($this->GetDataConnection(), $o_category->GetDescription()) . ", " .
			"code = " . Sql::ProtectString($this->GetDataConnection(), $o_category->GetUrl()) . ", " .
            'sort_override = ' . Sql::ProtectNumeric($o_category->GetSortOverride()) . ', ' .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U');

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$o_category->SetId($this->GetDataConnection()->insertID());
		}

		return $o_category->GetId();

	}

	/**
	 * Updates the derived hierarchy data stored in the database
	 *
	 * @param Object which can generate the category URLs $url_manager
	 * @param Name of method to generate the category URLs $url_method
	 */
	public function UpdateHierarchyData($url_manager, $url_method)
	{
		# Fresh start
		$this->Clear();
		$categories = array();
		$o_sorted_categories = new CategoryCollection();
		$category_table = $this->GetSettings()->GetTable('Category');

		# First, get all the categories from the db
		$s_sql = "SELECT id, parent, code FROM $category_table ORDER BY sort_override, name";

		$result = $this->GetDataConnection()->query($s_sql);
		while ($o_row = $result->fetch())
		{
			$o_category = new Category();
			$o_category->SetId($o_row->id);
			$o_category->SetParentId($o_row->parent);
			$o_category->SetUrl($o_row->code);

			$categories[] = $o_category;
		}
		$result->closeCursor();

		# Sort the categories, generating hierarchy data including a URL
		$a_stack = array();
		$this->GatherChildCategories($a_stack, $categories, $o_sorted_categories, $url_manager, $url_method);

		# Now write that hierarchy data back to the db
		$i = 0;
		foreach ($o_sorted_categories as $category)
		{
			/* @var $category Category */
			$s_sql = "UPDATE $category_table SET " .
			"navigate_url = " . Sql::ProtectString($this->GetDataConnection(), $category->GetNavigateUrl(), false) . ", " .
			"hierarchy_level = " . Sql::ProtectNumeric($category->GetHierarchyLevel()) . ", " .
			"hierarchy_sort = $i " .
			"WHERE id = " . Sql::ProtectNumeric($category->GetId());
			$i++;

			$this->GetDataConnection()->query($s_sql);
		}
	}


	/**
	* @return void
	* @param Category[] $a_stack
	* @param Category[] $a_categories
	* @param CategoryCollection $o_sorted_categories
	 * @param Object which can generate the category URLs $url_manager
	 * @param Name of method to generate the category URLs $url_method
	* @desc Recursively look for and gather categories in hierarchical order
	*/
	private function GatherChildCategories(&$a_stack, &$a_categories, &$o_sorted_categories, $url_manager, $url_method)
	{
		$i_total_categories = count($a_categories);
		for ($i = 0; $i < $i_total_categories; $i++)
		{
			array_push($a_stack, $a_categories[$i]);

			$o_child = &$this->IsChildCategory($a_stack, $url_manager, $url_method);
			if (is_object($o_child))
			{
				$o_sorted_categories->Add($o_child);
				$this->GatherChildCategories($a_stack, $a_categories, $o_sorted_categories, $url_manager, $url_method);
			}

			array_pop($a_stack);
		}
	}

	/**
	* @return Category
	* @param Category[] $a_stack
	 * @param Object which can generate the category URLs $url_manager
	 * @param Name of method to generate the category URLs $url_method
	* @desc Examine category on stack to determine whether it is a child of the current parent
	*/
	private function &IsChildCategory(&$a_stack, $url_manager, $url_method)
	{
		$i_depth = count($a_stack);
		$o_category = $a_stack[$i_depth-1];
		$o_parent = ($i_depth > 1) ? $a_stack[$i_depth-2] : null;

		if (is_object($o_category) and (($i_depth == 1 and !$o_category->HasParent()) or ($i_depth > 1 and $o_category->HasParent() and $o_category->GetParentId() == $o_parent->GetId())))
		{
			# update category based on context
			if (is_object($url_manager) and method_exists($url_manager, $url_method))
			{
				$o_category->SetNavigateUrl($url_manager->$url_method($a_stack, $i_depth));
			}
			$o_category->SetHierarchyLevel($i_depth);

			return $o_category;
		}
		else
		{
			$o_category = null;
			return $o_category;
		}
	}

	/**
	* @access public
	* @return void
	* @param int[] $a_ids
	* @desc Delete from the db the Categories matching the supplied ids. Categorised content will remain.
	*/
	function Delete($a_ids)
	{
		# check parameter
		if (!is_array($a_ids)) throw new Exception('No Categories to delete');

		# get category IDs
		$category = $this->GetSettings()->GetTable('Category');
		$s_ids = join(', ', $a_ids);

		# Re-assign forum topics to another category
		$topics = $this->GetSettings()->GetTable('ForumTopic');
		$sql = "UPDATE $topics SET category_id = (SELECT MIN(id) FROM $category WHERE id NOT IN ($s_ids)) WHERE category_id IN ($s_ids)";
		$this->GetDataConnection()->query($sql);
		
		# Remove competitions from category
		$competitions = $this->GetSettings()->GetTable('Competition');
		$sql = "UPDATE $competitions SET category_id = NULL WHERE category_id IN ($s_ids)";
		$this->GetDataConnection()->query($sql);
		
		# Remove galleries from category
		$galleries = $this->GetSettings()->GetTable('MediaGalleryLink');
		$sql = "DELETE FROM $galleries WHERE item_id IN ($s_ids) AND item_type = " . ContentType::CATEGORY;
		$this->GetDataConnection()->query($sql);
		
		# delete category(s)
		$sql = 'DELETE FROM ' . $category . ' WHERE id IN (' . $s_ids . ') ';
		$o_result = $this->GetDataConnection()->query($sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}
}
?>