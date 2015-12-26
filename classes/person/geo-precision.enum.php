<?php
/**
 * Enum type describing how precisely a geographic location is known
 *
 */
class GeoPrecision
{
	private function __construct() { die('GeoPrecision is an enum type'); }
	
	/**
	 * The geo location is not known
	 *
	 * @return int
	 */
	public static function Unknown() { return 0; }
	
	/**
	 * The location is known to be correct
	 *
	 * @return int
	 */
	public static function Exact() { return 1; }
	
	/**
	 * The location identifies the centre of the nearest postcode
	 *
	 * @return int
	 */
	public static function Postcode() { return 2; }
	
	/**
	 * The location identifies the centre of the correct street
	 *
	 * @return int
	 */
	public static function StreetDescriptor() { return 3; }
	
	/**
	 * The location identifies the centre of the correct town or village
	 *
	 * @return int
	 */
	public static function Town() { return 4; } 
}
?>