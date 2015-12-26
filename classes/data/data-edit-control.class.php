<?php
require_once('xhtml/forms/xhtml-form.class.php');

abstract class DataEditControl extends XhtmlForm
{
	private $b_built_posted_data_object = false;
	private $b_cancel;
	private $show_buttons_at_top = false;

	/**
	 * Whether to render this control in full, or just its child controls
	 *
	 * @var bool
	 */
	private $b_render_base_element;

	/**
	 * @return DataEditControl
	 * @param SiteSettings $o_settings
	 * @param bool $b_entire_form
	 * @desc Abstract data-editing form
	 */
	public function __construct(SiteSettings $o_settings, $b_entire_form=true)
	{
		parent::XhtmlForm();
		$this->o_settings = $o_settings;
		$this->SetCssClass(str_ireplace('control', '', get_class($this)));
		$this->b_render_base_element = $b_entire_form;
		$this->SetNamingPrefix(''); # Gets internal button info

		# Remember previous page number
		if (isset($_POST[$this->GetNamingPrefix() . 'Page'])) $this->SetCurrentPage($_POST[$this->GetNamingPrefix() . 'Page']);
	}

	private $o_data_object;

	/**
	 * Configurable settings for the current site
	 *
	 * @var SiteSettings
	 */
	private $o_settings;

	/**
	 * Gets the configurable settings for the current site
	 *
	 * @return SiteSettings
	 */
	protected function GetSettings()
	{
		return $this->o_settings;
	}

	/**
	 * class name of the object this control edits
	 *
	 * @var string
	 */
	private $s_data_object_class;

	/**
	 * Sets the class name of the object this control edits
	 *
	 * @param string $s_data_object_class
	 * @return void
	 */
	protected function SetDataObjectClass($s_data_object_class)
	{
		$this->s_data_object_class = (string)$s_data_object_class;
	}

	/**
	 * Gets the class name of the object this control edits
	 *
	 * @return string
	 */
	public function GetDataObjectClass()
	{
		return $this->s_data_object_class;
	}

	/**
	 * text of the main submit button
	 *
	 * @var string
	 */
	private $s_button_text = 'Save';

	/**
	 * Sets the text of the main submit button
	 *
	 * @param string $s_button_text
	 * @return void
	 */
	protected function SetButtonText($s_button_text)
	{
		$this->s_button_text = (string)$s_button_text;
	}

	/**
	 * Gets the text of the main submit button
	 *
	 * @return string
	 */
	protected function GetButtonText()
	{
		return $this->s_button_text;
	}


	/**
	 * prefix used to make child controls' ids unqiue
	 *
	 * @var string
	 */
	private $s_naming_prefix;

	/**
	 * Sets the prefix used to make child controls' ids unqiue
	 *
	 * @param string $s_naming_prefix
	 * @return void
	 */
	public function SetNamingPrefix($s_naming_prefix)
	{
		$this->s_naming_prefix = (string)$s_naming_prefix;

		if ($this->IsPostback() and isset($_POST[$this->GetNamingPrefix() . 'InternalButtons']))
		{
			$this->a_internal_buttons = explode('; ', $_POST[$this->GetNamingPrefix() . 'InternalButtons']);
		}
	}

	/**
	 * Gets the prefix used to make child controls' ids unqiue
	 *
	 * @return string
	 */
	public function GetNamingPrefix()
	{
		return $this->s_naming_prefix;
	}


	/**
	 * @return void
	 * @param object $o_object
	 * @desc Gets the data object this control is editing
	 */
	public function SetDataObject($o_object)
	{
		if (!isset($this->s_data_object_class)) die('Data object class not set in edit control');
		if ($o_object instanceof $this->s_data_object_class) $this->o_data_object = $o_object;
	}

	/**
	 * @return object
	 * @desc Gets the data object this control is editing
	 */
	public function GetDataObject()
	{
		$this->EnsurePostedDataObject();
		return $this->o_data_object;
	}

	/**
	 * Ensure that the posted data object has been reconstructed
	 *
	 */
	protected function EnsurePostedDataObject()
	{
		if (!$this->b_built_posted_data_object and $this->IsPostback())
		{
			$this->b_built_posted_data_object = true;
			$this->BuildPostedDataObject();
		}
	}

