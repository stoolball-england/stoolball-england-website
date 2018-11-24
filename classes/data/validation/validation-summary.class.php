<?php
require_once('xhtml/xhtml-element.class.php');

class ValidationSummary extends XhtmlElement
{
	var $a_controls_to_validate;
	var $s_header_text;

	function __construct($a_controls_to_validate, $s_header_text='')
	{
		if (!is_array($a_controls_to_validate)) die('No controls to validate');

		parent::__construct('div');
		$this->SetCssClass('validationSummary');
		$this->a_controls_to_validate = &$a_controls_to_validate;
		$this->s_header_text = trim($s_header_text);
	}

	function OnPreRender()
	{
		/* @var $o_validator DataValidator */

		$a_validators = array();
		$a_controls_to_validate = &$this->a_controls_to_validate;
		foreach ($a_controls_to_validate as $o_control)
		{
			$a_validators = array_merge($a_validators, $o_control->GetValidators());
		}

		if (count($a_validators))
		{
			$o_list = new XhtmlElement('ul');

			foreach ($a_validators as $o_validator)
			{
				if (!$o_validator->IsValid())
				{
					$o_item = new XhtmlElement('li', $o_validator->GetMessage());
					$o_list->AddControl($o_item);
				}
			}

			if ($o_list->CountControls())
			{
				if ($this->s_header_text) $this->AddControl(new XhtmlElement('span', $this->s_header_text));
				$this->AddControl($o_list);
				$this->SetVisible(true);
			}
			else
			{
				$this->SetVisible(false);
			}

		}
	}
}
?>