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

    function OnLoadPageData()
    {
        /* @var Match $tournament */

        # check parameter
        if (!isset($_GET['tournament']) or !is_numeric($_GET['tournament'])) $this->Redirect();

        # get tournament
        $match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
        $match_manager->ReadByMatchId(array($_GET['tournament']));
        $this->tournament = $match_manager->GetFirst();
        unset($match_manager);

        # must have found a ground
        if (!$this->tournament instanceof Match)   $this->Redirect('/play/');

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
        $title = "Statistics for the " . $this->tournament->GetTitle() . ", " . Date::BritishDate($this->tournament->GetStartTime());
        $this->SetPageTitle($title);
        $this->SetContentConstraint(StoolballPage::ConstrainColumns());
        $this->SetContentCssClass('stats');
    }

    function OnPageLoad()
    {
        echo "<h1>" . htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false) . "</h1>";

        # See what stats we've got available
        require_once("_summary-data-found.php");

        $tournament_page_link = "<a href=\"" . htmlentities($this->tournament->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) . "\">" . htmlentities($this->tournament->GetTitle(), ENT_QUOTES, "UTF-8", false) . " page</a>";
        if (!$has_player_stats)
        {
            echo "<p>There aren't any statistics for the " . htmlentities($this->tournament->GetTitle(), ENT_QUOTES, "UTF-8", false) . ' yet.</p>
            <p>To find out how to add them, see <a href="/play/manage/website/matches-and-results-why-you-should-add-yours/">Matches and results - why you should add yours</a>.</p>' .
            "<p>You can also view the $tournament_page_link.</p>";
        }
        else
        {
            echo "<p>View the $tournament_page_link.</p>";
            require_once("_summary-controls.php");
            echo "<p>View the $tournament_page_link.</p>";
        }
        $this->AddSeparator();
        $this->BuySomething();
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>