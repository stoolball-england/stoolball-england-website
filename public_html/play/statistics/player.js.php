<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/page.class.php');
require_once('stoolball/statistics/statistics-manager.class.php');
require_once("stoolball/statistics/statistics-filter.class.php");

class CurrentPage extends Page
{
	/**
	 * Player to view
	 * @var Player
	 */
	private $player;
	
	private $filter;
	/**
	 * @var StatisticsFilterControl
	 */
	private $filter_control;
    
    private $max_x_axis_scale_points = 35;

	public function OnPageInit()
	{
        header("Content-Type: application/javascript");

		# Get team to display players for
		if (!isset($_GET['player']) or !is_numeric($_GET['player'])) $this->Redirect();
		$this->player = new Player($this->GetSettings());
		$this->player->SetId($_GET['player']);
	}

	public function OnLoadPageData()
	{
		# Now get statistics for the player
		$filter_by_player = array($this->player->GetId());
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByPlayer($filter_by_player);

		# Apply filters common to all statistics
		$filter_opposition = StatisticsFilter::SupportOppositionFilter($statistics_manager);
		$this->filter .= $filter_opposition[2];

		$filter_competition = StatisticsFilter::SupportCompetitionFilter($statistics_manager);
		$this->filter .= $filter_competition[2];

		$this->filter .= StatisticsFilter::ApplySeasonFilter($this->GetSettings(), $this->GetDataConnection(), $statistics_manager);

		$filter_ground = StatisticsFilter::SupportGroundFilter($statistics_manager);
		$this->filter .= $filter_ground[2];

		$filter_date = StatisticsFilter::SupportDateFilter($statistics_manager);
		$this->filter .= $filter_date[2];

		$filter_batting_position = StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
		$this->filter .= $filter_batting_position[2];

		# Now get the statistics for the player
		$this->player = new Player($this->GetSettings());

		$data = $statistics_manager->ReadBestBattingPerformance(false, true);
		foreach ($data as $performance)
		{
			$batting = new Batting($this->player, $performance["how_out"], null, null, $performance["runs_scored"]);
            $match = new Match($this->GetSettings());
            $match->SetStartTime($performance["match_time"]);
            $batting->SetMatch($match);
			$this->player->Batting()->Add($batting);
		}
        $data = $statistics_manager->ReadBestBowlingPerformance(true);
        foreach ($data as $performance)
        {
            # Not useful for either average or economy
            if (is_null($performance["runs_conceded"])) {
                continue;
            }
            
            $bowling = new Bowling($this->player);
            $bowling->SetWickets($performance["wickets"]);
            $bowling->SetRunsConceded($performance["runs_conceded"]);
            $bowling->SetOvers($performance["overs"]);
            $match = new Match($this->GetSettings());
            $match->SetStartTime($performance["match_time"]);
            $bowling->SetMatch($match);
            $this->player->Bowling()->Add($bowling);
        }
       
        $statistics_manager->FilterByPlayer(null);
        $statistics_manager->FilterByBowler($filter_by_player);
        $statistics_manager->FilterByHowOut(array(Batting::BODY_BEFORE_WICKET, Batting::BOWLED, Batting::CAUGHT, Batting::CAUGHT_AND_BOWLED, Batting::HIT_BALL_TWICE));
        $this->player->SetHowWicketsTaken($statistics_manager->ReadHowWicketsFall());
		unset($statistics_manager);

        # How dismissed 
        ?>
{
    <?php 
    $score_spread = $this->player->ScoreSpread();
    if (is_array($score_spread))
    {
    ?>
    "scoreSpread": {
        "labels":  [
        <?php
        $len = count($score_spread);
        $count = 0;
        foreach ($score_spread as $key=>$value)
        {
            echo  '"' . $key. '"';
            
            $count++;
            if ($count < $len) echo ',';
        }
        ?>         
        ],
        "datasets": [
             {
                 "label": "Not out", 
                "data":  [
                <?php 
                    $len = count($score_spread);
                    $count = 0;
                    foreach ($score_spread as $key=>$value)
                    {
                        echo $value["not-out"];
                        
                        $count++;
                        if ($count < $len) echo ',';
                    }
                ?>
                ]
            }, 
             {
                 "label": "Out", 
                "data":  [
                <?php 
                    $len = count($score_spread);
                    $count = 0;
                    foreach ($score_spread as $key=>$value)
                    {
                        echo $value["out"];
                        
                        $count++;
                        if ($count < $len) echo ',';
                    }
                ?>
                ]
            }
        ]
    },
    <?php } 
    
    if ($this->player->Batting()->GetCount()) {
        ?>
        "battingForm": {
            "labels": [<?php echo $this->BuildBattingTimeline($this->player->Batting()->GetItems()); ?>],
            "datasets": [
                {
                    "label": "Scores",
                    "data": [<?php echo $this->BuildScoresData($this->player->Batting()->GetItems()); ?>]
                },
                {
                    "label": "Average",
                    "data": [<?php echo $this->BuildBattingAverageData($this->player->Batting()->GetItems()); ?>]
                }
            ]
        },                    
        <?php 
    }

    ?>
    "dismissals": [
        <?php
        $how_out = $this->player->HowOut();
        $len = count($how_out);
        $count = 0;
        foreach ($how_out as $key=>$value)
        {
            if ($key == Batting::DID_NOT_BAT) continue;
    
            $label = ucfirst(html_entity_decode(Batting::Text($key)));
            echo '{ "label":"' . $label . '","value":' . $value . "}";
            
            $count++;
            if ($count < $len) echo ',';
        }
        ?>        
    ],
    <?php
       if ($this->player->Bowling()->GetCount()) {
        ?>
        "economy": {
            "labels": [<?php echo $this->BuildBowlingTimeline($this->player->Bowling()->GetItems()); ?>],
            "datasets": [
                {
                    "label": "Economy in each match",
                    "data": [<?php echo $this->BuildMatchEconomyData($this->player->Bowling()->GetItems()); ?>]
                },
                {
                    "label": "Economy overall",
                    "data": [<?php echo $this->BuildEconomyData($this->player->Bowling()->GetItems()); ?>]
                }
            ]
        },                    
        "bowlingAverage": {
            "labels": [<?php echo $this->BuildBowlingTimeline($this->player->Bowling()->GetItems()); ?>],
            "datasets": [
                {
                    "label": "Average in each match",
                    "data": [<?php echo $this->BuildMatchBowlingAverageData($this->player->Bowling()->GetItems()); ?>]
                },
                {
                    "label": "Average overall",
                    "data": [<?php echo $this->BuildBowlingAverageData($this->player->Bowling()->GetItems()); ?>]
                }
            ]
        },                    
        <?php 
    }
    ?>
    "wickets": [
        <?php
        $how_out = $this->player->GetHowWicketsTaken();
        $len = count($how_out);
        $count = 0;
        foreach ($how_out as $key=>$value)
        {
            $label = ucfirst(html_entity_decode(Batting::Text($key)));
            echo '{ "label":"' . $label . '","value":' . $value . "}";
            
            $count++;
            if ($count < $len) echo ',';
        }
        ?>        
    ]    
}
        <?php
        exit();
 	}

