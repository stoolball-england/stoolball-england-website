<?php
require_once('xhtml/xhtml-element.class.php');

class XhtmlCell extends XhtmlElement 
{
	private $b_is_header;
	
	public function __construct($b_is_header = false, $m_content=null)
	{
		$this->b_is_header = (bool)$b_is_header;

		parent::__construct($this->b_is_header ? 'th' : 'td');
		$this->AddControl($m_content);
	}
	
	/**
	 * Sets how many columns this cell should span
	 *
	 * @param int $i_columns
	 * @return int
	 */
	public function SetColumnSpan($i_columns)
	{
		$this->SetSpan('colspan', $i_columns);
	}
	
	/**
	 * Gets how many columns this cell should span
	 *
	 * @return int
	 */
	public function GetColumnSpan()
	{
		return $this->GetSpan('colspan');
	}

	/**
	 * Sets how many rows this cell should span
	 *
	 * @param int $i_rows
	 * @return int
	 */
	public function SetRowSpan($i_rows)
	{
		$this->SetSpan('rowspan', $i_rows);
	}
	
	/**
	 * Gets how many rows this cell should span
	 *
	 * @return int
	 */
	public function GetRowSpan()
	{
		return $this->GetSpan('rowspan');
	}

	/**
	 * Sets how many cell positions this cell should span
	 *
	 * @param string $s_attribute
	 * @param int $i_span
	 * @return void
	 */
	private function SetSpan($s_attribute, $i_span)
	{
		if ((int)$i_span > 1)
		{
			$this->AddAttribute($s_attribute, $i_span);
		}
		else 
		{
			$this->RemoveAttribute($s_attribute);
		}

	}
	
	/**
	 * Gets how many cell positions this cell should span
	 *
	 * @param string $s_attribute
	 * @return int
	 */
	private function GetSpan($s_attribute)
	{
		$i_span = (int)$this->GetAttribute($s_attribute);
		if ($i_span > 1)
		{
			return $i_span;
		}
		else 
		{
			return 1;
		}
	}
}
?>