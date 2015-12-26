<?php 
require_once('collection.class.php');
require_once('category/category.class.php');

class CategoryCollection extends Collection
{
	# override base constructor to limit content to Category objects
	function CategoryCollection($a_items=null)
	{
		$this->SetItemClass('Category');
		
		parent::Collection($a_items);
	}
	
	/**
	* @return Category/bool
	* @param int $i_category_id
	* @desc Gets a category with the supplied id from the collection, or returns false
	*/
	function GetById($i_category_id)
	{
		# find the relevant category in the category list
		if (is_array($this->GetItems()))
		{
			foreach($this->GetItems() as $o_category)
			{
				if ($o_category instanceof $this->s_item_class and $o_category->GetId() == $i_category_id)
				{
					$o_found_category = $o_category;
					break;
				}
			}
		}
		
		# return either the requested category or false
		return (isset($o_found_category) and $o_found_category instanceof $this->s_item_class) ? $o_found_category : false;
	}

	function GetByUrl($s_category_url)
	{
		$o_found_category = null;
		
		# find the relevant category in the category list
		if (is_array($this->GetItems()))
		{
			foreach($this->GetItems() as $o_category)
			{
				if ($o_category instanceof $this->s_item_class and $o_category->GetUrl() == $s_category_url)
				{
					$o_found_category = $o_category;
					break;
				}
			}
		}
		
		# return either the requested category or false
		return $o_found_category instanceof $this->s_item_class ? $o_found_category : false;
	}

	/**
	* @return Category
	* @param Category $o_child
	* @desc Find the parent Category of the supplied child Category
	*/
	function GetParent($o_child)
	{
		$o_found_category = null;

		# check input
		if ($o_child instanceof $this->s_item_class)
		{
			# find the relevant category in the category list
			if (is_array($this->a_items))
			{
				foreach($this->a_items as $o_category)
				{
					if ($o_category instanceof $this->s_item_class and $o_category->GetId() == $o_child->GetParentId())
					{
						$o_found_category = $o_category;
						break;
					}
				}
			}
		}
		
		# return either the requested category or false
		return $o_found_category instanceof $this->s_item_class ? $o_found_category : false;
	}

	# check whether supplied category is ancestor of other supplied category in hierarchy
	function IsAncestor($o_child_category, $o_potential_ancestor)
	{
		# check parameters
		if ($o_child_category instanceof Category and $o_potential_ancestor instanceof Category)
		{
			# assume it's not an ancestor
			$b_is_ancestor = false;
			
			# spot a direct parent
			if ($o_potential_ancestor->GetId() == $o_child_category->GetParentId()) $b_is_ancestor = true;
			
			# look for grandparent etc
			if (!$b_is_ancestor and $o_child_category->HasParent())
			{
				foreach ($this->a_items as $o_category)
				{
					if ($o_category->GetId() == $o_child_category->GetParentId())
					{
						$o_child_category = $o_category;
						break;
					}
				}
				if ($o_potential_ancestor->GetId() == $o_child_category->GetParentId()) $b_is_ancestor = true;
			}
			
			return $b_is_ancestor;
		}
		else return false;
	}

	/**
	* @return int[]
	* @param SiteContext $o_site_context
	* @param Category $o_root_category
	* @desc Gets unique ids of the given category and all its descendants
	*/
	function GetDescendantIds($o_site_context, $o_root_category=null)
	{
		$a_collected_categories = array();
		$b_collect_ids = false;
		
		if (is_array($this->a_items))
		{
			# if no root category specified, assume current category
			if ($o_root_category == null and $o_site_context instanceof SiteContext) $o_root_category = $o_site_context->GetCurrent();
			
			# check we now have a root category
			if ($o_root_category instanceof Category)
			{
				foreach ($this->a_items as $o_category)
				{
					# turn collection off again when back at root level
					if ($b_collect_ids and $o_category->GetHierarchyLevel() <= $i_root_category_level) $b_collect_ids = false;
					
					# begin collection if category id matches
					if ($o_category->GetId() == $o_root_category->GetId()) 
					{
						$b_collect_ids = true;
						$i_root_category_level = $o_category->GetHierarchyLevel();
					}
		
					# store category id
					if ($b_collect_ids) $a_collected_categories[] = $o_category->GetId();
				}
			}
		}

		return $a_collected_categories;
	}

