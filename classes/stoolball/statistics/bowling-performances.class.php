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
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("bowling-performances");
        parent::SetTitle("All bowling performances");
        parent::SetDescription("See the best wicket-taking performances in all stoolball matches.");
        parent::SetSupportsFilterByPlayer(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("innings");
        parent::SetItemTypePlural("innings");
        parent::SetCssClass("bowling");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestBowlingPerformance();        
    }
}
?>