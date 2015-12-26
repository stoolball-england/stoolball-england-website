<?php
class BadLanguageFilter
{
	var $s_replacement;
	
	function &BadLanguageFilter($s_replacement='@*%!')
	{
		$this->s_replacement = (string)$s_replacement;
		return $this;
	}
	
	/**
	* @return string
	* @param string $s_text
	* @desc Replace banned words with sanitised text
	*/
	function Filter($s_text)
	{
		$a_bad_words = array();
		$a_replacement = array();
		
		$s_replacement_lower = strtolower($this->s_replacement);
		$s_replacement_title = ucfirst($s_replacement_lower);
		$s_replacement_upper = strtoupper($this->s_replacement);
		
		$a_bad_words[] = '/(\b)(fuck|cunt)/';
		$a_replacement[] = '$1' . $s_replacement_lower;
		
		$a_bad_words[] = '/(\b)(FUCK|CUNT)/';
		$a_replacement[] = '$1' . $s_replacement_upper;
		
		$a_bad_words[] = '/(\b)(Fuck|Cunt)/';
		$a_replacement[] = '$1' . $s_replacement_title;
		
		/* different kind of bad language! */
		/* Ought to be some kind of callback for proper OO */
		$a_bad_words[] = '/(\b)(S|s)meg(ing|er)/';
		$a_replacement[] = '$1' . '$2megg$3';
		
		$a_bad_words[] = '/(\b)SMEG(ING|ER)/';
		$a_replacement[] = '$1' . 'SMEGG$2';
		/* End callback */

		return preg_replace($a_bad_words, $a_replacement, $s_text);
	}
}
?>