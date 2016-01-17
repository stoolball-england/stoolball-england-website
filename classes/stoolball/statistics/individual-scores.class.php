<?php
require_once("statistic.class.php");

/**
 * Batting innings for a player
 */
class IndividualScores extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source, $is_filter_applied) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("individual-scores");
        parent::SetTitle($is_filter_applied ? "Individual scores" : "All individual scores");
        parent::SetDescription("See the highest scores by individuals in a single stoolball innings.");
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("innings");
        parent::SetItemTypePlural("innings");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestBattingPerformance();        
    }
}
?>