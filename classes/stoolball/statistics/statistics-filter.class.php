<?php
class StatisticsFilter
{
	/**
	 * If the to or from parameters are in the query string apply date filter and return dates
	 * @param StatisticsManager $statistics_manager
	 */
	public static function SupportDateFilter(StatisticsManager $statistics_manager)
	{
		$filter_data = array(null, null, "");

		$filter_data[0] = "";
		if (isset($_GET['from']) and is_string($_GET['from']))
		{
			# Replace slashes with hyphens in submitted date because then it's treated as a British date, not American
			$date = is_numeric($_GET['from']) ? (int)$_GET['from'] : strtotime(str_replace("/", "-", $_GET['from']));
			if ($date !== false) $filter_data[0] = $date;
		}
		$to = "";
		if (isset($_GET['to']) and is_string($_GET['to']))
		{
			# Replace slashes with hyphens in submitted date because then it's treated as a British date, not American
			$date = is_numeric($_GET['to']) ? (int)$_GET['to'] : strtotime(str_replace("/", "-", $_GET['to']));
			if ($date !== false) $filter_data[1] = $date;
		}

		if ($filter_data[0]) $statistics_manager->FilterAfterDate($filter_data[0]);
		if ($filter_data[1]) $statistics_manager->FilterBeforeDate($filter_data[1]);

		if ($filter_data[0] and $filter_data[1])
		{
			# Test whether this is a season
			$three_months_later = intval($filter_data[0]) + (60 * 60 * 24 * 30 * 3);
			$season_dates = Season::SeasonDates($three_months_later);
			if ($filter_data[0] == $season_dates[0] and $filter_data[1] == $season_dates[1])
			{
				$start_year = gmdate("Y", $filter_data[0]);
				$end_year = gmdate("Y", $filter_data[1]);
				if ($start_year == $end_year)
				{
					$filter_data[2] = "in the $start_year season ";
				}
				else
				{
					$filter_data[2] = "in the $start_year/" . substr($end_year, 2, 2) . " season ";
				}
			}
			else
			{
				$filter_data[2] = "between " . Date::BritishDate($filter_data[0], false, true, false) . " and " . Date::BritishDate($filter_data[1], false, true, false) . " ";
			}
		}
		else if ($filter_data[0])
		{
			$filter_data[2] = "since " . Date::BritishDate($filter_data[0], false, true, false) . " ";
		}
		else if ($filter_data[1])
		{
			$filter_data[2] = "before " . Date::BritishDate($filter_data[1], false, true, false) . " ";
		}

		return $filter_data;
	}

	/**
	 * If the player parameter is in the query string apply player filter
	 * @param SiteSettings $settings
	 * @param MySqlConnection $connection
	 * @param StatisticsManager $statistics_manager
	 */
	public static function ApplyPlayerFilter(SiteSettings $settings, MySqlConnection $connection, StatisticsManager $statistics_manager)
	{
		$filter = "";
		if (isset($_GET['player']) and is_numeric($_GET['player']))
		{
			require_once('stoolball/player-manager.class.php');
			$player_manager = new PlayerManager($settings, $connection);
			$player_manager->ReadPlayerById($_GET['player']);
			$player = $player_manager->GetFirst();
			unset($player_manager);

			if (!is_null($player))
			{
				$statistics_manager->FilterByPlayer(array($player->GetId()));
				$filter = "for " . $player->GetName() . " ";
			}
		}
		return $filter;
	}

    /**
     * If the tournament parameter is in the query string apply tournament filter
     * @param SiteSettings $settings
     * @param MySqlConnection $connection
     * @param StatisticsManager $statistics_manager
     */
    public static function ApplyTournamentFilter(SiteSettings $settings, MySqlConnection $connection, StatisticsManager $statistics_manager)
    {
        $filter = "";
        if (isset($_GET['tournament']) and is_numeric($_GET['tournament']))
        {
            require_once('stoolball/match-manager.class.php');
            $match_manager = new MatchManager($settings, $connection);
            $match_manager->ReadByMatchId(array($_GET['tournament']));
            $tournament = $match_manager->GetFirst();
            unset($match_manager);

            if (!is_null($tournament))
            {
                $statistics_manager->FilterByTournament(array($tournament->GetId()));
                $filter = "in the " . $tournament->GetTitle() . " on " . Date::BritishDate($tournament->GetStartTime()) . " ";
            }
        }
        return $filter;
    }

	/**
	 * Get the teams available for filtering, and if the team parameter is in the query string apply team filter
	 * @param StatisticsManager $statistics_manager
	 * @return Array containing teams, current team id, and text for filter description
	 */
	public static function SupportTeamFilter(StatisticsManager $statistics_manager)
	{
		$filter_data = array(array(), null, "");

		$filter_data[0] = $statistics_manager->ReadTeamsForFilter();

		if (isset($_GET['team']) and is_numeric($_GET['team']))
		{
			if (array_key_exists($_GET['team'], $filter_data[0]))
			{
				$statistics_manager->FilterByTeam(array($filter_data[0][$_GET['team']]->GetId()));
				$filter_data[1] = $filter_data[0][$_GET['team']]->GetId();
				$filter_data[2] = "for " . $filter_data[0][$_GET['team']]->GetName() . " ";
			}
		}
		return $filter_data;
	}

