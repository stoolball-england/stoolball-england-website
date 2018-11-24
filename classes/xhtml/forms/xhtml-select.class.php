<?php
require_once('xhtml/forms/xhtml-option.class.php');

class XhtmlSelect extends Placeholder
{
	private $b_blank = false;
	private $b_page_valid = true;

	/**
	 * The dropdown list
	 *
	 * @var XhtmlElement
	 */
	private $o_select;
	private $s_label;
	private $b_hide_label = false;

	public function __construct($s_id='', $s_label='', $page_valid=null)
	{
		parent::__construct();
		$this->o_select = new XhtmlElement('select');
		$this->SetXhtmlId($s_id);
		$this->s_label = $s_label;
		$this->b_page_valid = $page_valid;
	}

	/**
	 * Gets access to the drop-down list
	 */
	public function GetDropdown()
	{
		return $this->o_select;
	}

	/**
	 * @access public
	 * @return void
	 * @param string $s_value
	 * @desc Sets the id and name attributes of the XhtmlSelect together
	 */
	function SetXhtmlId($s_value)
	{
		$s_value = (string)$s_value;
		$this->o_select->SetXhtmlId($s_value);
		$this->o_select->AddAttribute('name', $s_value);
	}

	/**
	 * @access public
	 * @return string
	 * @desc Gets the id and name attributes of the XhtmlSelect
	 */
	function GetXhtmlId()
	{
		return $this->o_select->GetXhtmlId();
	}

	/**
	 * @return bool
	 * @param bool $b_enabled
	 * @desc Sets whether the XhtmlSelect is enabled
	 */
	function SetEnabled($b_enabled)
	{
		if ($b_enabled)
		{
			$this->o_select->RemoveAttribute('disabled');
		}
		else
		{
			$this->o_select->AddAttribute('disabled', 'disabled');
		}
	}

	/**
	 * @return bool
	 * @desc Gets whether the XhtmlSelect is enabled
	 */
	function GetEnabled()
	{
		return (bool)strlen($this->o_select->GetAttribute('disabled'));
	}

	/**
	 * @return void
	 * @param string $s_text
	 * @desc A space-separated list of CSS classes to be applied
	 */
	function SetCssClass($s_text)
	{
		$this->o_select->AddAttribute('class', $s_text);
	}

	/**
	 * @return string
	 * @desc Get a space-separated list of CSS classes to be applied
	 */
	function GetCssClass()
	{
		return $this->o_select->GetAttribute('class');
	}

	/**
	 * @return void
	 * @param bool $b_input
	 * @desc Set whether to include a blank option as the first option (default false)
	 */
	function SetBlankFirst($b_input) { $this->b_blank = (bool)$b_input; }

	/**
	 * @return bool
	 * @desc Get whether a blank option is added as the first option (default false)
	 */
	function GetBlankFirst() { return $this->b_blank; }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the text label for the dropdown list
	 */
	function SetLabel($s_input) { $this->s_label = (string)$s_input; }

	/**
	 * @return string
	 * @desc Gets the text label for the dropdown list
	 */
	function GetLabel() { return $this->s_label; }

	/**
	 * Sets whether the label is for screen reader users only
	 *
	 * @param bool $b_hide
	 */
	public function SetHideLabel($b_hide)
	{
		$this->b_hide_label = (bool)$b_hide;
	}

	/**
	 * Gets whether the label is for screen reader users only
	 *
	 * @return bool
	 */
	public function GetHideLabel()
	{
		return $this->b_hide_label;
	}

	/**
	 * @return void
	 * @param string $s_value
	 * @desc Set the first XhtmlOption with a value matching the supplied parameter to be selected
	 */
	function SelectOption($s_value)
	{
		# always work with strings
		$s_value = (string)$s_value;

		$i_options = $this->CountControls();
		$found = false;
		for($i_count = 0; $i_count < $i_options; $i_count++)
		{
			if ($this->a_controls[$i_count] instanceof XhtmlOption)
			{
				if (!$found and $this->a_controls[$i_count]->GetAttribute('value') == $s_value)
				{
					$this->a_controls[$i_count]->AddAttribute('selected', 'selected');
					$found = true;
				}
				else if ($this->a_controls[$i_count]->GetAttribute('selected') == 'selected')
				{
					$this->a_controls[$i_count]->RemoveAttribute('selected');
				}
			}
		}
	}

	/**
	 * @return bool
	 * @param XhtmlOption $o_control
	 * @desc Add an XhtmlOption to this list
	 */
	function AddControl($o_control)
	{
		/* @var $o_control XhtmlOption */
		if ($this->b_page_valid === false and isset($_POST[$this->GetXhtmlId()]) and $o_control instanceof XhtmlOption and $o_control->GetAttribute('value') == $_POST[$this->GetXhtmlId()])
		{
			$o_control->AddAttribute('selected', 'selected');
		}
		parent::AddControl($o_control);
	}

