<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/competition-manager.class.php');
require_once('stoolball/season-manager.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/user-edit-panel.class.php');
require_once('media/media-gallery-manager.class.php');

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
		if (!isset($_GET['season']) or !is_numeric($_GET['season']))
		{
			http_response_code(400);
			exit();
		}

        if (isset($_GET['season']) and is_numeric($_GET['season']))
        {
            $comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
            $comp_manager->ReadById(null, array($_GET['season']));
            $this->competition = $comp_manager->GetFirst();
            unset($comp_manager);
        }

        # must have found a competition
        if (!$this->competition instanceof Competition)
        {
            http_response_code(404);
            exit();
        }

		$this->season = $this->competition->GetWorkingSeason();
		if (is_object($this->season))
		{
			# get matches
			$o_match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
			$o_match_manager->ReadBySeasonId(array($this->season->GetId()));
			$a_matches = $o_match_manager->GetItems();
			$this->season->SetMatches($a_matches);

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
		else
		{
			# Must have a season
            http_response_code(404);
            exit();
		}
	}

	function OnPrePageLoad()
	{
		# set up page
		$this->SetOpenGraphType("sports_league");
		$this->SetPageTitle("League table for the " . $this->season->GetCompetitionName());
		$this->SetPageDescription($this->competition->GetSearchDescription());
		$this->SetContentConstraint(StoolballPage::ConstrainBox());
        $this->SetContentCssClass("season-table");
	}

	function OnPageLoad()
	{
        require_once('stoolball/season-list-control.class.php');

        /* @var $season Season */
        $season = $this->competition->GetWorkingSeason();

        echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

        ?>
        <div class="tab-option tab-inactive"><p><a href="<?php echo Html::Encode($season->GetNavigateUrl()); ?>">Summary</a></p></div>
        <div class="tab-option tab-active large"><h2>Table</h2></div>
        <?php 
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
        # Add matches
        $a_matches = $season->GetMatches();

        # Add teams
        $a_teams = $season->GetTeams();
        $no_content = true;
        if (count($a_teams) > 0)
        {
            # Check whether there's at least one league match to show a league table for
            $has_league_match = 0;
            foreach ($a_matches as $match)
            {
                /* @var $match Match */
                if ($match->GetMatchType() == MatchType::LEAGUE)
                {
                    $has_league_match = true;
                    break;
                }
            }

            if ($season->GetShowTable() and $has_league_match)
            {
                require_once('stoolball/season-table.class.php');

                echo new SeasonTable($this->GetSettings(), $season);
                $no_content = false;
            }
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
            
            $no_content = false;
        }
        
        if ($no_content) {
            ?>
            <img class="screenshot" src="/images/features/league-table.png" alt="Example league table" width="308" />
            <p>There's no league table for this season yet.</p>
            <p>You can have your league table updated automatically by adding your matches
                and results to this website. Find out about <a href="/play/manage/website/league-tables/">league tables &#8211; how they work</a>.</p>
            <?php            
        }

		# Check for other seasons. Check is >2 becuase current season is there twice - added above
		if (count($this->competition->GetSeasons()) > 2)
		{
			require_once("stoolball/season-list-control.class.php");
			echo new XhtmlElement('h2', 'Other seasons in the ' . htmlentities($this->competition->GetName(), ENT_QUOTES, "UTF-8", false),"screen");
			$season_list = new SeasonListControl($this->competition->GetSeasons());
			$season_list->SetExcludedSeasons(array($this->season));
            $season_list->AddCssClass("screen");
            $season_list->SetUrlMethod('GetTableUrl');
            echo $season_list;
		}
        
        ?>
        </div>
        </div>
        <?php 

		$this->ShowSocial();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>