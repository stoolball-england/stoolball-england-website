<?php
require_once("statistic.class.php");

/**
 * Mean average runs scored per 100 balls for a player
 */
class BattingStrikeRate extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("batting-strike-rate");
        parent::SetTitle("Batting strike rate");
        parent::SetDescription("A batsman's strike rate measures how many runs he or she typically scores per 100 balls faced.");
        parent::SetShowDescription(true);
        parent::SetColumnHeaders(array("Strike rate"));
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAverage("runs_scored", "balls_faced", true, null, null, 100);        
    }
}
?>