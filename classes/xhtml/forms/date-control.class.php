<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/forms/xhtml-select.class.php');

class DateControl extends XhtmlElement
{
	private $i_timestamp;
	private $b_show_date = true;
	private $b_show_time = false;
	private $b_require_date = true;
	private $b_require_time = true;
	private $i_minute_interval;
	private $b_time_known = true;
	private $b_page_valid = true;
	private $year_start;
	private $year_end;
	private $month_start;
	private $month_end;

	/**
	 * Control to select a date and, optionally, a time
	 *
	 * @param XHTML id of the control $s_id
	 * @param UNIX timestamp to select by default $i_timestamp
	 * @param true if the timestamp's time is valid, otherwise false $b_time_known
	 * @param bool $page_valid
	 * @return DateControl
	 */
	public function DateControl($s_id, $i_timestamp=null, $b_time_known=true, $page_valid=null)
	{
		parent::XhtmlElement('span');
		$this->SetXhtmlId($s_id);
		$this->SetCssClass('dateControl');
		$this->SetTimestamp($i_timestamp, $b_time_known);
		$this->SetMinuteInterval(1);
		$this->b_page_valid = $page_valid;

		$this->month_start = 1;
		$this->month_end = 12;
		$this->year_start = 1997;
		$i_thisyear = (int)gmdate('Y', gmdate('U'));
		$i_nextyear = $i_thisyear+1;
		$this->year_end = $i_nextyear;
	}

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the interval between minutes shown in the dropdown list
	 */
	public function SetMinuteInterval($i_input)
	{
		if (is_numeric($i_input))
		{
			$i_interval = (int)$i_input;
			if ($i_interval > 0) $this->i_minute_interval = $i_interval;
		}
	}

	/**
	 * @return int
	 * @desc Gets the interval between minutes shown in the dropdown list
	 */
	public function GetMinuteInterval() { return $this->i_minute_interval; }

	/**
	 * Sets the first year available to be selected
	 *
	 * @param int $year
	 */
	public function SetYearStart($year) { $this->year_start = (int)$year; }

	/**
	 * Gets the first year available to be selected
	 *
	 * @param int $year
	 */
	public function GetYearStart() { return $this->year_start; }

	/**
	 * Sets the last year available to be selected
	 *
	 * @param int $year
	 */
	public function SetYearEnd($year) { $this->year_end = (int)$year; }

	/**
	 * Gets the last year available to be selected
	 *
	 * @param int $year
	 */
	public function GetYearEnd() { return $this->year_end; }

	/**
	 * Sets the first month available to be selected
	 *
	 * @param int $month
	 */
	public function SetMonthStart($month) { $this->month_start = (int)$month; }

	/**
	 * Gets the first month available to be selected
	 *
	 * @param int $month
	 */
	public function GetMonthStart() { return $this->month_start; }

	/**
	 * Sets the last month available to be selected
	 *
	 * @param int $month
	 */
	public function SetMonthEnd($month) { $this->month_end = (int)$month; }

	/**
	 * Gets the last month available to be selected
	 *
	 * @param int $month
	 */
	public function GetMonthEnd() { return $this->month_end; }

	/**
	 * @return void
	 * @param int $i_input
	 * @param bool $b_time_known
	 * @desc Set the date and time to be edited as a UNIX timestamp
	 */
	public function SetTimestamp($i_input, $b_time_known=true)
	{
		if (is_numeric($i_input)) $this->i_timestamp = (int)$i_input;
		$this->b_time_known = (bool)$b_time_known;
	}

	/**
	 * @return int
	 * @desc Get the date and time to be edited as a UNIX timestamp
	 */
	public function GetTimestamp() { return $this->i_timestamp; }


	/**
	 * @return void
	 * @param bool $b_input
	 * @desc Get whether to show controls for setting the date
	 */
	public function SetShowDate($b_input) { $this->b_show_date = (bool)$b_input; }

	/**
	 * @return bool
	 * @desc Get whether to show controls for setting the date
	 */
	public function GetShowDate() { return $this->b_show_date; }

	/**
	 * @return void
	 * @param bool $b_input
	 * @desc Get whether to show controls for setting the time
	 */
	public function SetShowTime($b_input) { $this->b_show_time = (bool)$b_input; }

