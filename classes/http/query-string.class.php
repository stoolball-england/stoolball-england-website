<?php 
class QueryStringBuilder
{
	/**
	* @return string
	* @param parameter name to remove $s_param
	* @param query string from which to remove parameter $s_qs
	* @desc Remove all instances of the specified parameter from the query string
	*/
	public static function RemoveParameter($s_param, $s_qs='')
	{
		if (!$s_qs) $s_qs = $_SERVER['QUERY_STRING'];
		$s_text = '';

		# strip start/end semantics 'cos we add them later
		if (substr($s_qs, 0, 1) == '?') $s_qs = substr($s_qs, 1);
		if (substr($s_qs, strlen($s_qs)-1, 1) == '&') $s_qs = substr($s_qs, 0, strlen($s_qs)-1);

		$a_params = preg_split('/&/', $s_qs);

		if (is_array($a_params))
		{
			foreach ($a_params as $s_param_bit)
			{
				if (substr($s_param_bit,0,4) == 'amp;') $s_param_bit = substr($s_param_bit,0,4);
				$a_param_bits = preg_split('/=/', $s_param_bit);

				if (is_array($a_param_bits) and count($a_param_bits) > 1)
				{
					if ((strtolower($a_param_bits[0]) != strtolower($s_param)) and ($a_param_bits[0]))
					{
						$s_text .= strtolower($a_param_bits[0]) . '=' . $a_param_bits[1] . '&';
					}
				}
			}
		}

		return '?' . $s_text;
	}

	/**
	* @return string[]
	* @param string $s_requested_param
	* @param string $s_qs
	* @desc If param specified in query string multiple times, $_GET only stores the last. This returns all values.
	*/
	function GetParameterValues($s_requested_param, $s_qs='')
	{
		if (!$s_qs) $s_qs = $_SERVER['QUERY_STRING'];

		$a_params = preg_split('/&/', $s_qs);
		$a_result = null;

		if (is_array($a_params))
		{
			foreach($a_params as $s_param)
			{
				if ($s_param)
				{
					$a_param = preg_split('/=/', $s_param);

					if (is_array($a_param))
					{
						if (strtolower($a_param[0]) == strtolower($s_requested_param))
						{
							if (is_null($a_result)) $a_result = array();
							$a_result[] = $a_param[1];
						}
					}
				}
			}
		}

		return $a_result;
	}

	/**
	* @return string
	* @desc Build a query string from all the data in the $_POST array
	*/
	function GetPostData()
	{
		# converts all data in a $_POST array to a query string
		$a_keys = array_keys($_POST);

		$s_text = '';

		if (count($a_keys) > 0)
		{
			for ($i_count = 0; $i_count < count($a_keys); $i_count++)
			{
				$s_text .= $a_keys[$i_count] . '=' . $_POST[$a_keys[$i_count]];
				if ($i_count < count($a_keys)-1) $s_text .= '&';
			}
		}

		return $s_text;
	}
}
?>