<?php
require_once("statistic.class.php");

/**
 * Number of catches taken by a player in a single match, with the highest listed first
 */
class MostCatchesInAnInnings extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-catches-in-innings");
        parent::SetTitle("Most catches in an innings");
        parent::SetColumnHeaders(array("Catches"));
        parent::SetDescription("This measures the number of catches taken by a fielder, not how often a batsman has been caught out. We only include players who took at least three catches.");
        parent::SetShowDescription(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("innings");
        parent::SetItemTypePlural("innings");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        require_once("stoolball/statistics/statistics-field.class.php");
        $catches = new StatisticsField("catches", "Catches", false, null);
        $player_name = new StatisticsField("player_name", null, true, null);

        return $this->statistics_data_source->ReadBestFiguresInAMatch($catches, array($player_name), 3, true);        
    }
}
?>