<?php
require_once('data/content-type.enum.php');
require_once('context/site-context.class.php');

abstract class SiteSettings
{
	var $a_tables;

	/**
	* @return string
	* @desc Gets the display name of the site
	*/
    public function GetSiteName() { return 'Stoolball England'; }

	/**
	* @return string
	* @desc Gets the root URL for any files to be downloaded by the client
	*/
	function GetClientRoot() { return '/'; }
	/**
	* @return unknown
	* @desc Gets the root folder for any files accessed on the server-side
	*/
	function GetServerRoot()
	{
		if (SiteContext::IsWordPress())
		{
			return ABSPATH;
		}
		else
		{
			return $_SERVER['DOCUMENT_ROOT'] . '/';
		}
	}

    /**
     * @return string
     * @param string $s_key
     * @desc Get the virtual URL of a folder, filename and initial query string (if any), eg "/somefolder/somefile.php?item="
     */
    function GetUrl($s_key)
    {
        switch($s_key)
        {
            case 'AccountActivate':
                return $this->GetFolder('Account') . 'a.php'; # short because link sent by email
            case 'AccountConfirmEmail':
                return $this->GetFolder('Account') . 'e.php'; # short because link sent by email
            case 'AccountCreate':
                return $this->GetFolder('Account') . 'signup.php';
            case 'AccountEdit':
                return $this->GetFolder('Account') . 'settings.php';
            case 'AccountEssential':
                return $this->GetFolder('Account') . 'essential.php';
            case 'AdminClubEdit':
                return $this->GetFolder('Admin') . 'clubedit.php?item=';
            case 'Club':
                return $this->GetFolder('Play') . 'club.php?item=';
            case 'Competition':
                return $this->GetFolder('Play') . 'competitions/competition.php?latest=1&item=';
            case 'EmailAlerts':
                return $this->GetFolder('Account') . 'emails.php';
            case 'Ground':
                return $this->GetFolder('Play') . 'ground.php?item=';
            case 'GroundStatistics':
                return $this->GetFolder('Play') . 'statistics/summary-ground.php?item={0}';
            case 'Match':
                return $this->GetFolder('Play') . 'match.php?item=';
            case 'MatchAddFriendlyToSeason':
                return $this->GetFolder('Play') . 'matchadd.php?season={0}';
            case 'MatchAddLeagueToSeason':
                return $this->GetFolder('Play') . 'matchadd.php?season={0}&type=' . MatchType::LEAGUE;
            case 'MatchAddCupToSeason':
                return $this->GetFolder('Play') . 'matchadd.php?season={0}&type=' . MatchType::CUP;
            case 'MatchAddPracticeToSeason':
                return $this->GetFolder('Play') . 'matchadd.php?season={0}&type=' . MatchType::PRACTICE;
            case 'MatchAddFriendlyToTeam':
                return $this->GetFolder('Play') . 'matchadd.php?team={0}';
            case 'MatchAddLeagueToTeam':
                return $this->GetFolder('Play') . 'matchadd.php?team={0}&type=' . MatchType::LEAGUE;
            case 'MatchAddCupToTeam':
                return $this->GetFolder('Play') . 'matchadd.php?team={0}&type=' . MatchType::CUP;
            case 'MatchAddPracticeToTeam':
                return $this->GetFolder('Play') . 'matchadd.php?team={0}&type=' . MatchType::PRACTICE;
            case 'MatchCalendar':
                return $this->GetFolder('Play') . 'calendar.php?match={0}';
            case 'Players':
                return $this->GetFolder('Play') . 'players.php?team={0}';
            case 'Player':
                return $this->GetFolder('Play') . 'player.php?player={0}';
            case 'PlayerAdd':
                return $this->GetFolder('Play') . 'playeredit.php?team={0}';
            case 'PlayerEdit':
                return $this->GetFolder('Play') . 'playeredit.php?item={0}';
            case 'PlayerDelete':
                return $this->GetFolder('Play') . 'playerdelete.php?item={0}';
            case 'Season':
                return $this->GetFolder('Play') . 'competitions/competition.php?item=';
            case 'SeasonCalendar':
                return $this->GetFolder('Play') . 'calendar.php?season={0}';
            case 'SeasonResults':
                return $this->GetFolder('Play') . 'results.php?season={0}';
            case 'Team':
                return $this->GetFolder('Play') . 'teams/team.php?item=';
            case 'TeamAdd':
                return $this->GetFolder('Play') . 'yourteam.php';
            case 'TeamCalendar':
                return $this->GetFolder('Play') . 'calendar.php?team={0}';
            case 'TeamResults':
                return $this->GetFolder('Play') . 'results.php?team={0}';
            case 'TeamStats':
                return $this->GetFolder('Play') . 'statistics/summary-team.php?item={0}';
            case 'TournamentEdit':
                return $this->GetFolder('Play') . 'tournamentedit.php?item={0}';
            default:
                return '';
        }
    }

