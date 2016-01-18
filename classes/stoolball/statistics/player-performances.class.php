<?php
require_once("statistic.class.php");

/**
 * Player performance summaries for matches a player was involved in
 */
class PlayerPerformances extends Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    
    public function __construct(StatisticsManager $statistics_data_source) {
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("player-performances");
        parent::SetTitle("Player performances");
        parent::SetDescription("All of the match performances by a stoolball player, summarising their batting, bowling and fielding in the match.");
        parent::SetSupportsFilterByPlayer(true);
        parent::SetSupportsFilterByBattingPosition(true);
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("performance");
        parent::SetItemTypePlural("performances");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadMatchPerformances();
    }
}
?>