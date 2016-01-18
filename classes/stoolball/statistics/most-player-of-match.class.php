<?php
require_once("statistic.class.php");

/**
 * Aggregate player of the match nominations recieved by a player
 */
class MostPlayerOfTheMatch extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-player-of-match");
        parent::SetTitle("Most player of the match nominations");
        parent::SetColumnHeaders(array("Nominations"));
        parent::SetDescription("Find out who has won the most player of the match awards for their outstanding performances on the pitch.");
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestPlayerAggregate("player_of_match");
    }
}
?>