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
            case 'AccountCreate':
                return $this->GetFolder('Account') . 'signup.php';
            case 'AccountEdit':
                return $this->GetFolder('Account') . 'settings.php';
            case 'AccountEssential':
                return $this->GetFolder('Account') . 'essential.php';
            case 'Competition':
                return $this->GetFolder('Play') . 'competitions/competition.php?latest=1&item=';
            case 'EmailAlerts':
                return $this->GetFolder('Account') . 'emails.php';
            case 'PlayerAdd':
                return $this->GetFolder('Play') . 'playeredit.php?team={0}';
            case 'Season':
                return $this->GetFolder('Play') . 'competitions/competition.php?item=';
            case 'Team':
                return $this->GetFolder('Play') . 'teams/team.php?item=';
            case 'TeamAdd':
                return $this->GetFolder('Play') . 'yourteam.php';
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
        // Generated using openssl_random_pseudo_bytes(32);
        return "";
    } 
    
    /***
     * Gets the iv used to encrypt and decrypt email addresses to hide them from spambots
     */
    public function GetEmailAddressEncryptionIv() {
        // Generated using:
        // $ivlen = openssl_cipher_iv_length($cipher);
        // $iv = openssl_random_pseudo_bytes($ivlen);
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

        require_once('stoolball/clubs/club.class.php');
        $a_formats[] = Club::GetShortUrlFormatForType();

        require_once('stoolball/competition.class.php');
        $a_formats[] = Competition::GetShortUrlFormatForType();

        require_once('stoolball/ground.class.php');
        $a_formats[] = Ground::GetShortUrlFormatForType();

        require_once('stoolball/match.class.php');
        $a_formats[] = Match::GetShortUrlFormatForType();

        require_once('stoolball/season.class.php');
        $a_formats[] = Season::GetShortUrlFormatForType();

        require_once('stoolball/team.class.php');
        $a_formats[] = Team::GetShortUrlFormatForType();

        require_once 'stoolball/player.class.php';
        $a_formats[] = Player::GetShortUrlFormatForType();

        require_once 'authentication/user.class.php';
        $a_formats[] = User::GetShortUrlFormatForType();

        return $a_formats;

    }
}
?>