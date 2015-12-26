<?php
require_once('stoolball/team.class.php');
require_once('stoolball/competition.class.php');
require_once('stoolball/points-adjustment.class.php');
require_once('collection.class.php');
require_once('data/date.class.php');
require_once('http/has-short-url.interface.php');
require_once('http/short-url.class.php');
require_once('media/has-media.interface.php');

class Season extends Collection implements IHasShortUrl, IHasMedia
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $o_settings;
	private $i_id;
	private $s_name;
	private $b_latest;
	private $i_start_year;
	private $i_end_year;
	private $s_intro;
	private $s_results;
	private $a_teams;

	/**
	 * Teams which joined the league but withdrew during the season
	 *
	 * @var Collection
	 */
	private $teams_withdrawn_from_league;
	private $a_galleries;
	/**
	 * The competition of which this season is a part
	 *
	 * @var Competition
	 */
	private $o_competition;
	private $b_show_table;
	private $table_runs_scored;
	private $table_runs_conceded;
	/**
	 * Possible results for a match played in this season
	 *
	 * @var Collection
	 */
	private $o_possible_results;
	private $s_short_url;
	/**
	 * Additional points deducted from/awarded to teams in this season
	 *
	 * @var Collection
	 */
	private $o_points_adjustments;

	/**
	 * The types of matches which this season can include
	 *
	 * @var Collection
	 */
	private $match_types;

	/**
	 * @return Season
	 * @param SiteSettings $o_settings
	 * @desc Creates a new stoolball season
	 */
	function __construct(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;

		$this->a_teams = array();
		$this->teams_withdrawn_from_league = new Collection();
		$this->a_galleries = array();
		$this->match_types = new Collection();
		$this->o_possible_results = new Collection(null, 'MatchResult');
		$this->o_points_adjustments = new Collection(null, 'PointsAdjustment');

		parent::Collection();

		$this->s_item_class = 'Match';

		# Default to hiding league table
		$this->SetShowTable(false);
	}

	/**
	 * Gets a ContentType that identifies this as a season
	 *
	 * @return ContentType
	 */
	public function GetContentType() { return ContentType::STOOLBALL_SEASON; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the unique database id of the season
	 */
	function SetId($i_input) { $this->i_id = (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the unique database id of the season
	 */
	function GetId() { return $this->i_id; }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the name of the season - eg Summer 2005
	 */
	public function SetName($s_input) { $this->s_name = trim((string)$s_input); }

	/**
	 * @return string
	 * @desc Gets the name of the season - eg Summer 2005
	 */
	public function GetName() { return $this->s_name; }

	/**
	 * @return string
	 * @desc Gets the name of the competition and season - eg Sussex Mixed League, 2005 season
	 */
	public function GetCompetitionName()
	{
		if (!isset($this->o_competition)) return $this->GetName();

		$s_name = $this->o_competition->GetName();

		if ($this->GetName())
		{
			$s_name .= ', ' . $this->GetName() . ' season';
		}
		else
		{
			$s_years = $this->GetYears();
			if ($s_years) $s_name .= ', ' . $s_years . ' season';
		}

		return $s_name;
	}

	/**
	 * Gets the name of the competition and season - eg Sussex Mixed League, 2005 season
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->GetCompetitionName();
	}

	/**
	 * @return string
	 * @desc Gets the years of the season - eg 2005 or 2005/06
	 */
	function GetYears()
	{
		if (!isset($this->i_start_year) or is_null($this->i_start_year) or !isset($this->i_end_year) or is_null($this->i_end_year)) return '';
		if ($this->i_start_year == $this->i_end_year) return (string)$this->i_start_year;
		if ($this->i_start_year == ($this->i_end_year - 1)) return (string)$this->i_start_year . '/' . substr((string)$this->i_end_year, 2);
		return (string)$this->i_start_year . '/' . (string)$this->i_end_year;
	}

	/**
	 * @return void
	 * @param bool $b_latest
	 * @desc Sets whether this is the latest known season of the competition
	 */
	function SetIsLatest($b_latest) { $this->b_latest = (bool)$b_latest; }

	/**
	 * @return bool
	 * @desc Gets whether this is the latest known season of the competition
	 */
	function GetIsLatest() { return $this->b_latest; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the year the season starts in
	 */
	function SetStartYear($i_input) { $this->i_start_year = (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the year the season starts in
	 */
	function GetStartYear() { return $this->i_start_year; }

	/**
	 * @return void
	 * @param int $i_input
	 * @desc Sets the year the season ends in
	 */
	function SetEndYear($i_input) { $this->i_end_year = (int)$i_input; }

	/**
	 * @return int
	 * @desc Gets the year the season ends in
	 */
	function GetEndYear() { return $this->i_end_year; }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the intro to the season
	 */
	function SetIntro($s_input) { $this->s_intro = trim((string)$s_input); }

	/**
	 * @return string
	 * @desc Gets the intro to the season
	 */
	function GetIntro() { return $this->s_intro; }

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the information about the season's results
	 */
	function SetResults($s_input) { $this->s_results = trim((string)$s_input); }

	/**
	 * @return string
	 * @desc Gets the information about the season's results
	 */
	function GetResults() { return $this->s_results; }

	/**
	 * @return void
	 * @param Competition $o_input
	 * @desc Sets the competition of which this season is a part
	 */
	function SetCompetition(Competition $o_input) { $this->o_competition = &$o_input; }

	/**
	 * @return Competition
	 * @desc Gets the competition of which this season is a part
	 */
	function GetCompetition() { return $this->o_competition; }

	/**
	 * @return void
	 * @param Team $o_team
	 * @desc Add a team which is taking part in the competition
	 */
	public function AddTeam(Team $o_team)
	{
		$this->a_teams[] = &$o_team;
	}

	/**
	 * @return Team[]
	 * @desc Gets the teams in the competition in this season
	 */
	public function &GetTeams() { return $this->a_teams; }

	/**
	 * Teams which played in the league but withdrew during the season
	 *
	 * @return Collection
	 */
	public function TeamsWithdrawnFromLeague() { return $this->teams_withdrawn_from_league; }

	/**
	 * @return void
	 * @param Match[] $a_input
	 * @desc Sets the matches which form this season
	 */
	function SetMatches(&$a_input)
	{
		$this->SetItems($a_input);
	}

	/**
	 * @return Match[]
	 * @desc Get an array of Matches which form this season
	 */
	function &GetMatches() { return $this->a_items; }

	/**
	 * Gets the match types which are in this season
	 *
	 * @return Collection
	 */
	public function MatchTypes()
	{
		return $this->match_types;
	}

	/**
	 * @return void
	 * @param MediaGallery $o_gallery
	 * @desc Add a media gallery
	 */
	function AddMediaGallery(MediaGallery $o_gallery)
	{
		$this->a_galleries[] = $o_gallery;
	}

	/**
	 * @return void
	 * @param MediaGallery[] $a_galleries
	 * @desc Sets the media galleries related to the season
	 */
	function SetMediaGalleries($a_galleries) { if (is_array($a_galleries)) $this->a_galleries = &$a_galleries; }

	/**
	 * @return MediaGallery[]
	 * @desc Gets the media galleries related to the season
	 */
	function GetMediaGalleries() { return $this->a_galleries; }

	/**
	 * @return void
	 * @param Season $o_season
	 * @desc Merge details from supplied season into this season. Where both copies have info, this copy is assumed correct.
	 */
	function Merge($o_season)
	{
		if (!$this->i_id) $this->SetId($o_season->GetId());
		if (!$this->s_name) $this->SetName($o_season->GetName());
		if (!$this->i_start_year) $this->SetStartYear($o_season->GetStartYear());
		if (!$this->i_end_year) $this->SetEndYear($o_season->GetEndYear());
		if (!$this->s_intro) $this->SetIntro($o_season->GetIntro());
		if (!$this->s_results) $this->SetResults($o_season->GetResults());
		if (!count($this->a_teams)) $this->a_teams = $o_season->GetTeams();
		if (!count($this->a_galleries)) $this->a_galleries = $o_season->GetMediaGalleries();
		if (!is_object($this->o_competition)) $this->SetCompetition($o_season->GetCompetition());
	}

	/**
	 * @return string
	 * @desc Gets the URL for the season
	 */
	public function GetNavigateUrl()
	{

		return $this->o_settings->GetClientRoot() . $this->GetShortUrl();
	}

	/**
	 * @return string
	 * @desc Gets the URL to edit the season
	 */
	public function GetEditSeasonUrl()
	{
		return $this->o_settings->GetFolder('Play') . 'competitions/seasonedit.php?item=' . $this->GetId();
	}

	/**
	 * @return string
	 * @desc Gets the URL to delete the season
	 */
	public function GetDeleteSeasonUrl()
	{
		return "/play/competitions/seasondelete.php?item=" . $this->GetId();
	}

	/**
	 * @return string
	 * @param MatchType $i_type
	 * @desc Gets the URL for adding a match to the season
	 */
	public function GetNewMatchNavigateUrl($i_type)
	{
		$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl();
		switch ($i_type)
		{
			case MatchType::TOURNAMENT:
				$s_url .= '/matches/tournaments/add';
				break;
			case MatchType::LEAGUE:
				$s_url .= '/matches/league/add';
				break;
			case MatchType::CUP:
				$s_url .= '/matches/cup/add';
				break;
			case MatchType::PRACTICE:
				$s_url .= '/matches/practices/add';
				break;
			default:
				$s_url .= '/matches/friendlies/add';
		}

		return $s_url;
	}

	/**
	 * @return string
	 * @desc Gets the URL for updating the season's match reults
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
	 * @desc Gets the URL for adding a new media gallery associated with the season
	 */
	public function GetAddMediaGalleryNavigateUrl()
	{
		$s_url = '';
		if ($this->GetShortUrl())
		{
			$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . 'createalbum';
		}
		else
		{
			$s_url = str_replace('{0}', $this->GetId(), $this->o_settings->GetUrl('SeasonAddMediaGallery'));
		}

		return $s_url;
	}

	/**
	 * Gets the URL for statistics about the season
	 * @return string
	 */
	public function GetStatisticsUrl() { return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . "/statistics"; }

    /**
     * Gets the URL for the season league table
     * @return string
     */
    public function GetTableUrl() { return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . "/table"; }

    /**
     * Gets the URL for the season map
     * @return string
     */
    public function GetMapUrl() { return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . "/map"; }

	/**
	 * Sets whether to show a league table of results
	 *
	 * @param bool $b_show
	 */
	function SetShowTable($b_show)
	{
		$this->b_show_table = (bool)$b_show;
	}

	/**
	 * Gets whether to show a league table of results
	 *
	 * @return bool
	 */
	function GetShowTable() { return $this->b_show_table; }

	/**
	 * Sets whether to show runs scored in a league table of results
	 *
	 * @param bool $b_show
	 */
	function SetShowTableRunsScored($b_show)
	{
		$this->table_runs_scored = (bool)$b_show;
	}

	/**
	 * Gets whether to show runs scored in a league table of results
	 *
	 * @return bool
	 */
	function GetShowTableRunsScored() { return $this->table_runs_scored; }

	/**
	 * Sets whether to show runs conceded in a league table of results
	 *
	 * @param bool $b_show
	 */
	function SetShowTableRunsConceded($b_show)
	{
		$this->table_runs_conceded = (bool)$b_show;
	}

	/**
	 * Gets whether to show runs conceded in a league table of results
	 *
	 * @return bool
	 */
	function GetShowTableRunsConceded() { return $this->table_runs_conceded; }

		/**
	 * Gets the possible results that a match may return, and the points to be awarded
	 *
	 * @return Collection
	 */
	function PossibleResults() { return $this->o_possible_results; }


	/**
	 * Sets the short URL for a team
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = trim((string)$s_url); }

	/**
	 * Gets the short URL for a team
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->s_short_url; }

	/**
	 * Gets the real URL for a season
	 *
	 * @return string
	 */
	public function GetRealUrl()
	{
		return $this->o_settings->GetUrl('Season') . $this->GetId();
	}

	/**
	 * Gets the format to use for a season's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings)
	{
		return new ShortUrlFormat($settings->GetTable('Season'), 'short_url', array('season_id'), array('GetId'),
		array(
		'{0}' => $settings->GetUrl('Season') . '{0}',
		'{0}/matches/friendlies/add' => $settings->GetUrl('MatchAddFriendlyToSeason'),
		'{0}/matches/league/add' => $settings->GetUrl('MatchAddLeagueToSeason'),
		'{0}/matches/cup/add' => $settings->GetUrl('MatchAddCupToSeason'),
		'{0}/matches/practices/add' => $settings->GetUrl('MatchAddPracticeToSeason'),
		'{0}/matches/tournaments/add' => "/play/tournaments/add.php?season={0}",
		'{0}/matches/edit' => $settings->GetUrl('SeasonResults'),
		'{0}/calendar' => $settings->GetUrl('SeasonCalendar'),
		'{0}createalbum' => $settings->GetUrl('SeasonAddMediaGallery'),
		'{0}/statistics' => '/play/statistics/summary-season.php?season={0}',
        '{0}/table' => '/play/competitions/table.php?season={0}',
        '{0}/map' => '/play/competitions/map.php?season={0}'
		));
	}

	/**
	 * Gets the format to use for a season's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return Season::GetShortUrlFormatForType($this->o_settings);
	}

	/**
	 * Suggests a short URL to use to view the team
	 *
	 * @param int $i_preference
	 * @return string
	 */
	public function SuggestShortUrl($i_preference=1)
	{
		$s_url = $this->GetCompetition()->GetShortUrl() . '/' . $this->GetStartYear();

		# Apply preference
		if (($i_preference == 2) and ($this->GetEndYear() > $this->GetStartYear()))
		{
			# Append end year, so long as it's not a repeat of the start year
			$s_url .= $this->GetEndYear();
		}
		else if ($i_preference >= 2)
		{
			$i_num_to_append = $i_preference-2;
			if (!$i_num_to_append) $i_num_to_append++; # in case pref = 2
			$s_url .= $i_num_to_append;
		}

		return $s_url;
	}

	/**
	 * Gets the start and end times of a stoolball season as GMT UNIX timestamps based on a given timestamp (which defaults to now)
	 *
	 * @return int[2]
	 */
	public static function SeasonDates($i_start_time=null)
	{
		$i_now =  is_null($i_start_time) ? gmdate('U') : $i_start_time;
		$i_this_month = gmdate('m', $i_now);
		$i_this_year = gmdate('Y', $i_now);

		// May need to tweak this. Started to get match data for 2007 around mid-March, but not sure when winter season ends.
		$i_summer_season_start = 3;
		$i_winter_season_start = 10;

		if ($i_this_month < $i_summer_season_start or $i_this_month >= $i_winter_season_start)
		{
			# Winter/indoor season
			$i_start = gmmktime(0, 0, 1, 10, 1, ($i_this_month < $i_summer_season_start) ? $i_this_year-1 : $i_this_year);
			$i_end = gmmktime(23, 59, 59, $i_summer_season_start-1, 31, ($i_this_month < $i_summer_season_start) ? $i_this_year : $i_this_year+1);
		}
		else
		{
			# Summer/outdoor season
			$i_start = gmmktime(0, 0, 1, $i_summer_season_start, 1, $i_this_year);
			$i_end = gmmktime(23, 59, 59, $i_winter_season_start-1, 30, $i_this_year);
		}

		# testing only
		/*		if (SiteContext::IsDevelopment())
		 {
		 $i_start = gmmktime(0, 0, 1, 3, 1, 2007);
		 $i_end = gmmktime(23, 59, 59, 9, 30, 2007);
		 }
		 */
		return array($i_start, $i_end);
	}

	/**
	 * Additional points deducted from/awarded to teams in this season
	 *
	 * @return Collection
	 */
	public function PointsAdjustments()
	{
		return $this->o_points_adjustments;
	}
}
?>