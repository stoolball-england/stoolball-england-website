<?php
require_once("statistic.class.php");

/**
 * Aggregate run-outs completed by a player
 */
class MostRunOuts extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-run-outs");
        parent::SetTitle("Most run-outs");
        parent::SetColumnHeaders(array("Run-outs"));
        parent::SetDescription("This measures the number of run-outs completed by a fielder, not how often a batsman has been run-out.");
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
        return $this->statistics_data_source->ReadBestPlayerAggregate("run_outs");
    }
}
?>