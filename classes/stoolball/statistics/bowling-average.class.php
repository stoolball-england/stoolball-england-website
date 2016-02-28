<?php
require_once("statistic.class.php");

/**
 * Mean average runs conceded by a bowler for each wicket they take
 */
class BowlingAverage extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("bowling-average");
        parent::SetTitle("Bowling averages");
        parent::SetColumnHeaders(array("Average"));
        parent::SetDescription("A bowler's average measures how many runs he or she typically concedes before taking a wicket. Lower numbers are better.

We only include people who have bowled in at least three matches, and we ignore wickets in innings where the bowling card wasn't filled in.");
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
        return $this->statistics_data_source->ReadBestPlayerAverage("runs_conceded", "wickets_with_bowling", false, "runs_conceded", 3);        
    }
}
?>