	/**
	 * @return bool
	 * @desc Get whether to show controls for setting the time
	 */
	public function GetShowTime() { return $this->b_show_time; }

	/**
	 * @return void
	 * @param bool $b_input
	 * @desc Sets whether to require a date to be selected
	 */
	public function SetRequireDate($b_input) { $this->b_require_date = (bool)$b_input; }

	/**
	 * @return bool
	 * @desc Gets whether to require a date to be selected
	 */
	public function GetRequireDate() { return $this->b_require_date; }

	/**
	 * @return void
	 * @param bool $b_input
	 * @desc Sets whether to require a time to be selected
	 */
	public function SetRequireTime($b_input) { $this->b_require_time = (bool)$b_input; }

	/**
	 * @return bool
	 * @desc Gets whether to require a time to be selected
	 */
	public function GetRequireTime() { return $this->b_require_time; }

	function OnPreRender()
	{
		if ($this->b_show_date)
		{
			# create dropdown menu for day of month
			$o_day = new XhtmlSelect($this->GetXhtmlId() . '_day', '', $this->b_page_valid);
			if (!$this->GetRequireDate()) $o_day->AddControl(new XhtmlOption());
			for ($i = 1; $i <= 31; $i++)
			{
				$o_day->AddControl(new XhtmlOption($i, $i));
			}
			if ($this->i_timestamp != null and (!($this->b_page_valid === false))) $o_day->SelectOption(date('j', $this->i_timestamp));

			# create label, and add controls
			$o_day_label = new XhtmlElement('label', 'Day');
			$o_day_label->AddAttribute('for', $o_day->GetXhtmlId());
			$o_day_label->SetCssClass('aural');
			$this->AddControl($o_day_label);
			$this->AddControl($o_day);

			# create dropdown menu for month
			$o_month = new XhtmlSelect($this->GetXhtmlId() . '_month', '', $this->b_page_valid);
			if (!$this->GetRequireDate()) $o_month->AddControl(new XhtmlOption());
			if ($this->month_end > $this->month_start)
			{
				for ($i = $this->month_start; $i <= $this->month_end; $i++)
				{
					$s_monthname = date('F', gmmktime(0, 0, 0, $i, 1, 0));
					$o_month->AddControl(new XhtmlOption($s_monthname, $i));
				}
			}
			else
			{
				# Support order of  Oct, Nov, Dec, Jan, Feb Mar
				for ($i = $this->month_start; $i <= 12; $i++)
				{
					$s_monthname = date('F', gmmktime(0, 0, 0, $i, 1, 0));
					$o_month->AddControl(new XhtmlOption($s_monthname, $i));
				}
				for ($i = 1; $i <= $this->month_end; $i++)
				{
					$s_monthname = date('F', gmmktime(0, 0, 0, $i, 1, 0));
					$o_month->AddControl(new XhtmlOption($s_monthname, $i));
				}
			}
			if ($this->i_timestamp != null and (!($this->b_page_valid === false))) $o_month->SelectOption(date('n', $this->i_timestamp));

			# create label, and add controls
			$o_month_label = new XhtmlElement('label', 'Month');
			$o_month_label->AddAttribute('for', $o_month->GetXhtmlId());
			$o_month_label->SetCssClass('aural');
			$this->AddControl($o_month_label);
			$this->AddControl($o_month);

			# create dropdown for year
			if ($this->GetRequireDate() and $this->year_start == $this->year_end)
			{
				# If there's only one possible value for year and it's required, just hard-code it
				$o_year = new TextBox($this->GetXhtmlId() . '_year', $this->year_start, $this->b_page_valid);
				$o_year->SetMode(TextBoxMode::Hidden());

				$this->AddControl($o_year);
				$this->AddControl(' ' . $this->year_start);
			}
			else
			{
				$o_year = new XhtmlSelect($this->GetXhtmlId() . '_year', '', $this->b_page_valid);
				if (!$this->GetRequireDate()) $o_year->AddControl(new XhtmlOption());
				for ($i = $this->year_start; $i <= $this->year_end; $i++)
				{
					$o_option = new XhtmlOption($i, $i);
					$o_year->AddControl($o_option);
				}
				if ($this->i_timestamp != null and (!($this->b_page_valid === false))) $o_year->SelectOption(date('Y', $this->i_timestamp));

				# create label, and add controls
				$o_year_label = new XhtmlElement('label', 'Year');
				$o_year_label->AddAttribute('for', $o_year->GetXhtmlId());
				$o_year_label->SetCssClass('aural');
				$this->AddControl($o_year_label);
				$this->AddControl($o_year);
			}

		}
		else
		{
			$o_day = new TextBox($this->GetXhtmlId() . '_day');
			$o_day->SetMode(TextBoxMode::Hidden());
			$o_day->SetText(date('j', $this->i_timestamp));
			$this->AddControl($o_day);

			$o_month = new TextBox($this->GetXhtmlId() . '_month');
			$o_month->SetMode(TextBoxMode::Hidden());
			$o_month->SetText(date('n', $this->i_timestamp));
			$this->AddControl($o_month);

			$o_year = new TextBox($this->GetXhtmlId() . '_year');
			$o_year->SetMode(TextBoxMode::Hidden());
			$o_year->SetText(date('Y', $this->i_timestamp));
			$this->AddControl($o_year);
		}


		if ($this->b_show_time)
		{
			if ($this->b_show_date) $this->AddControl(' ');
			$this->AddControl('<span class="time">');
			if ($this->b_show_date) $this->AddControl('at ');

			# create dropdown menu for hour
			$o_hour = new XhtmlSelect($this->GetXhtmlId() . '_hour', '', $this->b_page_valid);
			if (!$this->GetRequireTime()) $o_hour->AddControl(new XhtmlOption());
			for ($i = 1; $i <= 12; $i++)
			{
				$o_hour->AddControl(new XhtmlOption($i, $i));
			}
			if ($this->i_timestamp != null and $this->b_time_known and (!($this->b_page_valid === false))) $o_hour->SelectOption(date('g', $this->i_timestamp));

			# create label, and add controls
			$o_hour_label = new XhtmlElement('label', 'Hour');
			$o_hour_label->AddAttribute('for', $o_hour->GetXhtmlId());
			$o_hour_label->SetCssClass('aural');
			$this->AddControl($o_hour_label);
			$this->AddControl($o_hour);

			# create dropdown menu for minute
			$o_minute = new XhtmlSelect($this->GetXhtmlId() . '_minute', '', $this->b_page_valid);
			if (!$this->GetRequireTime()) $o_minute->AddControl(new XhtmlOption());
			for ($i = 0; $i <= 59; $i++)
			{
				if (($i % $this->i_minute_interval) == 0)
				{
					$s_minute = ($i > 9) ? (string)$i : "0" . (string)$i;
					$o_minute->AddControl(new XhtmlOption($s_minute, $s_minute));
				}
			}
			if ($this->i_timestamp != null and $this->b_time_known and (!($this->b_page_valid === false))) $o_minute->SelectOption(date('i', $this->i_timestamp));

			# create label, and add controls
			$o_min_label = new XhtmlElement('label', 'Minutes');
			$o_min_label->AddAttribute('for', $o_minute->GetXhtmlId());
			$o_min_label->SetCssClass('aural');
			$this->AddControl($o_min_label);
			$this->AddControl($o_minute);

			# create dropdown menu for am/pm
			$o_ampm = new XhtmlSelect($this->GetXhtmlId() . '_ampm', '', $this->b_page_valid);
			if (!$this->GetRequireTime()) $o_ampm->AddControl(new XhtmlOption());
			$o_ampm->AddControl(new XhtmlOption('am', 'am'));
			$o_ampm->AddControl(new XhtmlOption('pm', 'pm'));
			if ($this->i_timestamp != null and $this->b_time_known and (!($this->b_page_valid === false))) $o_ampm->SelectOption(date('a', $this->i_timestamp));

			# create label, and add controls
			$o_ampm_label = new XhtmlElement('label', 'AM or PM');
			$o_ampm_label->AddAttribute('for', $o_ampm->GetXhtmlId());
			$o_ampm_label->SetCssClass('aural');
			$this->AddControl($o_ampm_label);
			$this->AddControl($o_ampm);

			$this->AddControl('</span>');
		}
		else
		{
			$o_hour = new TextBox($this->GetXhtmlId() . '_hour');
			$o_hour->SetMode(TextBoxMode::Hidden());
			if ($this->b_time_known) $o_hour->SetText(date('g', $this->i_timestamp));
			$this->AddControl($o_hour);

			$o_minute = new TextBox($this->GetXhtmlId() . '_minute');
			$o_minute->SetMode(TextBoxMode::Hidden());
			if ($this->b_time_known) $o_minute->SetText(date('i', $this->i_timestamp));
			$this->AddControl($o_minute);

			$o_ampm = new TextBox($this->GetXhtmlId() . '_ampm');
			$o_ampm->SetMode(TextBoxMode::Hidden());
			if ($this->b_time_known) $o_ampm->SetText(date('a', $this->i_timestamp));
			$this->AddControl($o_ampm);
		}
	}

