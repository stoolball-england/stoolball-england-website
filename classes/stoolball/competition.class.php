<?php
require_once('stoolball/season.class.php');
require_once('stoolball/team.class.php');
require_once('category/category.class.php');
require_once('collection.class.php');
require_once('http/has-short-url.interface.php');
require_once('http/short-url.class.php');
require_once 'stoolball/player-type.enum.php';

class Competition extends Collection implements IHasShortUrl
{
	var $o_settings;
	var $i_id;
	var $s_name;
	var $s_contact;
	private $s_website;
	private $player_type;
	private $s_intro;
	private $i_working_index;
	private $s_notification_email;
	private $s_short_url;
	private $b_active = true;
	private $category;
	private $players_per_team = 11;
	private $overs = 16;
    private $update_search;

	/**
	 * An ongoing competition such as a league or cup
	 *
	 * @param SiteSettings $o_settings
	 * @return Competition
	 */
	function Competition(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;

		parent::Collection();
		$this->s_item_class = 'Season';
		$this->SetWorkingIndex(-1);
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database id of the competition
	*/
	function SetId($i_input) { $this->i_id = (int)$i_input; }

	/**
	* @return int
	* @desc Gets the unique database id of the competition
	*/
	function GetId() { return $this->i_id; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the name of the competition - eg XYZ League, ABC Tournament
	*/
	function SetName($s_input) { $this->s_name = trim((string)$s_input); }

	/**
	* @return string
	* @desc Gets the name of the competition - eg XYZ League, ABC Tournament
	*/
	function GetName() { return $this->s_name; }

	/**
	* @return string
	* @desc Gets the name and player type of the competition - eg XYZ League (Ladies), ABC Mixed Tournament
	*/
	function GetNameAndType()
	{
		$s_type = PlayerType::Text($this->GetPlayerType());

		if (stristr(str_replace("'", '', $this->s_name), str_replace("'", '', $s_type)) === false)
		{
			return $this->s_name . ' (' . $s_type . ')';
		}
		else
		{
			return $this->s_name;
		}
	}

	/**
	* @return void
	* @param bool $b_active
	* @desc Sets whether the competition is still running
	*/
	function SetIsActive($b_active)
	{
		$this->b_active = (bool)$b_active;
	}

	/**
	* @return bool
	* @desc Gets whether the competition is still running
	*/
	function GetIsActive()
	{
		return $this->b_active;
	}

	/**
	 * Sets the category the competition is in
	 *
	 * @param Category $category
	 */
	public function SetCategory(Category $category)
	{
		$this->category = $category;
	}

	/**
	 * Gets the category the competition is in
	 *
	 * @return Category
	 */
	public function GetCategory() { return $this->category; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the intro to the competition
	*/
	function SetIntro($s_input) { $this->s_intro = trim((string)$s_input); }

	/**
	* @return string
	* @desc Gets the intro to the competition
	*/
	function GetIntro() { return $this->s_intro; }

	/**
	 * Gets text to use as the description of this competition in search results
	 */
	public function GetSearchDescription() 
	{
		$description = "A stoolball competition";
		$season = $this->GetLatestSeason();
		$teams = $season->GetTeams();
		$teams_count = count($teams);
		if ($teams_count)
		{
			$description .= " played by ";
			if ($teams_count > 10)
			{
				$description .= "teams including ";
				$teams_count = 10;
			} 
			for ($i = 0; $i < $teams_count; $i++) 
			{
				$description .= $teams[$i]->GetName();
				if ($i < ($teams_count-2)) $description .= ", ";
				if ($i == ($teams_count-2)) $description .= " and ";
			}
		}
		else if ($this->s_intro)
		{
			$description = trim($this->s_intro);
			$description = XhtmlMarkup::ApplyLinks($description, true);
			$description = XhtmlMarkup::ApplyLists($description, true);
			$description = XhtmlMarkup::ApplySimpleTags($description, true);
			
			$break = strpos($description, "\n");
			if ($break !== false and $break > 0) $description = substr($description, 0, $break-1);
		} 
		$description .= ".";	

		return $description;
	}

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
	* @return void
	* @param string $s_input
	* @desc Sets the contact details for the competition - eg a name and address
	*/
	function SetContact($s_input) { $this->s_contact = trim((string)$s_input); }

	/**
	* @return string
	* @desc Gets the contact details for the competition - eg a name and address
	*/
	function GetContact() { return $this->s_contact; }

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the email address to notify when public users update match details
	*/
	function SetNotificationEmail($s_input) { $this->s_notification_email = trim((string)$s_input); }

	/**
	* @return string
	* @desc Gets the email address to notify when public users update match details
	*/
	function GetNotificationEmail() { return $this->s_notification_email; }

	/**
	* @return void
	* @param string $s_url
	* @desc Sets the URL of the competition's website
	*/
	function SetWebsiteUrl($s_url) { $this->s_website = trim((string)$s_url); }

	/**
	* @return string
	* @desc Gets the URL of the competition's website
	*/
	function GetWebsiteUrl() { return $this->s_website; }


	/**
	* @return void
	* @param Season[] $a_input
	* @desc Sets the seasons of this competition
	*/
	function SetSeasons($a_input)
	{
		$this->SetItems($a_input);
	}

	/**
	* @return void
	* @param int $i_index
	* @desc Sets the season being dealt with at the moment
	*/
	function SetWorkingIndex($i_index)
	{
		$this->i_working_index = (int)$i_index;
	}

	/**
	* @return Season
	* @desc Sets the season being dealt with at the moment
	*/
	function GetWorkingSeason()
	{
		$o_working = $this->GetByIndex($this->i_working_index);
		return $o_working;
	}

	/**
	* @return void
	* @param Season $o_season
	* @param bool $b_is_working
	* @desc Adds a season to the end of the competition
	*/
	function Add($o_season)
	{
		return $this->AddSeason($o_season);
	}

	/**
	* @return void
	* @param Season $o_season
	* @param bool $b_is_working
	* @desc Adds a season to the end of the competition
	*/
	public function AddSeason(Season $o_season, $b_is_working=false)
	{
		$o_season->SetCompetition($this);
		$i_index = parent::Add($o_season);

		if ($b_is_working or $this->i_working_index == -1) $this->SetWorkingIndex($i_index);
	}

	/**
	* @return void
	* @param Season[] $a_input
	* @desc Sets the seasons of this competition
	*/
	function SetItems($a_input)
	{
		/* @var $o_season Season */
		/* @var $o_latest Season */

		if (is_array($a_input))
		{
			$o_latest = &$this->GetLatestSeason();
			$b_latest = is_object($o_latest);

			$i_seasons = count($a_input);
			for ( $i = 0; $i < $i_seasons; $i++)
			{
				$o_season = $a_input[$i];

				if ($o_season instanceof Season)
				{
					$o_season->SetCompetition($this);

					# Latest season often has richer info, so don't throw it away
					if ($b_latest and $o_season->GetId() == $o_latest->GetId())
					{
						$o_season->Merge($o_latest);
					}
				}
			}
		}

		parent::SetItems($a_input);
	}

	/**
	* @return Season[]
	* @desc Gets an array of seasons of this competition
	*/
	function GetSeasons()
	{
		return $this->a_items;
	}

	/**
	* @return Season
	* @desc Gets the latest season of this competition
	*/
	function &GetLatestSeason()
	{
		/* @var $o_season Season */

		$a_items = &$this->a_items;
		foreach ($a_items as $o_season)
		{
			if ($o_season->GetIsLatest()) return $o_season;
		}

		$o_latest = null;
		return $o_latest;
	}

	/**
	* @return void
	* @param int $player_type
	* @desc Sets the player type allowed to compete in the competition
	*/
	public function SetPlayerType($player_type) { $this->player_type = (int)$player_type; }

	/**
	* @return int
	* @desc Gets the player type allowed to compete in the competition
	*/
	public function GetPlayerType() { return $this->player_type; }

	/**
	 * Sets the maximum number of players per team
	 * @param int $player_count
	 * @return void
	 */
	public function SetMaximumPlayersPerTeam($player_count) { $this->players_per_team = (int)$player_count; }

	/**
	 * Gets the maximum number of players per team
	 * @return int
	 */
	public function GetMaximumPlayersPerTeam() { return $this->players_per_team; }

	/**
	 * Sets the number of overs in each innings
	 * @param int $overs
	 * @return void
	 */
	public function SetOvers($overs) { $this->overs = (int)$overs; }

	/**
	 * Gets the number of overs in each innings
	 * @return int
	 */
	public function GetOvers() { return $this->overs; }

	/**
	* @return string
	* @desc Gets the URL for the competition
	*/
	public function GetNavigateUrl()
	{
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
	* @desc Gets the URL to edit the competition
	*/
	public function GetEditCompetitionUrl()
	{
		return $this->o_settings->GetFolder('Play') . 'competitions/competitionedit.php?item=' . $this->GetId();
	}

	/**
	* @return string
	* @desc Gets the URL to delete the competition
	*/
	public function GetDeleteCompetitionUrl()
	{
		return "/play/competitions/competitiondelete.php?item=" . $this->GetId();
	}

    /**
    * @return string
    * @desc Gets the URL to to view teams playing in the competition
    */
    public function GetCompetitionMapUrl()
    {
        return $this->GetNavigateUrl() . "/map";
    }

	/**
	 * Gets the URL for statistics about the competition
	 * @return string
	 */
	public function GetStatisticsUrl() { return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . "/statistics"; }

	/**
	 * Sets the short URL for a competition
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = trim((string)$s_url); }

	/**
	 * Gets the short URL for a competition
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
		$o_latest = $this->GetLatestSeason();
		if (is_object($o_latest))
		{
			return $o_latest->GetNavigateUrl();
		}
		else
		{
			return $this->o_settings->GetUrl('Competition') . $this->GetId();
		}
	}


		/**
	 * Gets the format to use for a competition's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings)
	{
		return new ShortUrlFormat($settings->GetTable('Competition'), 'short_url', array('competition_id'), array('GetId'),
			array('{0}' => $settings->GetUrl('Competition') . '{0}',
					'{0}/statistics' => '/play/statistics/summary-competition.php?competition={0}', 
					'{0}/map' => "/play/competitions/map.php?competition={0}"
					));
	}

	/**
	 * Gets the format to use for a competition's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Competition::GetShortUrlFormatForType($this->o_settings);
	}

	/**
	 * Suggests a short URL to use to view the competition
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
		$s_url = preg_replace(array('/\bstoolball\b/i', '/\bleague\b/i', '/\bladies\b/i', '/\bmixed\b/i', '/\bdistrict\b/i', '/\bthe\b/i', '/\band\b/i', '/\bin\b/i', '/\bwith\b/i', '/\bassociation\b/i', '/\bdivision\b/i', '/\bcounty\b/i', '/\bfriendlies\b/i'), '', $s_url);

		# Apply preference
		if ($i_preference == 2)
		{
			# Append player type
			$s_url .= strtolower(html_entity_decode(PlayerType::Text($this->GetPlayerType())));
			$s_url = preg_replace('/[^a-z0-9 ]/i', '', $s_url);
		}
		else if ($i_preference > 2)
		{
			$s_url = ShortUrl::SuggestShortUrl($s_url, $i_preference, 2);
		}

		# Remove spaces
		$s_url = str_replace(' ', '', $s_url);

		return $s_url;
	}
}
?>