	/**
	 * Gets the unique identifier for the data object being edited
	 *
	 * @return string
	 */
	public function GetDataObjectId()
	{
		if (is_object($this->o_data_object) and method_exists($this->o_data_object, 'GetId') and $this->o_data_object->GetId())
		{
			return (string)$this->o_data_object->GetId();
		}
		else if ($this->IsPostback() and isset($_POST[$this->s_naming_prefix . 'item']))
		{
			return $_POST[$this->s_naming_prefix . 'item'];
		}
		else return null;
	}

	private $a_internal_buttons = array();

	/**
	 * Allows a child control to register a button as one which alters the state of the control, rather than initiating a save
	 *
	 * @param string $s_id
	 */
	public function RegisterInternalButton($s_id)
	{
		if (strlen($s_id)) $this->a_internal_buttons[] = (string)$s_id;
	}

	/**
	 * Allows a child control to remove a button from the list of those which alter the state of the control, rather than initiating a save
	 *
	 * @param string $s_id
	 */
	public function RemoveInternalButon($s_id)
	{
		if (strlen($s_id))
		{
			$key = array_search($s_id, $this->a_internal_buttons, true);
			if (!($key === false))
			{
				unset($this->a_internal_buttons[$key]);
			}
		}
	}

	/**
	 * Gets whether the current request is a postback initiated by a button registered as internal
	 *
	 */
	public function IsInternalPostback()
	{
		if (!$this->IsPostback()) return false;

		foreach($this->a_internal_buttons as $s_id)
		{
			if (isset($_POST[$s_id])) return true;
		}

		return false;
	}

	private $current_page = 1;

	/**
	 * Sets the identifier of the current page in a wizard
	 * @param int $page_id
	 * @return void
	 */
	public function SetCurrentPage($page_id)
	{
		$this->current_page = (int)$page_id;
	}

	/**
	 * Gets the identified of the current page in a wizard
	 * @return int
	 */
	public function GetCurrentPage() { return $this->current_page; }

	/**
	 * Sets whether to show buttons at the top of the form as well as the bottom
	 * @param bool $b_show
	 * @return void
	 */
	public function SetShowButtonsAtTop($b_show) { $this->show_buttons_at_top = (bool)$b_show; }

	/**
	 * Gets whether to show buttons at the top of the form as well as the bottom
	 * @return bool
	 */
	public function GetShowButtonsAtTop() { return $this->show_buttons_at_top; }

	/**
	 * Sets whether to show a cancel button
	 *
	 * @param bool $allow
	 */
	public function SetAllowCancel($allow)
	{
		$this->b_cancel = (bool)$allow;
	}

	/**
	 * Gets whether to show a cancel button
	 *
	 * @return bool
	 */
	public function GetAllowCancel()
	{
		return $this->b_cancel;
	}

	/**
	 * Gets whether the cancel button was clicked
	 *
	 * @return bool
	 */
	public function CancelClicked()
	{
		return isset($_POST[$this->GetNamingPrefix() . 'DataEditCancel']) or isset($_POST[$this->GetNamingPrefix() . 'DataEditCancel2']);
	}