	/**
	 * Gets the timestamp posted by a DateControl, with the time adjusted to UTC
	 *
	 * @param string $s_control_id
	 * @return int
	 */
	public static function GetPostedTimestampUtc($s_control_id)
	{
		$i_day = (isset($_POST[$s_control_id . '_day']) and is_numeric($_POST[$s_control_id . '_day'])) ? (int)$_POST[$s_control_id . '_day'] : 1;
		$i_month = (isset($_POST[$s_control_id . '_month']) and is_numeric($_POST[$s_control_id . '_month'])) ? (int)$_POST[$s_control_id . '_month'] : date('n', time()); # date() here is filling in for a missing user-supplied value; user doesn't think in UTC
		$i_year = (isset($_POST[$s_control_id . '_year']) and is_numeric($_POST[$s_control_id . '_year'])) ? (int)$_POST[$s_control_id . '_year'] : date('Y', time()); # date() here is filling in for a missing user-supplied value; user doesn't think in UTC

		if ($i_day == 0 and $i_month == 0 and $i_year == 0) return null; # value not allowed by PHP

		if (isset($_POST[$s_control_id . '_hour']) and is_numeric($_POST[$s_control_id . '_hour']))
		{
			$i_hour = (int)$_POST[$s_control_id . '_hour'];
			if (isset($_POST[$s_control_id . '_ampm']) and $_POST[$s_control_id . '_ampm'] == 'am' and $i_hour == 12) $i_hour = 0;
			if (isset($_POST[$s_control_id . '_ampm']) and $_POST[$s_control_id . '_ampm'] == 'pm' and $i_hour != 12) $i_hour = $i_hour + 12;
		}
		else
		{
			# If the hour isn't known, use midday because middle of day best bet for queries that rely on "is xxx before/after the current date"
			# or similar and can't check whether time is known for each item
			$i_hour = 12;
		}

		$i_minute = (isset($_POST[$s_control_id . '_minute']) and is_numeric($_POST[$s_control_id . '_minute'])) ? (int)$_POST[$s_control_id . '_minute'] : 0;

		// First, assume the user posted UK time, but DST-adjusted time rather than UTC
		$i_uk_timestamp = mktime($i_hour, $i_minute, 0, $i_month, $i_day, $i_year);

		// Get the UTC version of that
		$i_utc_timestamp = gmdate('U', $i_uk_timestamp);

		// But really, they posted their own perception of the DST-adjusted time
		return $i_utc_timestamp;
	}

	/**
	 * Gets whether the posted timestamp includes a complete time
	 *
	 * @param string $s_control_id
	 * @return bool
	 */
	public static function GetIsTimePosted($s_control_id)
	{
		$b_posted = (isset($_POST[$s_control_id . '_hour']) and is_numeric($_POST[$s_control_id . '_hour']));
		$b_posted = ($b_posted and isset($_POST[$s_control_id . '_minute']) and is_numeric($_POST[$s_control_id . '_minute']));
		$b_posted = ($b_posted and isset($_POST[$s_control_id . '_ampm']) and ($_POST[$s_control_id . '_ampm'] == 'am' or $_POST[$s_control_id . '_ampm'] == 'pm'));

		return $b_posted;
	}

}
?>