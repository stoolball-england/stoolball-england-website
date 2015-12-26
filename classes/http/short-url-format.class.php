<?php
/**
 * Information on how to get and format a short URL, for use with ShortUrlManager
 *
 */
class ShortUrlFormat
{
	private $s_table;
	private $s_short_url_field;
	private $a_parameter_fields;
	private $a_parameter_properties;
	private $a_destination_urls;
	
	/**
	 * Instantiates a ShortUrlFormat
	 *
	 * @param string $s_table
	 * @param string $s_short_url_field
	 * @param string[] $a_parameter_fields
	 * @param string[] $a_parameter_properties
	 * @param string[] $a_destination_url (key is destination URL, value is prefix/suffix for short URL)
	 */
	public function __construct($s_table, $s_short_url_field, $a_parameter_fields, $a_parameter_properties, $a_destination_urls)
	{
		# Check input
		if (!strlen($s_table)) throw new Exception('ShortUrlFormat needs a table name');
		if (!strlen($s_short_url_field)) throw new Exception('ShortUrlFormat needs a short URL field name');
		if (!is_array($a_parameter_fields)) throw new Exception('ShortUrlFormat needs a list of fields to be used as URL parameters');
		if (!is_array($a_parameter_properties)) throw new Exception('ShortUrlFormat needs a list of object properties to be used as URL parameters');
		if (!is_array($a_destination_urls)) throw new Exception('ShortUrlFormat needs at least one destination URL format');

		$this->s_table = (string)$s_table;
		$this->s_short_url_field = (string)$s_short_url_field;
		$this->a_parameter_fields = $a_parameter_fields;
		$this->a_parameter_properties = $a_parameter_properties;
		$this->a_destination_urls = $a_destination_urls;
	}
	
	/**
	 * Gets the name of the table to read short URLs from
	 *
	 * @return string
	 */
	public function GetTable() { return $this->s_table; }
	
	/**
	 * Gets the name of the field containing the short URLs
	 *
	 * @return string
	 */
	public function GetShortUrlField() { return $this->s_short_url_field; }
	
	/**
	 * Gets the names of the fields to be used as parameters in the destination URL
	 *
	 * @return string[]
	 */
	public function GetParameterFields() { return $this->a_parameter_fields; }
	
	/**
	 * Gets the names of the object properties to be used as parameters in the destination URL
	 *
	 * @return string[]
	 */
	public function GetParameterProperties() { return $this->a_parameter_properties; }

	/**
	 * Gets the destination URLs, with parameters indicated by {0}, {1}, {2} ... 
	 *
	 * @return string[] (key is destination URL, value is qualifier for short URL)
	 */
	public function GetDestinationUrls() { return $this->a_destination_urls; }

}
?>