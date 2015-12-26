<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/tables/xhtml-rowgroup.class.php');
require_once('xhtml/tables/xhtml-colgroup.class.php');

/**
 * A valid XHTML data table
 *
 */
class XhtmlTable extends XhtmlElement
{
	/**
	 * Group of rows forming the header of the table
	 *
	 * @var XhtmlRowGroup
	 */
	private $o_headergroup;

	/**
	 * Groups of rows in the body of the table
	 *
	 * @var XhtmlRowGroup[]
	 */
	private $a_rowgroups;

	/**
	 * Group of rows forming the footer of the table
	 *
	 * @var XhtmlRowGroup
	 */
	private $o_footergroup;

	/**
	 * Groups of columns in the header of the table
	 *
	 * @var XhtmlColumnGroup[]
	 */
	private $a_colgroups;

	private $s_caption;
	
	/**
	 * Tracks the number of rows added to the table
	 *
	 * @var int
	 */
	private $i_rowcount = 0;

	function XhtmlTable()
	{
		parent::XhtmlElement('table');

		$this->a_rowgroups = array();
		$this->a_rowgroups[] = new XhtmlRowGroup();
		$this->a_colgroups = array();
	}

	/**
	 * Adds a column group to the header of the table
	 *
	 * @param XhtmlColumnGroup $o_group
	 */
	public function AddColumnGroup(XhtmlColumnGroup $o_group)
	{
		$this->a_colgroups[] = $o_group;
	}
	
	/**
	 * Gets the columns groups in the header of the table
	 *
	 * @return XhtmlColumnGroup[]
	 */
	public function GetColumnGroups()
	{
		return $this->a_colgroups;
	}
	
	/**
	 * Sets the number of columns each group of columns spans. Overwrites any existing column groups.
	 *
	 * @param int[] $a_sizes
	 * @return void
	 */
	public function SetColumnGroupSizes($a_sizes)
	{
		if (is_array($a_sizes))
		{
			$this->a_colgroups = array();
			foreach ($a_sizes as $i_size)
			{
				$this->AddColumnGroup(new XhtmlColumnGroup($i_size));
			}
		}
	}
	
	/**
	 * Adds a row to the table
	 *
	 * @param XhtmlRow $o_row
	 * @param int $i_group_index
	 */
	function AddRow(XhtmlRow $o_row, $i_group_index=0)
	{
		if ($o_row->GetIsHeader())
		{
			if (!is_object($this->o_headergroup)) $this->o_headergroup = new XhtmlRowGroup(true);
			$this->o_headergroup->AddControl($o_row);
		}
		else if ($o_row->GetIsFooter())
		{
			if (!is_object($this->o_footergroup))
			{
				$this->o_footergroup = new XhtmlRowGroup();
				$this->o_footergroup->SetIsFooter(true);
			}
			$this->o_footergroup->AddControl($o_row);
		}

		else if (isset($this->a_rowgroups[$i_group_index]))
		{
			$this->a_rowgroups[$i_group_index]->AddControl($o_row);
			$this->i_rowcount++;
		}
	}
	
	/**
	 * Gets the number of rows added to the body of the table
	 *
	 * @return int
	 */
	public function CountRows()
	{
		return $this->i_rowcount;
	}

	/**
	 * Displays the data in the array in tabular form
	 *
	 * @param array[] $a_data
	 * @param bool $b_first_row_contains_headings
	 * @param bool $b_first_column_contains_headings
	 * @return bool
	 */
	function BindArray(&$a_data, $b_first_row_contains_headings=false, $b_first_column_contains_headings=false)
	{
		# Options for the future
		if ($b_first_column_contains_headings or $b_first_row_contains_headings) die('Binding with headings not implemented');

		# Check it's an array of arrays
		if (!is_array($a_data)) return false;
		foreach ($a_data as $a_row_data) if (!is_array($a_row_data)) return false;

		# Add rows
		foreach ($a_data as $a_row_data)
		{
			$o_row = new XhtmlRow($a_row_data);
			$this->AddRow($o_row);
		}

		return true;
	}

	/**
	 * Sets the title of the table
	 *
	 * @param string $s_caption
	 */
	function SetCaption($s_caption)
	{
		$this->s_caption = (string)$s_caption;
	}

	/**
	 * Gets the title of the table
	 *
	 * @return string
	 */
	function GetCaption()
	{
		return $this->s_caption;
	}

