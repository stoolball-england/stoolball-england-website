<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/tables/xhtml-cell.class.php');

/**
 * A row in an XHTML data table
 *
 */
class XhtmlRow extends XhtmlElement
{
	private $b_is_header;
	private $b_is_footer;
	private $i_columns;

	function XhtmlRow($a_values=null)
	{
		parent::XhtmlElement('tr');

		if (is_array($a_values)) foreach ($a_values as $m_value) $this->AddCell($m_value);
		else if (!is_null($a_values)) throw new Exception('Table row values must be supplied in an array');

		$this->b_is_header = false;
		$this->i_columns = -1;

	}

	/**
	 * Sets whether the row is a header row
	 *
	 * @param bool $b_is_header
	 */
	function SetIsHeader($b_is_header)
	{
		/* @var $o_cell XhtmlCell */

		$this->b_is_header = (bool)$b_is_header;

		# Convert existing cells to/from headers
		foreach($this->a_controls as $o_cell) $o_cell->SetElementName($b_is_header ? 'th' : 'td');
	}

	/**
	 * Gets whether the row is a header row
	 *
	 * @return bool
	 */
	function GetIsHeader()
	{
		return $this->b_is_header;
	}

	/**
	 * Sets whether the row is a footer row
	 *
	 * @param bool $b_is_footer
	 */
	function SetIsFooter($b_is_footer)
	{
		$this->b_is_footer = (bool)$b_is_footer;
	}

	/**
	 * Gets whether the row is a footer row
	 *
	 * @return bool
	 */
	function GetIsFooter()
	{
		return $this->b_is_footer;
	}

	/**
	 * Adds a cell to the end of the row
	 *
	 * @param XhtmlCell $m_cell_or_data
	 */
	function AddCell($m_cell_or_data)
	{
		if ($m_cell_or_data instanceof XhtmlCell)
		{
			parent::AddControl($m_cell_or_data);
		}
		else
		{
			$o_cell = new XhtmlCell(false, $m_cell_or_data);
			$o_cell->SetElementName($this->b_is_header ? 'th' : 'td');
			parent::AddControl($o_cell);
		}
		$this->i_columns = -1;
	}

	/**
	 * Adds a cell to the end of the row
	 *
	 * @param XhtmlCell $m_cell_or_data
	 */
	public function AddControl($o_control)
	{
		$this->AddCell($o_control);
	}

	/**
	 * Gets the first cell in the row
	 *
	 * @return XhtmlCell
	 */
	public function GetFirstCell()
	{
		$i_controls = $this->CountControls();
		if (!$i_controls) return null;
		$a_controls = $this->GetControls();
		return $a_controls[0];
	}

	/**
	 * Gets the last cell in the row
	 *
	 * @return XhtmlCell
	 */
	public function GetLastCell()
	{
		$i_controls = $this->CountControls();
		if (!$i_controls) return null;
		$a_controls = $this->GetControls();
		return $a_controls[$i_controls-1];
	}

	/**
	 * Gets how many columns this row will span
	 *
	 * @return int
	 */
	public function CountColumns()
	{
		/* @var $o_col XhtmlCell */
		if ($this->i_columns > -1) return $this->i_columns; # Cache value

		$this->i_columns = 0;
		foreach ($this->a_controls as $o_col)
		{
			$this->i_columns += $o_col->GetColumnSpan();
		}
		return $this->i_columns;
	}

	/**
	 * Gets whether any cells in this row overlap subsequent rows
	 *
	 * @return bool
	 */
	public function OverlapsRows()
	{
		/* @var $o_col XhtmlCell */
		foreach ($this->a_controls as $o_col)
		{
			if ($o_col->GetRowSpan() > 1) return true;
		}
		return false;
	}
}
?>