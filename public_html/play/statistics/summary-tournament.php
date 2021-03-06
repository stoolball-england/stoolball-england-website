<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');

class CurrentPage extends StoolballPage
{
    /**
     * The tournament to display
     *
     * @var Match
     */
    private $tournament;
    private $statistics = array();
    private $page_not_found = false;

    function OnLoadPageData()
    {
        /* @var Match $tournament */

        # check parameter
        if (!isset($_GET['match']) or !is_numeric($_GET['match'])) $this->Redirect();

        # get tournament
        $match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
        $match_manager->ReadByMatchId(array($_GET['match']));
        $this->tournament = $match_manager->GetFirst();
        unset($match_manager);

        # must have found a match
        if (!$this->tournament instanceof Match) {
            $this->page_not_found = true;
            return;
        }

        # Get some stats on the best players
        require_once('stoolball/statistics/statistics-manager.class.php');
        $statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
        $statistics_manager->FilterByTournament(array($this->tournament->GetId()));
        
        $this->statistics["querystring"] = "?tournament=" . $this->tournament->GetId();
        require_once("_summary-data-query.php");
        unset($statistics_manager);
    }

    function OnPrePageLoad()
    {
        if ($this->page_not_found)
        {
            $this->SetPageTitle('Page not found');
            return;
        }
        
        $title = "Statistics for the " . $this->tournament->GetTitle() . ", " . Date::BritishDate($this->tournament->GetStartTime());
        $this->SetPageTitle($title);
        $this->SetContentConstraint(StoolballPage::ConstrainBox());
        $this->SetContentCssClass('stats');
    }

    function OnPageLoad()
    {
        if ($this->page_not_found)
        {
           require_once($_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/stoolball/section-404.php");
           return;
        }
        
        echo "<h1>" . Html::Encode($this->GetPageTitle()) . "</h1>";

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => $this->tournament->GetNavigateUrl(), 'Tournament statistics' => '');       
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php


        # See what stats we've got available
        require_once("_summary-data-found.php");

        if (!$has_player_stats)
        {
            echo "<p>There aren't any statistics for the " . htmlentities($this->tournament->GetTitle(), ENT_QUOTES, "UTF-8", false) . ' yet.</p>
            <p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results - why you should add yours</a>.</p>';
        }
        else
        {
            require_once("_summary-controls.php");
        }
        ?>
        </div>
        </div>
        <?php 
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>