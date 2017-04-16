<?php
require_once('stoolball/match-result.class.php');
require_once('stoolball/match-type.enum.php');
require_once('stoolball/match-qualification.enum.php');
require_once('http/has-short-url.interface.php');
require_once('http/short-url.class.php');
require_once('team.class.php');
require_once('season.class.php');
require_once('ground.class.php');
require_once('player.class.php');

/**
 * A stoolball match
 *
 */
class Match implements IHasShortUrl
{
	/**
	 * Site-wide settings
	 *
	 * @var SiteSettings
	 */
	private $o_settings;
	private $i_id;
	private $s_title;
	/**
	 * Seasons of which this match is a part
	 *
	 * @var Collection
	 */
	private $seasons;
	private $o_home_team;
	private $o_ground;
	private $i_start_time;
	private $b_time_known = true;
	/**
	 * The result of the match
	 * @var MatchResult
	 */
	private $result;
	private $s_notes;
	private $s_new_comment;
	private $i_type;
	private $o_tournament_match;
	private $a_away_teams;
	private $added_by;
    private $date_added;
	private $a_matches_in_tournament;
    private $max_tournament_teams;
    private $spaces_in_tournament;
    private $order_in_tournament;
	private $s_short_url;
	private $b_custom_title = false;
	private $b_custom_url = false;
    private $qualification = MatchQualification::UNKNOWN;

	/**
	 * The ages and genders to whom the match is open
	 *
	 * @var PlayerType
	 */
	private $player_type;
	private $players_per_team;
	private $overs;
    private $update_search;