	/**
	 * Get the opposition teams available for filtering, and if the opposition parameter is in the query string apply opposition filter
	 * @param StatisticsManager $statistics_manager
	 * @return Array containing teams, current team id, and text for filter description
	 */
	public static function SupportOppositionFilter(StatisticsManager $statistics_manager)
	{
		$filter_data = array(array(), null, "");

		$filter_data[0] = $statistics_manager->ReadOppositionTeamsForFilter();

		if (isset($_GET['opposition']) and is_numeric($_GET['opposition']))
		{
			if (array_key_exists($_GET['opposition'], $filter_data[0]))
			{
				$statistics_manager->FilterByOpposition(array($filter_data[0][$_GET['opposition']]->GetId()));
				$filter_data[1] = $filter_data[0][$_GET['opposition']]->GetId();
				$filter_data[2] = "against " . $filter_data[0][$_GET['opposition']]->GetName() . " ";
			}
		}
		return $filter_data;
	}

    /**
     * Get the match types available for filtering, and if match type parameter is in the query string apply the match type filter
     * @param StatisticsManager $statistics_manager
     * @return Array containing match types, current match type id, and text for filter description
     */
    public static function SupportMatchTypeFilter(StatisticsManager $statistics_manager)
    {
        require_once("stoolball/match-type.enum.php");
        $match_types = array(MatchType::CUP, MatchType::FRIENDLY, MatchType::LEAGUE, MatchType::TOURNAMENT_MATCH);
        $filter_data = array($match_types, null, "");

        if (isset($_GET['match-type']) and is_numeric($_GET['match-type']))
        {
            if (in_array($_GET['match-type'], $match_types))
            {
                $statistics_manager->FilterByMatchType(array((int)$_GET['match-type']));
                $filter_data[1] = (int)$_GET['match-type'];
                $filter_data[2] = "in " . MatchType::Text((int)$_GET['match-type']) . "es ";
            }
        }
        return $filter_data;
    }
    

    /**
     * Get the player types available for filtering, and if a player type parameter is in the query string apply player type filter
     * @param StatisticsManager $statistics_manager
     * @return Array containing player types, current player type id, and text for filter description
     */
    public static function SupportPlayerTypeFilter(StatisticsManager $statistics_manager)
    {
        require_once("stoolball/player-type.enum.php");
        $player_types = array(PlayerType::LADIES, PlayerType::MIXED, PlayerType::GIRLS, PlayerType::JUNIOR_MIXED);
        $filter_data = array($player_types, null, "");

        if (isset($_GET['player-type']) and is_numeric($_GET['player-type']))
        {
            if (in_array($_GET['player-type'], $player_types))
            {
                $statistics_manager->FilterByPlayerType(array((int)$_GET['player-type']));
                $filter_data[1] = (int)$_GET['player-type'];
                $filter_data[2] .= "in " . strtolower(PlayerType::Text((int)$_GET['player-type'])) . " matches ";
            }
        }
        return $filter_data;
    }
    
    /**
     * Combines the descriptions for type filters into a single plain English phrase
     */
    public static function CombineMatchTypeDescriptions($description) {
        $description = str_replace("ladies ", "ladies’ ", $description);
        $description = str_replace("girls ", "girls’ ", $description);
        $description = StatisticsFilter::KeepOnlyFirstOccurrenceInString($description, "in ");
        $description = StatisticsFilter::KeepOnlyLastOccurrenceInString($description, "matches ");
        return $description;
    } 
    
    private static function KeepOnlyFirstOccurrenceInString($subject, $substring){
        if (substr_count($subject, $substring) > 1) {
            $after_first = strpos($subject, $substring)+strlen($substring);
            $subject = substr($subject, 0, $after_first) . str_replace($substring, "", substr($subject, $after_first));
        }
        return $subject;
    }

    private static function KeepOnlyLastOccurrenceInString($subject, $substring){
        if (substr_count($subject, $substring) > 1) {
            $before_last = strrpos($subject, $substring);
            $subject = str_replace($substring, "", substr($subject, 0, $before_last)) . substr($subject, $before_last);
        }
        return $subject;
    }

