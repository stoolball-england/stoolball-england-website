<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

# Offload tournaments to a separate page to prevent code on this one getting too complex.
require_once('stoolball/match-type.enum.php');
if (isset($_GET['type']) and $_GET['type'] == MatchType::TOURNAMENT and isset($_GET['match']))
{
    require_once('summary-tournament.php');
    exit();
}

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');

class CurrentPage extends StoolballPage
{
    /**
     * The match to display
     *
     * @var Match
     */
    private $match;
    private $statistics = array();
    private $page_not_found = false;
    private $has_statistics = false;

    function OnLoadPageData()
    {
        /* @var Match $tournament */

        # check parameter
        if (!isset($_GET['match']) or !is_numeric($_GET['match'])) $this->Redirect();

        # get match
        $match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
        $match_manager->ReadByMatchId(array($_GET['match']));
        $match_manager->ExpandMatchScorecards();
        $this->match = $match_manager->GetFirst();
        unset($match_manager);

        # must have found a match
        if (!$this->match instanceof Match) {
            $this->page_not_found = true;
            return;
        }
        
        $result = $this->match->Result();
        $this->has_statistics = $result->HomeOvers()->GetCount() or $result->AwayOvers()->GetCount();
    }

    function OnPrePageLoad()
    {
        if ($this->page_not_found)
        {
            $this->SetPageTitle('Page not found');
            return; 
        }
        
        $title = "Statistics for " . $this->match->GetTitle() . ", " . Date::BritishDate($this->match->GetStartTime(), false);
        $this->SetPageTitle($title);
        $this->SetContentConstraint(StoolballPage::ConstrainBox());
        if ($this->has_statistics) {
            $this->LoadClientScript("/scripts/lib/chart.min.js");
            $this->LoadClientScript("/scripts/chart.js?v=2");
            $this->LoadClientScript("/play/statistics/match.js");
            ?>
            <!--[if lte IE 8]><script src="/scripts/lib/excanvas.compiled.js"></script><![endif]-->
            <?php
        }
    }

    function OnPageLoad()
    {
        # Matches this page shouldn't edit are page not found
        if ($this->page_not_found)
        {
           require_once($_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/stoolball/section-404.php");
           return;
        }
        
        echo "<h1>" . Html::Encode($this->GetPageTitle()) . "</h1>";

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => $this->match->GetNavigateUrl());
        if ($this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH) {
            $tabs['Match statistics'] = '';   
            $tabs['Tournament statistics'] = $this->match->GetTournament()->GetNavigateUrl() . '/statistics';   
        } else {
            $tabs['Statistics'] = '';   
        }    
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php        
        if (!$this->has_statistics)
        {
            echo "<p>There aren't any statistics for " . htmlentities($this->match->GetTitle(), ENT_QUOTES, "UTF-8", false) . ' yet.</p>
            <p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results - why you should add yours</a>.</p>';
        }
        else
        {
            ?>
            <div class="statsColumns">
                <div class="statsColumn">
            <div class="chart-js-template" id="worm-chart"></div>
            </div>
                <div class="statsColumn">
            <div class="chart-js-template" id="run-rate-chart"></div>
            </div>
            </div>
            <div class="statsColumns manhattan">
            <h2>Scores in each over</h2>
                <div class="statsColumn">
                    <div class="chart-js-template" id="manhattan-chart-first-innings"></div>
                </div>
                <div class="statsColumn">
                    <div class="chart-js-template" id="manhattan-chart-second-innings"></div>
                </div>
            </div>
            <?php
        }
        ?>
        </div>
        </div>
        <?php 
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>