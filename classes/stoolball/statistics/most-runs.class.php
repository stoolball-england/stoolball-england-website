<?php
require_once("statistic.class.php");

/**
 * Batting aggregate runs for a player
 */
class MostRuns extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-runs");
        parent::SetTitle("Most runs");
        parent::SetColumnHeaders(array("Runs"));
        parent::SetDescription("Find out who has scored the most runs overall in all stoolball matches.");
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAggregate("runs_scored");        
    }
}
?>