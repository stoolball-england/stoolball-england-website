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
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByPlayer(array($this->player->GetId()));

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

		$data = $statistics_manager->ReadBestBattingPerformance(false);
		foreach ($data as $performance)
		{
			$batting = new Batting($this->player, $performance["how_out"], null, null, $performance["runs_scored"]);
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
    <?php } ?>
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
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>