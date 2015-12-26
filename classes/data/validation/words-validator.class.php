<?php
require_once('data/validation/data-validator.class.php');

class WordsValidator extends DataValidator
{
	private $i_minlength;
	private $i_maxlength;
	
	/**
	* @return WordsValidator
	* @param array $a_keys
	* @param string $s_message
	* @param int $i_minlength Minimum number of words, below which the field(s) will not validate
	* @param int $i_maxlength Maximum number of words, above which the field(s) will not validate
	* @param int $i_mode
	* @desc Constructor for all validators - can be used as inherited constructor
	*/
	public function __construct($a_keys, $s_message, $i_minlength, $i_maxlength, $i_mode=null)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);
		$this->i_minlength = (int)$i_minlength;
		$this->i_maxlength = ($i_maxlength == null) ? 10000000 : (int)$i_maxlength;
	}
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given string less than or equal to a given number of words
	*/
	function Test($s_input, $a_keys) 
	{
		// $words = str_word_count($s_input); For "Word count of 5 words" str_word_count returns 4, because 5 isn't considered a word
		
		$normalised = preg_replace('/\s+/i', ' ', trim($s_input));
		$words = count(explode(' ', $normalised)); # Count the spaces instead
		return (($words >= $this->i_minlength) and ($words <= $this->i_maxlength)); 
	}
}
?>