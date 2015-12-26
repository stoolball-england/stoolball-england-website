<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/season-manager.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/user-edit-panel.class.php');

class CurrentPage extends StoolballPage
{
	private $competition;
	/**
	 * The season to display
	 *
	 * @var Season
	 */
	private $season;
	private $best_batting;
	private $best_bowling;
	private $most_runs;
	private $most_wickets;
	private $most_catches;
	private $has_player_stats;

	function OnLoadPageData()
	{
		/* @var $o_competition Competition */

		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item']))
		{
			http_response_code(400);
			exit();
		}

		# new data managers
		$o_comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());

		# get competition
		$latest = (isset($_GET['latest']) and $_GET['latest'] == '1');
		if ($latest)
		{
			$o_comp_manager->ReadById(array($_GET['item']), null);
		}
		else
		{
			$o_comp_manager->ReadById(null, array($_GET['item']));
		}
		$this->competition = $o_comp_manager->GetFirst();
        $this->season = $this->competition->GetWorkingSeason();

        # must have found a competition
        if (!$this->competition instanceof Competition or !$this->season instanceof Season)
        {
            http_response_code(404);
            exit();
        }

        # If the competition was requested, redirect to the current season
        if ($latest) {
            http_response_code(303);
            header("Location: " . $this->season->GetNavigateUrl());
            return;
        }


        # Update search engine. Only do this for the latest season as then we have the right teams already.
        if ($this->competition->GetSearchUpdateRequired() and $latest)
        { 
            require_once ("search/lucene-search.class.php");
            $search = new LuceneSearch();
            $search->DeleteDocumentById("competition" . $this->competition->GetId());
            $search->IndexCompetition($this->competition);
            $search->CommitChanges();
            
            $o_comp_manager->SearchUpdated($this->competition->GetId());
        }
		unset($o_comp_manager);

		# get matches
		$o_match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
		$o_match_manager->ReadBySeasonId(array($this->season->GetId()));
		$a_matches = $o_match_manager->GetItems();
		$this->season->SetMatches($a_matches);

		# While we're here, check if there are any outstanding notifications to be sent
		$o_match_manager->NotifyMatchModerator();
		unset($o_match_manager);

		# Get stats highlights
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterBySeason(array($this->season->GetId()));
		$statistics_manager->FilterMaxResults(1);
		$this->best_batting = $statistics_manager->ReadBestBattingPerformance();
		$this->best_bowling = $statistics_manager->ReadBestBowlingPerformance();
		$this->most_runs = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
		$this->most_wickets = $statistics_manager->ReadBestPlayerAggregate("wickets");
		$this->most_catches = $statistics_manager->ReadBestPlayerAggregate("catches");

		# See what stats we've got available
		$best_batting_count = count($this->best_batting);
		$best_bowling_count = count($this->best_bowling);
		$best_batters = count($this->most_runs);
		$best_bowlers = count($this->most_wickets);
		$best_catchers = count($this->most_catches);
		$this->has_player_stats = ($best_batting_count or $best_batters or $best_bowling_count or $best_bowlers or $best_catchers);

		if (!$this->has_player_stats)
		{
			$player_of_match = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
			$this->has_player_stats = (bool)count($player_of_match);
		}
		unset($statistics_manager);

		# Get other seasons
		$a_comp_ids = array($this->competition->GetId());
		$o_season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
		$o_season_manager->ReadByCompetitionId($a_comp_ids);
		$a_other_seasons = $o_season_manager->GetItems();

		$this->competition->SetSeasons(array());
		foreach ($a_other_seasons as $season)
		{
			if ($season->GetId() == $this->season->GetId())
			{
				$this->competition->AddSeason($this->season, true);
			} 
			else 
			{
				$this->competition->AddSeason($season, false);					
			}
		}

