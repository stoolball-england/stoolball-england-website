<?php
require_once("statistic.class.php");

/**
 * Aggregate wickets taken by a particular combination of bowler and catcher
 */
class MostWicketsForBowlerAndCatcher extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-wickets-by-bowler-and-catcher");
        parent::SetTitle("Most wickets by a bowling and catching combination");
        parent::SetDescription("This measures which combination of bowler and catcher has taken the most wickets. Catches taken by a bowler off their own bowling are not counted.");
        parent::SetShowDescription(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("combination");
        parent::SetItemTypePlural("combinations");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadMostWicketsForBowlerAndCatcher();        
    }
}
?>