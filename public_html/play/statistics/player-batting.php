<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/statistics/statistics-manager.class.php');
require_once("stoolball/statistics/statistics-filter.class.php");
require_once("stoolball/statistics/statistics-filter-control.class.php");
require_once("stoolball/user-edit-panel.class.php");
require_once("xhtml/tables/xhtml-table.class.php");

class CurrentPage extends StoolballPage
{
	/**
	 * Player to view
	 * @var Player
	 */
	private $player;
	
		/**
	 * Player to view
	 * @var Player
	 */
	private $player_unfiltered;
	
	private $filter;
	/**
	 * @var StatisticsFilterControl
	 */
	private $filter_control;
    
    private $filter_matched_nothing;
    
    private $not_found = false;

    private $regenerating = false;
    private $regenerating_control;

	public function OnPageInit()
	{
		# Get team to display players for
		if (!isset($_GET['player']) or !is_numeric($_GET['player'])) $this->Redirect();
		$this->player = new Player($this->GetSettings());
		$this->player->SetId($_GET['player']);
	}

	public function OnLoadPageData()
	{
		# Always get the player's unfiltered profile, because it's needed for the page description
		require_once("stoolball/player-manager.class.php");
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$player_manager->ReadPlayerById($this->player->GetId());
		$this->player_unfiltered = $player_manager->GetFirst();
        if (!$this->player_unfiltered instanceof Player) 
        {
            http_response_code(404);
            $this->not_found = true;
            return;
        }
        
        # Update search engine
        if ($this->player_unfiltered->GetSearchUpdateRequired())
        {
            require_once("search/player-search-adapter.class.php");
            $this->SearchIndexer()->DeleteFromIndexById("player" . $this->player->GetId());
            $adapter = new PlayerSearchAdapter($this->player_unfiltered);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
            $this->SearchIndexer()->CommitChanges();

            $player_manager->SearchUpdated($this->player->GetId());
        }
		     
		unset($player_manager);

        # Check first for a player created using 'add player', who hasn't played yet
        if ($this->player_unfiltered->GetTotalMatches() == 0) {
            
            $this->player = $this->player_unfiltered;
        } 
        else {
        
    		# Now get statistics for the player
    		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
    		$statistics_manager->FilterByPlayer(array($this->player->GetId()));
    
    		# Apply filters common to all statistics
    		$this->filter_control = new StatisticsFilterControl();
            
            $filter_match_type = StatisticsFilter::SupportMatchTypeFilter($statistics_manager);
            $this->filter_control->SupportMatchTypeFilter($filter_match_type);
            $this->filter .= $filter_match_type[2];
            
            $filter_opposition = StatisticsFilter::SupportOppositionFilter($statistics_manager);
    		$this->filter_control->SupportOppositionFilter($filter_opposition);
    		$this->filter .= $filter_opposition[2];
    
    		$filter_competition = StatisticsFilter::SupportCompetitionFilter($statistics_manager);
    		$this->filter_control->SupportCompetitionFilter($filter_competition);
    		$this->filter .= $filter_competition[2];
    
    		$this->filter .= StatisticsFilter::ApplySeasonFilter($this->GetSettings(), $this->GetDataConnection(), $statistics_manager);
    
    		$filter_ground = StatisticsFilter::SupportGroundFilter($statistics_manager);
    		$this->filter_control->SupportGroundFilter($filter_ground);
    		$this->filter .= $filter_ground[2];
    
    		$filter_date = StatisticsFilter::SupportDateFilter($statistics_manager);
    		if (!is_null($filter_date[0])) $this->filter_control->SupportAfterDateFilter($filter_date[0]);
    		if (!is_null($filter_date[1])) $this->filter_control->SupportBeforeDateFilter($filter_date[1]);
    		$this->filter .= $filter_date[2];
    
    		$filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
    		$this->filter_control->SupportBattingPositionFilter($filter_batting_position);
    		$this->filter .= $filter_batting_position[2];
    
    		# Now get the statistics for the player
    		$data = $statistics_manager->ReadPlayerSummary();
    		if (count($data))
    		{
    			$this->player = $data[0];
    		}
    		else if ($this->filter)
    		{
    			# If no matches matched the filter, ensure we have the player's name and team
    			$this->player = $this->player_unfiltered;
                $this->filter_matched_nothing = true;
    		}
    		else
    		{
    			$this->regenerating = true;
    		}
    
    		$data = $statistics_manager->ReadBestBattingPerformance(false);
    		foreach ($data as $performance)
    		{
    			$batting = new Batting($this->player, $performance["how_out"], null, null, $performance["runs_scored"], $performance["balls_faced"]);
    			$this->player->Batting()->Add($batting);
    		}
    
            if ($this->player->GetPlayerRole() == Player::PLAYER)
            {
        		$data = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
        		$this->player->SetTotalPlayerOfTheMatchNominations(count($data) ? $data[0]["statistic"] : 0);
            }
            
    		unset($statistics_manager);
		}
		
	}