	/**
	 * @return bool
	 * @desc Test whether all registered DataValidators are valid and postback is a submit of the full control
	 */
	public function IsValidSubmit()
	{
		// Use when the desired behaviour is to assume a GET request is automatically valid, and on postback
		// an invalid or internal request shouldn't save, but keep typed values in controls rather than
		// repopulating with saved data.
		return (parent::IsValid() and !$this->IsInternalPostback());
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	protected abstract function BuildPostedDataObject();

	/**
	 * Create and populate the controls used to edit the data object
	 *
	 */
	protected abstract function CreateControls();

	/**
	 * Creates standard controls used by most data edit forms
	 *
	 */
	protected function OnPreRender()
	{
		require_once('xhtml/forms/textbox.class.php');
		require_once('xhtml/forms/checkbox.class.php');
		require_once('xhtml/forms/xhtml-select.class.php');

		# add id
		if (is_object($this->o_data_object) and method_exists($this->o_data_object, 'GetId') and $this->o_data_object->GetId())
		{
			$o_id_box = new TextBox($this->s_naming_prefix . 'item', (string)$this->o_data_object->GetId());
			$o_id_box->SetMode(TextBoxMode::Hidden());
			$this->AddControl($o_id_box);
		}

		# Add save button if building the form
		# Add at top of form to trap enter key
		if ($this->b_render_base_element)
		{
			$o_buttons = new XhtmlElement('div');
			$o_buttons->AddCssClass($this->show_buttons_at_top ? 'buttonGroup buttonGroupTop' : 'buttonGroup aural');
			$o_save = new XhtmlElement('input');
			$o_save->SetEmpty(true);
			$o_save->AddAttribute('type', 'submit');
			$o_save->SetXhtmlId($this->GetNamingPrefix() . 'DataEditSave');
			$o_save->AddAttribute('name', $o_save->GetXhtmlId());
			$o_buttons->AddControl($o_save);

			if ($this->show_buttons_at_top and $this->GetAllowCancel())
			{
				require_once('xhtml/forms/button.class.php');
				$o_buttons->AddControl(new Button($this->GetNamingPrefix() . 'DataEditCancel', 'Cancel'));
			}

			$this->AddControl($o_buttons);
		}

		# fire event for child class to create its controls
		$this->CreateControls();

		# Add a second save button
		if ($this->b_render_base_element)
		{
			$o_save->AddAttribute('value', $this->GetButtonText()); # Allow button text to be set late

			$o_save2 = clone $o_save;
			$o_save2->SetXhtmlId($this->GetNamingPrefix() . 'DataEditSave2');
			$o_save2->AddAttribute('name', $o_save2->GetXhtmlId());
            $o_save2->AddCssClass('primary');

			$o_buttons2 = new XhtmlElement('div');
			$o_buttons2->SetCssClass('buttonGroup');
			$o_buttons2->AddControl($o_save2);

			if ($this->GetAllowCancel())
			{
				if (!$this->show_buttons_at_top) require_once('xhtml/forms/button.class.php');
				$o_buttons2->AddControl(new Button($this->GetNamingPrefix() . 'DataEditCancel2', 'Cancel'));
			}

			$this->AddControl($o_buttons2);
		}

		# Add page number if it's not the default
		if ($this->current_page != 1)
		{
			$page = new TextBox($this->GetNamingPrefix() . 'Page', $this->GetCurrentPage(), $this->IsValidSubmit());
			$page->SetMode(TextBoxMode::Hidden());
			$this->AddControl($page);
		}


		# to be valid, should all be in a div
		$o_container = new XhtmlElement('div');
		$o_container->SetControls($this->GetControls());
		if (!$this->b_render_base_element)
		{
			$o_container->SetCssClass($this->GetCssClass());
			$o_container->SetXhtmlId($this->GetXhtmlId());
		}
		$a_controls = array();
		$a_controls[] = $o_container;
		$this->SetControls($a_controls);
	}

	/**
	 * Gets the XHTML representation of the element's opening tag
	 *
	 * @return string
	 */
	protected function GetOpenTagXhtml()
	{
		return $this->b_render_base_element ? parent::GetOpenTagXhtml() : '';
	}

	/**
	 * Gets the XHTML representation of the element's closing tag
	 *
	 * @return string
	 */
	protected function GetCloseTagXhtml()
	{
		# add internal buttons at the last possible stage before closing the form,
		# to ensure that all child controls have a chance to register their internal buttons

		$s_xhtml = $this->b_render_base_element ? parent::GetCloseTagXhtml() : '';
		if (count($this->a_internal_buttons))
		{
			$this->a_internal_buttons = array_unique($this->a_internal_buttons);
			$o_internal = new TextBox($this->GetNamingPrefix() . 'InternalButtons', implode('; ', $this->a_internal_buttons));
			$o_internal->SetMode(TextBoxMode::Hidden());
			$s_xhtml = '<div>' . $o_internal->__toString() . '</div>' . $s_xhtml;
		}
		return $s_xhtml;

	}
}
?>