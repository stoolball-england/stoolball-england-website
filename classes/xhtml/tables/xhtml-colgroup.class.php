<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/tables/xhtml-column.class.php');

/**
 * A group of rows in an XHTML table
 *
 */
class XhtmlColumnGroup extends XhtmlElement
{
	/**
	 * A colum group within an XHTML table
	 *
	 * @param int $i_column_count
	 * @return XhtmlColumnGroup
	 */
	public function XhtmlColumnGroup($i_column_count=1)
	{
		parent::XhtmlElement('colgroup');
		$this->SetColumnCount($i_column_count);
	}

	/**
	* @return bool
	* @param XhtmlColumn $o_control
	* @desc Add a column to the end of the group
	*/
	public function AddControl($o_control)
	{
		$b_result = parent::AddControl($o_control);

		if ($b_result) $this->SetColumnCount($this->CountControls());

		return $b_result;
	}

	/**
	* @return void
	* @param int $i_columns
	* @desc Sets how many columns this group spans
	*/
	public function SetColumnCount($i_columns)
	{
		if (!$i_columns)
		{
			$this->RemoveAttribute('span');
		}
		else
		{
			$this->AddAttribute('span', $i_columns);
		}

		$this->GenerateColumns();
	}

	/**
	* @return string
	* @desc Gets how many columns this group spans
	*/
	public function GetColumnCount()
	{
		$i_count = $this->GetAttribute('span');
		if (!$i_count) return 1;
		else return (int)$i_count;
	}

	/**
	 * Synchronise the internal column objects with the column count
	 *
	 */
	private function GenerateColumns()
	{
		$i_controls = $this->CountControls();

		$i_cols = $this->GetColumnCount();

		if (!$i_controls)
		{
			# If none, generate them all
			for ($i = 0; $i < $i_cols; $i++)
			{
				parent::AddControl(new XhtmlColumn());
			}
		}
		else if ($i_controls < $i_cols)
		{
			# If some missing, make up the difference
			$i_missing = $i_cols - $i_controls;

			for ($i = 0; $i < $i_missing; $i++)
			{
				parent::AddControl(new XhtmlColumn());
			}
		}
		else if ($i_controls > $i_cols)
		{
			# If too many, remove from end
			$i_extra = $i_controls - $i_cols;
			$a_controls = $this->GetControls();
			while ($i_extra > 0)
			{
				$i_key = count($a_controls)-1;
				if (array_key_exists($i_key, $a_controls)) unset($a_controls[$i_key]);
				$i_extra--;
			}
			$this->SetControls($a_controls);

		}
	}

	/**
	* @return string
	* @desc Gets the XHTML representation of the element
	*/
	public function __toString()
	{
		$this->GenerateColumns(); // make sure columns match declared count

		/* Commented out as mixing groups and columns is invalid XHTML

		if ($this->GetColumnCount() == 1)
		{
		# If only one column, don't render the group. Instead render the column, passing on any CSS class applied to the group
		if ($this->GetCssClass())
		{
		$a_controls = $this->GetControls();
		$s_css = $a_controls[0]->GetCssClass();
		$s_new_css = $s_css ? $s_css . ' ' . $this->GetCssClass() : $this->GetCssClass();
		$a_controls[0]->SetCssClass($s_new_css);
		}
		return $this->GetChildXhtml();
		}*/

		return parent::__toString();
	}
}
?>