    private function BuildBattingTimeline(array $batting) {
        $data = "";
        
        $total_performances = count($batting);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);
        
        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $batting[$i];
            /* @var $performance Batting */
            if (!$show_every_second_performance or ($i%2===0))
            {            
                if (strlen($data)) {
                    $data .= ",";
                }
                $data .= '"' . Date::BritishDate($performance->GetMatch()->GetStartTime(), false, false, true) . '"';
            }
        }
        return $data;
    }

    private function BuildScoresData(array $batting) {
        $data = "";

        $total_performances = count($batting);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);
        
        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $batting[$i];
            /* @var $performance Batting */

            # Sample the performances plotted to prevent the scale getting too cluttered
            if (!$show_every_second_performance or ($i%2===0))
            {                                  
                if (strlen($data)) {
                    $data .= ",";
                }
                $data .= $performance->GetRuns();
            }
        }
        return $data;
    }
    
    private function BuildBattingAverageData(array $batting) {
    
        # Exclude performances that aren't relevant
        $batting_to_average = array();
        foreach ($batting as $performance) {
            if ($performance->GetHowOut() !== Batting::DID_NOT_BAT) {
                $batting_to_average[] = $performance;
            }
        }
    
        $runs = 0;
        $dismissals = 0;
        $not_out = array(Batting::NOT_OUT, Batting::RETIRED, Batting::RETIRED_HURT);
        $data = "";

        $total_performances = count($batting_to_average);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);
        
        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $batting_to_average[$i];
            /* @var $performance Batting */

            # Always count the data for every performance            
            $runs += $performance->GetRuns();
            $how_out = $performance->GetHowOut();
            
            if (!in_array($how_out, $not_out)) {
                $dismissals++;
            }

            # Sample the performances plotted to prevent the scale getting too cluttered
            if (!$show_every_second_performance or ($i%2===0))
            {                                  
                if (strlen($data)) {
                    $data .= ",";
                }
                if ($dismissals > 0)
                {
                    $data .= round($runs/$dismissals,2);
                }
                else {
                    $data .= "null";
                }
            }
        }
        return $data;
    }
        
    private function BuildBowlingTimeline(array $bowling) {
        $data = "";
        
        $total_performances = count($bowling);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);
        
        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $bowling[$i];
            /* @var $performance Bowling */
            if (!$show_every_second_performance or ($i%2===0))
            {            
                if (strlen($data)) {
                    $data .= ",";
                }
                $data .= '"' . Date::BritishDate($performance->GetMatch()->GetStartTime(), false, false, true) . '"';
            }
        }
        return $data;
    }

    private function BuildMatchEconomyData(array $bowling) {
        $data = "";

        $total_performances = count($bowling);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);

        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $bowling[$i];
            /* @var $performance Bowling */

            # Sample the performances plotted to prevent the scale getting too cluttered
            if (!$show_every_second_performance or ($i%2===0))
            {                                  
                if (strlen($data)) {
                    $data .= ",";
                }
    
                if (is_null($performance->GetOvers()) or $performance->GetWickets() === 0) {
                    $data .= "null";
                } else {            
                    $data .= StoolballStatistics::BowlingEconomy($performance->GetOvers(), $performance->GetRunsConceded());
                }
            }
        }
        return $data;
    }

    private function BuildEconomyData(array $bowling) {
        $data = "";
        $balls_bowled = 0;
        $runs_conceded = 0;

        $total_performances = count($bowling);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);

        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $bowling[$i];
            /* @var $performance Bowling */

            # Always count the data for every performance            
            if (!is_null($performance->GetOvers())) {
                $runs_conceded += $performance->GetRunsConceded();
                $balls_bowled += StoolballStatistics::OversToBalls($performance->GetOvers());
            }
                            
            # Sample the performances plotted to prevent the scale getting too cluttered
            if (!$show_every_second_performance or ($i%2===0))
            {                                  
                if (strlen($data)) {
                    $data .= ",";
                }
    
                if (is_null($performance->GetOvers())) {
                    $data .= "null";
                } else {            
                    $data .= StoolballStatistics::BowlingEconomy(StoolballStatistics::BallsToOvers($balls_bowled), $runs_conceded);
                }
            }
        }
        return $data;
    }
    
    private function BuildBowlingAverageData(array $bowling) {
        $data = "";
        $wickets = 0;
        $runs_conceded = 0;

        $total_performances = count($bowling);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);

        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $bowling[$i];
            /* @var $performance Bowling */
            
            # Always count the data for every performance            
            $runs_conceded += $performance->GetRunsConceded();
            $wickets += $performance->GetWickets();
            
            # Sample the performances plotted to prevent the scale getting too cluttered
            if (!$show_every_second_performance or ($i%2===0))
            {                                  
                if (strlen($data)) {
                    $data .= ",";
                }
    
                if ($wickets > 0) {
                    $data .= StoolballStatistics::BowlingAverage($runs_conceded, $wickets);
                } else {
                    $data .= "null";
                }
            }
        }
        return $data;
    }

    private function BuildMatchBowlingAverageData(array $bowling) {
        $data = "";

        $total_performances = count($bowling);
        $show_every_second_performance = ($total_performances > $this->max_x_axis_scale_points);

        for ($i = 0; $i < $total_performances; $i++) {
            $performance = $bowling[$i];
            /* @var $performance Bowling */
            
            # Sample the performances plotted to prevent the scale getting too cluttered
            if (!$show_every_second_performance or ($i%2===0))
            {                                  
                if (strlen($data)) {
                    $data .= ",";
                }
    
                if (is_null($performance->GetRunsConceded()) or $performance->GetWickets() === 0) {
                    $data .= "null";
                } else {
                    $data .= StoolballStatistics::BowlingAverage($performance->GetRunsConceded(), $performance->GetWickets());
                }
            }
        }
        return $data;
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>