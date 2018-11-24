<?php
require_once('xhtml/xhtml-element.class.php');
require_once('http/query-string.class.php');

class AZControl extends XhtmlElement
{
	private $s_page;
	private $s_current;
	private $disabled = array();

	public function __construct()
	{
		parent::__construct('ul');
		$this->SetCssClass('az');
		$this->s_page = $_SERVER['REQUEST_URI'];
		$query = strpos($this->s_page, "?");
		if ($query) $this->s_page = substr($this->s_page, 0, $query);
	}

	/**
	 * @return void
	 * @param string $s_current
	 * @desc Sets the currently selected item
	 */
	public function SetCurrent($s_current)
	{
		$this->s_current = strtoupper((string)$s_current);
	}

	/**
	 * @return string
	 * @desc Gets the currently selected item
	 */
	public function GetCurrent()
	{
		return $this->s_current;
	}

	/**
	 * Sets a string of letters which should be disabled in the A-Z
	 *
	 * @param string[] $letters
	 */
	public function SetDisabled($letters)
	{
		if (is_array($letters)) $this->disabled = $letters;
	}

	/**
	 * Gets a string of letters which are disabled in the A-Z
	 *
	 * @return string[]
	 */
	public function GetDisabled() { return $this->disabled; }

	public function OnPreRender()
	{
		$a_items = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		$querystring = QueryStringBuilder::RemoveParameter('az');

		foreach ($a_items as $s_item)
		{
			if ($s_item == $this->s_current)
			{
				$o_li = new XhtmlElement('li', new XhtmlElement('strong', $s_item));
				$this->AddControl($o_li);
				unset($o_li);
			}
			else if (in_array($s_item, $this->disabled, true))
			{
				$this->AddControl(new XhtmlElement('li', $s_item));
			}
			else
			{
				$o_link = new XhtmlElement('a', $s_item);
				# $o_link->AddAttribute('href', $this->s_page . $querystring . 'az=' . strtolower($s_item));
                $o_link->AddAttribute('href', $this->s_page . '?az=' . strtolower($s_item));
				$o_li = new XhtmlElement('li', $o_link);
				$this->AddControl($o_li);
				unset($o_link);
				unset($o_li);
			}
		}
	}
}
?>