<?php
require_once("statistic.class.php");

/**
 * Wickets taken and runs conceded by a bowler, ordered with the best performances first
 */
class BowlingPerformances extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source, $filter_applied) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("bowling-performances");
        parent::SetTitle($filter_applied ? "Bowling performances" : "All bowling performances");
        parent::SetDescription("See the best wicket-taking performances in all stoolball matches.");
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("innings");
        parent::SetItemTypePlural("innings");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestBowlingPerformance();        
    }
}
?>