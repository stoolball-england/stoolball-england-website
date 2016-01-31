<?php
require_once("statistic.class.php");

/**
 * Aggregate number of innings where a player scored at least the given number of runs
 */
class MostScoresOf extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    private $minimum_qualifying_score;
    
    public function __construct($minimum_qualifying_score, StatisticsManager $statistics_data_source) {
        $this->minimum_qualifying_score = (int)$minimum_qualifying_score;
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-scores-of");
        parent::SetTitle("Most scores of $minimum_qualifying_score or more runs");
        parent::SetColumnHeaders(array("Innings"));
        parent::SetDescription("Find out who has played the most innings of $minimum_qualifying_score runs or more in all stoolball matches.");
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestAggregateOfQualifyingPerformances("runs_scored", $this->minimum_qualifying_score);        
    }
}
?>