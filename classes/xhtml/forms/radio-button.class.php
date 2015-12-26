<?php
require_once('xhtml/xhtml-element.class.php');

class RadioButton extends XhtmlElement
{
	var $b_checked;
	var $b_name_set = false;

	/**
	 * Creates a new RadioButton
	 *
	 * @param string $s_id
	 * @param string $s_group_name
	 * @param string $s_label
	 * @param string $s_value
	 * @param bool $b_checked
	 * @param bool $page_valid
	 * @return RadioButton
	 */
	function RadioButton($s_id, $s_group_name, $s_label, $s_value='', $b_checked=false, $page_valid=null)
	{
		# this element is the label for the radio button
		parent::XhtmlElement('label');
		$this->AddAttribute('for', $s_id);
		$this->SetCssClass('radioButton');

		# create radio button as child control
		$o_radio = new XhtmlElement('input');
		$o_radio->SetEmpty(true);
		$o_radio->AddAttribute('type', 'radio');
		$o_radio->AddAttribute('value', $s_value);
		$this->a_controls[0] = &$o_radio;
		$this->SetGroupName($s_group_name);

		# wait to set id, because it'll set both controls at once
		$this->SetXhtmlId($s_id);

		# wait to set label so that it comes after radio button
		$this->a_controls[1] = htmlentities($s_label, ENT_QUOTES, "UTF-8", false);

		if ($page_valid === false)
		{
			$this->b_checked = (isset($_POST[$s_group_name]) and $_POST[$s_group_name] == $s_value);
		}
		else
		{
			$this->b_checked = (bool)$b_checked;
		}
	}

	function AddControl($control) { return false; }

	/**
	* @return void
	* @param bool $b_input
	* @desc Sets whether the radio button is selected
	*/
	function SetChecked($b_input) { $this->b_checked = (bool)$b_input; }

	/**
	* @return bool
	* @desc Gets whether the radio button is selected
	*/
	function GetChecked() { return $this->b_checked; }

	/**
	* @access public
	* @return void
	* @param string $s_name
	* @desc Sets the name attribute of the radio button
	*/
	function SetGroupName($s_name)
	{
		$this->a_controls[0]->AddAttribute('name', $s_name);
		$this->b_name_set = true;
	}

	/**
	* @access public
	* @return string
	* @desc Gets the name attribute of the radio button
	*/
	function GetGroupName()
	{
		return $this->a_controls[0]->GetAttribute('name');
	}

	/**
	* @access public
	* @return void
	* @param string $s_value
	* @desc Set the id and name attributes of the radio button together
	*/
	function SetXhtmlId($s_value)
	{
		$s_value = (string)$s_value;

		parent::SetXhtmlId($s_value . '_label');

		if (is_object($this->a_controls[0]))
		{
			$this->a_controls[0]->SetXhtmlId($s_value);
			if (!$this->b_name_set) $this->a_controls[0]->AddAttribute('name', $s_value);
		}
	}

	function OnPreRender()
	{
		if ($this->b_checked) $this->a_controls[0]->AddAttribute('checked', 'checked');
	}
}
?>