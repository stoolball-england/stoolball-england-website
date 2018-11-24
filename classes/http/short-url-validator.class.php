<?php
require_once('data/validation/data-validator.class.php');
require_once('short-url-manager.class.php');

class ShortUrlValidator extends DataValidator
{
	/**
	 * Object with short URL
	 * @var IHasShortUrl
	 */
	private $object_with_short_url;
	
	/**
	* @return DataValidator
	* @param array $a_keys
	* @param string $s_message
	* @param int $i_mode
	* @param IHasShortUrl $object_with_short_url
	* @desc Creates a new ShortUrlValidator
	*/
	public function __construct($a_keys, $s_message, $i_mode, IHasShortUrl $object_with_short_url)
	{
		parent::__construct($a_keys, $s_message, $i_mode);
		$this->object_with_short_url = $object_with_short_url;
	}
	
	/**
	 * Gets whether the validator requires a SiteSettings object to work
	 * @return bool
	 */
	public function RequiresSettings() { return true; }
	
	/**
	 * Gets whether the validator requires a data connection to work
	 * @return bool
	 */
	public function RequiresDataConnection() { return true; }
	
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a short URL is already taken by another page
	*/
	public function Test($s_input, $a_keys) 
	{
		$short_url = new ShortUrl($s_input);
		$short_url->SetFormat($this->object_with_short_url->GetShortUrlFormat());
		$short_url->SetParameterValuesFromObject($this->object_with_short_url);
		
		$manager = new ShortUrlManager($this->GetSiteSettings(), $this->GetDataConnection());
		$taken = $manager->IsUrlTaken($short_url);
		unset($manager);
		
		return !$taken; 
	}
}
?>