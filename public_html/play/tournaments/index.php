<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/match-list-control.class.php');
require_once('xhtml/forms/xhtml-form.class.php');
require_once('xhtml/forms/xhtml-select.class.php');

class CurrentPage extends StoolballPage
{
    private $a_matches;
    private $player_type;
    private $player_type_text;
    private $rss_url;

    function OnLoadPageData()
    {
        # new data manager
        $manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

        # get matches
        $i_one_day = 86400;
        $i_start = gmdate('U')-($i_one_day*1); # yesterday
        $i_end = gmdate('U')+($i_one_day*365); # in the next year

        # Check for player type
        $this->player_type = null;
        if (isset($_GET['player']))
        {
            $this->player_type = PlayerType::Parse($_GET['player']);
        }
        $a_player_types = is_null($this->player_type) ? null : array($this->player_type);
        if ($this->player_type == PlayerType::JUNIOR_MIXED) 
        {
            $a_player_types[] = PlayerType::GIRLS;
            $a_player_types[] = PlayerType::BOYS;
        }

        $manager->FilterByMatchType(array(MatchType::TOURNAMENT));
        $manager->FilterByPlayerType($a_player_types);
        $manager->FilterByDateStart($i_start);
        $manager->FilterByDateEnd($i_end);
        $manager->ReadMatchSummaries();
        $this->a_matches = $manager->GetItems();
        unset($manager);
    }

    function OnPrePageLoad()
    {
        $title = "Stoolball tournaments";
        if ($this->player_type)
        {
            $this->player_type_text = PlayerType::Text($this->player_type) . " ";
            if ($this->player_type == PlayerType::JUNIOR_MIXED) 
            {
                $this->player_type_text = "Junior ";
            }    
            $title = $this->player_type_text . strtolower($title);
        } 
        $this->SetPageTitle($title);
        $this->SetPageDescription("See all the " . strtolower($this->player_type_text) . " stoolball tournaments taking place in the next year.");
        $this->SetContentConstraint(StoolballPage::ConstrainBox());
    }

    function OnCloseHead()
    {
        $this->rss_url = htmlentities("https://" . trim($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],"/") . ".rss", ENT_QUOTES, "UTF-8", false);
        ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false); ?> RSS feed" href="<?php echo $this->rss_url; ?>" />
        <?php
        parent::OnCloseHead();
    }

    function OnPageLoad()
    {
        $title = "Go to a stoolball tournament";
        if ($this->player_type)
        {
            $title = "Go to a " . strtolower($this->player_type_text) . " stoolball tournament";
        } 
        echo "<h1>" . htmlentities($title, ENT_QUOTES, "UTF-8", false) . "</h1>";

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('List of tournaments' => '', 'Map of tournaments' => '/tournaments/map');
        echo new Tabs($tabs);
        ?>
        <div class="box tab-box">
        <div class="dataFilter"><nav><p class="follow-on large">Show me:</p>
            <ul>
                <?php 
                echo ($this->player_type == null) ? '<li><em>All tournaments</em></li>' : '<li><a href="/tournaments/all">All tournaments</a></li>';
                echo ($this->player_type == PlayerType::LADIES) ? '<li><em>Ladies</em></li>' : '<li><a href="/tournaments/ladies">Ladies</a></li>';
                echo ($this->player_type == PlayerType::MIXED) ? '<li><em>Mixed</em></li>' : '<li><a href="/tournaments/mixed">Mixed</a></li>';
                echo ($this->player_type == PlayerType::JUNIOR_MIXED) ? '<li><em>Junior</em></li>' : '<li><a href="/tournaments/junior">Junior</a></li>';
                ?>
            </ul>
            <p class="follow-on"><span class="or">or</span> <a href="/tournaments/add">add your tournament</a></p>
        </nav></div>
        <div class="box-content">
            <img src="/images/play/angmering.jpg" alt="" width="300" height="171" />
        <?php 
                        
        # Display the matches
        if (count($this->a_matches))
        {
            echo new MatchListControl($this->a_matches);
        }
        else
        {
            ?>
            <p>Sorry, there are no <?php echo strtolower($this->player_type_text);?>tournaments to show you.</p>
            <?php 
        }
        
        ?>
        <p><a class="icalendar" href="/<?php echo htmlentities(trim($_SERVER['REQUEST_URI'], "/"), ENT_QUOTES, "UTF-8", false); ?>/calendar">Add <?php echo strtolower($this->player_type_text);?>tournaments to your calendar</a></p>
        
        <p>Join the <a href="https://www.facebook.com/groups/1451559361817258/">Sussex Stoolball tournaments Facebook group</a>.</p>
        
        <form method='post' action='http://blogtrottr.com' class="rss-form">
            <h2>Get alerts about new <?php echo strtolower($this->player_type_text);?>tournaments <small>(uses <a href="http://blogtrottr.com">blogtrottr.com</a>)</small></h2>
            <label for="rss-email">Your email:</label>
            <input type='email' name='btr_email' id="rss-email" />
            <input type='hidden' name='btr_url' value='<?php echo $this->rss_url; ?>' />
            <input type='hidden' name='schedule_type' value='0' />
            <input type='submit' value='Subscribe' />
            <p><a href="<?php echo $this->rss_url; ?>" rel="alternate" type="application/rss+xml" class="rss">Subscribe by RSS</a></p>
        </form>
        </div></div>
        <?php
        $this->ShowSocialAccounts();
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>