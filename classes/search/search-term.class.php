<?php 
class SearchTerm
{
	var $s_original_term;
	var $a_terms;
	var $a_ignored_words;
	
	function SearchTerm($s_input='')
	{
		$this->SetTerm($s_input);
	}
	
	function SetTerm($s_input)
	{
		if ($s_input)
		{
			# store original term
			$this->s_original_term = $s_input;

			# strip non-alphanumeric
			$s_input = preg_replace('/[^\w\s]/', '', $s_input);

			# remove (and store) ignored words
			$a_input = $this->StripIgnoredWords($s_input);

			# store tidied term
			$this->a_terms = $a_input;
		}
	}
	
	function GetTerm()
	{
		return is_array($this->a_terms) ? join($this->a_terms, ' ') : false;
	}
	
	function GetTerms()
	{
		return $this->a_terms;
	}
	
	function GetOriginalTerm()
	{
		return $this->s_original_term;
	}
	
	function StripIgnoredWords($s_text)
	{
		$a_ignored_words = array();
		$a_keep_words = array();
		
		# split input into words
		$a_words = explode(' ', $s_text);
		
		# check each word - is it more than 3 chars?
		foreach ($a_words as $s_word)
		{
			if (strlen($s_word) <= 3)
			{
				if (!in_array($s_word, $a_ignored_words)) $a_ignored_words[] = $s_word;
			}
			else 
			{
				if (!in_array($s_word, $a_keep_words)) $a_keep_words[] = $s_word;
			}
		}

		# save and return 2 sets of words
		$this->a_ignored_words = $a_ignored_words;

		return $a_keep_words;
	}
	
	function GetIgnoredWords()
	{
		return $this->a_ignored_words;
	}
	
	function GetIgnoredWordsCount()
	{
		return count($this->a_ignored_words);
	}
}
?>