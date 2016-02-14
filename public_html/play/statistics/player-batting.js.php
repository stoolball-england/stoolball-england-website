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
        StatisticsFilter::SupportMatchTypeFilter($statistics_manager);
		StatisticsFilter::SupportOppositionFilter($statistics_manager);
		StatisticsFilter::SupportCompetitionFilter($statistics_manager);
		StatisticsFilter::ApplySeasonFilter($this->GetSettings(), $this->GetDataConnection(), $statistics_manager);
		StatisticsFilter::SupportGroundFilter($statistics_manager);
		StatisticsFilter::SupportDateFilter($statistics_manager);
		StatisticsFilter::SupportBattingPositionFilter($statistics_manager);
		
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
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>