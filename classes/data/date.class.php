<?php
class Date
{
	/**
	 * @return string
	 * @param int $i_utc_timestamp
	 * @desc Gets a comma if the date isn't within a week either side of the current date
	 */
	private static function TimeDateSeparator($i_utc_timestamp)
	{
		# Get the current day, the timestamp and the day of the timestamp
		$i_utc_timestamp_day = gmmktime(0, 0, 0, gmdate('m', $i_utc_timestamp), gmdate('d', $i_utc_timestamp), gmdate('Y', $i_utc_timestamp));
        $i_utc_now = gmdate('U');
		$i_utc_today = gmmktime(0, 0, 0, gmdate('m', $i_utc_now), gmdate('d', $i_utc_now), gmdate('Y', $i_utc_now));

		# Handy shortcuts
		$i_one_day = 60 * 60 * 24;
		$i_one_week = $i_one_day * 7;

		# Format the timestamp
		$s_day = '';
		if ($i_utc_timestamp_day == $i_utc_today)
		{
			return ' ';
		}
		else if ($i_utc_timestamp_day == ($i_utc_today - $i_one_day))
		{
			return ' ';
		}
		else if ($i_utc_timestamp_day == ($i_utc_today + $i_one_day))
		{
			return ' ';
		}
		else if ($i_utc_timestamp_day > ($i_utc_today-$i_one_week) and $i_utc_timestamp_day < $i_utc_today)
		{
			return ' ';
		}
		else if ($i_utc_timestamp_day < ($i_utc_today+$i_one_week-$i_one_day) and $i_utc_timestamp_day > $i_utc_today)
		{
			return ' ';
		}
		else
		{
			return ', ';
		}
	}



	/**
	 * @return string
	 * @param int $i_utc_timestamp
	 * @param bool $b_include_day_name
	 * @param Refer to "this Sunday" rather than "Sunday 12 June" $b_relative_date
	 * @param Use Jan, Feb etc rather than January, February etc $b_short_month
	 * @desc Get a date in the format "Sunday 1 January 2000"
	 */
	public static function BritishDate($i_utc_timestamp, $b_include_day_name=true, $b_relative_date=true, $b_abbreviate_where_possible=false)
	{
		# Get the current day, the timestamp and the day of the timestamp
        $i_utc_timestamp_day = gmmktime(0, 0, 0, gmdate('m', $i_utc_timestamp), gmdate('d', $i_utc_timestamp), gmdate('Y', $i_utc_timestamp));
        $i_utc_now = gmdate('U');
		$i_utc_today = gmmktime(0, 0, 0, gmdate('m', $i_utc_now), gmdate('d', $i_utc_now), gmdate('Y', $i_utc_now));

		# Handy shortcuts
		$i_one_day = 60 * 60 * 24;
		$i_one_week = $i_one_day * 7;

		# Format the timestamp according to the user's perception of when it occurred
		$s_day = '';
		if ($i_utc_timestamp_day == $i_utc_today)
		{
			$s_day = 'today';
		}
		else if ($i_utc_timestamp_day == ($i_utc_today - $i_one_day))
		{
			$s_day = 'yesterday';
		}
		else if ($i_utc_timestamp_day == ($i_utc_today + $i_one_day))
		{
			$s_day = 'tomorrow';
		}
		else if ($i_utc_timestamp_day > ($i_utc_today-$i_one_week) and $i_utc_timestamp_day < $i_utc_today)
		{
			$s_day = 'last ' . date($b_abbreviate_where_possible ? 'D' : 'l', $i_utc_timestamp);
		}
		else if ($i_utc_timestamp_day < ($i_utc_today+$i_one_week-$i_one_day) and $i_utc_timestamp_day > $i_utc_today)
		{
			$s_day = 'this ' . date($b_abbreviate_where_possible ? 'D' : 'l', $i_utc_timestamp);
		}

		# If that didn't match anything, or if caller specifically asked not to get a relative date, use an absolute date format
		if (!$s_day or !$b_relative_date)
		{
			$format = '';
			if ($b_include_day_name) $format .= ($b_abbreviate_where_possible ? 'D ' : 'l ');
			if ($b_abbreviate_where_possible)
			{
				$format .= 'j M y';
			}
			else
			{
				$format .= 'j F Y';
			}

			$s_day = date($format, $i_utc_timestamp);
			if ($b_abbreviate_where_possible or $b_relative_date)
			{
				# try to lop off the current year
				if ($b_abbreviate_where_possible)
				{
					$pos =strlen($s_day)-2;
					$year = date('y');
				}
				else
				{
					$pos =strlen($s_day)-4;
					$year = date('Y');
				}
				if (substr($s_day, $pos) == $year) $s_day = substr($s_day, 0, $pos-1);
			}
		}
		return $s_day;
	}

	/**
	 * @return string
	 * @param int $i_utc_timestamp
	 * @param bool $b_include_day_name
	 * @param Refer to "this Sunday" rather than "Sunday 12 June" $b_relative_date
	 * @param Use Jan, Feb etc rather than January, February etc $b_short_month
	 * @desc Get a date in the format "10am, Sunday 1 January 2000"
	 */
	public static function BritishDateAndTime($i_utc_timestamp, $b_include_day_name=true, $b_relative_date=true, $b_short_month=false)
	{
		return Date::Time($i_utc_timestamp) . Date::TimeDateSeparator($i_utc_timestamp) . Date::BritishDate($i_utc_timestamp, $b_include_day_name, $b_relative_date, $b_short_month);
	}

	/**
	 * @return string
	 * @param int $i_utc_timestamp
	 * @desc Get a date in the format "January 2005"
	 */
	public static function MonthAndYear($i_utc_timestamp)
	{
		return date('F Y', $i_utc_timestamp);
	}

	/**
	 * @return string
	 * @param int $i_utc_timestamp
	 * @desc Get a date in the format "2005"
	 */
	public static function Year($i_utc_timestamp)
	{
		return date('Y', $i_utc_timestamp);
	}

	/**
	 * Gets the time in the format 10am or 10.01am
	 *
	 * @param int $i_utc_timestamp
	 * @return string
	 */
	public static function Time($i_utc_timestamp)
	{
		$s_time = date('g.ia', $i_utc_timestamp);
		$s_time = str_replace('.00', '', $s_time);
		if ($s_time == '12pm') $s_time = 'Midday';
		else if ($s_time == '12am') $s_time = 'Midnight';
		return $s_time;
	}

	/**
	 * Gets a date in ISO8601 (RFC3339) format from a UTC date, as used by microformats (see http://www.faqs.org/rfcs/rfc3339.html)
	 *
	 * @param int $i_timestamp
	 */
	public static function Microformat($i_utc_timestamp=0)
	{
		if (!$i_utc_timestamp) $i_utc_timestamp = gmdate('U'); # UTC now
		return gmdate('c', $i_utc_timestamp);
	}
}
?>