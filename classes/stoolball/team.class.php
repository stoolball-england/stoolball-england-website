<?php
require_once('collection.class.php');
require_once('stoolball/ground.class.php');
require_once('stoolball/clubs/club.class.php');
require_once('stoolball/team-role.enum.php');
require_once 'stoolball/player-type.enum.php';
require_once('http/has-short-url.interface.php');
require_once('http/short-url.class.php');

class Team implements IHasShortUrl
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $o_settings;
	private $i_id;
	private $s_name;
    private $comparable_name;
	private $s_website;
	
	/**
     * Seasons the team competes in
     * @var Collection
     */
	private $seasons;
	private $b_active;
	private $team_type;
	/**
	 * The ages and genders to whom the team is open
	 *
	 * @var IdValue
	 */
	private $player_type;
	private $o_ground;
	private $s_intro;
	private $s_playing_times;
	private $s_cost;
	private $s_contact;
	private $s_contact_private;
	private $s_short_url;
    private $short_url_prefix;
	private $o_club;
    private $update_search;
    private $owner_role_id;
    private $school_years = array();
    
	/**
	 * @return Team
	 * @param SiteSettings $o_settings
	 * @desc A stoolball team
	 */
	function Team(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;
		$this->seasons = new Collection(null, "TeamInSeason");
		$this->b_active = true;
		$this->o_ground = new Ground($this->o_settings);
	}

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Set the database id of the team
	 */
	function SetId($i_input) { $this->i_id = (int)$i_input; }
	/**
	 * @return int
	 * @desc Get the database id of the team
	 */
	function GetId() { return $this->i_id; }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the name of the team
	 */
	public function SetName($s_input)
    {
        $this->s_name = trim($s_input); 
        $this->comparable_name = null;
    }

	/**
	 * @return string
	 * @desc Gets the name of the team
	 */
	public function GetName() { return $this->s_name; }

	/**
	 * @return string
	 * @desc Gets the name of the team and the type of players (if not stated in the name))
	 */
	public function GetNameAndType()
	{
		$s_name = $this->s_name;
		$type = is_null($this->player_type) ? '' : PlayerType::Text($this->player_type);
		if ($type and strpos(strtolower(str_replace("'", '', $s_name)), strtolower(str_replace("'", '', $type))) === false)
		{
			$s_name .= ' (' . $type . ')';
		}
		return $s_name;
	}

    /**
     * Gets the version of the team's name used to match them for updates
     * @return string
     */
    public function GetComparableName()
    {
        if (is_null($this->comparable_name))
        {
            $this->comparable_name = $this->GetShortUrlPrefix() . preg_replace('/[^a-z0-9]/i', '', strtolower($this->GetNameAndType()));
        }
        return $this->comparable_name;
    }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the intro to the team
	 */
	function SetIntro($s_input) { $this->s_intro = trim($s_input); }

	/**
	 * @return string
	 * @desc Gets the intro to the team
	 */
	function GetIntro() { return $this->s_intro; }

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
	 * @desc Sets the days and times the team plays
	 */
	function SetPlayingTimes($s_input) { $this->s_playing_times = trim($s_input); }

	/**
	 * @return string
	 * @desc Gets the days and times the team plays
	 */
	function GetPlayingTimes() { return $this->s_playing_times; }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the cost to play in the team
	 */
	public function SetCost($s_input) { $this->s_cost = trim($s_input); }

	/**
	 * @return string
	 * @desc Gets the cost to play in the team
	 */
	public function GetCost() { return $this->s_cost; }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the contact details for the team - eg a name and address
	 */
	function SetContact($s_input) { $this->s_contact = trim($s_input); }

	/**
	 * @return string
	 * @desc Gets the contact details for the team - eg a name and address
	 */
	function GetContact() { return $this->s_contact; }

	/**
	 * Gets the main phone number for the team
	 */
	public function GetContactPhone()
	{
		# There isn't actually a main phone number, so try to get the first one
		$matches = array();
		if (!preg_match("/[0-9 ]{11,13}/", $this->s_contact, $matches)) return "";
		return trim($matches[0]);
	}

	/**
	 * Gets the main e-mail address for the team
	 */
	public function GetContactEmail()
	{
		# There isn't actually a main e-mail address, so try to get the first one

		# From http://regexlib.com/REDetails.aspx?regexp_id=328
		$email_pattern = "/((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-])+\.)+[A-Za-z\-]+))/";

		$matches = array();
		if (!preg_match($email_pattern, $this->s_contact, $matches)) return "";
		return $matches[0];
	}

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the contact details for the team for Stoolball England use only - eg a name and address
	 */
	function SetPrivateContact($s_input) { $this->s_contact_private = trim($s_input); }

	/**
	 * @return string
	 * @desc Gets the contact details for the team for Stoolball England use only - eg a name and address
	 */
	function GetPrivateContact() { return $this->s_contact_private; }

	/**
	 * @return void
	 * @param Club $o_club
	 * @desc Sets the club to which this team belongs (if any)
	 */
	function SetClub(Club $o_club) { $this->o_club = $o_club; }

	/**
	 * @return Club
	 * @desc Gets the club to which this team belongs (if any)
	 */
	function GetClub() { return $this->o_club; }

	/**
	 * @return void
	 * @param string $s_url
	 * @desc Sets the URL of the team's website
	 */
	function SetWebsiteUrl($s_url) { $this->s_website = trim($s_url); }

	/**
	 * @return string
	 * @desc Gets the URL of the team's website
	 */
	function GetWebsiteUrl() { return $this->s_website; }

	/**
	 * @return void
	 * @param Ground $o_ground
	 * @desc Sets the ground at which the team plays
	 */
	function SetGround($o_ground) { if ($o_ground instanceof Ground) $this->o_ground = $o_ground; }

	/**
	 * @return Ground
	 * @desc Gets the ground at which the team plays
	 */
	function GetGround() { return $this->o_ground; }

	/**
	 * @return Collection
	 * @desc Gets this seasons the team competes in
	 */
	public function Seasons() { return $this->seasons; }

	/**
	 * Gets the URI which uniquely identifies this team
	 */
	public function GetLinkedDataUri()
	{
		return "https://www.stoolball.org.uk/id/team/" . $this->GetShortUrl();
	}

	/**
	 * @return string
	 * @desc Gets the URL for the team
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
	 * @desc Gets the URL to edit the team
	 */
	public function GetEditTeamUrl()
	{
	    return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/edit';
	}

	/**
	 * @return string
	 * @desc Gets the URL to delete the team
	 */
	public function GetDeleteTeamUrl()
	{
        return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/delete';
	}

	/**
	 * @return string
	 * @desc Gets the URL for updating the team's match reults
	 */
	public function GetResultsNavigateUrl()
	{
		return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/matches/edit';
	}

	/**
	 * @return string
	 * @desc Gets the URL for downloading the iCalendar file
	 */
	public function GetCalendarNavigateUrl()
	{
        return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/calendar';
	}

	/**
	 * @return string
	 * @desc Gets the URL for viewing team stats
	 */
	public function GetStatsNavigateUrl()
	{
		return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/statistics';
	}

	/**
	 * Gets the URL for adding a match to this team's fixtures
	 *
	 * @param MatchType $i_match_type
	 * @return string
	 */
	public function GetAddMatchNavigateUrl($i_match_type)
	{
		$s_url = '';
		switch ($i_match_type)
		{
			case MatchType::TOURNAMENT:
				$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/matches/tournaments/add';
				break;
			case MatchType::PRACTICE:
				$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/matches/practices/add';
				break;
			case MatchType::FRIENDLY:
				$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/matches/friendlies/add';
				break;
			case MatchType::LEAGUE:
				$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/matches/league/add';
				break;
			case MatchType::CUP:
				$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/matches/cup/add';
				break;
		}
		return $s_url;
	}

	/**
	 * @return string
	 * @desc Gets the URL for viewing a list of players in the team
	 */
	public function GetPlayersNavigateUrl()
	{
		return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/players';
	}

	/**
	 * @return string
	 * @desc Gets the URL for adding a new player in the team
	 */
	public function GetPlayerAddNavigateUrl()
	{
		$s_url = '';
		if ($this->GetShortUrl())
		{
			$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/players/add';
		}
		else
		{
			$s_url = str_replace('{0}', $this->GetId(), $this->o_settings->GetUrl('PlayerAdd'));
		}

		return $s_url;
	}

	/**
	 * @return void
	 * @param bool $b_active
	 * @desc Sets whether the team is currently taking part in matches
	 */
	function SetIsActive($b_active)
	{
		$this->b_active = (bool)$b_active;
	}

	/**
	 * @return bool
	 * @desc Gets whether the team is currently taking part in matches
	 */
	function GetIsActive()
	{
		return $this->b_active;
	}

	/**
	 * @return void
	 * @param int $team_type
	 * @desc Sets the type of team
	 */
	function SetTeamType($team_type)
	{
		$this->team_type = (int)$team_type;
	}

	/**
	 * @return int
	 * @desc Gets the type of team
	 */
	function GetTeamType()
	{
		return $this->team_type;
	}
    
    /**
     * Sets the school years represented by this team
     * @param array[int=>bool] $school_years [$year] = bool
     */
    public function SetSchoolYears(array $school_years) 
    {
        foreach ($school_years as $key => $value) {
            $year = (int)$key;
            if ($year < 1 or $year > 12 or !is_bool($value)) {
                unset($school_years[$key]);
            }
        }
        $this->school_years = $school_years;
    }
    
    /**
     * Sets the school years represented by this team
     * @return array[int=>bool] $school_years [$year] = bool
     */
    public function GetSchoolYears() {
        return $this->school_years;
    }

	/**
	 * @return void
	 * @param int $player_type
	 * @desc Sets the player type allowed to play in the team
	 */
	public function SetPlayerType($player_type)
    {
        $this->player_type = (int)$player_type; 
        $this->comparable_name = null;
    }

	/**
	 * @return int
	 * @desc Gets the player type allowed to play in the team
	 */
	public function GetPlayerType() { return $this->player_type; }

	/**
	 * Sets the short URL for a team
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = trim($s_url); }

	/**
	 * Gets the short URL for a team
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->s_short_url; }

    /**
     * Sets the prefix to use when selecting a short URL for a team
     *
     * @param string $prefix
     */
    public function SetShortUrlPrefix($prefix) 
    {
        $this->short_url_prefix = trim($prefix, " /"); 
        $this->comparable_name = null;
    }

    /**
     * Gets the prefix to use when selecting a short URL for a team
     *
     * @return string
     */
    public function GetShortUrlPrefix() 
    {
        $prefix = $this->short_url_prefix;
        if ($prefix) 
        {
            $prefix .= "/";
        } 
        return $prefix;
    }

	/**
	 * Gets the real URL for a team
	 *
	 * @return string
	 */
	public function GetRealUrl()
	{
		return $this->o_settings->GetUrl('Team') . $this->GetId();
	}

	/**
	 * Gets the format to use for a team's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings)
	{
		return new ShortUrlFormat("nsa_team", 'short_url', array('team_id'), array('GetId'),
		array(
		'{0}' => $settings->GetUrl('Team') . '{0}',
		'{0}/edit' => "/play/teams/teamedit.php?item={0}",
		'{0}/delete' => "/play/teams/teamdelete.php?item={0}", 
		'{0}/matches/friendlies/add' => '/play/matches/matchadd.php?team={0}',
		'{0}/matches/league/add' => '/play/matches/matchadd.php?team={0}&type=' . MatchType::LEAGUE,
		'{0}/matches/cup/add' => '/play/matches/matchadd.php?team={0}&type=' . MatchType::CUP,
		'{0}/matches/practices/add' => '/play/matches/matchadd.php?team={0}&type=' . MatchType::PRACTICE,
		'{0}/matches/tournaments/add' => '/play/tournaments/add.php?team={0}',
		'{0}/matches/edit' => $settings->GetUrl('TeamResults'),
		'{0}/calendar' => $settings->GetUrl('TeamCalendar'),
		'{0}/statistics' => $settings->GetUrl('TeamStats'),
		'{0}/players' => '/play/teams/players.php?team={0}',
		'{0}/players/add' => $settings->GetUrl('PlayerAdd'),
        '{0}/statistics.json' => "/play/statistics/team.js.php?team={0}"
		));
	}

	/**
	 * Gets the format to use for a team's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Team::GetShortUrlFormatForType($this->o_settings);
	}

	/**
	 * Suggests a short URL to use to view the team
	 *
	 * @param int $i_preference
	 * @return string
	 */
	public function SuggestShortUrl($i_preference=1)
	{        
	    # Base the short URL on the team name
		$s_url = strtolower(html_entity_decode($this->GetName()));

		# Remove punctuation
		$s_url = preg_replace('/[^a-z ]/i', '', $s_url);

		# Remove noise words
		$s_url = preg_replace(array('/\bstoolball\b/i', '/\bclub\b/i', '/\bladies\b/i', '/\bmixed\b/i', '/\bsports\b/i', '/\bthe\b/i', '/\band\b/i'), '', $s_url);

		# Apply preference
		if ($i_preference == 2)
		{
			# Append player type
			$s_url .= strtolower(html_entity_decode(PlayerType::Text($this->GetPlayerType())));
			$s_url = preg_replace('/[^a-z0-9 ]/i', '', $s_url);
		}
		else if ($i_preference > 2)
		{
			$s_url = ShortUrl::SuggestShortUrl($s_url, $i_preference, 2, 'team');
		}

		# Remove spaces
		$s_url = str_replace(' ', '', $s_url);

		return $this->GetShortUrlPrefix() . $s_url;
	}
    
	/**
	 * Gets the name of the team
	 *
	 */
	public function __toString()
	{
		return $this->GetName();
	}

    /**
     * Audit data about the last action taken on this object
     *
     * @var AuditData
     */
    private $last_audit;

    /**
     * Sets audit data about the last action taken on this object
     *
     * @param AuditData $data
     */
    public function SetLastAudit(AuditData $data) { $this->last_audit = $data; }

    /**
     * Gets audit data about the last action taken on this object
     *
     * @return AuditData
     */
    public function GetLastAudit() { return $this->last_audit; }

    /**
     * @return void
     * @param int $role_id
     * @desc Set the database id of the owner role for the team
     */
    public function SetOwnerRoleId($role_id) { $this->owner_role_id = (int)$role_id; }

    /**
     * @return int
     * @desc Get the database id of the owner role for the team
     */
    public function GetOwnerRoleId() { return $this->owner_role_id; }

	/**
	 * A team that plays regularly
	 * @var int
	 */
	const REGULAR = 0;

	/**
	 * A team that only particular people can join, such as a work team
	 * @var int
	 */
	const CLOSED_GROUP = 1;

	/**
	 * A team selected to represent a league or other group
	 * @var int
	 */
	const REPRESENTATIVE = 2;

	/**
	 * A team that plays occasional friendlies or tournaments
	 * @var int
	 */
	const OCCASIONAL = 3;

	/**
	 * A one-off team for a single match or tournament
	 * @var int
	 */
	const ONCE = 4;
    
    /**
     * A team made up of pupils from multiple school years
     */
	const SCHOOL_YEARS = 6;
    /**
     * A extra-curricular school club, such as an after-school club 
     */
    const SCHOOL_CLUB = 7;
    
    /** 
     * Any other type of school team
     */
    const SCHOOL_OTHER = 8;
}
?>