	public function OnPrePageLoad()
	{
	    if ($this->not_found) 
	    {
	       $this->SetPageTitle("Page not found");
           return;    
	    }
        
        if ($this->regenerating)
        {
            require_once("stoolball/statistics/regenerating-control.class.php");
            $this->regenerating_control = new \Stoolball\Statistics\RegeneratingControl();
            $title = $this->regenerating_control->GetPageTitle();
        }
        else
		{
			$title = ($this->player->GetPlayerRole() == Player::PLAYER) ? ", a player for " : " conceded by ";
			$title = $this->player->GetName() . $title . $this->player->Team()->GetName() . " stoolball team";

			if ($this->player->GetPlayerRole() == Player::PLAYER)
			{
				$this->SetOpenGraphTitle($this->player->GetName() . ", " . $this->player->Team()->GetName() . " stoolball team");
			}
			if ($this->filter)
			{
				$this->filter = ", " . $this->filter;
				$title .= $this->filter;
			}
			
			$this->SetPageDescription($this->player_unfiltered->GetPlayerDescription());
		}

		$this->SetPageTitle($title);
		$this->SetOpenGraphType("athlete");
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		$this->SetContentCssClass("playerStats");

		$this->LoadClientScript("/scripts/lib/jquery-ui-1.8.11.custom.min.js");
		$this->LoadClientScript("/play/statistics/statistics-filter.js");
        $this->LoadClientScript("/scripts/lib/chart.min.js");
        $this->LoadClientScript("/scripts/chart.js?v=2");
        $this->LoadClientScript("/scripts/lib/Chart.StackedBar.js");
        $this->LoadClientScript("/play/statistics/player-batting.js");
		?>
<link rel="stylesheet" href="/css/custom-theme/jquery-ui-1.8.11.custom.css" media="screen" />
<!--[if lte IE 8]><script src="/scripts/lib/excanvas.compiled.js"></script><![endif]-->
		<?php
	}

	public function OnPageLoad()
	{
	    if ($this->not_found) 
	    {
           require_once($_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/stoolball/section-404.php");
	       return;   
	    }
        
		if ($this->regenerating)
		{
            echo $this->regenerating_control;
			return;
		}

		# Container element for structured data
		echo '<div typeof="schema:Person" about="' . htmlentities($this->player->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '">';

        $querystring = '?player=' . $this->player->GetId();
        if ($_SERVER["QUERY_STRING"]) {
             $querystring = htmlspecialchars('?' . $_SERVER["QUERY_STRING"]);
        }

        require_once("stoolball/statistics/player-summary-control.class.php");
        echo new \Stoolball\Statistics\PlayerSummaryControl($this->player, $this->filter, $this->filter_matched_nothing, $querystring);		
                
        if ($this->player->GetPlayerRole() == Player::PLAYER)
        {
            require_once('xhtml/navigation/tabs.class.php');
            $tabs = array('Batting' => '', 'Bowling and fielding' => $this->player->GetPlayerUrl() . '/bowling');       
            echo new Tabs($tabs);
            
            ?>
            <div class="box tab-box">
                <div class="dataFilter"></div>
                <div class="box-content">
            <?php
        }


         # Filter control
         echo $this->filter_control;
        
         # Batting stats
         if ($this->player->TotalBattingInnings())
         {
         	//echo "<h2>Batting</h2>";
        
         	# Overview table
         	$batting_table = new XhtmlTable();
         	$batting_table->SetCssClass("numeric");
         	$batting_table->SetCaption("Batting");
        
         	$batting_heading_row = new XhtmlRow(array('<abbr title="Innings" class="small">Inn</abbr><span class="large">Innings</span>', 
         	                                      "Not out", 
         	                                      "Runs", 
                                                  "50s", 
                                                  "100s", 
           	                                      "Best", 
         	                                      '<abbr title="Average" class="small">Avg</abbr><span class="large">Average</span>',
                                                  '<abbr title="Strike rate" class="small">S/R</abbr><span class="large">Strike rate</span>'));
         	$batting_heading_row->SetIsHeader(true);
         	$batting_table->AddRow($batting_heading_row);
        
         	$batting_table->AddRow(new XhtmlRow(array(
         	$this->player->TotalBattingInnings(),
         	$this->player->NotOuts(),
         	$this->player->TotalRuns(),
            $this->player->Fifties(),
            $this->player->Centuries(),
         	$this->player->BestBatting(),
         	is_null($this->player->BattingAverage()) ? "&#8211;" : $this->player->BattingAverage(),
            is_null($this->player->BattingStrikeRate()) ? "&#8211;" : $this->player->BattingStrikeRate(),
            )));
        
         	echo $batting_table;
        
         	if ($this->player->TotalBattingInnings())
         	{
         		echo '<p class="statsViewAll"><a href="/play/statistics/individual-scores' . $querystring . '">Individual scores &#8211; view all and filter</a></p>';
         	}
            ?>
            <span class="chart-js-template" id="score-spread-chart"></span>
            <span class="chart-js-template" id="batting-form-chart"></span>
            <span class="chart-js-template" id="dismissals-chart"></span>
            <?php
             }

        $this->ShowSocial();
        
        if ($this->player->GetPlayerRole() == Player::PLAYER)
        {
            ?>
            </div>
            </div>
            <?php 
        }

         # End container for structured data
         echo "</div>";

        if ($this->player->GetPlayerRole() == Player::PLAYER)
        {
             $has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS, $this->player->Team()->GetLinkedDataUri()));
             $has_admin_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_PLAYERS));
             if ($has_permission)
             {
             	$this->AddSeparator();
             	$panel = new UserEditPanel($this->GetSettings());
             	$panel->AddLink("rename this player", $this->player->GetEditUrl());
                if ($has_admin_permission) {
             	  $panel->AddLink("delete this player", $this->player->GetDeleteUrl());
                }
             	echo $panel;
             }
        }
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>