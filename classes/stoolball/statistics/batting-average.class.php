<?php
require_once("statistic.class.php");

/**
 * Mean average batting score for a player
 */
class BattingAverage extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("batting-average");
        parent::SetTitle("Batting averages");
        parent::SetDescription("A batsman's average measures how many runs he or she typically scores before getting out. We only include people who have batted at least three times, and got out at least once.");
        parent::SetShowDescription(true);
        parent::SetColumnHeaders(array("Average"));
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAverage("runs_scored", "dismissed", true, "runs_scored", 3);        
    }
}
?>