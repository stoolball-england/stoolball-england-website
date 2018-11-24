<?php
require_once('xhtml/xhtml-element.class.php');

class XhtmlOption extends XhtmlElement
{
	private $group;

	public function __construct($s_text=null, $s_value=null, $selected=false)
	{
		parent::__construct('option');

		# store text
		$this->AddControl(Html::Encode((string)$s_text));

		# use text as value if no value supplied
		$this->AddAttribute('value', (is_null($s_value)) ? $s_text : $s_value);
		if ($selected) $this->AddAttribute('selected', 'selected');
	}

	/**
	 * Sets the option group name
	 * @param string $name
	 * @return void
	 */
	public function SetGroupName($name)
	{
		$this->group = (string)$name;
	}

	/**
	 * Gets the option group name
	 * @return string
	 */
	public function GetGroupName()
	{
		return $this->group;
	}
}
?>