<?php
require_once("statistic.class.php");

/**
 * Aggregate wickets taken by a player
 */
class MostWickets extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-wickets");
        parent::SetTitle("Most wickets");
        parent::SetColumnHeaders(array("Wickets"));
        parent::SetDescription("If a player is out caught, caught and bowled, bowled, body before wicket or for hitting the ball twice the wicket is credited to the bowler and included here. Players run-out or timed out are not counted.");
        parent::SetShowDescription(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAggregate("wickets");        
    }
}
?>