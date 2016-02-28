<?php
namespace Stoolball\Statistics;

require_once("statistic.class.php");

/**
 * Aggregate number of innings where a player scored at least the given number of runs
 */
class MostWicketHaulsOf extends \Statistic {

    /**
     * @var StatisticsManager
     */
    private $statistics_data_source;
    private $minimum_qualifying_wickets;
    
    public function __construct($minimum_qualifying_wickets, \StatisticsManager $statistics_data_source) {
        $this->minimum_qualifying_wickets = (int)$minimum_qualifying_wickets;
        $this->statistics_data_source = $statistics_data_source;
        parent::SetUrlSegment("most-{0}-wickets");
        parent::SetTitle("Most times taking $minimum_qualifying_wickets wickets in an innings");
        parent::SetColumnHeaders(array("Innings"));
        parent::SetDescription("Find out who has taken $minimum_qualifying_wickets wickets in an innings the most times in all stoolball matches.");
        parent::SetSupportsPagedResults(true);
        parent::SetItemTypeSingular("player");
        parent::SetItemTypePlural("players");
        parent::SetCssClass("bowling");
    }
            
    /**
     * Gets the statistical data from the data source
     */
    public function ReadStatistic() {
        return $this->statistics_data_source->ReadBestAggregateOfQualifyingPerformances("wickets", $this->minimum_qualifying_wickets);        
    }
}
?>