	/**
	 * Gets the competitions available for filtering, and if the competition parameter is in the query string apply competition filter
	 * @param StatisticsManager $statistics_manager
	 */
	public static function SupportCompetitionFilter(StatisticsManager $statistics_manager)
	{
		$filter_data = array(array(), null, "");

		$filter_data[0] = $statistics_manager->ReadCompetitionsForFilter();

		if (isset($_GET['competition']) and is_numeric($_GET['competition']))
		{
			if (array_key_exists($_GET['competition'], $filter_data[0]))
			{
				$statistics_manager->FilterByCompetition(array($filter_data[0][$_GET['competition']]->GetId()));
				$filter_data[1] = $filter_data[0][$_GET['competition']]->GetId();
				$filter_data[2] = "in the " . $filter_data[0][$_GET['competition']]->GetName() . " ";
			}
		}
		return $filter_data;
	}

	/**
	 * If the season parameter is in the query string apply season filter
	 * @param SiteSettings $settings
	 * @param MySqlConnection $connection
	 * @param StatisticsManager $statistics_manager
	 */
	public static function ApplySeasonFilter(SiteSettings $settings, MySqlConnection $connection, StatisticsManager $statistics_manager)
	{
		$filter = "";

		if (isset($_GET['season']) and is_numeric($_GET['season']))
		{
			require_once('stoolball/season-manager.class.php');
			$season_manager = new SeasonManager($settings, $connection);
			$season_manager->ReadById(array($_GET['season']));
			$season = $season_manager->GetFirst();
			unset($season_manager);

			if (!is_null($season))
			{
				$statistics_manager->FilterBySeason(array($season->GetId()));
				$filter = "in the " . $season->GetCompetitionName() . " ";
			}
		}

		return $filter;
	}

	/**
	 * Get the grounds available for filtering, and if the ground parameter is in the query string apply ground filter
	 * @param StatisticsManager $statistics_manager
	 */
	public static function SupportGroundFilter(StatisticsManager $statistics_manager)
	{
		$filter_data = array(array(), null, "");

		$filter_data[0] = $statistics_manager->ReadGroundsForFilter();

		if (isset($_GET['ground']) and is_numeric($_GET['ground']))
		{
			if (array_key_exists($_GET['ground'], $filter_data[0]))
			{
				$statistics_manager->FilterByGround(array($filter_data[0][$_GET['ground']]->GetId()));
				$filter_data[1] = $filter_data[0][$_GET['ground']]->GetId();
				$filter_data[2] = "at " . $filter_data[0][$_GET['ground']]->GetNameAndTown() . " ";
			}
		}
		return $filter_data;
	}

	/**
	 * Get the batting positions available for filtering, and if the batting position parameter is in the query string apply batting position filter
	 * @param StatisticsManager $statistics_manager
	 */
	public static function SupportBattingPositionFilter(StatisticsManager $statistics_manager)
	{
		$filter_data = array(array(), null, "");

		$filter_data[0] = $statistics_manager->ReadBattingPositionsForFilter();

		if (isset($_GET['batpos']) and is_numeric($_GET['batpos']))
		{
			if (array_key_exists($_GET['batpos'], $filter_data[0]))
			{
				$statistics_manager->FilterByBattingPosition(array($filter_data[0][$_GET['batpos']]));
				$filter_data[1] = $filter_data[0][$_GET['batpos']];
				if ($filter_data[0][$_GET['batpos']] == 1)
				{
					$filter_data[2] = "opening the batting ";
				}
				else
				{
					$filter_data[2] = "batting at " . $filter_data[0][$_GET['batpos']] . " ";
				}
			}
		}
		return $filter_data;
	}
    
    /**
     * If the innings parameter is in the query string apply the innings filter
     * @param StatisticsManager $statistics_manager
     */
    public static function SupportInningsFilter(StatisticsManager $statistics_manager)
    {
        $filter_data = array(array(1,2), null, "");

        if (isset($_GET['innings']) and is_numeric($_GET['innings']))
        {
            if (in_array($_GET['innings'], $filter_data[0]))
            {
                $filter_data[1] = (int)$_GET['innings'];
                $statistics_manager->FilterByInnings($filter_data[1]);
                if ($filter_data[1] === 1)
                {
                    $filter_data[2] = "in the first innings ";
                }
                else
                {
                    $filter_data[2] = "in the second innings ";
                }
            }
        }
        return $filter_data;
    }
    
    /**
     * If the match result parameter is in the query string apply the match result filter
     * @param StatisticsManager $statistics_manager
     */
    public static function SupportMatchResultFilter(StatisticsManager $statistics_manager)
    {
        $filter_data = array(array(-1,0,1), null, "");

        if (isset($_GET['result']) and is_numeric($_GET['result']))
        {
            if (in_array($_GET['result'], $filter_data[0]))
            {
                $filter_data[1] = (int)$_GET['result'];
                $statistics_manager->FilterByMatchResult($filter_data[1]);
                if ($filter_data[1] === -1)
                {
                    $filter_data[2] = "to lose a match ";
                }
                else if ($filter_data[1] === 0)
                {
                    $filter_data[2] = "to tie a match ";
                }
                else
                {
                    $filter_data[2] = "to win a match ";
                }
            }
        }
        return $filter_data;
    }
}