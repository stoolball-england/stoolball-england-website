<?php
require_once("statistic.class.php");

/**
 * Catches by a player
 */
class Catches extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("catches");
        parent::SetTitle("Catches");
        parent::SetDescription("See all catches by an individual");
        parent::SetSupportsFilterByCatcher(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("catch");
        parent::SetItemTypePlural("catches");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadMatchPerformances();        
    }
}
?>