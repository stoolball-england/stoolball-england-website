<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');

class CurrentPage extends Page
{
	private $team_id;
    private $season;

	public function OnPageInit()
	{
		# This is a JavaScript file
		if (!headers_sent()) header("Content-Type: text/javascript; charset=utf-8");

		# First, check that one or more teams have been requested
		if (!isset($_GET['team']) or !$_GET['team']) die();

		$this->team_id = $_GET['team'];
		if (!is_numeric($this->team_id)) die();
        
        if (isset($_GET['season'])) {
            $this->season = str_replace("-", "/", preg_replace('/[^0-9-]/i', '', $_GET['season']));
        }
	}
	
	public function OnLoadPageData()
	{
		require_once('stoolball/team-manager.class.php');
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $team_manager->FilterByTeamType(array());
		$team_manager->ReadById(array($this->team_id));
		$team = $team_manager->GetFirst();

		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByTeam(array($team->GetId()));
		$statistics_manager->ReadMatchStatistics();
		$stats = $statistics_manager->GetItems();
		
        if (!$team instanceof Team) die();

		require_once('stoolball/statistics-calculator.class.php');
		$calculator = new StatisticsCalculator();
		$calculator->AnalyseMatchData($stats, $team);
		$has_team_stats = $calculator->EnoughDataForStats($this->season);

		if ($has_team_stats)
		{
 			?>
{
    "all": [
        {"label":"Won","value":<?php echo $calculator->Wins($this->season); ?>}, 
        {"label":"Lost","value":<?php echo $calculator->Losses($this->season); ?>}, 
        {"label":"Tied","value":<?php echo $calculator->EqualResults($this->season); ?>}, 
        {"label":"No result","value":<?php echo $calculator->NoResults($this->season); ?>}
    ], 
    
    "home": [
        {"label":"Won","value":<?php echo $calculator->HomeWins($this->season); ?>}, 
        {"label":"Lost","value":<?php echo $calculator->HomeLosses($this->season); ?>}, 
        {"label":"Tied","value":<?php echo $calculator->HomeEqualResults($this->season); ?>}, 
        {"label":"No result","value":<?php echo $calculator->HomeNoResults($this->season); ?>}
    ], 
    
    "away": [
        {"label":"Won","value":<?php echo $calculator->AwayWins($this->season); ?>}, 
        {"label":"Lost","value":<?php echo $calculator->AwayLosses($this->season); ?>}, 
        {"label":"Tied","value":<?php echo $calculator->AwayEqualResults($this->season); ?>}, 
        {"label":"No result","value":<?php echo $calculator->AwayNoResults($this->season); ?>}
    ], 
    "opponents": <?php $this->WriteOpponentsData($calculator->Opponents($this->season));?>
}       
            <?php
		} 
		exit();
	}

    /**
     * Write a chart showing results against various opponents
     *
     * @param array $opponents
     */
    private function WriteOpponentsData($opponents)
    {
        # First check there are some opponents to chart
        if (!is_array($opponents)  or !count($opponents)) return ; 
        
        $matches = array();

        foreach ($opponents as $opponent)
        {
            $matches[] = $opponent['matches'];
        }
        array_multisort($matches, SORT_DESC, $opponents);

        $team_names = array();
        $matches = array();
        $wins = array();
        $losses = array();
        $equal = array();

        $total = count($opponents);
        $maximum_results = 10;
        for ($i =0;$i<$total;$i++)
        {
            # The Google Chart API will only allow a maximum chart size, so the last one may have to be "others"
            if (($i < $maximum_results-1) or $total == $maximum_results)
            {
                $index = $i;
                $team = $opponents[$i]['team'];
                /* @var $team Team */

                $team_name = html_entity_decode(preg_replace("/\b(The|Mixed|Ladies|Club)\b/", "", $team->GetName()));
            }
            else
            {
                $index = $maximum_results - 1;
                $team_name = "Others";
            }

            if (!isset($matches[$index])) $matches[$index] = 0;
            $matches[$index] += $opponents[$i]['matches'];
            if (!isset($wins[$index])) $wins[$index] = 0;
            $wins[$index] += $opponents[$i]['wins'];
            if (!isset($losses[$index])) $losses[$index] = 0;
            $losses[$index] += $opponents[$i]['losses'];
            if (!isset($equal[$index])) $equal[$index] = 0;
            $equal[$index] += $opponents[$i]['equal'];

            $team_names[$index] = $team_name . " (" . $matches[$index] . ")";
        }

        if ($total > $maximum_results) $total = $maximum_results;
        
        echo '{"labels":[';
        $total = count($team_names);
        for ($i = 0; $i < $total;$i++) 
        {
            echo  '"' . $team_names[$i] . '"';
            if ($i< $total -1) echo  ",";
        }
        echo '],"datasets":[{"label":"Won","data":['; 
        $total = count($wins);
        for ($i = 0;$i < $total;$i++) 
        {
            echo $wins[$i];
            if ($i< $total -1) echo  ",";            
        }
        echo ']},{"label":"Tied","data":['; 
        $total = count($equal);
        for ($i = 0;$i < $total;$i++) 
        {
            echo $equal[$i];
            if ($i< $total -1) echo  ",";            
        }
        echo ']},{"label":"Lost","data":['; 
        $total = count($losses);
        for ($i = 0;$i < $total;$i++) 
        {
            echo $losses[$i];
            if ($i< $total -1) echo  ",";            
        }
        echo "]}]}";
    }

}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>