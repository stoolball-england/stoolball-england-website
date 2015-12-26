<?php
class SearchHighlighter
{
	/**
	 * Highlight terms in text
	 * @param string/string[] $a_terms
	 * @param string $s_text
	 * @return string
	 */
	public static function Highlight($a_terms, $s_text)
	{
		if (is_string($a_terms)) $a_terms[] = $a_terms; # if string supplied, convert to string array
		$s_final_text = ' ' . $s_text; # space added because term not highlighted at start of string
		$s_formatted_text = '';

		if (is_array($a_terms))
		{
			# loop through all search terms
			foreach($a_terms as $s_term)
			{
				# look for an instance of the term in the string
				while(!strpos(strtolower($s_final_text), strtolower($s_term))  === false)
				{
					# note the position of the instance
					$i_pos = strpos(strtolower($s_final_text), strtolower($s_term));

					# ensure we're not within an HTML element
					$s_string_to_here = substr($s_final_text, 0, $i_pos);

					$in_html_tag = (substr_count($s_string_to_here, '<') != substr_count($s_string_to_here, '>')); 
					$in_markup_tag = (substr_count($s_string_to_here, '[') != substr_count($s_string_to_here, ']')); 
					if ($in_html_tag)
					{
						# if we're in a tag, go to the end of the tag
						$i_pos = strpos($s_final_text, '>')+1;
						$s_formatted_text .= substr($s_final_text, 0, $i_pos);
						$s_final_text = substr($s_final_text, $i_pos);
					}
					else if ($in_markup_tag)
					{
						# if we're in a tag, go to the end of the tag
						$i_pos = strpos($s_final_text, ']')+1;
						$s_formatted_text .= substr($s_final_text, 0, $i_pos);
						$s_final_text = substr($s_final_text, $i_pos);
					}
					else
					{
						# surround instance with span tags (note: this way preserves case, unlike search and replace)
						$s_formatted_text .= substr($s_final_text, 0, $i_pos) . '<em class="search-term">' . substr($s_final_text, $i_pos, strlen($s_term)) . '</em>';

						# trim string so that we don't find the same instance again
						$s_final_text = substr($s_final_text, $i_pos + strlen($s_term));
					}
				}

				# re-add remainder of string
				$s_formatted_text .= $s_final_text;

				# reset for next loop
				$s_final_text = $s_formatted_text;
				$s_formatted_text = '';
			}
		}
		return trim($s_final_text); # remove extra space
	}
}
?>