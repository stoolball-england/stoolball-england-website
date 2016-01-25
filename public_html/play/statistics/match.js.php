<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/page.class.php');
require_once('stoolball/match-manager.class.php');

class CurrentPage extends Page
{
	/**
	 * Match to return data for
	 * @var Match
	 */
	private $match;

	public function OnPageInit()
	{
        header("Content-Type: application/javascript");

		# Get match to display data for
		if (!isset($_GET['match']) or !is_numeric($_GET['match'])) {
		    http_response_code(404);
            exit();
		}
	}

	public function OnLoadPageData()
	{
        $match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
        $match_manager->ReadByMatchId(array($_GET['match']));
        $match_manager->ExpandMatchScorecards();
        $this->match = $match_manager->GetFirst();
        unset($match_manager);

        if ($this->match->GetHomeTeam() instanceof Team and $this->match->GetAwayTeam() instanceof Team) {
        ?>
{
    "worm": {
        "labels": [
            <?php
            $overs = $this->HowManyOversInTheMatch();
            echo $this->BuildOversLabels(0, $overs);
            ?>        
        ],
        "datasets": [
        <?php
        $home_batted_first = $this->match->Result()->GetHomeBattedFirst();
        if ($home_batted_first === true || is_null($home_batted_first)) {
            ?>
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetHomeTeam()->GetName(), $home_batted_first === true) ?>",
                "data": [0<?php echo $this->BuildCumulativeOverTotals($this->match->Result()->AwayOvers()->GetItems(), $overs) ?>]
            },
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetAwayTeam()->GetName(), $home_batted_first === false) ?>",
                "data": [0<?php echo $this->BuildCumulativeOverTotals($this->match->Result()->HomeOvers()->GetItems(), $overs) ?>]
            }      
            <?php
        } else {
            ?>
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetAwayTeam()->GetName(), $home_batted_first === false) ?>",
                "data": [0<?php echo $this->BuildCumulativeOverTotals($this->match->Result()->HomeOvers()->GetItems(), $overs) ?>]
            },      
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetHomeTeam()->GetName(), $home_batted_first === true) ?>",
                "data": [0<?php echo $this->BuildCumulativeOverTotals($this->match->Result()->AwayOvers()->GetItems(), $overs) ?>]
            }
            <?php
        }
        ?>]    
    },
    "manhattanFirstInnings": {
        "labels": [
            <?php
            $overs = $this->HowManyOversInTheMatch();
            echo $this->BuildOversLabels(1, $overs);
            ?>        
        ],
        "datasets": [
        <?php if ($home_batted_first === true || is_null($home_batted_first)) { ?>
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetHomeTeam()->GetName(), $home_batted_first === true) ?>",
                "data": [<?php echo $this->BuildOverTotals($this->match->Result()->AwayOvers()->GetItems(), $overs) ?>]
            }      
            <?php
        } else {
            ?>
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetAwayTeam()->GetName(), $home_batted_first === false) ?>",
                "data": [<?php echo $this->BuildOverTotals($this->match->Result()->HomeOvers()->GetItems(), $overs) ?>]
            }      
        <?php } ?>
        ]
    },
    "manhattanSecondInnings": {
        "labels": [
            <?php
            $overs = $this->HowManyOversInTheMatch();
            echo $this->BuildOversLabels(1, $overs);
            ?>        
        ],
        "datasets": [
        <?php if ($home_batted_first === false) { ?>
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetHomeTeam()->GetName(), $home_batted_first === true) ?>",
                "data": [<?php echo $this->BuildOverTotals($this->match->Result()->AwayOvers()->GetItems(), $overs) ?>]
            }      
            <?php
        } else {
            ?>
            {
                "label": "<?php echo $this->BuildTeamNameLabel($this->match->GetAwayTeam()->GetName(), $home_batted_first === false) ?>",
                "data": [<?php echo $this->BuildOverTotals($this->match->Result()->HomeOvers()->GetItems(), $overs) ?>]
            }      
        <?php } ?>
        ]
    }
    <?php } ?>
}
        <?php
        exit();
 	}
    
    private function HowManyOversInTheMatch() {
        # Get number of overs in match, going on the bowling data rather than the official match length because 
        # a match may be cut short for light or extended for a friendly.
        $home_overs = $this->match->Result()->HomeOvers()->GetCount();
        $away_overs = $this->match->Result()->AwayOvers()->GetCount();
        $overs = $home_overs;
        if ($away_overs > $home_overs) {
            $overs = $away_overs;
        }
        return $overs;
    }

    private function BuildOversLabels($starting_from, $overs) {
        $labels = "";
        for ($i = $starting_from; $i <= $overs; $i++) {
            $labels .= '"' . $i . '"';
            if ($i < $overs) {
                $labels .= ",";
            }
        }
        return $labels;
    }

    private function BuildTeamNameLabel($team_name, $batting_first) {
        $label = str_replace('\\', '', $team_name);
        if ($batting_first) {
            $label .= " (batting first)";
        }
        return $label;
    }


    private function BuildCumulativeOverTotals(array $overs, $total_overs) {
        $total = 0;
        $data = "";
        for ($i = 0; $i < $total_overs; $i++) {
            if (array_key_exists($i, $overs)) {
                $over = $overs[$i];
                /* @var $over Over */
                $total += $over->GetRunsInOver();
                $data .= "," .  $total;
            }
        }
        return $data;
    }

    private function BuildOverTotals(array $overs, $total_overs) {
        $data = "";
        for ($i = 0; $i < $total_overs; $i++) {
            if (array_key_exists($i, $overs)) {
                $over = $overs[$i];
                /* @var $over Over */
                if ($data) {
                    $data .= ",";
                }
                $data .= $over->GetRunsInOver();
            }
        }
        return $data;
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>