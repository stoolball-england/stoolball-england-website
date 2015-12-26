<?php
class Placeholder implements IteratorAggregate
{
	var $a_controls;

	/**
	* @return Placeholder
	* @param XhtmlControl/Placeholder/string $o_control
	* @desc A container for controls which does not render any additional XHTML
	*/
	function Placeholder($o_control=null)
	{
		$this->a_controls = array();
		if (!is_null($o_control)) $this->AddControl($o_control); # null check important, overriden AddControl might have a type hint
	}

	/**
	* @return bool
	* @param XhtmlControl/Placeholder/string $o_control
	* @desc Add a child element to this Placeholder
	*/
	function AddControl($o_control)
	{
		if ($o_control != null and ($o_control instanceof XhtmlElement or $o_control instanceof Placeholder or is_string($o_control)))
		{
			$this->a_controls[] = $o_control;
			return true;
		}
		else return false;
	}

	/**
	* @return int
	* @desc Get the number of direct child elements of this Placeholder
	*/
	function CountControls()
	{
		return count($this->a_controls);
	}

	/**
	* @return void
	* @param (Placeholder/XhtmlElement/string)[] $a_controls
	* @desc Sets the child controls of the placeholder
	*/
	public function SetControls($a_controls)
	{
		if (is_array($a_controls)) $this->a_controls = &$a_controls;
	}

	/**
	* @return (Placeholder/XhtmlElement/string)[]
	* @desc Gets the child controls of the placeholder
	*/
	function &GetControls()
	{
		return $this->a_controls;
	}

	# event to be overriden by child controls to build content
	protected function OnPreRender() 	{ 	}

	function __toString()
	{
		$this->OnPreRender();

		$s_text = '';
		if (is_array($this->a_controls))
		{
			foreach ($this->a_controls as $o_element)
			{
				if (is_object($o_element)) $s_text .= $o_element->__toString();
				else if (is_string($o_element)) $s_text .= $o_element;
			}
		}

		return $s_text;
	}

	/**
	 * Gets an iterator for the class (implements IteratorAggregate)
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ArrayObject($this->a_controls);
	}
}
?>