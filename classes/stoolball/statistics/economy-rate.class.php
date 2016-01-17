<?php
require_once("statistic.class.php");

/**
 * Mean average runs conceded by a bowler for each complete over they bowl
 */
class EconomyRate extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("economy-rate");
        parent::SetTitle("Economy rates");
        parent::SetColumnHeaders(array("Economy rate"));
        parent::SetDescription("A bowler's economy rate measures how many runs he or she typically concedes in each over. Lower numbers are better. We only include people who have bowled in at least three matches.");
        parent::SetShowDescription(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAverage("runs_conceded", "overs_decimal", false, "runs_conceded", 3);
    }
}
?>