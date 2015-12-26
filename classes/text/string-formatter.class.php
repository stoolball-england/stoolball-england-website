<?php 
/**
 * Utility methods for working with strings
 *
 */
class StringFormatter
{
	# trim multi-paragraph text to the specified number of paras; default to first para
	public static function TrimToParagraph($s_text, $i_trim=1)
	{
		$s_new_text = '';
		
		if ($s_text)
		{
			$s_text = preg_replace("/\r/", '', $s_text);
			$a_paras = explode("\n\n", $s_text);
			for ($i_count = 0; $i_count < $i_trim; $i_count++) $s_new_text .= $a_paras[$i_count] . "\n\n";
		}
		
		return trim($s_new_text);
	}

	/**
	* @return string
	* @param string $s_text
	* @param int $i_trim
	* @param bool $b_add_xhtml_ellipsis
	* @desc Trims multi-word text to the specified number of words; defaults to first word only
	*/
	public static function TrimToWord($s_text, $i_trim=1, $b_add_xhtml_ellipsis=false)
	{
		$s_new_text = '';
		
		if ($s_text)
		{
			$a_words = explode(' ', $s_text); # split into words
			if ($i_trim >= count($a_words))
			{
				$s_new_text = $s_text;
			}
			else
			{
				for ($i_count = 0; $i_count < $i_trim; $i_count++) $s_new_text .= $a_words[$i_count] . ' ';
				if ($b_add_xhtml_ellipsis) $s_new_text = StringFormatter::AddEllipsis($s_new_text);
			}
		}
		
		return trim($s_new_text);
	}

	/**
	* @return string
	* @param string $s_text
	* @desc Adds an ellipsis to the end of text if appropriate
	*/
	public static function AddEllipsis($s_text)
	{
		if ($s_text)
		{
			$i_last_char_pos = strlen($s_text) -1;
			$i_last_char = substr($s_text, $i_last_char_pos, 1);
			$s_punctuation = '?!';
			
			# if no ellipsis, and not ending in punctuation, add ellipsis
			if (!substr_count($s_punctuation, $i_last_char) and substr($s_text, $i_last_char_pos-2) != '&#8230;')
			{
				$s_text .= '&#8230;';
			}
			# replace trailing fullstops with true ellipsis
			else if (substr($s_text, $i_last_char_pos-2) == '...')
			{
				$s_text = substr($s_text, 0, $i_last_char_pos-2) . '&#8230;';
			}
		}

		return $s_text;
	}
	
	/**
	 * Converts any given string to plain text
	 *
	 * @param string $s_text
	 * @return string
	 */
	public static function PlainText($s_text)
	{
		return html_entity_decode($s_text, ENT_QUOTES);
	}
}
?>