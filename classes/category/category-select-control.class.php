<?php
require_once('category/category-collection.class.php');
require_once('xhtml/forms/xhtml-select.class.php');

class CategorySelectControl extends XhtmlSelect 
{
	function __construct(CategoryCollection $o_categories, $page_valid=null)
	{
		# set properties
		parent::XhtmlSelect('category', null, $page_valid);
		$this->SetBlankFirst(true);

		# add categories
		$a_categories = $o_categories->GetItems();
		foreach($a_categories as $o_category)
		{
			$o_opt = new XhtmlOption($o_category->GetName(), $o_category->GetId());
			$o_opt->AddAttribute('style', 'padding-left: ' . (($o_category->GetHierarchyLevel()-1)*20) . 'px');
			$this->AddControl($o_opt);
			unset($o_opt);
		}
	}
}
?>