	/**
	 * Adds an array of options (array keys become values, array values become display text)
	 *
	 * @param array $options
	 * @param array $exclude_keys
	 */
	public function AddOptions($options, $exclude_keys=null)
	{
		if (is_array($options))
		{
			if (!is_array($exclude_keys)) $exclude_keys = array();
			foreach ($options as $key => $value)
			{
				if (!in_array($key, $exclude_keys, true)) $this->AddControl(new XhtmlOption($value, $key));
			}
		}
	}

	/**
	 * @return void
	 * @param int $i_index
	 * @desc Set the option at the given index to be selected
	 */
	function SelectIndex($i_index)
	{
		# reset, otherwise could end up with two items selected
		$this->SelectNothing();

		$i_index = (int)$i_index;

		# if index was 0, we've cleared any existing selection so the browser will select the first entry by default anyway
		if (!$i_index) return;

		if (array_key_exists($i_index, $this->a_controls) and $this->a_controls[$i_index] instanceof XhtmlOption)
		{
			$this->a_controls[$i_index]->AddAttribute('selected', 'selected');
		}
	}

	/**
	 * Deselect any currently selected item
	 *
	 */
	public function SelectNothing()
	{
		for($i_count = 0; $i_count < count($this->a_controls); $i_count++)
		{
			if ($this->a_controls[$i_count] instanceof XhtmlOption and $this->a_controls[$i_count]->GetAttribute('selected') == 'selected')
			{
				$this->a_controls[$i_count]->RemoveAttribute('selected');
			}
		}
	}

	/**
	 * @return bool
	 * @desc Gets whether any option is selected
	 */
	function HasSelected()
	{
		$b_has_selected = false;

		for($i_count = 0; $i_count < count($this->a_controls); $i_count++)
		{
			if ($this->a_controls[$i_count] instanceof XhtmlOption and $this->a_controls[$i_count]->GetAttribute('selected') == 'selected')
			{
				$b_has_selected = true;
			}
		}

		return $b_has_selected;
	}

	/**
	 * Gets the selected value, if there is one
	 *
	 */
	public function GetSelectedValue()
	{
		for($i_count = 0; $i_count < count($this->a_controls); $i_count++)
		{
			if ($this->a_controls[$i_count] instanceof XhtmlOption and $this->a_controls[$i_count]->GetAttribute('selected') == 'selected')
			{
				return $this->a_controls[$i_count]->GetAttribute('value');
			}
		}
		return '';
	}


	protected function OnPreRender()
	{
		# Build up child option controls for the select list
		$options = array();

		# Add blank option if requested
		if ($this->b_blank)	$options[] = new XhtmlOption();

		# Loop through options, converting group names to option groups
		$current_group_name = '';
		$current_group = null;
		foreach ($this->GetControls() as $option)
		{
			/* @var $option XhtmlOption */

			# Same group as last option
			if ($option->GetGroupName() === $current_group_name)
			{
				if ($current_group_name)
				{
					# Add option to the group
					$current_group->AddControl($option);
				}
				else
				{
					# It's not in a group
					$options[] = $option;
				}
			}
			else
			{
				# Different group than last option
				if (!is_null($current_group)) $options[] = $current_group;

				# If it's another group, create the group
				if ($option->GetGroupName())
				{
					$current_group_name = $option->GetGroupName();
					$current_group = new XhtmlElement('optgroup', $option);
					$current_group->AddAttribute('label', $current_group_name);
				}
				else
				{
					# If we're now out of a group, clear the current groups
					$current_group_name = '';
					$current_group = null;
					$options[] = $option;
				}
			}
		}

		# Add the final group, if there is one
		if (!is_null($current_group)) $options[] = $current_group;

		# Move child controls into select list
		$this->o_select->SetControls($options);
		$no_controls = array();
		$this->SetControls($no_controls);

		# Add select list to this control
		if ($this->GetLabel())
		{
			if ($this->b_hide_label)
			{
				$o_label = new XhtmlElement('label');
				$o_label->SetCssClass('aural');
				$o_label->AddAttribute('for', $this->o_select->GetXhtmlId());
				$o_label->AddControl($this->GetLabel() . ' ');
				$this->AddControl($o_label);
				$this->AddControl($this->o_select);
			}
			else
			{
				$o_label = new XhtmlElement('label');
				$o_label->AddAttribute('for', $this->o_select->GetXhtmlId());
				$o_label->AddControl($this->GetLabel() . ' ');
				$o_label->AddControl($this->o_select);
				$this->AddControl($o_label);
			}
		}
		else
		{
			$this->AddControl($this->o_select);
		}

		parent::OnPreRender();
	}
}
?>