	public function Match(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;
		$this->seasons = new Collection();
		$this->SetMatchType(MatchType::FRIENDLY);
		$this->a_away_teams = array();
		$this->a_matches_in_tournament = array();
		$this->result = new MatchResult();
	}

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the database id of the stoolball match
	 */
	function SetId($i_input) { $this->i_id = (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the database id of the stoolball match
	 */
	function GetId() { return $this->i_id; }

    /**
     * Sets when the match was added to the website
     *
     * @param int $timestamp
     */
    public function SetDateAdded($timestamp) { $this->date_added = (int)$timestamp; }

    /**
     * Gets when the match was added to the website
     *
     * @return int
     */
    public function GetDateAdded() { return $this->date_added; }
     
	/**
	 * Sets who added the match to the website
	 *
	 * @param User $person
	 */
	public function SetAddedBy(User $person) { $this->added_by = $person; }

	/**
	 * Gets who added the match to the website
	 *
	 * @return User
	 */
	public function GetAddedBy() { return $this->added_by; }


	/**
	 * Seasons of which this match is a part
	 *
	 * @return Collection
	 */
	public function Seasons()
	{
		return $this->seasons;
	}

	/**
	 * @return void
	 * @param Team $o_input
	 * @desc Sets the home team for the Match
	 */
	public function SetHomeTeam(Team $o_input) { $this->o_home_team = $o_input; }

	/**
	 * @return Team
	 * @desc Gets the home team
	 */
	public function GetHomeTeam() { return $this->o_home_team; }

	/**
	 * @return void
	 * @param Team $o_input
	 * @desc Sets the away team for the Match
	 */
	public function SetAwayTeam(Team $o_input) { $this->a_away_teams[0] = $o_input; }

	/**
	 * @return Team
	 * @desc Gets the away team
	 */
	public function GetAwayTeam() { return isset($this->a_away_teams[0]) ? $this->a_away_teams[0] : null; }       

	/**
	 * @return int
	 * @desc Gets the database id of the home team
	 */
	function GetHomeTeamId()
	{
		return (is_object($this->o_home_team)) ? $this->o_home_team->GetId() : null;
	}

	/**
	 * @return int
	 * @desc Gets the database id of the away team
	 */
	function GetAwayTeamId()
	{
		return (isset($this->a_away_teams[0])) ? $this->a_away_teams[0]->GetId() : null;
	}

	/**
	 * @return void
	 * @param Team $o_team
	 * @desc Add an away team to a tournament
	 */
	function AddAwayTeam(Team $o_team)
	{
		if (!isset($this->a_away_teams[0])) $this->SetAwayTeam($o_team);
		else $this->a_away_teams[] = $o_team;
	}

	/**
	 * @return Team[]
	 * @desc Gets all of the away teams in a tournament
	 */
	public function GetAwayTeams()
	{
		return $this->a_away_teams;
	}

	/**
	 * @return void
	 * @param	string $s_title
	 * @desc Sets the title of the match, eg "Team 1 v Team 2"
	 */
	public function SetTitle($s_title)
	{
		$this->s_title = (string)$s_title;
	}

	/**
	 * @return string
	 * @desc Gets the title of the match, eg "Team 1 v Team 2"
	 */
	public function GetTitle()
	{
		if ((!isset($this->s_title) or !$this->s_title) and !$this->b_custom_title) $this->s_title = $this->GenerateTitle();
		return $this->s_title;
	}

	/**
	 * Sets whether the title has been customised
	 *
	 * @param bool $b_custom
	 */
	public function SetUseCustomTitle($b_custom)
	{
		$this->b_custom_title = (bool)$b_custom;
	}

	/**
	 * Gets whether the title has been customised
	 *
	 * @return bool
	 */
	public function GetUseCustomTitle()
	{
		return $this->b_custom_title;
	}

	/**
	 * @access private
	 * @return string
	 * @desc Generates the title of the match from match information, eg "Team 1 v Team 2"
	 */
	function GenerateTitle()
	{
		$tbc = 'To be confirmed';

		$s_home = $tbc;
		if (is_object($this->o_home_team)) {
	        if ($this->o_home_team->GetName()) {
		        $s_home = $this->o_home_team->GetName();
            } else {
                # Better than blank when debugging
                $s_home = $this->o_home_team->GetId();
            }
		} 
        
		$s_away = $tbc;
		if (isset($this->a_away_teams[0])) {
		      if ($this->a_away_teams[0]->GetName()) {
		          $s_away = $this->a_away_teams[0]->GetName();
              } else {
                  $s_away = $this->a_away_teams[0]->GetId();
              }    
		}

		if ($this->GetMatchType() == MatchType::TOURNAMENT)
		{
			# prefer the competition name for a tournament
			if ($this->Seasons()->GetCount())
			{
				$season = $this->Seasons()->GetFirst();
				/* @var $season Season */
				if ($season->GetCompetition() instanceof Competition and $season->GetCompetition()->GetName())
				{
					return $season->GetCompetition()->GetName() . ' tournament';
				}
				else
				{
					return $s_home . ' tournament';
				}
			}
			else
			{
				return $s_home . ' tournament';
			}
		}

		if (($s_home == $s_away && $s_home != $tbc)|| $this->GetMatchType() == MatchType::PRACTICE)
		{
			return $s_home . ' (practice)';
		}

		if ($this->result->GetResultType() == MatchResult::UNKNOWN)
		{
			return $s_home . ' v ' . $s_away;
		}
		else if ($this->result->GetResultType() == MatchResult::HOME_WIN)
		{
			return $s_home . ' beat ' . $s_away;
		}
		else if ($this->result->GetResultType() == MatchResult::AWAY_WIN)
		{
			return $s_home . ' lost to ' . $s_away;
		}
		else if ($this->result->GetResultType() == MatchResult::HOME_WIN_BY_FORFEIT)
		{
			return $s_home . ' won by forfeit against ' . $s_away;
		}
		else if ($this->result->GetResultType() == MatchResult::AWAY_WIN_BY_FORFEIT)
		{
			return $s_home . ' forfeit to ' . $s_away;
		}
		else if ($this->result->GetResultType() == MatchResult::TIE)
		{
			return $s_home . ' tied with ' . $s_away;
		}
		else if ($this->result->GetResultType() == MatchResult::ABANDONED)
		{
			return $s_home . ' v ' . $s_away . ' (abandoned)';
		}
		else if ($this->result->GetResultType() == MatchResult::CANCELLED)
		{
			return $s_home . ' v ' . $s_away . ' (cancelled)';
		}
		else if ($this->result->GetResultType() == MatchResult::POSTPONED)
		{
			return $s_home . ' v ' . $s_away . ' (postponed)';
		}
		else
		{
			return '';
		}
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
	 * @param int $i_input
	 * @desc Sets the start date and time of the Match as a UNIX UTC timestamp
	 */
	public function SetStartTime($i_input)	{ if (is_numeric($i_input)) $this->i_start_time = (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the start date and time of the Match as a UNIX UTC timestamp. If the start time is not known, it is returned as 12:00:00 UTC.
	 */
	public function GetStartTime()
	{
		if ($this->GetIsStartTimeKnown())
		{
			return $this->i_start_time;
		}
		else
		{
			# If the start time isn't known, return UTC midday on match day (note: this is 1pm BST). Middle of day best bet for queries that
			# rely on "is this match before/after the current date" or similar and can't check whether start time is known for each individual match.
			if (is_null($this->i_start_time)) return null;
			return gmmktime(12, 0, 0, gmdate('m', $this->i_start_time), gmdate('d', $this->i_start_time), gmdate('Y', $this->i_start_time));
		}
	}

	/**
	 * Gets the match start time as an English string in the format 10am, Monday 1 January 2000
	 *
	 * @param Include name of day before the date $b_include_day_name
	 * @param Refer to "this Sunday" rather than "Sunday 12 June" $b_relative_date
     * @param $abbreviate_where_possible
	 * @return string
	 */
	public function GetStartTimeFormatted($b_include_day_name=true, $b_relative_date=true, $abbreviate_where_possible=false)
	{
		if ($this->GetIsStartTimeKnown())
		{
			return Date::BritishDateAndTime($this->GetStartTime(), $b_include_day_name, $b_relative_date, $abbreviate_where_possible);
		}
		else
		{
			return Date::BritishDate($this->GetStartTime(), $b_include_day_name, $b_relative_date, $abbreviate_where_possible);
		}
	}

	/**
	 * @return void
	 * @param bool $b_known
	 * @desc Sets whether the time of day when the match starts is known (if false, GetStartTime() returns only the date)
	 */
	public function SetIsStartTimeKnown($b_known)	{ $this->b_time_known = (bool)$b_known; }

	/**
	 * @return bool
	 * @desc Gets whether the time of day when the match starts is known (if false, GetStartTime() returns only the date)
	 */
	public function GetIsStartTimeKnown() { return $this->b_time_known; }

	/**
	 * @return void
	 * @param Ground $o_input
	 * @desc Sets the ground for the Match
	 */
	function SetGround(Ground $o_input) { $this->o_ground = &$o_input; }

	/**
	 * @return Ground
	 * @desc Gets the ground for the Match
	 */
	function GetGround() { return $this->o_ground; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the database id of the Ground
	 */
	function SetGroundId($i_input)
	{
		if (!is_object($this->o_ground)) $this->o_ground = new Ground($this->o_settings);
		$this->o_ground->SetId($i_input);
	}

	/**
	 * @return int
	 * @desc Gets the database id of the Ground
	 */
	function GetGroundId()
	{
		return (is_object($this->o_ground)) ? $this->o_ground->GetId() : null;
	}

	/**
	 * @return string
	 * @desc Gets the URL for the match
	 */
	public function GetNavigateUrl()
	{
		return $this->o_settings->GetClientRoot() . $this->GetShortUrl();
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
     * @desc Gets the URL for editing matches in the tournament
     */
    public function GetEditTournamentMatchesUrl()
    {
        return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/matches/edit';
    }

	/**
	 * @return MatchResult
	 * @desc Gets the result of the match
	 */
	public function Result() { return $this->result; }

	/**
	 * Gets a text description of the match result
	 */
	public function GetResultDescription()
	{
		if ($this->result->GetResultType() == MatchResult::UNKNOWN) return '';

		$s_text = MatchResult::Text($this->result->GetResultType());
		if (is_object($this->o_home_team)) $s_text = str_ireplace('Home', $this->o_home_team->GetName(), $s_text);
		if (is_object($this->GetAwayTeam())) $s_text = str_ireplace('Away', $this->GetAwayTeam()->GetName(), $s_text);
		return $s_text;

	}

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets notes for the match
	 */
	public function SetNotes($s_input) { $this->s_notes = (string)$s_input; }

	/**
	 * @return string
	 * @desc Gets notes for the match
	 */
	public function GetNotes() { return $this->s_notes; }
    
    
    /**
     * Gets the main phone number for the match (expected for tournaments)
     */
    public function GetContactPhone()
    {
        # There isn't actually a main phone number, so try to get the first one
        $matches = array();
        if (!preg_match("/[0-9 ]{11,13}/", $this->s_notes, $matches)) return "";
        return trim($matches[0]);
    }

    /**
     * Gets the main e-mail address for the match (expected for tournaments)
     */
    public function GetContactEmail()
    {
        # There isn't actually a main e-mail address, so try to get the first one

        # From http://regexlib.com/REDetails.aspx?regexp_id=328
        $email_pattern = "/((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-])+\.)+[A-Za-z\-]+))/";

        $matches = array();
        if (!preg_match($email_pattern, $this->s_notes, $matches)) return "";
        return $matches[0];
    }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets a new comment to post about the match
	 */
	public function SetNewComment($s_input) { $this->s_new_comment = (string)$s_input; }

	/**
	 * @return string
	 * @desc Gets a new comment to post about the match
	 */
	public function GetNewComment() { return $this->s_new_comment; }

	/**
	 * @return void
	 * @param MatchType $i_type
	 * @desc Sets whether this is a single match or tournament
	 */
	function SetMatchType($i_type)
	{
		$this->i_type = (int)$i_type;
	}

	/**
	 * @return MatchType
	 * @desc Gets whether this is a single match or tournament
	 */
	function GetMatchType()
	{
		return $this->i_type;
	}

	/**
	 * @return void
	 * @param int $player_type
	 * @desc Sets the player type allowed to play in the match
	 */
	public function SetPlayerType($player_type) { $this->player_type = (int)$player_type; }

	/**
	 * @return int
	 * @desc Gets the player type allowed to play in the match
	 */
	public function GetPlayerType() { return $this->player_type; }

    /**
     * @return void
     * @param int $qualification
     * @desc Sets how teams qualify for the match
     */
    public function SetQualificationType($qualification) { $this->qualification = (int)$qualification; }

    /**
     * @return int
     * @desc Gets how teams qualify for the match
     */
    public function GetQualificationType() { return $this->qualification; }

	/**
	 * Sets the maximum number of players per team
	 * @param int $player_count
	 * @return void
	 */
	public function SetMaximumPlayersPerTeam($player_count) { $this->players_per_team = (int)$player_count; }

	/**
	 * Gets whether the number of players per team is known, or is using a default value
	 * @return bool
	 */
	public function GetIsMaximumPlayersPerTeamKnown() { return isset($this->players_per_team); }

	/**
	 * Gets the maximum number of players per team
	 * @return int
	 */
	public function GetMaximumPlayersPerTeam()
	{
		if (isset($this->players_per_team)) return $this->players_per_team;

		# Not set, use defaults
		if ($this->GetMatchType() == MatchType::TOURNAMENT) return 8;
		return 11;
	}
    
    /**
     * Sets the maximum amount of teams that can be accommodated if this is a tournament
     * @param int $maximum
     */
    public function SetMaximumTeamsInTournament($maximum) {
        if (is_null($maximum) or intval($maximum) === 0) {
            $this->max_tournament_teams = null;
        } else {
            $this->max_tournament_teams = (int)$maximum;
        }
    }
    
    /**
     * Gets the maximum amount of teams that can be accommodated if this is a tournament
     */
    public function GetMaximumTeamsInTournament() {
        return $this->max_tournament_teams;
    }
    
    /**
     * Sets how many spaces are left for more teams to enter a tournament
     * @param int $spaces
     */
    public function SetSpacesLeftInTournament($spaces) {
        $this->spaces_in_tournament = is_null($spaces) ? null : (int)$spaces;
    }
    
    /**
     * Gets how many spaces are left for more teams to enter a tournament
     */
    public function GetSpacesLeftInTournament() {
        if (!is_null($this->spaces_in_tournament)) {            
            return $this->spaces_in_tournament;
        } 
        else if (!is_null($this->max_tournament_teams)) {
            return $this->max_tournament_teams - count($this->a_away_teams);
        } else {
            return null;
        }
    }
    
    /**
     * Sets the order of the match if it is part of a tournament
     */
    public function SetOrderInTournament($order) {
        if (is_null($order) or intval($order) === 0) {
            $this->order_in_tournament = null;
        } else {
            $this->order_in_tournament = (int)$order;
        }
    }
    
    /**
     * Gets the order of the match if it is part of a tournament
     */
    public function GetOrderInTournament() {
        return $this->order_in_tournament;
    }

	/**
	 * Sets the number of overs in each innings
	 * @param int $overs
	 * @return void
	 */
	public function SetOvers($overs) { $this->overs = (int)$overs; }

	/**
	 * Gets whether the number of overs is known, or is using a default value
	 * @return unknown_type
	 */
	public function GetIsOversKnown() { return isset($this->overs); }

	/**
	 * Gets the number of overs in each innings
	 * @return int
	 */
	public function GetOvers()
	{
		if (isset($this->overs)) return $this->overs;

		# Not set, use defaults
		if ($this->GetMatchType() == MatchType::TOURNAMENT) return 6;
		return 12;
	}

	/**
	 * @return void
	 * @param Match $o_match
	 * @desc Sets the tournament this match is a part of
	 */
	function SetTournament(Match $o_match)
	{
		$this->o_tournament_match = $o_match;
	}

	/**
	 * @return Match
	 * @desc Gets the tournament this match is a part of
	 */
	function &GetTournament()
	{
		return $this->o_tournament_match;
	}

	/**
	 * @return bool
	 * @param Match $o_match
	 * @desc Add a match which forms part of this tournament
	 */
	function AddMatchInTournament(Match $o_match)
	{
		if ($this->GetMatchType() == MatchType::TOURNAMENT)
		{
			$o_match->SetMatchType(MatchType::TOURNAMENT_MATCH);
            $o_match->SetTournament($this);
			$this->a_matches_in_tournament[] = &$o_match;
			return true;
		}
		else return false;
	}

	/**
	 * @return Match[]
	 * @desc Gets an array of matches in this tournament
	 */
	function &GetMatchesInTournament() { return $this->a_matches_in_tournament; }

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
	 * Sets the short URL for a match
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = trim((string)$s_url); }

	/**
	 * Gets the short URL for a match
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->s_short_url; }

	/**
	 * Sets whether the short URL has been explicitly specified
	 *
	 * @param bool $b_custom
	 */
	public function SetUseCustomShortUrl($b_custom)
	{
		$this->b_custom_url = (bool)$b_custom;
	}

	/**
	 * Gets whether the title has been explicitly specified
	 *
	 * @return bool
	 */
	public function GetUseCustomShortUrl()
	{
		return $this->b_custom_url;
	}

	/**
	 * Gets the format to use for a team's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings)
	{
		return new ShortUrlFormat($settings->GetTable('Match'), 'short_url', array('match_id', 'match_type'), array('GetId', 'GetMatchType'),
		array(
		'{0}' => '/play/matches/match.php?item={0}',
        '{0}/add/teams' => '/play/tournaments/teams.php?item={0}&action=add',
        '{0}/add/competitions' => '/play/tournaments/seasons.php?item={0}&action=add',
		'{0}/edit' => '/play/matches/matchedit.php?item={0}&type={1}',
        '{0}/edit/highlights' => '/play/matches/highlights.php?item={0}',
        '{0}/edit/scorecard' => '/play/matches/scorecard.php?item={0}',
        '{0}/edit/teams' => '/play/tournaments/teams.php?item={0}',
        '{0}/edit/competitions' => '/play/tournaments/seasons.php?item={0}',
		'{0}/delete' => '/play/matches/matchdelete.php?item={0}',
		'{0}/calendar' => $settings->GetUrl('MatchCalendar'),
		'{0}/matches/edit' => "/play/tournaments/matches.php?item={0}",
        '{0}/matches/results' => "/play/results.php?tournament={0}",
		'{0}/statistics' => "/play/statistics/summary-match.php?match={0}&type={1}",
        '{0}.json' => "/play/statistics/match.js.php?match={0}"
		));
	}

	/**
	 * Gets the format to use for a team's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Match::GetShortUrlFormatForType($this->o_settings);
	}

	/**
	 * Suggests a short URL to use to view the match
	 *
	 * @param int $i_preference
	 * @return string
	 */
	public function SuggestShortUrl($i_preference=1)
	{
		switch ($this->GetMatchType())
		{
			case MatchType::TOURNAMENT:
				$s_url = strtolower(html_entity_decode($this->GetTitle()));
				break;
			case MatchType::PRACTICE:
                if ($this->GetHomeTeam() instanceof Team) {
				    $s_url = $this->GetHomeTeam()->GetShortUrl();
                } else {
                    $s_url = strtolower(html_entity_decode($this->GetTitle()));
                }
				break;
			default:
				if ($this->GetHomeTeam() instanceof Team and $this->GetAwayTeam() instanceof Team)
				{
					$s_url = $this->GetHomeTeam()->GetShortUrl() . "-" . $this->GetAwayTeam()->GetShortUrl();
				}
				else
				{
					$s_url = strtolower(html_entity_decode($this->GetTitle()));
				}
		}

		# Remove numbers (can be entities)
		if ($this->GetMatchType() == MatchType::TOURNAMENT) $s_url = preg_replace('/[^a-z ]/i', '', $s_url);

		$s_url .= "-" . strtolower(date('jMY', $this->GetStartTime()));

		# Remove punctuation
		$s_url = preg_replace('/[^a-z0-9- ]/i', '', $s_url);

		# Remove noise words
		$s_url = preg_replace(array('/\bstoolball\b/i', '/\bleague\b/i', '/\bladies\b/i', '/\bmixed\b/i', '/\bdistrict\b/i', '/\bthe\b/i', '/\band\b/i', '/\bin\b/i', '/\bwith\b/i', '/\bassociation\b/i', '/\bdivision\b/i', '/\bcounty\b/i'), '', $s_url);

		# Apply preference
		if ($i_preference >= 2)
		{
			# Append 'tournament' or 'match'
			switch ($this->GetMatchType())
			{
				case MatchType::TOURNAMENT:
					$s_url .= '-tournament';
					break;
				case MatchType::PRACTICE:
					$s_url .= '-practice';
					break;
				default:
					$s_url .= '-match';
			}
		}
		if ($i_preference > 2)
		{
			# Append a number too
			$s_url .= $i_preference-1;
		}

		# Remove spaces
		$s_url = str_replace(' ', '', $s_url);

		return "match/$s_url";
	}


    /**
     * @return string
     * @desc Gets the base URL for the stages of adding a tournament
     */
    private function AddTournamentBaseUrl()
    {
        return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/add';
    }

	/**
	 * @return string
	 * @desc Gets the URL for editing the match
	 */
	public function GetEditNavigateUrl()
	{
		return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/edit';
	}

	/**
	 * @return string
	 * @desc Gets the URL for deleting the match
	 */
	public function GetDeleteNavigateUrl()
	{
		return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . '/delete';
	}

    /**
     * @return string
     * @desc Gets the URL for editing the match scorecard
     */
    public function EditScorecardUrl()
    {
        return $this->GetEditNavigateUrl() . '/scorecard';
    }
    
    /**
     * @return string
     * @desc Gets the URL for editing the match highlights
     */
    public function EditHighlightsUrl()
    {
        return $this->GetEditNavigateUrl() . '/highlights';
    }
    
    /**
     * Gets the URL for editing the teams entered in this tournament, when first adding the tournament
     */
    public function AddTournamentTeamsUrl() 
    {
        return $this->AddTournamentBaseUrl() . "/teams";
    }

    /**
     * Gets the URL for editing the teams entered in this tournament
     */
    public function EditTournamentTeamsUrl() 
    {
        return $this->GetEditNavigateUrl() . "/teams";
    }
    
    /**
     * Gets the URL for editing the competitions this tournament is in, when first adding the tournament
     */
    public function AddTournamentCompetitionsUrl() 
    {
        return $this->AddTournamentBaseUrl() . "/competitions";
    }
    
    /**
     * Gets the URL for editing the competitions this tournament is in
     */
    public function EditTournamentCompetitionsUrl() 
    {
        return $this->GetEditNavigateUrl() . "/competitions";
    }
    
	/**
	 * Gets the URI which uniquely identifies this match
	 */
	public function GetLinkedDataUri()
	{
	    $short_url = $this->GetShortUrl(); 
        if (substr($short_url,0,6) === 'match/') {
            $short_url = substr($short_url,6);
        }
		if ($this->GetMatchType() == MatchType::TOURNAMENT)
		{
			return "https://www.stoolball.org.uk/id/tournament/" . $short_url;
		}
		else
		{
			return "https://www.stoolball.org.uk/id/match/" . $short_url;
		}
	}
}
?>