	function OnPreRender()
	{
		/* @var $o_row XhtmlRow */

		$b_rowgroups = (count($this->a_rowgroups) > 1 or (isset($this->a_rowgroups[0]) and $this->a_rowgroups[0]->CountControls()));
		if (!$b_rowgroups)
		{
			$this->SetVisible(false);
			return;
		}
		
		if ($this->s_caption) $this->AddControl(new XhtmlElement('caption', $this->s_caption));
		foreach ($this->a_colgroups as $o_colgroup) $this->AddControl($o_colgroup);

		$b_header_group = is_object($this->o_headergroup);
		$b_footer_group = is_object($this->o_footergroup);

		# Attempt to cope automatically with missing colspans
		$i_min_columns = 0;
		$i_max_columns = 0;
		$b_add_columns = true;

		if ($b_header_group)
		{
			foreach ($this->o_headergroup as $o_row)
			{
				$i_min_columns = $o_row->CountColumns();
				$i_max_columns = $i_min_columns;
			}
		}
		foreach ($this->a_rowgroups as $o_group)
		{
			foreach ($o_group as $o_row)
			{
				if ($o_row->OverlapsRows())
				{
					# Rowspans in data make it complicated so, for now, give up
					$b_add_columns = false;
					break;
				}
				$i_min_columns = min($i_min_columns, $o_row->CountColumns());
				$i_max_columns = max($i_max_columns, $o_row->CountColumns());
			}
		}

		if ($b_add_columns and $b_footer_group)
		{
			foreach ($this->o_footergroup as $o_row)
			{
				$i_min_columns = min($i_min_columns, $o_row->CountColumns());
				$i_max_columns = max($i_max_columns, $o_row->CountColumns());
			}
		}

		if ($b_add_columns and $i_min_columns < $i_max_columns)
		{
			if ($b_header_group)
			{
				foreach ($this->o_headergroup as $o_row)
				{
					if ($o_row->CountColumns() < $i_max_columns)
					{
						$o_cell = $o_row->GetLastCell();
						$o_cell->SetColumnSpan($o_cell->GetColumnSpan() + ($i_max_columns-$o_row->CountColumns()));
					}
				}
			}
			foreach ($this->a_rowgroups as $o_group)
			{
				foreach ($o_group as $o_row)
				{
					if ($o_row->CountColumns() < $i_max_columns)
					{
						$o_cell = $o_row->GetLastCell();
						$o_cell->SetColumnSpan($o_cell->GetColumnSpan() + ($i_max_columns-$o_row->CountColumns()));
					}
				}
			}
			if ($b_footer_group)
			{
				foreach ($this->o_footergroup as $o_row)
				{
					if ($o_row->CountColumns() < $i_max_columns)
					{
						$o_cell = $o_row->GetLastCell();
						$o_cell->SetColumnSpan($o_cell->GetColumnSpan() + ($i_max_columns-$o_row->CountColumns()));
					}
				}
			}
		}

		# Get CSS classes from column groups
		$a_col_classes = array();
		$b_col_classes = false;
		$i_column = 1;
		foreach ($this->a_colgroups as $o_colgroup)
		{
			/* @var $o_colgroup XhtmlColumnGroup */
			$i_cols = $o_colgroup->GetColumnCount();
			$i = 1;
			while ($i <= $i_cols)
			{
				$a_col_classes[$i_column] = $o_colgroup->GetCssClass();
				$i++;
				$i_column++;
				$b_col_classes = ($b_col_classes or strlen($o_colgroup->GetCssClass()));
			}
		}
		
		// Add the rows to the table
		if ($b_header_group) $this->BuildRowGroup($this->o_headergroup, $a_col_classes, $b_col_classes);
		foreach ($this->a_rowgroups as $o_group) $this->BuildRowGroup($o_group, $a_col_classes, $b_col_classes);
		if ($b_footer_group) $this->BuildRowGroup($this->o_footergroup, $a_col_classes, $b_col_classes);
	}
	
	/**
	 * Adds a row group to the control tree, applying CSS classes assigned in the header
	 *
	 * @param XhtmlRowGroup $o_rowgroup
	 * @param string[] $a_col_classes
	 * @param bool $b_col_classes
	 */
	private function BuildRowGroup(XhtmlRowGroup $o_rowgroup, &$a_col_classes, $b_col_classes)
	{
		if (!$o_rowgroup->CountControls()) return;
		
		if ($b_col_classes)
		{
			foreach ($o_rowgroup as $o_row)
			{
				/* @var $o_row XhtmlRow */
				$i_column = 1;
				foreach ($o_row as $o_cell)
				{
					/* @var $o_cell XhtmlCell */
					$o_cell->SetCssClass($a_col_classes[$i_column]);
					$i_column++;
				}
			}
		}
		$this->AddControl($o_rowgroup);
	}
}
?>