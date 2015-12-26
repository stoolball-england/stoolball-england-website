<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/placeholder.class.php');
require_once('textbox-mode.enum.php');

class TextBox extends XhtmlElement
{
	var $s_value;
	var $i_mode;

	/**
	 * Creates a TextBox
	 *
	 * @param string $s_id
	 * @param string $s_value
	 * @param bool $page_valid
	 * @return TextBox
	 */
	function TextBox($s_id, $s_value='', $page_valid=null)
	{
		# set options assuming <input type="text" />
		parent::XhtmlElement("input");
		$this->SetMode(TextBoxMode::SingleLine());
		$this->SetXhtmlId($s_id);

		if ($page_valid === false and isset($_POST[$this->GetXhtmlId()]))
		{
			$this->SetText($_POST[$this->GetXhtmlId()]);
		}
		else
		{
			$this->SetText($s_value);
		}
	}


	/**
	 * @access public
	 * @return void
	 * @param string $s_id
	 * @desc Set the id and name attributes of the TextBox together
	 */
	function SetXhtmlId($s_id)
	{
		$s_id = (string)$s_id;
		parent::SetXhtmlId($s_id);
		parent::AddAttribute('name', $s_id);
	}

	/**
	 * @access public
	 * @return void
	 * @param string $s_text
	 * @desc Set the text to be edited in the textbox
	 */
	function SetText($s_text) { $this->s_value = (string)$s_text; }

	/**
	 * @access public
	 * @return string
	 * @desc Get the text to be edited in the textbox
	 */
	function GetText() { return $this->s_value; }

	/**
	 * @access public
	 * @return void
	 * @param int $i_mode
	 * @desc Sets the type of textbox as a TextBoxMode enum
	 */
	function SetMode($i_mode) { $this->i_mode = (int)$i_mode; }

	/**
	 * @access public
	 * @return int
	 * @desc Get the type of textbox as a TextBoxMode enum
	 */
	function GetMode() { return $this->i_mode; }

	/**
	 * @return void
	 * @param int $i_length
	 * @desc Sets the type of textbox as a TextBoxMode enum
	 */
	function SetMaxLength($i_length)
	{
		$this->AddAttribute('maxlength', (int)$i_length);
	}

	/**
	 * @return int
	 * @desc Get the maximum length allowed for the content
	 */
	function GetMaxLength()
	{
		return (int)$this->GetAttribute('maxlength');
	}

	/**
	 * @access private
	 * @return void
	 * @desc Build control from supplied properties, ready for display
	 */
	function OnPreRender()
	{
		# build element differently depending on mode
		switch ($this->i_mode)
		{
			case TextBoxMode::MultiLine():
				$this->SetElementName('textarea');
                $this->AddControl(Html::Encode($this->s_value));
				$this->RemoveAttribute('maxlength'); // not valid option
				$this->AddAttribute('rows', 7); // arbitrary value to validate, will use CSS in practice
				$this->AddAttribute('cols', 40); // arbitrary value to validate, will use CSS in practice
				$this->SetEmpty(false);
				break;

			case TextBoxMode::Hidden():
				$this->AddAttribute('type', 'hidden');
				$this->AddAttribute('value', $this->s_value);
				$this->SetEmpty(true);
				break;

			case TextBoxMode::File():
				$this->AddAttribute('type', 'file');
				$this->SetEmpty(true);
				break;

            case TextBoxMode::Number():
                $this->AddAttribute('type', 'number');
                $this->AddAttribute('value', $this->s_value);
                $this->SetEmpty(true);
                break;

			default:
				if (!$this->GetAttribute("type")) $this->AddAttribute('type', 'text');
				$this->AddAttribute('value', $this->s_value);
				$this->SetEmpty(true);
				break;
		}
	}

	/**
	 * Populate the control based on the value passed in the querystring or post data
	 *
	 */
	public function PopulateData()
	{
		$s_key = $this->GetAttribute('name');
		if (isset($_GET[$s_key])) $this->SetText($_GET[$s_key]);
		else if (isset($_POST[$s_key])) $this->SetText($_POST[$s_key]);
	}

	/**
	 * (non-PHPdoc)
	 * @see xhtml/XhtmlElement#GetOpenTagXhtml()
	 */
	public function GetOpenTagXhtml()
	{
		# For a file upload control, use the maxlength attribute to set a max file size
		$max_size = '';

		if ($this->i_mode == TextBoxMode::File())
		{
			$max_size = new TextBox('MAX_FILE_SIZE', $this->GetMaxLength());
			$max_size->SetMode(TextBoxMode::Hidden());
			$this->RemoveAttribute('maxlength'); // not valid option
		}

		return $max_size . parent::GetOpenTagXhtml();
	}
}
?>