	/**
	* @return Category[]
	* @param Category $o_parent_category
	* @param int $i_number_of_levels
	* @desc Get an array of Categories which are children of the provided category
	*/
	function GetChildren($o_parent_category, $i_number_of_levels=1)
	{
		$b_start_flag = false;
		$b_finished_flag = false;
		$b_collect_flag = false;
		$a_child_categories = array();
		
		if (is_array($this->a_items) and $o_parent_category instanceof Category)
		{
			foreach ($this->a_items as $o_category)
			{
				# finish point is another category at same level as root
				if ($b_start_flag and ($o_category->GetHierarchyLevel() <= $o_parent_category->GetHierarchyLevel()) and ($o_category->GetId() != $o_parent_category->GetId())) $b_finished_flag = true;

				# don't collect too deep
				if ($o_category->GetHierarchyLevel() > ($o_parent_category->GetHierarchyLevel() + $i_number_of_levels)) $b_collect_flag = false;
				
				# identify category to collect
				if (($o_category->GetHierarchyLevel() > $o_parent_category->GetHierarchyLevel()) and ($o_category->GetHierarchyLevel() <= ($o_parent_category->GetHierarchyLevel() + $i_number_of_levels))) $b_collect_flag = true;

				# collect category
				if ($b_start_flag and $b_collect_flag and !$b_finished_flag) $a_child_categories[] = $o_category;

				# find category to start at
				if (($o_category->GetId() == $o_parent_category->GetId())) $b_start_flag = true;
			}
		}

		return $a_child_categories;
	}
	
	/**
	* @return array
	* @param SiteContext $o_context
	* @param int $i_start_level
	* @desc Return first, previous, next and last categories in a section
	*/
	function GetRelativeInSection($o_context, $i_start_level)
	{
		/* @var $o_current_category Category */
		/* @var $o_category Category */
		/* @var $o_next_category Category */
		/* @var $o_first_category Category */
		/* @var $o_last_category Category */
		
		$a_links = array();
		
		if ($o_context instanceof SiteContext and is_numeric($i_start_level))
		{
			# get current category
			$o_current_category = $o_context->GetCurrent();
			
			# loop through all categories
			$i_total_categories = $this->GetCount();
			for($i_count = 0; $i_count < $i_total_categories; $i_count++)
			{
				# match current category
				$o_category = $this->GetByIndex($i_count);
				if ($o_category->GetId() == $o_current_category->GetId())
				{
					# get prev link, if not section home
					if ($o_category->GetHierarchyLevel() != $i_start_level)
					{
						$a_links['prev'] = $this->GetByIndex($i_count-1);
					}
	
					# get next link, if not new section
					$o_next_category = $this->GetByIndex($i_count+1);
					if ($o_next_category->GetHierarchyLevel() > ($i_start_level)) $a_links['next'] = $o_next_category;
					
					# get first link, unless this is first page
					if ($o_category->GetHierarchyLevel() >= ($i_start_level+1))
					{
						$i_first_count = $i_count;
						$o_first_category = $this->GetByIndex($i_first_count);
						while ($o_first_category->GetHierarchyLevel() >= $i_start_level)
						{
							$a_links['first'] = $o_first_category;
							if ($a_links['first']->GetId() == $o_category->GetParentId()) break;

							$i_first_count--;
							$o_first_category = $this->GetByIndex($i_first_count);
						}
					}
					
					# get last link, if this isn't last
					if ($o_next_category->GetHierarchyLevel() >= ($i_start_level+1))
					{
						$i_last_count = $i_count;
						
						# cater for section home page
						if ($o_category->GetHierarchyLevel() == $i_start_level)
						{
							$o_last_category = $this->GetByIndex($i_last_count+1);
							while ($o_last_category->GetParentId() == $o_category->GetId() or $o_last_category->GetHierarchyLevel() > ($i_start_level+1))
							{
								$a_links['last'] = $this->GetByIndex($i_last_count);
								$i_last_count++;
								$o_last_category = $this->GetByIndex($i_last_count+1);
							}
							$a_links['last'] = $this->GetByIndex($i_last_count);
						}
						else # for all other pages in section
						{
							$o_last_category = $this->GetByIndex($i_last_count);
							while ($o_last_category->GetHierarchyLevel() > $i_start_level)
							{
								$a_links['last'] = $o_last_category;
								$i_last_count++;
								$o_last_category = $this->GetByIndex($i_last_count);
							}
						}
					}
	
					break;
				}
			}
		}
		
		return $a_links;
	}
	
}
?>