    /**
     * @return string
     * @param string $s_key
     * @desc Get the virtual URL of a folder, eg "/somefolder/"
     */
    public function GetFolder($s_key)
    {
        switch($s_key)
        {
            case 'About':
                return $this->GetClientRoot() . 'about/';
            case 'Account':
                return $this->GetClientRoot() . 'you/';
            case 'Admin':
                return '/yesnosorry/';
            case 'Contact':
                return $this->GetClientRoot() . 'contact/';
            case 'Css':
                return $this->GetClientRoot() . 'css/';
            case 'ForumIcons':
                return $this->GetFolder('Images') . 'icons/forums/';
            case 'ForumIconsServer':
                return $this->GetFolder('ImagesServer') . 'icons/forums/';
            case 'ForumImages':
                return $this->GetFolder('Images') . 'forums/';
            case 'History':
                return '/history/';
            case 'Images':
                return $this->GetClientRoot() . 'images/';
            case 'ImagesServer':
                return $this->GetServerRoot() . 'images/';
            case 'News':
                return '/news/';
            case 'Play':
                return '/play/';
            case 'Rules':
                return '/rules/';
            default:
                return '/';
        }
    }

	/**
	* @return string
	* @param string $s_key
	* @desc Gets the name of a database table which matches the generic purpose specified
	*/

    function GetTable($s_table_key)
    {
        switch ($s_table_key)
        {
            case 'Activation':
                return 'nsa_activation';
            case 'Audit':
                return 'nsa_audit';
            case 'Batting':
                return 'nsa_batting';
            case 'Bowling':
                return 'nsa_bowling';
            case 'Category':
                return 'nsa_category';
            case 'Club':
                return 'nsa_club';
            case 'Competition':
                return 'nsa_competition';
            case 'EmailSubscription':
                return 'nsa_email_subscription';
            case 'ForumTopicLink':
                return 'nsa_forum_topic_link';
            case 'ForumMessage':
                return 'nsa_forum_message';
            case 'Ground':
                return 'nsa_ground';
            case 'Match':
                return 'nsa_match';
            case 'MatchTeam':
                return 'nsa_match_team';
            case 'PermissionRoleLink':
                return 'nsa_permission_role';
            case 'Player':
                return "nsa_player";
            case 'PlayerMatch':
                return "nsa_player_match";
            case 'PointsAdjustment':
                return 'nsa_point';
            case 'Queue':
                return 'nsa_queue';
            case 'Role':
                return 'nsa_role';
            case 'Season':
                return 'nsa_season';
            case 'SeasonMatch':
                return 'nsa_season_match';
            case 'SeasonRule':
                return 'nsa_season_rule';
            case 'SeasonMatchType':
                return 'nsa_season_matchtype';
            case 'ShortUrl':
                return 'nsa_short_url';
            case 'TeamSeason':
                return 'nsa_team_season';
            case 'User':
                return 'nsa_user';
            case 'UserRole':
                return 'nsa_user_role';
            default:
                return null;
        }
    }


	function GetSubscriptionEmailTo() { return $this->GetEmailAddress(); }
	function GetSubscriptionEmailFrom() { return $this->GetEmailAddress(); }

