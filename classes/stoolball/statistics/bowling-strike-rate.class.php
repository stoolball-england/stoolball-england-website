<?php
require_once("statistic.class.php");

/**
 * Mean average number of balls delivered by a bowler for each wicket they take
 */
class BowlingStrikeRate extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("bowling-strike-rate");
        parent::SetTitle("Bowling strike rates");
        parent::SetColumnHeaders(array("Strike rate"));
        parent::SetDescription("A bowler's strike rate measures how many deliveries he or she typically bowls before taking a wicket. Lower numbers are better.
        
We only include people who have bowled in at least three matches, and we ignore wickets in innings where the bowling card wasn't filled in.");
        parent::SetShowDescription(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAverage("balls_bowled", "wickets_with_bowling", false, "balls_bowled", 3);
    }
}
?>