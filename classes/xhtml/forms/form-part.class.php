<?php
require_once('xhtml/xhtml-element.class.php');

class FormPart extends XhtmlElement
{
	private $b_required;
	private $b_fieldset;

	/**
	* @return FormPart
	* @param XhtmlElement/string $m_label
	* @param XhtmlElement $o_control
	* @desc Create a container for a form control and its label
	*/
	function FormPart($s_label = null, $o_control = null)
	{
		parent::XhtmlElement('div');
		$this->SetCssClass('formPart');

		$this->SetLabel($s_label);
		$this->SetControl($o_control);
		$this->SetIsRequired(false);
		$this->SetIsFieldset(false);
	}

	function AddControl($o_xhtml_element) { return false; }

	/**
	* @return void
	* @param bool $b_required
	* @desc Sets whether the field is required
	*/
	function SetIsRequired($b_required)
	{
		$this->b_required = (bool)$b_required;
	}

	/**
	* @return bool
	* @desc Gets whether the field is required
	*/
	function GetIsRequired()
	{
		return $this->b_required;
	}

	/**
	* @return void
	* @param bool $b_fieldset
	* @desc Sets whether the FormPart should be rendered as a fieldset
	*/
	function SetIsFieldset($b_fieldset)
	{
		$this->b_fieldset = (bool)$b_fieldset;
	}

	/**
	* @return bool
	* @desc Gets whether the FormPart should be rendered as a fieldset
	*/
	function GetIsFieldset()
	{
		return $this->b_fieldset;
	}

	/**
	* @return bool
	* @param XhtmlElement/string $m_label
	* @desc Add a text label to associate with the form control
	*/
	function SetLabel(&$m_label)
	{
		/* @var $m_label XhtmlElement */

		if ($m_label instanceof XhtmlElement)
		{
			$m_label->SetCssClass('formLabel');
			if (count($this->a_controls) > 0 and $this->a_controls[1] instanceof XhtmlElement) $m_label->AddAttribute('for', $this->a_controls[1]->GetId());
			$this->a_controls[0] = &$m_label;
			return true;
		}
		else if (is_string($m_label))
		{
			$o_new_label = new XhtmlElement('label');
			$o_new_label->AddControl($m_label);
			return $this->SetLabel($o_new_label);
		}
		else return false;
	}

	/**
	* @return XhtmlElement
	* @desc Gets the label control associated with the form control
	*/
	function &GetLabel() { return $this->a_controls[0]; }

	/**
	* @return bool
	* @param XhtmlElement $o_control
	* @desc Set the form element to be used for data entry
	*/
	function SetControl(&$o_control)
	{
		if ($o_control instanceof XhtmlElement or $o_control instanceof Placeholder)
		{
			$b_css_method = method_exists($o_control, 'getcssclass');
			$b_attr_method = ($b_css_method or method_exists($o_control, 'GetAttribute'));

			# Some Placeholder-based controls don't have CSS class method, so wrap in a div
			if (!$b_css_method and !$b_attr_method)
			{
				$o_new_control = new XhtmlElement('div', $o_control);
				$o_control = $o_new_control;
			}

			$s_css = ($b_css_method) ? $o_control->GetCssClass() : $o_control->GetAttribute('class');
			if (strlen($s_css) > 0) $s_css .= ' ';
			if ($b_css_method) $o_control->SetCssClass($s_css . 'formControl');
			else $o_control->AddAttribute('class', $s_css . 'formControl');
			if (method_exists($this->a_controls[0], 'AddAttribute')) $this->a_controls[0]->AddAttribute('for', $o_control->GetXhtmlId());
			$this->a_controls[1] = &$o_control;
			return true;
		}
		else return false;
	}

	function &GetControl() { return $this->a_controls[1]; }

	function OnPreRender()
	{
		# Add required asterisk
		if ($this->b_required)
		{
			$o_label = $this->GetLabel();
			if (is_object($o_label))
			{
				$o_req = new XhtmlElement('span', '*');
				$o_req->SetCssClass('requiredField');
				$o_label->AddControl($o_req);
			}
		}
		
		# Change to fieldset if needed
		if ($this->b_fieldset)
		{
			$this->SetElementName('fieldset');
			$o_label = $this->GetLabel();
			if (is_object($o_label))
			{
				$o_label->SetElementName('legend');
				$o_label->RemoveAttribute('for');
			}
		}
		
	}
}
?>