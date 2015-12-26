<?php
/**
 * A short URL such as mydomain.com/shorturl, and its redirection destination
 *
 */
class ShortUrl
{
	private $s_short_url;
	private $a_parameter_values;

	/**
	 * Format of short URL
	 *
	 * @var ShortUrlFormat
	 */
	private $format;

	/**
	 * Instantiates a ShortUrl
	 *
	 * @param string $s_short_url
	 */
	public function __construct($s_short_url='')
	{
		$this->SetShortUrl($s_short_url);
	}

	/**
	 * Gets the format of the short URL
	 *
	 * @param ShortUrlFormat $format
	 */
	public function SetFormat(ShortUrlFormat $format) { $this->format = $format; }

	/**
	 * Sets the format of the short URL
	 *
	 * @return ShortUrlFormat
	 */
	public function GetFormat() { return $this->format; }

	/**
	 * Sets the short URL which follows the host and trailing forward slash
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = (string)$s_url; }

	/**
	 * Gets the short URL which follows the host and trailing forward slash
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->s_short_url; }


	/**
	 * Sets the values which should be substituted into the destination URL by ApplyParameters()
	 *
	 * @param string[] $a_values
	 */
	public function SetParameterValues($a_values) { $this->a_parameter_values = $a_values; }

	/**
	 * Gets the values which should be substituted into the destination URL by ApplyParameters()
	 *
	 * @return string[]
	 */
	public function GetParameterValues() { return $this->a_parameter_values; }

	/**
	 * Sets the values which should be substituted into the destination URL by ApplyParameters() by reading from the supplied object's properties
	 *
	 * @param IHasShortUrl $object
	 */
	public function SetParameterValuesFromObject(IHasShortUrl $object)
	{
		$a_param_values = array();
		foreach ($this->GetFormat()->GetParameterProperties() as $s_param_property)
		{
			$s_param_property = (string)$s_param_property;
			if (method_exists($object, $s_param_property)) $a_param_values[] = $object->$s_param_property();
		}
		$this->SetParameterValues($a_param_values);
	}

	/**
	 * Substitutes parameter values into destination URL
	 * 
	 * @param $s_destination_url: Destination URL with markers for parameters 
	 * @return string: Destination URL with markers replaced with actual data
	 */
	public function ApplyParameters($s_destination_url)
	{
		if (is_array($this->a_parameter_values) and strlen($s_destination_url))
		{
			foreach ($this->a_parameter_values as $i_key => $s_value)
			{
				$s_destination_url = str_replace('{' . $i_key . '}', $s_value, $s_destination_url);
			}
		}
		return $s_destination_url;
	}

	/**
	 * Helper function to suggest short URLs
	 *
	 * @param string $s_url
	 * @param int $i_preference
	 * @param int $i_already_tried
	 * @param string $s_suffix
	 * @param Use hyphens instead of removing spaces $b_use_hyphens 
	 * @return string
	 */
	public static function SuggestShortUrl($s_url, $i_preference=1, $i_already_tried=0, $s_suffix='', $b_use_hyphens=false)
	{
		# Count words and lop off until only one left
		$s_url = trim(preg_replace('/ {2,}/', ' ', $s_url)); # collapse spaces
		$a_words = explode(' ', $s_url);
		$i_words = count($a_words);
		$i_prev_options = $i_already_tried;
		$i_remove_words = $i_preference-$i_prev_options;
		$i_words_to_keep = $i_words-$i_remove_words;
		if ($i_words_to_keep > 0)
		{
			for ($i = $i_words-1; $i+1 > $i_words_to_keep; $i--) array_pop($a_words);

			$s_url = implode(' ', $a_words);
		}
		else
		{
			# If that didn't work, append suffix to 1 word, 2 words etc
			$i_prev_options = $i_prev_options + $i_words -1;
			$i_add_suffix_to_words = $i_preference-$i_prev_options;
			if ($i_add_suffix_to_words <= $i_words)
			{
				for ($i = $i_words-1; $i >= $i_add_suffix_to_words; $i--) array_pop($a_words);

				$s_url = implode(' ', $a_words) . $s_suffix;
			}
			else
			{
				# Last resort: Add a number (taking away number of options tried)
				$i_prev_options += $i_words;
				$s_url .= $i_preference-$i_prev_options;
			}
		}

		return str_replace(' ', $b_use_hyphens ? '-' : '', $s_url);
	}}
?>