    public function GetDomain()
    {
        return (SiteContext::IsDevelopment()) ? 'stoolball.local' : 'www.stoolball.org.uk';
    }
    
    /**
     * Gets the linked data URI which represents Stoolball England as a body
     */
    public function GetOrganisationLinkedDataUri() {
        return "https://www.stoolball.org.uk/#";
    }
    
    /**
     * Gets the no-reply email address to send automated emails from
     */
    public function GetEmailAddress() { return 'example@example.org'; }
    
    /**
     * Gets the email address to send technical notifications to
     */
    public function GetTechnicalContactEmail() { return 'example@example.org'; }        
    
    /**
     * Gets the email address to send website updates to
     */
    public function GetWebEditorEmail() { return 'example@example.org'; }
    
    /**
     * Gets the email address to send general enquiries to
     */
    public function GetEnquiriesEmail() { return 'example@example.org'; }
        
    /**
     * Gets the email address to sales queries to
     */
    public function GetSalesEmail() { return 'example@example.org'; }
            
    /**
     * Gets the email address to send match update notifications to
     */
    public function GetMatchUpdatesEmail() { return 'example@example.org'; }
    
    /**
     * Gets private email addresses and the official versions they should be replaced with
     */
    public function GetPublicEmailAliases(){
        return array(
            'example@example.org' => 'example@example.org' 
        );
    }
    
    /***
     * Gets the key used to encrypt and decrypt email addresses to hide them from spambots
     */
    public function GetEmailAddressEncryptionKey() {
        return "";
    } 
    
    /**
     * Gets the signature to add to all automated emails
     */
    public function GetEmailSignature() 
    {
        return "\n\n" . "Stoolball England\nwww.stoolball.org.uk\n\nFollow us on Twitter @Stoolball\nFind us on Facebook www.facebook.com/stoolball";
    }

    /**
     * Gets API keys that allow protected data to be requested without exposing it to spam bots
     */
    public function GetApiKeys() {
        return array();
    }
    
    /**
     * Get key which might potentially be used to decrypt old sign-in cookies until 1 Feb 2016, the latest date an old-method could expire
     */
    public function GetOldAutoSignInCookieKey() {
        return '';
    }

    /**
     * Get salt which might potentially be used to decrypt old sign-in cookies until 1 Feb 2016, the latest date an old-method could expire
     */
    public function GetOldAutoSignInCookieSalt() {
        return '';
    }

	/**
	 * Gets the hostname to connect to the site database
	 *
	 * @return string
	 */
	public function DatabaseHost() { return ''; }

	/**
	 * Gets the name of the site database
	 *
	 * @return string
	 */
	public function DatabaseName() { return ''; }

	/**
	 * Gets the username to connect to the site database
	 *
	 * @return string
	 */
	public function DatabaseUser() { return ''; }

	/**
	 * Gets the password used to connect to the site database
	 *
	 * @return string
	 */
	public function DatabasePassword() { return ''; }

    /**
     * Gets the short URL formats for the site
     *
     * @return ShortUrlFormat[]
     */
    public function GetShortUrlFormats()
    {
        require_once('http/short-url-format.class.php');

        $a_formats = array();

        require_once('stoolball/club.class.php');
        $a_formats[] = Club::GetShortUrlFormatForType($this);

        require_once('stoolball/competition.class.php');
        $a_formats[] = Competition::GetShortUrlFormatForType($this);

        require_once('stoolball/ground.class.php');
        $a_formats[] = Ground::GetShortUrlFormatForType($this);

        require_once('stoolball/match.class.php');
        $a_formats[] = Match::GetShortUrlFormatForType($this);

        require_once('stoolball/season.class.php');
        $a_formats[] = Season::GetShortUrlFormatForType($this);

        require_once('stoolball/team.class.php');
        $a_formats[] = Team::GetShortUrlFormatForType($this);

        require_once 'stoolball/player.class.php';
        $a_formats[] = Player::GetShortUrlFormatForType($this);

        return $a_formats;

    }
}
?>