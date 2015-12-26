<?php
require_once('placeholder.class.php');
require_once('html.class.php');

/**
 * An XHTML element on a web page
 *
 */
class XhtmlElement extends Placeholder
{
	private $s_base_element;
	private $a_attributes;
	private $b_empty;
	private $b_visible;
	private $s_rendered_xhtml;
	private $s_rendered_child_xhtml;
	private $b_stream = false;

	/**
	* @return XhtmlElement
	* @param string $s_base_element
	* @param XhtmlElement/Placeholder/string $o_control
	* @param string $s_css_class_or_id
	* @desc Constructor - sets up a basic XHTML element
	*/
	function XhtmlElement($s_base_element, $o_control=null, $s_css_class_or_id='')
	{
		$this->s_base_element = $s_base_element;
		$this->a_attributes = array();
		$this->b_empty = false;
		$this->b_visible = true;
		$this->InvalidateXhtml();

		parent::Placeholder($o_control);

		if ($s_css_class_or_id)
		{
			if (substr($s_css_class_or_id, 0, 1) == '#')
			{
				$this->SetXhtmlId(substr($s_css_class_or_id, 1));
			}
			else
			{
				$this->SetCssClass($s_css_class_or_id);
			}
		}
	}

	/**
	* @return void
	* @param string $s_name
	* @param string $s_value
	* @desc Add an XML attribute to the opening XHTML element
	*/
	function AddAttribute($s_name, $s_value)
	{
		$this->a_attributes[(string)$s_name] = (string)$s_value;
		$this->InvalidateXhtml();
	}

	/**
	* @return string
	* @param string $s_name
	* @desc Get the value of an XHTML attribute of the XHTML element
	*/
	function GetAttribute($s_name)
	{
		return (is_array($this->a_attributes) and key_exists($s_name, $this->a_attributes)) ? $this->a_attributes[$s_name] : '';
	}

	/**
	 * Removes the specified attribute from the element, if it exists
	 *
	 * @param string $s_name
	 */
	function RemoveAttribute($s_name)
	{
		if (isset($this->a_attributes[$s_name])) unset($this->a_attributes[$s_name]);
		$this->InvalidateXhtml();
	}

	/**
	 * Sets whether to stream element content straight to the output buffer
	 *
	 * @param bool $b_stream
	 */
	public function SetStreamContent($b_stream)
	{
		$this->b_stream = (bool)$b_stream;
	}

	/**
	 * Sets whether to stream element content straight to the output buffer
	 *
	 * @return bool
	 */
	public function GetStreamContent()
	{
		return $this->b_stream;
	}

	/**
	* @return bool
	* @param XhtmlElement/Placeholder/string $o_control
	* @desc Add a child element to this XHTML element
	*/
	public function AddControl($o_control)
	{
		if (is_integer($o_control) or is_float($o_control)) $o_control = (string)$o_control;

		if ($o_control != null and !$this->b_empty)
		{
			if ($o_control instanceof XhtmlElement or $o_control instanceof Placeholder or is_string($o_control))
			{
				if ($this->b_stream)
				{
					if (is_string($o_control)) echo $o_control;
					else echo ($o_control->__toString());
					ob_flush();
				}
				else
				{
					$this->a_controls[] = $o_control;
					$this->InvalidateXhtml();
				}
				return true;
			}
			else return false;
		}
		else return false;
	}

	/**
	 * Clear the cached rendering of the current control
	 *
	 */
	protected function InvalidateXhtml()
	{
		$this->s_rendered_xhtml = '';
		$this->s_rendered_child_xhtml = '';
	}

	/**
	* @return int
	* @desc Get the number of direct child elements of this element
	*/
	function CountControls()
	{
		return ($this->b_empty) ? 0 : parent::CountControls();
	}

	/**
	* @return void
	* @param XhtmlElement[] $a_controls
	* @desc Sets the array of child elements for this element
	*/
	public function SetControls($a_controls)
	{
		parent::SetControls($a_controls);
		$this->InvalidateXhtml();
	}

	/**
	* @return void
	* @param string $s_id
	* @desc Sets the value of the XHTML id attribute
	*/
	function SetXhtmlId($s_text)
	{
		if (!$s_text)
		{
			$this->RemoveAttribute('id');
		}
		else
		{
			$this->AddAttribute('id', $s_text);
		}
	}

	/**
	* @return string
	* @desc Gets the value of the XHTML id attribute
	*/
	function GetXhtmlId()
	{
		return $this->GetAttribute('id');
	}

	/**
	* @return void
	* @param string $s_text
	* @desc A space-separated list of CSS classes to be applied
	*/
	function SetCssClass($s_text)
	{
		if (!$s_text)
		{
			$this->RemoveAttribute('class');
		}
		else
		{
			$this->AddAttribute('class', $s_text);
		}
	}

	/**
	* @return string
	* @desc Gets a space-separated list of CSS classes to be applied
	*/
	function GetCssClass()
	{
		return $this->GetAttribute('class');
	}

