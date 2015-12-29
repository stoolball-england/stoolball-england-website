<?php 
/**
 * A search term provided from user input
 */
class SearchTerm
{
	private $original_term;
	private $terms;
	private $ignored_words;
	
	public function __construct($search_term='')
	{
		$this->SetTerm($search_term);
	}
	
    /**
     * Sets the search term
     * @param string $search_term
     */
	public function SetTerm($search_term)
	{
		if ($search_term)
		{
			# store original term
			$this->original_term = $search_term;

			# strip non-alphanumeric
			$search_term = preg_replace('/[^\w\s]/', '', $search_term);

			# remove (and store) ignored words
			$search_term = $this->StripIgnoredWords($search_term);

			# store tidied term
			$this->terms = $search_term;
		}
	}
	
    /**
     * Gets the sanitised search term
     */
	public function GetSanitisedTerm()
	{
		return is_array($this->terms) ? join($this->terms, ' ') : false;
	}
	
    /**
     * Gets the sanitised search term as an array of words
     * @return string[]
     */
	public function GetSanitisedTerms()
	{
		return $this->terms;
	}
	
    /**
     * Gets the original, unaltered search term
     */
	public function GetOriginalTerm()
	{
		return $this->original_term;
	}
	
	private function StripIgnoredWords($text)
	{
		$ignored_words = array();
		$keep_words = array();
		
		# split input into words
		$words = explode(' ', $text);
		
		# check each word - is it more than 3 chars?
		foreach ($words as $word)
		{
			if (strlen($word) <= 3)
			{
				if (!in_array($word, $ignored_words)) $ignored_words[] = $word;
			}
			else 
			{
				if (!in_array($word, $keep_words)) $keep_words[] = $word;
			}
		}

		# save and return 2 sets of words
		$this->ignored_words = $ignored_words;

		return $keep_words;
	}
	
    /**
     * Gets the words from the original search term which were ignored
     */
	public function GetIgnoredWords()
	{
		return $this->ignored_words;
	}
}
?>