<?php
require_once('collection.class.php');
require_once('team.class.php');
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
	private $o_settings;
	private $s_short_url;
    private $twitter;

	/**
	 * Instantiates a Club
	 *
	 * @param SiteSettings $o_settings
	 */
	public function __construct(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;
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
	* @return string
	* @desc Gets the URL for the club
	*/
	public function GetNavigateUrl()
	{
		$s_url = '';
		if ($this->GetShortUrl())
		{
			$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl();
		}
		else
		{
			$s_url = $this->GetRealUrl();
		}

		return $s_url;
	}
	
	/**
	* @return string
	* @desc Gets the URL to edit the club
	*/
	public function GetEditClubUrl()
	{
		return $this->o_settings->GetUrl('AdminClubEdit') . $this->GetId();
	}

	/**
	* @return string
	* @desc Gets the URL to delete the club
	*/
	public function GetDeleteClubUrl()
	{
		return "/play/clubdelete.php?item=" . $this->GetId();
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
	 * Gets the real URL for a team
	 *
	 * @return string
	 */
	public function GetRealUrl()
	{
		$s_url = $this->o_settings->GetUrl('Club') . $this->GetId();
		if (isset($_GET['tempus'])) $s_url .= '&amp;tempus=fixit';
		return $s_url;
	}

	/**
	 * Gets the format to use for a club's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings)
	{
		return new ShortUrlFormat($settings->GetTable('Club'), 'short_url', array('club_id'), array('GetId'), array('{0}' => $settings->GetUrl('Club') . '{0}'));
	}

	/**
	 * Gets the format to use for a club's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Club::GetShortUrlFormatForType($this->o_settings);
	}

	/**
	 * Suggests a short URL to use to view the club
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
		$s_url = preg_replace(array('/\bstoolball\b/i', '/\bclub\b/i', '/\bladies\b/i', '/\bmixed\b/i', '/\bsports\b/i', '/\bthe\b/i', '/\band\b/i'), '', $s_url);

		# Apply preference
		if ($i_preference > 1)
		{
			$s_url = ShortUrl::SuggestShortUrl($s_url, $i_preference, 1, 'club');
		}

		# Remove spaces
		$s_url = str_replace(' ', '', $s_url);

		return $s_url;
	}
}
?>