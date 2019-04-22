<?php
require_once('xhtml/forms/xhtml-form.class.php');

/**
 * A container for repeated instances of the same DataEditControl type - when instantiating, supply details of a factory method which returns a DataEditControl.
 *
 */
class DataEditRepeater extends XhtmlForm
{
	private $o_factory_object;
	private $s_factory_method;
	private $a_posted_data_objects;
	private $b_show_validation_errors_override = true;
	private $a_section_headings = array();
	private $a_section_buttons = array();
	private $i_max_section = 0;
	private $b_show_sections = true;
	private $a_edit_controls = array();
	private $a_controls_to_render = array(); 

	/**
	 * Creates a container for repeated instances of the same DataEditControl type.
	 *
	 * @param object $o_factory_object
	 * @param string $s_factory_method
	 * @param string $csrf_token
	 */
	public function __construct($o_factory_object, $s_factory_method, $csrf_token)
	{
		parent::__construct($csrf_token);

		if (!is_object($o_factory_object) or !method_exists($o_factory_object, $s_factory_method)) throw new Exception('Could not find factory method for edit control');

		$this->o_factory_object = $o_factory_object;
		$this->s_factory_method = $s_factory_method;
		parent::SetShowValidationErrors(false); # Set to false to prevent base validtion summary from showing

		$this->SetStreamContent(true);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') $this->BuildPostedDataObjects();
	}

	/**
	 * Build the modified data objects from the posted data and store in an internal array
	 *
	 */
	private function BuildPostedDataObjects()
	{
		$a_object_ids = isset($_POST['items']) ? explode(';', $_POST['items']) : array();
		$this->a_posted_data_objects = array();
		$s_factory_method = $this->s_factory_method;

		foreach ($a_object_ids as $s_id)
		{
			$o_control = $this->o_factory_object->$s_factory_method();
			$o_control->SetNamingPrefix($o_control->GetDataObjectClass() . $s_id);
			$this->a_posted_data_objects[] = $o_control->GetDataObject();
		}
	}

	/**
	 * Adds an object to be edited
	 *
	 * @param object $o_object
	 * @param int $i_section
	 */
	public function AddDataObject($o_object, $i_section=1)
	{
		if (is_object($o_object))
		{
			if ($i_section > $this->i_max_section)
			{
				if ($this->i_max_section)
				{
					# Add button to end of previous section
					$this->a_section_buttons[$this->i_max_section] = $this->CreateDefaultButtons();
					$this->a_controls_to_render[] = $this->a_section_buttons[$this->i_max_section];
				}

				$this->i_max_section = $i_section;
				$this->a_section_headings[$i_section] = new XhtmlElement('h2', 'Section ' . $i_section);
				$this->a_controls_to_render[] = $this->a_section_headings[$i_section];
			}

			$s_factory_method = $this->s_factory_method;
			$o_edit = $this->o_factory_object->$s_factory_method();
			$o_edit->SetNamingPrefix($o_edit->GetDataObjectClass() . $o_object->GetId()); # Added Feb 08 to try to resolve validation bug
			$o_edit->SetDataObject($o_object);
			$this->AddControl($o_edit);

			# Cache edit control in another array so that we can access its properties later
			$this->a_edit_controls[] = $o_edit;
		}
	}

	/**
	 * Gets the data objects being edited by the aggregated edit controls
	 *
	 * @return object[]
	 */
	public function GetDataObjects()
	{
		/* @var $o_control DataEditControl */
		if (is_array($this->a_posted_data_objects)) return $this->a_posted_data_objects;

		$a_objects = array();
		foreach ($this->a_controls_to_render as $o_control)
		{
			if ($o_control instanceof DataEditControl)
			{
				$a_objects[] = $o_control->GetDataObject();
			}
		}
		return $a_objects;
	}

	/**
	 * Adds a data edit control to the aggregated edit controls
	 *
	 * @param DataEditControl $o_control
	 */
	public function AddControl($o_control)
	{
		if (!$o_control instanceof DataEditControl) return;
		$o_control->SetNamingPrefix($o_control->GetDataObjectClass() . $o_control->GetDataObject()->GetId());
		$o_control->SetShowValidationErrors(false);
		$this->a_controls_to_render[] = $o_control;
	}

	private $s_button_text = 'Save';

	/**
	 * Sets the text of the main submit button
	 *
	 * @param string $s_button_text
	 * @return void
	 */
	public function SetButtonText($s_button_text)
	{
		$this->s_button_text = (string)$s_button_text;
	}

	/**
	 * Gets the text of the main submit button
	 *
	 * @return string
	 */
	public function GetButtonText()
	{
		return $this->s_button_text;
	}


	/**
	 * show buttons at top of form as well as the bottom
	 *
	 * @var bool
	 */
	private $b_show_buttons_at_top;

	/**
	 * Sets the show buttons at top of form as well as the bottom
	 *
	 * @param bool $b_show_buttons_at_top
	 * @return void
	 */
	public function SetShowButtonsAtTop($b_show_buttons_at_top)
	{
		$this->b_show_buttons_at_top = (bool)$b_show_buttons_at_top;
	}

	/**
	 * Gets the show buttons at top of form as well as the bottom
	 *
	 * @return bool
	 */
	public function GetShowButtonsAtTop()
	{
		return $this->b_show_buttons_at_top;
	}

