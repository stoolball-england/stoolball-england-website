<?php
require_once("statistic.class.php");

/**
 * Number of run-outs completed by a player in a single match, with the highest listed first
 */
class MostRunOutsInAnInnings extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-run-outs-in-innings");
        parent::SetTitle("Most run-outs in an innings");
        parent::SetColumnHeaders(array("Run-outs"));
        parent::SetDescription("This measures the number of run-outs completed by a fielder, not how often a batsman has been run-out. We only include players who completed at least two run-outs.");
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
        $runouts = new StatisticsField("run_outs", "Run-outs", false, null);
        $player_name = new StatisticsField("player_name", null, true, null);

        return $this->statistics_data_source->ReadBestFiguresInAMatch($runouts, array($player_name), 2, true);        
    }
}
?>