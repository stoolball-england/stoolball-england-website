<?php
require_once('xhtml/placeholder.class.php');

class CategoryTreeControl extends Placeholder
{
	var $o_categories;

	public function __construct(CategoryCollection $o_categories)
	{
		$this->o_categories = $o_categories;
	}

	function OnPreRender()
	{
		/* @var $o_category Category */

		if ($this->o_categories->GetCount())
		{
			$a_categories = $this->o_categories->GetItems();

			$current_level = 1;
			$active_lists = array();
			$active_lists[$current_level] = new XhtmlElement('ul');
			$current_list = $active_lists[$current_level];
			$current_item = null;
			$parent_list = null;

			foreach ($a_categories as $category)
			{
				if ($category->GetHierarchyLevel() == $current_level)
				{
					$current_item = $this->CreateItem($category);
					$current_list->AddControl($current_item);
				}
				else if ($category->GetHierarchyLevel() > $current_level)
				{
					$current_level = $category->GetHierarchyLevel();

					$parent_list = $current_list;
					$current_list = new XhtmlElement('ul');
					$current_item->AddControl($current_list);
					$active_lists[$current_level] = $current_list;
					
					$current_item = $this->CreateItem($category);
					$current_list->AddControl($current_item);
				}
				else if ($category->GetHierarchyLevel() < $current_level)
				{
					$current_level = $category->GetHierarchyLevel();

					$current_list = $active_lists[$current_level];

					$current_item = $this->CreateItem($category);
					$current_list->AddControl($current_item);
				}
			}

			$this->AddControl($active_lists[1]);
		}
	}

	private function CreateItem(Category $category)
	{
		$o_link = new XhtmlElement('a', Html::Encode($category->GetName()));
		$o_link->AddAttribute('href', 'categoryedit.php?item=' . $category->GetId());
		return new XhtmlElement('li', $o_link);
	}
}
?>