	/**
	 * Adds a CSS class to those already applied
	 *
	 * @param string $s_class
	 */
	public function AddCssClass($s_class)
	{
		$a_classes = explode(' ', $this->GetCssClass());
		$a_classes[] = $s_class;
		$a_classes = array_unique($a_classes);
		$this->SetCssClass(trim(implode(' ', $a_classes)));
	}

	/**
	* @return void
	* @param string $s_text
	* @desc Sets the pop-up text to apply to the element
	*/
	function SetTitle($s_text)
	{
		if (!$s_text)
		{
			$this->RemoveAttribute('title');
		}
		else
		{
			$this->AddAttribute('title', $s_text);
		}
	}

	/**
	* @return string
	* @desc Gets the pop-up text to apply to the element
	*/
	function GetTitle()
	{
		return $this->GetAttribute('title');
	}

	/**
	* @return void
	* @param bool $b_input
	* @desc Set whether this element uses the XML empty element syntax &lt;element /&gt;
	*/
	function SetEmpty($b_input)
	{
		$this->b_empty = (bool)$b_input;
		$this->InvalidateXhtml();
	}

	function GetEmpty()
	{
		return $this->b_empty;
	}

	/**
	* @return void
	* @param bool $b_input
	* @desc Sets whether this element should be rendered
	*/
	function SetVisible($b_input)
	{
		$this->b_visible = (bool)$b_input;
		$this->InvalidateXhtml();
	}

	/**
	* @return bool $b_input
	* @desc Gets whether this element should be rendered
	*/
	function GetVisible()
	{
		return $this->b_visible;
	}

	/**
	* @return void
	* @param string $s_name
	* @desc Sets the name of the element
	*/
	function SetElementName($s_name)
	{
		$this->s_base_element = (string)$s_name;
		$this->InvalidateXhtml();
	}

	/**
	* @return string $s_name
	* @desc Gets the name of the element
	*/
	function GetElementName()
	{
		return $this->s_base_element;
	}

	/**
	* @return string
	* @desc Gets the XHTML representation of the element
	*/
	function __toString()
	{
		// Cache XHTML in case control is used multiple times on the same page
		if ($this->s_rendered_xhtml)
		{
			if ($this->b_stream)
			{
				echo $this->s_rendered_child_xhtml;
				ob_flush();
				return '';
			}
			else
			{
				return $this->s_rendered_xhtml;
			}
		}

		# Dump any current content and stream opening tag
		if ($this->b_stream)
		{
			ob_flush();
			echo $this->GetOpenTagXhtml();
		}

		$this->OnPreRender();

		if ($this->GetVisible())
		{
			if ($this->b_stream)
			{
				// AddControl should've handled the content
				echo $this->GetCloseTagXhtml();
				ob_flush();
				return '';
			}
			else
			{
				$this->s_rendered_xhtml = $this->GetOpenTagXhtml();
				$this->s_rendered_xhtml .= $this->GetChildXhtml();
				$this->s_rendered_xhtml .= $this->GetCloseTagXhtml();
				return $this->s_rendered_xhtml;
			}
		}
		else
		{
			if ($this->b_stream)
			{
				# Cancel the opening tag
				ob_clean();
			}
			return '';
		}
	}

	/**
	 * Gets the XHTML representation of the element's opening tag
	 *
	 * @return string
	 */
	protected function GetOpenTagXhtml()
	{
		$s_xhtml = '<' . $this->s_base_element;

		if (is_array($this->a_attributes))
        {
             foreach ($this->a_attributes as $s_name => $s_value) 
             {
                 $s_xhtml .= ' ' . Html::Encode($s_name) . '="' . Html::Encode($s_value) . '"';
             }   
        }

		$s_xhtml .= ($this->b_empty) ? ' />' : '>';

		return $s_xhtml;
	}

	/**
	* @return string
	* @desc Gets the XHTML representation of the element's child controls
	*/
	protected function GetChildXhtml()
	{
		# If empty element there is no child XHTML
		if ($this->b_empty) return '';

		// Cache XHTML in case control is used multiple times on the same page
		if ($this->s_rendered_child_xhtml) return $this->s_rendered_child_xhtml;

		if (is_array($this->a_controls))
		{
			foreach ($this->a_controls as $o_xhtml_element)
			{
				if (is_object($o_xhtml_element)) $this->s_rendered_child_xhtml .= $o_xhtml_element->__toString();
				elseif (is_string($o_xhtml_element)) $this->s_rendered_child_xhtml .= $o_xhtml_element;
			}
		}

		return $this->s_rendered_child_xhtml;
	}

	/**
	 * Gets the XHTML representation of the element's closing tag
	 *
	 * @return string
	 */
	protected function GetCloseTagXhtml()
	{
		if (!$this->b_empty)
		{
			return '</' . $this->s_base_element . ">";
		}
		else return '';
	}

}
?>