<?php
require_once("statistic.class.php");

/**
 * Run-outs by a player
 */
class RunOuts extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("run-outs");
        parent::SetTitle("Run-outs");
        parent::SetDescription("See all run-outs by an individual");
        parent::SetSupportsFilterByFielder(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("run-out");
        parent::SetItemTypePlural("run-outs");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadMatchPerformances();        
    }
}
?>