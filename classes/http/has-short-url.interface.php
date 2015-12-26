<?php
/**
 * Interface for an object which has a view accessed using a short URL
 *
 */
interface IHasShortUrl
{
	/**
	 * Suggest a suitable short URL based on the properties of the object
	 *
	 * @param int $i_preference
	 */
	public function SuggestShortUrl($i_preference=1);
	
	/**
	 * Sets the short URL for an object
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url);
	
	/**
	 * Gets the short URL for an object
	 *
	 */
	public function GetShortUrl();
	
	/**
	 * Gets the format to use for an object's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat();

	/**
	 * Gets the format to use for an object's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings);
}
?>