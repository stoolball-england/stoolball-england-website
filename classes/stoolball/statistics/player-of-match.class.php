<?php
require_once("statistic.class.php");

/**
 * Player of the match nominations recieved by a player
 */
class PlayerOfTheMatch extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("player-of-match");
        parent::SetTitle("Player of the match nominations");
        parent::SetDescription("All of the matches where players were awarded player of the match for their outstanding performances on the pitch.");
        parent::SetSupportsFilterByPlayer(true);
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("nomination");
        parent::SetItemTypePlural("nominations");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        $this->statistics_data_source->FilterPlayerOfTheMatch(true);
        return $this->statistics_data_source->ReadMatchPerformances();
    }
}
?>