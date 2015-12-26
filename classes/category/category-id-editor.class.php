<?php
require_once('data/related-id-editor.class.php');

/**
 * Editor control for selecting multiple categories
 *
 */
class CategoryIdEditor extends RelatedIdEditor 
{
	/**
	 * Creates an CategoryIdEditor
	 *
	 * @param SiteSettings $settings
	 * @param DataEditControl $controlling_editor
	 * @param string $s_id
	 */
	public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id)
	{
		parent::__construct($settings, $controlling_editor, $s_id, 'Categories', array('Category'), 'Category', false, 'GetId', 'SetId', 'SetName');
		$this->SetDisableSelected(true);
	}
	
	/**
	 * Creates an XhtmlOption from a category
	 *
	 * @param int $id
	 * @param Category $object
	 * @return XhtmlOption
	 */
	protected function CreateOption($id, $object)
	{
		$opt = new XhtmlOption(ucfirst($object->__toString()), $id . RelatedItemEditor::VALUE_DIVIDER . ucfirst($object->__toString()));
		$opt->AddAttribute('style', 'padding-left: ' . (($object->GetHierarchyLevel()-1)*20) . 'px');
		return $opt;
	}
}
?>