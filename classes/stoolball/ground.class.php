<?php
require_once('person/postal-address.class.php');
require_once('http/has-short-url.interface.php');
require_once('http/short-url.class.php');

/**
 * A stoolball ground
 *
 */
class Ground implements IHasShortUrl
{
	var $o_settings;
	var $i_id;
	private $s_facilities;
	private $s_directions;
	private $s_parking;
	/**
	 * Postal address of the ground
	 *
	 * @var PostalAddress
	 */
	var $o_address;
	private $s_short_url;
	private $i_updated;
	private $teams;
    private $update_search;

	function Ground(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;
		$this->o_address = new PostalAddress();
		$this->teams = new Collection();
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Set the database id of the Ground
	*/
	function SetId($i_input) { $this->i_id = (int)$i_input; }

	/**
	* @return int
	* @desc Get the database id of the Ground
	*/
	function GetId() { return $this->i_id; }

	/**
	* @return string
	* @desc Gets the name of the Ground
	*/
	function GetName()
	{
		if ($this->o_address->GetSaon()) return $this->o_address->GetSaon();
		return $this->o_address->GetPaon();
	}

	/**
	 * Gets the name of the ground and the town it's in
	 *
	 * @return string
	 */
	function GetNameAndTown()
	{
		$s_text = $this->o_address->GetSaon();
		if ($this->o_address->GetPaon())
		{
			if ($s_text) $s_text .= ', ';
			$s_text .= $this->o_address->GetPaon();
		}
		if ($this->o_address->GetTown())
		{
			$s_text .= ', ';
			if ($this->o_address->GetLocality() and strlen($this->o_address->GetTown()) > 5 and substr_compare($this->o_address->GetTown(), 'near ', 0, 5, true) == 0)
			{
				$s_text .= $this->o_address->GetLocality();
			}
			else
			{
				$s_text .= $this->o_address->GetTown();
			}
		}
		return $s_text;
	}

	/**
	* @return void
	* @param PostalAddress $o_address
	* @desc Sets the address of the ground
	*/
	function SetAddress(PostalAddress $o_address) { $this->o_address = $o_address; }

	/**
	* @return PostalAddress
	* @desc Gets the address of the ground
	*/
	function GetAddress() { return $this->o_address; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the directions to the Ground
	*/
	function SetDirections($s_input) { $this->s_directions = trim((string)$s_input); }

	/**
	* @return string
	* @desc Gets the directions to the Ground
	*/
	function GetDirections() { return $this->s_directions; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the parking at the Ground
	*/
	function SetParking($s_input) { $this->s_parking = trim((string)$s_input); }

	/**
	* @return string
	* @desc Gets the parking at the Ground
	*/
	function GetParking() { return $this->s_parking; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the facilities at the Ground
	*/
	function SetFacilities($s_input) { $this->s_facilities = trim((string)$s_input); }

	/**
	* @return string
	* @desc Gets the facilities at the Ground
	*/
	function GetFacilities() { return $this->s_facilities; }

    /**
     * Notes that the object may have changed since the last time it was indexed for search
     */
    public function SetSearchUpdateRequired() 
    {
        $this->update_search = true;
    }
    
    /**
     * Gets whether the object has changed since last time it was indexed for search
     */
    public function GetSearchUpdateRequired() 
    {
        return (bool)$this->update_search;
    }

	/**
	 * Gets the URI which uniquely identifies this ground
	 */
	public function GetLinkedDataUri()
	{
		return "https://www.stoolball.org.uk/id/" . $this->GetShortUrl();
	}

	/**
	* @return string
	* @desc Gets the URL for information about the ground
	*/
	public function GetNavigateUrl()
	{
		# Absolute URL because used as property for linked data
		return "https://" . $this->o_settings->GetDomain() . $this->o_settings->GetClientRoot() . $this->GetShortUrl();
	}

	/**
	* @return string
	* @desc Gets the URL to edit the ground
	*/
	public function GetEditGroundUrl()
	{
		return $this->GetNavigateUrl() . '/edit';
	}

	/**
	* @return string
	* @desc Gets the URL to delete the ground
	*/
	public function GetDeleteGroundUrl()
	{
		return  $this->GetNavigateUrl() . "/delete";
	}

	/**
	 * Sets the short URL for a ground
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = trim((string)$s_url); }

	/**
	 * Gets the short URL for a ground
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->s_short_url; }

	/**
	 * @return string
	 * @desc Gets the URL for viewing ground stats
	 */
	public function GetStatsNavigateUrl()
	{
		return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/statistics';
	}

	/**
	 * Gets a URL for viewing match listings at this ground
	 */
	public function GetMatchesUrl() {
		return $this->GetNavigateUrl() . "/matches";
	}

		/**
	 * Gets the format to use for a ground's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType()
	{
		return new ShortUrlFormat("nsa_ground", 'short_url', array('ground_id'), array('GetId'),
			array('{0}' => '/play/grounds/ground.php?item={0}',
				'{0}/matches' => '/play/matches/matches-at-ground.php?item={0}',
				'{0}/statistics' => '/play/statistics/summary-ground.php?item={0}',
                '{0}/edit' => '/play/grounds/groundedit.php?item={0}',
                '{0}/delete' => '/play/grounds/grounddelete.php?item={0}'
			));
	}

	/**
	 * Gets the format to use for a ground's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Ground::GetShortUrlFormatForType();
	}

	/**
	 * Suggests a short URL to use to view the ground
	 *
	 * @param int $i_preference
	 * @return string
	 */
	public function SuggestShortUrl($i_preference=1)
	{
		$s_url = strtolower(html_entity_decode($this->GetName()));

		# Remove punctuation
		$s_url = preg_replace('/[^a-z ]/i', '', $s_url);

		# Remove noise words
		$s_url = preg_replace(array('/\bstoolball\b/i', '/\bclub\b/i', '/\bplaying\b/i', '/\fields\b/i', '/\bfield\b/i', '/\bground\b/i', '/\bthe\b/i', '/\band\b/i'), '', $s_url);

		# Apply preference
		if ($i_preference == 2)
		{
			# Append locality
			$s_locality = preg_replace('/[^a-z0-9 ]/i', '', strtolower(html_entity_decode($this->GetAddress()->GetLocality())));
			if ($s_locality and stristr($s_url, $s_locality) === false) $s_url .= $s_locality;
		}
		else if ($i_preference == 3)
		{
			# Append town
			$s_town = preg_replace('/[^a-z0-9 ]/i', '', strtolower(html_entity_decode($this->GetAddress()->GetTown())));
			if ($s_town and stristr($s_url, $s_town) === false) $s_url .= $s_town;
		}
		else if ($i_preference > 3)
		{
			$s_url = ShortUrl::SuggestShortUrl($s_url, $i_preference, 3, 'ground');
		}

		# Remove spaces
		$s_url = str_replace(' ', '', $s_url);

		return "ground/$s_url";
	}

	/**
	 * Sets the date the ground info was last updated
	 *
	 * @param int $i_date
	 */
	public function SetDateUpdated($i_date)
	{
		$this->i_updated = (int)$i_date;
	}

	/**
	 * Gets the date the ground info was last updated
	 *
	 * @return int
	 */
	public function GetDateUpdated()
	{
		return $this->i_updated;
	}

	/**
	 * Gets the teams at the ground
	 */
	public function Teams() { return $this->teams; }
}
?>