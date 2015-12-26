<?php
require_once('data/related-id-editor.class.php');
require_once('stoolball/season-select.class.php');

/**
 * Editor control for selecting multiple stoolball seasons
 *
 */
class SeasonIdEditor extends RelatedIdEditor
{
	/**
	 * Creates a SeasonIdEditor
	 *
	 * @param SiteSettings $settings
	 * @param DateEditControl $controlling_editor
	 * @param string $s_id
	 */
	public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id)
	{
		parent::__construct($settings, $controlling_editor, $s_id, 'Seasons', array('Season'), 'Season', true, 'GetId', 'SetId', 'SetName');
	}

	/**
	 * Creates the dropdown list to pick new items from
	 *
	 * @return XhtmlSelect
	 */
	protected function CreateSelect()
	{
		return new SeasonSelect();
	}

	/**
	 * Adds objects as options in a dropdown list
	 *
	 * @param XhtmlSelect $select
	 * @param object[] $a_objects_to_add
	 * @param int[] $a_objects_to_disable
	 * @return XhtmlSelect
	 */
	protected function AddOptions(XhtmlSelect $select, $a_objects_to_add, $a_objects_to_disable)
	{
		$select->AddSeasons($a_objects_to_add);

		# Should now have options sorted and grouped by SeasonSelect,
		# but the values will be wrong for this control.
		$a_controls = $select->GetControls();
		foreach ($a_controls as $control)
		{
			$child_controls = $control->GetControls();
			$control->AddAttribute('value', $control->GetAttribute('value') . RelatedItemEditor::VALUE_DIVIDER . $child_controls[0]);
		}

		return $select;

	}
}