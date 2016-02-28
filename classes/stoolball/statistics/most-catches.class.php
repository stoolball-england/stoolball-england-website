<?php
require_once("statistic.class.php");

/**
 * Aggregate catches taken by a player
 */
class MostCatches extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-catches");
        parent::SetTitle("Most catches");
        parent::SetColumnHeaders(array("Catches"));
        parent::SetDescription("This measures the number of catches taken by a fielder, not how often a batsman has been caught out.");
        parent::SetShowDescription(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
        parent::SetCssClass("bowling");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAggregate("catches");        
    }
}
?>