		unset($o_season_manager);
	}

	function OnPrePageLoad()
	{
		# set up page
		$this->SetOpenGraphType("sports_league");
		$this->SetPageTitle($this->season->GetCompetitionName());
		$this->SetPageDescription($this->competition->GetSearchDescription());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
        require_once('stoolball/match-list-control.class.php');
        require_once('stoolball/season-list-control.class.php');

        /* @var $season Season */
        $season = $this->competition->GetWorkingSeason();

        echo new XhtmlElement('h1', Html::Encode($this->season->GetCompetitionName()));

        ?>
        <div class="tab-option tab-active large"><h2>Summary</h2></div>
        <?php
        if ($season->MatchTypes()->Contains(MatchType::LEAGUE))
        {
            ?>
            <div class="tab-option tab-inactive"><p><a href="<?php echo Html::Encode($season->GetTableUrl());?>">Table</a></p></div>
            <?php
        }

        if (count($season->GetTeams()))
        {
            ?>
            <div class="tab-option tab-inactive"><p><a href="<?php echo Html::Encode($season->GetMapUrl());?>">Map</a></p></div>
            <?php 
        }
        ?>
        <div class="tab-option tab-inactive"><p><a href="<?php echo Html::Encode($season->GetStatisticsUrl());?>">Statistics</a></p></div>

        <div class="box tab-box">
            <div class="dataFilter large"></div>
            <div class="box-content">
  
        <?php 
        # Add intro
        if ($this->competition->GetIntro())
        {
            $intro = htmlentities($this->competition->GetIntro(), ENT_QUOTES, "UTF-8", false);
            $intro = XhtmlMarkup::ApplyCharacterEntities($intro);
            $intro = XhtmlMarkup::ApplyParagraphs($intro);
            $intro = XhtmlMarkup::ApplyLinks($intro);
            $intro = XhtmlMarkup::ApplyLists($intro);
            $intro = XhtmlMarkup::ApplySimpleTags($intro);
            $intro = XhtmlMarkup::ApplyTables($intro);
            echo $intro;
        }

        # Add season intro
        if ($season->GetIntro())
        {
            $intro = htmlentities($season->GetIntro(), ENT_QUOTES, "UTF-8", false);
            $intro = XhtmlMarkup::ApplyCharacterEntities($intro);
            $intro = XhtmlMarkup::ApplyParagraphs($intro);
            $intro = XhtmlMarkup::ApplyLinks($intro);
            $intro = XhtmlMarkup::ApplyLists($intro);
            $intro = XhtmlMarkup::ApplySimpleTags($intro);
            $intro = XhtmlMarkup::ApplyTables($intro);
            echo $intro;
        }

        # Add not active, if relevant
        if (!$this->competition->GetIsActive())
        {
            echo new XhtmlElement('p', new XhtmlElement('strong', 'This competition isn\'t played any more.'));
        }

        # Add matches
        $a_matches = $season->GetMatches();
        $i_matches = count($a_matches);
        if ($i_matches > 0)
        {
            echo new XhtmlElement('h2', 'Matches in ' . htmlentities($season->GetName(), ENT_QUOTES, "UTF-8", false) . ' season');

            $o_matches = new MatchListControl($a_matches);
            if ($season->MatchTypes()->Contains(MatchType::LEAGUE))
            {
                $o_matches->SetMatchTypesToLabel(array(MatchType::FRIENDLY, MatchType::CUP, MatchType::PRACTICE));
            }
            else if ($season->MatchTypes()->Contains(MatchType::CUP))
            {
                $o_matches->SetMatchTypesToLabel(array(MatchType::FRIENDLY, MatchType::PRACTICE));
            }
            else
            {
                $o_matches->SetMatchTypesToLabel(array(MatchType::PRACTICE));
            }
            echo $o_matches;
        }

        # Add teams
        $a_teams = $season->GetTeams();
        if (count($a_teams) > 0)
        {
            require_once('stoolball/team-list-control.class.php');

            echo new XhtmlElement('h2', 'Teams playing in ' . htmlentities($season->GetName(), ENT_QUOTES, "UTF-8", false) . ' season');
            echo new TeamListControl($a_teams);
        }

        # Add results
        if ($season->GetResults())
        {
            $s_results = htmlentities($season->GetResults(), ENT_QUOTES, "UTF-8", false);
            $s_results = XhtmlMarkup::ApplyCharacterEntities($s_results);
            $s_results = XhtmlMarkup::ApplyParagraphs($s_results);
            $s_results = XhtmlMarkup::ApplyLinks($s_results);
            $s_results = XhtmlMarkup::ApplyLists($s_results);
            $s_results = XhtmlMarkup::ApplySimpleTags($s_results);
            $s_results = XhtmlMarkup::ApplyTables($s_results);
            echo $s_results;
        }

        # Add contact details
        $s_contact = $this->competition->GetContact();
        $s_website = $this->competition->GetWebsiteUrl();
        if ($s_contact or $s_website)
        {
            echo new XhtmlElement('h2', 'Contact details');
        }
        if ($s_contact)
        {
            $s_contact = htmlentities($s_contact, ENT_QUOTES, "UTF-8", false);
            $s_contact = XhtmlMarkup::ApplyCharacterEntities($s_contact);

            require_once('email/email-address-protector.class.php');
            $protector = new EmailAddressProtector($this->GetSettings());
            $s_contact = $protector->ApplyEmailProtection($s_contact, AuthenticationManager::GetUser()->IsSignedIn());

            $s_contact = XhtmlMarkup::ApplyParagraphs($s_contact);
            $s_contact = XhtmlMarkup::ApplyLinks($s_contact);
            $s_contact = XhtmlMarkup::ApplySimpleTags($s_contact);

            echo $s_contact;
        }

        if ($s_website)
        {
            echo new XhtmlAnchor("Visit the " . htmlentities($this->competition->GetName(), ENT_QUOTES, "UTF-8", false) . ' website', $s_website);
        }

		# Check for other seasons. Check is >2 becuase current season is there twice - added above
		if (count($this->competition->GetSeasons()) > 2)
		{
			require_once("stoolball/season-list-control.class.php");
			echo new XhtmlElement('h2', 'Other seasons in the ' . htmlentities($this->competition->GetName(), ENT_QUOTES, "UTF-8", false),"screen");
			$season_list = new SeasonListControl($this->competition->GetSeasons());
			$season_list->SetExcludedSeasons(array($this->season));
            $season_list->AddCssClass("screen");
			echo $season_list;
		}
        
        ?>
        </div>
        </div>
        <?php 

		$this->ShowSocial();
		$this->AddSeparator();

		# Panel for updates
		$you_can = new UserEditPanel($this->GetSettings(), 'this season');
        $you_can->AddCssClass("with-tabs");
		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_COMPETITIONS))
		{
			$you_can->AddLink('edit this competition', $this->competition->GetEditCompetitionUrl());
			$you_can->AddLink('delete this competition', $this->competition->GetDeleteCompetitionUrl());
			$you_can->AddLink('edit this season', $this->season->GetEditSeasonUrl());

			# Only offer delete option if there's more than one season. Don't want to delete last season because 
			# that leaves an empty competition which won't display. Instead, must delete whole competition with its one remaining season.
			if (count($this->competition->GetSeasons()) > 1)
			{
				$you_can->AddLink('delete this season', $this->season->GetDeleteSeasonUrl());
			} 
		}
		foreach ($this->season->MatchTypes() as $i_type)
		{
			if ($i_type != MatchType::PRACTICE and $i_type != MatchType::TOURNAMENT_MATCH)
			{
				$you_can->AddLink('add a ' . MatchType::Text($i_type), $this->season->GetNewMatchNavigateUrl($i_type));
			}
		}
		if (count($this->season->GetMatches()))
		{
			# Make sure there's at least one match which is not a tournament or a practice
			foreach ($this->season->GetMatches() as $o_match)
			{
				/* @var $o_match Match */
				if ($o_match->GetMatchType() == MatchType::PRACTICE or $o_match->GetMatchType() == MatchType::TOURNAMENT or $o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH)
				{
					continue;
				}
				else
				{
					$you_can->AddLink('update results', $this->season->GetResultsNavigateUrl());
					break;
				}
			}
			$you_can->AddLink('add matches to your calendar', $this->season->GetCalendarNavigateUrl());
		}

		if ($this->has_player_stats)
		{
			$you_can->AddLink("view statistics for this season", $this->season->GetStatisticsUrl(), "small");
		}
		else
		{
			$you_can->AddLink("add player statistics now", "/play/manage/website/how-to-add-match-results/", "small");
		}
		echo $you_can;

        if ($this->has_player_stats)
		{
			require_once('stoolball/statistics-highlight-table.class.php');
			echo new StatisticsHighlightTable($this->best_batting, $this->most_runs, $this->best_bowling, $this->most_wickets, $this->most_catches, $this->season->GetName() . " season");
		}
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>