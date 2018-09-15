<?php
require_once('collection.class.php');
require_once('stoolball/team.class.php');
require_once('http/has-short-url.interface.php');
require_once('http/short-url.class.php');

/**
 * A stoolball club, comprised of multiple stoolball teams
 *
 */
class Club extends Collection implements IHasShortUrl
{
	private $i_id;
	private $s_name;
	private $settings;
	private $s_short_url;
    private $how_many_players;
    private $age_range_lower;
    private $age_range_upper;
    private $twitter;
    private $facebook;
    private $instagram;
    private $clubmark = false;
    private $club_type;
    private $plays_outdoors;
    private $plays_indoors;

	/**
	 * Instantiates a Club
	 *
	 * @param SiteSettings $settings
	 */
	public function __construct(SiteSettings $settings)
	{
		$this->settings = $settings;
		$this->s_item_class = 'Team';
	}

	/**
	 * Sets the database id of the stoolball club
	 *
	 * @param int $i_input
	 */
	public function SetId($i_input) { $this->i_id = (int)$i_input; }

	/**
	* @return int
	* @desc Get the database id of the stoolball club
	*/
	public function GetId() { return $this->i_id; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the name of the stoolball club
	*/
	public function SetName($s_input) { $this->s_name = trim((string)$s_input); }
	/**
	* @return string
	* @desc Gets the name of the stoolball club
	*/
	public function GetName() { return $this->s_name; }
    
    /**
     * Sets the type of club this is: an independent club or a school
     */
    public function SetTypeOfClub($club_type) { $this->club_type = (int)$club_type; }
    
    /**
     * Gets the type of club this is: an independent club or a school
     */
    public function GetTypeOfClub() { return $this->club_type; }

    /**
    * @return void
    * @param int $how_many_players
    * Sets an estimate of how many active players the club has
    */
    public function SetHowManyPlayers($how_many_players) 
    {
        if (!$how_many_players) {
            $this->how_many_players = null;
        }
        else $this->how_many_players = (int)$how_many_players;
    }
    
    /**
    * @return int
    * @desc Gets an estimate of how many active players the club has
    */
    public function GetHowManyPlayers() { return $this->how_many_players; }

    /**
    * @return void
    * @param int $age
    * Sets the lower end of the age range for club members
    */
    public function SetAgeRangeLower($age) 
    {
        if (!$age) {
            $this->age_range_lower = null;
        }
        else $this->age_range_lower = (int)$age;
    }
    
    /**
    * @return int
    * @desc Gets the lower end of the age range for club members
    */
    public function GetAgeRangeLower() { return $this->age_range_lower; }
    
    /**
    * @return void
    * @param int $age
    * Sets the upper end of the age range for club members
    */
    public function SetAgeRangeUpper($age) 
    {
        if (!$age) {
            $this->age_range_upper = null;
        }
        else $this->age_range_upper = (int)$age;
    }
    
    /**
    * @return int
    * @desc Gets the upper end of the age range for club members
    */
    public function GetAgeRangeUpper() { return $this->age_range_upper; }

    /**
    * @return void
    * @param string $twitter
    * Sets the twitter username of the stoolball club
    */
    public function SetTwitterAccount($twitter) 
    {
        $this->twitter = trim((string)$twitter, ' @');
        if ($this->twitter) $this->twitter = "@" . $this->twitter;
    }
    
    /**
    * @return string
    * @desc Gets the twitter account of the stoolball club
    */
    public function GetTwitterAccount() { return $this->twitter; }

    /**
    * @return void
    * @param string $facebook
    * Sets the Facebook URL of the stoolball club
    */
    public function SetFacebookUrl($facebook) 
    {
        $this->facebook = (string)$facebook;
    }
    
    /**
    * @return string
    * @desc Gets the Facebook URL of the stoolball club
    */
    public function GetFacebookUrl() { return $this->facebook; }

    /**
    * @return void
    * @param string $instagram
    * Sets the instagram username of the stoolball club
    */
    public function SetInstagramAccount($instagram) 
    {
        $this->instagram = trim((string)$instagram, ' @');
        if ($this->instagram) $this->instagram = "@" . $this->instagram;
    }
        
    /**
    * @return string
    * @desc Gets the instagram account of the stoolball club
    */
    public function GetInstagramAccount() { return $this->instagram; }

    /**
     * Sets whether the club plays outdoor stoolball
     * @param bool? $plays_outdoors
     */
    public function SetPlaysOutdoors($plays_outdoors) {
        if (is_null($plays_outdoors)) 
        {
            $this->plays_outdoors = null;
        }
        else $this->plays_outdoors = (bool)$plays_outdoors;
    }
    
    /**
     * Gets whether the club plays outdoor stoolball
     */
    public function GetPlaysOutdoors() {
        return $this->plays_outdoors;
    }
    
    /**
     * Sets whether the club plays indoor stoolball
     * @param bool? $plays_indoors
     */
    public function SetPlaysIndoors($plays_indoors) {
        if (is_null($plays_indoors)) 
        {
            $this->plays_indoors = null;
        }
        else $this->plays_indoors = (bool)$plays_indoors;
    }
    
    /**
     * Gets whether the club plays indoor stoolball
     */
    public function GetPlaysIndoors() {
        return $this->plays_indoors;
    }
        
    /**
     * Sets whether the club is Clubmark accredited
     */
    public function SetClubmarkAccredited($accredited) {
        $this->clubmark = (bool)$accredited;
    }
    
    /**
     * Gets whether the club is Clubmark accredited
     */
    public function GetClubmarkAccredited() {
        return $this->clubmark;
    }
    
    
	/**
	* @return string
	* @desc Gets the URL for the club
	*/
	public function GetNavigateUrl()
	{
        return $this->settings->GetClientRoot() . $this->GetShortUrl();
	}
	
	/**
	* @return string
	* @desc Gets the URL to edit the club
	*/
	public function GetEditClubUrl()
	{
		return $this->GetNavigateUrl() . "/edit";
	}

	/**
	* @return string
	* @desc Gets the URL to delete the club
	*/
	public function GetDeleteClubUrl()
	{
		return $this->GetNavigateUrl() . "/delete";
	}
    
    /**
     * @return string
     * @desc Gets the URL for an RSS feed of matches
     */
    public function GetMatchesRssUrl()
    {
        return $this->settings->GetClientRoot() . $this->GetShortUrl() . '/matches.rss';
    }
    
	/**
	 * Sets the short URL for a club
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = trim((string)$s_url); }

	/**
	 * Gets the short URL for a club
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->s_short_url; }

	/**
	 * Gets the format to use for a club's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType()
	{
        return new ShortUrlFormat("nsa_club", 'short_url', array('club_id'), array('GetId'),
        array(
        '{0}' => '/play/clubs/club.php?item={0}',
        '{0}/edit' => "/play/clubs/clubedit.php?item={0}",
        '{0}/delete' => "/play/clubs/clubdelete.php?item={0}", 
        '{0}/edit-contacts' => "/play/schools/edit-contacts.php?item={0}",
        '{0}/matches.rss' => '/play/matches/matches-rss.php?club={0}',
        ));
	}

	/**
	 * Gets the format to use for a club's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Club::GetShortUrlFormatForType();
	}

	/**
	 * Suggests a short URL to use to view the club
	 *
	 * @param int $i_preference
	 * @return string
	 */
	public function SuggestShortUrl($i_preference=1)
	{
		$url = strtolower(html_entity_decode($this->GetName()));

		# Remove punctuation
		$url = preg_replace('/[^a-z ]/i', '', $url);

		# Remove noise words
		$url = trim(preg_replace(array('/\bstoolball\b/i', '/\bclub\b/i', '/\bladies\b/i', '/\bmixed\b/i', '/\bsports\b/i'), '', $url));

        if ($this->GetTypeOfClub() == Club::SCHOOL) {
            $url = "school/$url";
            $suffix_if_duplicate = 'school';
        }
        else {
            $suffix_if_duplicate = 'club';
        }

		# Apply preference
		if ($i_preference > 1)
		{
			$url = ShortUrl::SuggestShortUrl($url, $i_preference, 1, $suffix_if_duplicate);
		}

		# Remove spaces
		$url = str_replace(' ', '-', $url);

		return $url;
	}
    
    /**
     * This club is an independent multi-sport or stoolball club
     */
    const STOOLBALL_CLUB = 1;
    
    /**
     * This club represents a school
     */
    const SCHOOL = 2;
}
?>