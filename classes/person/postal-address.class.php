<?php
require_once('geo-precision.enum.php');

/**
 * A BS7666-compliant UK postal address
 *
 */
class PostalAddress
{
	private $s_saon;
	private $s_paon;
	private $s_street_descriptor;
	private $s_locality;
	private $s_town;
	private $s_administrative_area;
	private $s_postcode;
	private $f_lat;
	private $f_long;
	private $f_precision;
	
	/**
	* @return PostalAddress 
	* @desc A UK postal address
	*/
	public function __construct()
	{
	}
	
	/**
	 * Gets a concatenated version of the address which can be used for sorting addresses
	 *
	 * @return string
	 */
	public function GenerateSortName()
	{
		return trim(str_replace(' the ', '', strtolower(' ' . $this->GetSaon() . ' ' . $this->GetPaon() . ' ' . $this->GetTown())));
	}

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the secondary addressable object name
	*/
	public function SetSaon($s_input) { $this->s_saon = trim((string)$s_input); }
	
	/**
	* @return string 
	* @desc Gets the secondary addressable object name
	*/
	public function GetSaon() { return $this->s_saon; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the primary addressable object name
	*/
	public function SetPaon($s_input) { $this->s_paon = trim((string)$s_input); }
	
	/**
	* @return string 
	* @desc Gets the primary addressable object name
	*/
	public function GetPaon() { return $this->s_paon; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the street descriptor
	*/
	public function SetStreetDescriptor($s_input) { $this->s_street_descriptor = trim((string)$s_input); }
	
	/**
	* @return string 
	* @desc Gets the street descriptor
	*/
	public function GetStreetDescriptor() { return $this->s_street_descriptor; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the locality
	*/
	public function SetLocality($s_input) { $this->s_locality = trim((string)$s_input); }
	
	/**
	* @return string 
	* @desc Gets the locality
	*/
	public function GetLocality() { return $this->s_locality; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the town in the postal address
	*/
	public function SetTown($s_input) { $this->s_town = trim((string)$s_input); }
	
	/**
	* @return string 
	* @desc Gets the town in the postal address
	*/
	public function GetTown() { return $this->s_town; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the administrative area
	*/
	public function SetAdministrativeArea($s_input) { $this->s_administrative_area = trim((string)$s_input); }
	
	/**
	* @return string 
	* @desc Gets the administrative area
	*/
	public function GetAdministrativeArea() { return $this->s_administrative_area; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the postcode in the postal address
	*/
	public function SetPostcode($s_input) { $this->s_postcode = trim((string)$s_input); }
	
	/**
	* @return string 
	* @desc Gets the postcode in the postal address
	*/
	public function GetPostcode() { return $this->s_postcode; }
	
	/**
	 * Sets the geographic location of the address
	 *
	 * @param int $f_latitude
	 * @param int $f_longitude
	 * @param int $f_precision
	 */
	public function SetGeoLocation($f_latitude, $f_longitude, $f_precision)
	{
		$this->f_lat = floatval($f_latitude);
		$this->f_long = floatval($f_longitude);
		$this->f_precision = floatval($f_precision);
	}
	
	/**
	 * Gets the latitude of the address
	 *
	 * @return float
	 */
	public function GetLatitude() { return $this->f_lat; }
	
	/**
	 * Gets the longitude of the address
	 *
	 * @return float
	 */
	public function GetLongitude() { return $this->f_long; }
	
	/**
	 * Gets how precisely the latitude and longitude are known
	 *
	 * @return float
	 */
	public function GetGeoPrecision() { return $this->f_precision; }
}
?>