	/**
	 * Sets whether to show a summary of validation errors
	 *
	 * @param bool $b_show
	 */
	public function SetShowValidationErrors($b_show)
	{
		$this->b_show_validation_errors_override = (bool)$b_show;
	}

	/**
	 * Gets whether to show a summary of validation errors
	 *
	 * @return bool
	 */
	public function GetShowValidationErrors()
	{
		return $this->b_show_validation_errors_override;
	}

	/**
	 * Sets whether to show the section headings
	 *
	 * @param bool $b_show
	 */
	public function SetShowSections($b_show)
	{
		$this->b_show_sections = (bool)$b_show;
	}

	/**
	 * Gets whether to show the section headings
	 *
	 * @return unknown
	 */
	public function GetShowSections()
	{
		return $this->b_show_sections;
	}

	/**
	 * semi-colon-separated list of parameters preserved from the querystring using hidden fields
	 *
	 * @var string[]
	 */
	private $a_persisted_params = array();

	/**
	 * Sets the parameters preserved from the querystring using hidden fields
	 *
	 * @param string[] $a_persisted_params
	 * @return void
	 */
	public function SetPersistedParameters($a_persisted_params)
	{
		if (is_array($a_persisted_params)) $this->a_persisted_params = $a_persisted_params;
	}

	/**
	 * Gets the parameters preserved from the querystring using hidden fields
	 *
	 * @return string[]
	 */
	public function GetPersistedParameters()
	{
		return $this->a_persisted_params;
	}


	/**
	 * Creates the default action buttons for the repeated group of controls
	 * @return XhtmlElement
	 */
	private function CreateDefaultButtons()
	{
		$o_buttons = new XhtmlElement('div');
		$o_buttons->SetCssClass('buttonGroup');
		$o_button = new XhtmlElement('input');
		$o_button->SetEmpty(true);
		$o_button->AddAttribute('type', 'submit');
		$o_button->AddAttribute('value', $this->GetButtonText());
		$o_buttons->AddControl($o_button);
		return $o_buttons;
	}

	public function CreateValidators()
	{
		foreach ($this->a_controls_to_render as $o_control)
		{
			if (method_exists($o_control, 'GetValidators'))
			{
				$a_validators = $o_control->GetValidators();
				foreach ($a_validators as $o_validator) $this->a_validators[] = $o_validator;
			}
		}
	}

	/**
	 * Sets the text of a section heading
	 *
	 * @param int $i_section
	 * @param string $s_heading
	 */
	public function SetSectionHeading($i_section, $s_heading)
	{
		if (isset($this->a_section_headings[$i_section]))
		{
			$controls = array($s_heading);
			$this->a_section_headings[$i_section]->SetControls($controls);
		}
	}

	protected function OnPreRender()
	{
		require_once('xhtml/forms/textbox.class.php');

		parent::AddControl('<div>'); # div inside form required by XHTML

		# persist params as requested
		foreach ($this->a_persisted_params as $s_param)
		{
			$s_param = trim($s_param);
			$a_fields = ($this->IsPostback()) ? $_POST : $_GET;
			if (isset($a_fields[$s_param]))
			{
				$o_param_box = new TextBox($s_param);
				$o_param_box->SetText($a_fields[$s_param]);
				$o_param_box->SetMode(TextBoxMode::Hidden());
				parent::AddControl($o_param_box);
				unset($o_param_box);
			}
		}

		# add default buttons
		if ($this->b_show_buttons_at_top)
		{
			parent::AddControl($this->CreateDefaultButtons());
		}

		# display validator errors
		if (!$this->IsValid() and $this->GetShowValidationErrors())
		{
			require_once('data/validation/validation-summary.class.php');
			$a_controls = array($this);
			parent::AddControl(new ValidationSummary($a_controls));
		}

		# Adjust headings
		if ($this->b_show_sections)
		{
			foreach($this->a_edit_controls as $o_control) $o_control->SetIsInSection(true);
		}
		else
		{
			foreach($this->a_section_headings as $o_heading)
			{
				$i_key = array_search($o_heading, $this->a_controls_to_render, true);
				if (!($i_key === false))
				{
					if (array_key_exists($i_key, $this->a_controls_to_render))
					{
						unset($this->a_controls_to_render[$i_key]);
					}
				}
			}
		}

		# Add the edit controls themselves, one at a time so that they're streamed
		foreach ($this->a_controls_to_render as $control) parent::AddControl($control);

		# add ids
		$a_items = $this->GetDataObjects();
		$s_item_ids = '';
		foreach ($a_items as $o_data_object)
		{
			if (is_object($o_data_object) and method_exists($o_data_object, 'GetId'))
			{
				if ($s_item_ids) $s_item_ids .= ';';
				$s_item_ids .= $o_data_object->GetId();
			}
		}
		if ($s_item_ids)
		{
			$o_id_box = new TextBox('items', $s_item_ids);
			$o_id_box->SetMode(TextBoxMode::Hidden());
			parent::AddControl($o_id_box);
		}

		# add default buttons
		parent::AddControl($this->CreateDefaultButtons());

		parent::AddControl('</div>'); # div inside form required by XHTML
	}

}
?>