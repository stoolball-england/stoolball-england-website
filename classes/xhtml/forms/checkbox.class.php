<?php
require_once('xhtml/xhtml-element.class.php');

/**
 * A checkbox which can be selected independently of any other
 *
 */
class CheckBox extends XhtmlElement 
{
	var $b_checked;
	
	/**
	 * Creates a new CheckBox
	 *
	 * @param string $s_id
	 * @param string $s_label
	 * @param string $s_value
	 * @param bool $b_checked
	 * @param bool $page_valid
	 * @return CheckBox
	 */
	function __construct($s_id, $s_label, $s_value='', $b_checked=false, $page_valid=null)
	{
		# this element is the label for the checkbox
		parent::__construct('label');
		$this->AddAttribute('for', $s_id);
		$this->SetCssClass('checkBox');
		
		# create checkbox as child control
		$o_checkbox = new XhtmlElement('input');
		$o_checkbox->SetEmpty(true);
		$o_checkbox->AddAttribute('type', 'checkbox');
		$o_checkbox->SetCssClass('checkBox');
		$o_checkbox->AddAttribute('value', $s_value);
		$this->a_controls[0] = $o_checkbox;
		
		# wait to set id, because it'll set both controls at once
		$this->SetXhtmlId($s_id);
		
		# wait to add label, so that it comes after the checkbox
		$this->a_controls[1] = htmlentities($s_label, ENT_QUOTES, "UTF-8", false);

		# If we know the page has failed validation, repopulate value, otherwise use the hard-coded value
		if ($page_valid === false and isset($_POST[$o_checkbox->GetXhtmlId()]))
		{
			$this->SetChecked(isset($_POST[$o_checkbox->GetXhtmlId()]) and $_POST[$o_checkbox->GetXhtmlId()] == (string)$s_value);
		}
		else 
		{
			$this->SetChecked($b_checked);
		}
	}
	
	function AddControl($control) { return false; }
	
	/**
	* @return void
	* @param bool $b_input
	* @desc Sets whether the checkbox is checked
	*/
	function SetChecked($b_input) { $this->b_checked = (bool)$b_input; }

	/**
	* @return bool
	* @desc Gets whether the checkbox is checked
	*/
	function GetChecked() { return $this->b_checked; }
	
	/**
	* @return void
	* @param bool $b_input
	* @desc Sets whether the checkbox is enabled
	*/
	function SetEnabled($b_input) { if ($b_input) $this->a_controls[0]->RemoveAttribute('disabled'); else $this->a_controls[0]->AddAttribute('disabled', 'disabled'); }

	/**
	* @return bool
	* @desc Gets whether the checkbox is enabled
	*/
	function GetEnabled() { return ($this->GetAttribute('disabled') != 'disabled'); }

	/**
	* @access public
	* @return void
	* @param string $s_value
	* @desc Set the id and name attributes of the CheckBox together
	*/
	public function SetXhtmlId($s_value) 
	{
		$s_value = (string)$s_value; 
		
		parent::SetXhtmlId($s_value . '_label');
		
		if (is_object($this->a_controls[0]))
		{
			$this->a_controls[0]->SetXhtmlId($s_value);
			$this->a_controls[0]->AddAttribute('name', $s_value);
		}
	}
	
	public function GetXhtmlId()
	{
		if (is_object($this->a_controls[0])) return $this->a_controls[0]->GetXhtmlId();
		else return parent::GetXhtmlId(); # should never get to this
	}

	function OnPreRender()
	{
		if ($this->b_checked) $this->a_controls[0]->AddAttribute('checked', 'checked');
	}
}
?>