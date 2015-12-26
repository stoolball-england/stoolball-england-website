<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('xhtml/forms/xhtml-form.class.php');

class CurrentPage extends StoolballPage
{
    private $player_type;
    private $player_type_text;
    private $rss_url;
    
	function OnPageInit()
	{
		$this->SetHasGoogleMap(true);
		parent::OnPageInit();
	}

	function OnPrePageLoad()
	{
        $title = "Map of stoolball tournaments";
        
                # Check for player type
        $this->player_type = null;
        if (isset($_GET['player']))
        {
            $this->player_type = PlayerType::Parse($_GET['player']);
            $this->player_type_text = PlayerType::Text($this->player_type) . " ";
            if ($this->player_type == PlayerType::JUNIOR_MIXED) 
            {
                $this->player_type_text = "Junior ";
            }    
            $title = "Map of " . strtolower($this->player_type_text) . " stoolball tournaments";
        } 
        $this->SetPageTitle($title);
        $this->SetPageDescription("See a map of all the " . strtolower($this->player_type_text) . " stoolball tournaments taking place in the next year.");
		    
        $this->LoadClientScript("/scripts/lib/markerclusterer_compiled.js");
        $this->LoadClientScript('maps-3.js');
		$this->LoadClientScript("map.js.php?player=" . $this->player_type, true);
	}
    
        function OnCloseHead()
    {
        $this->rss_url = htmlentities("http://" . trim($_SERVER['HTTP_HOST'] . str_replace("/map", "", $_SERVER['REQUEST_URI']),"/") . ".rss", ENT_QUOTES, "UTF-8", false);
        ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false); ?> RSS feed" href="<?php echo $this->rss_url; ?>" />
        <?php
        parent::OnCloseHead();
    }

	function OnPageLoad()
	{
        $title = "Map of stoolball tournaments";
        if ($this->player_type)
        {
            $title = "Map of " . strtolower($this->player_type_text) . " stoolball tournaments";
        } 
        echo "<h1>" . htmlentities($title, ENT_QUOTES, "UTF-8", false) . "</h1>";
         
        ?>
        <div class="tab-option tab-inactive"><p><a href="/tournaments/all">List of tournaments</a></p></div>
        <div class="tab-option tab-active large"><h2>Map of tournaments</h2></div>
        <div class="box tab-box">
        <div class="dataFilter"><nav><p class="follow-on large">Show me:</p>
            <ul>
                <?php 
                echo ($this->player_type == null) ? '<li><em>All tournaments</em></li>' : '<li><a href="/tournaments/map/all">All tournaments</a></li>';
                echo ($this->player_type == PlayerType::LADIES) ? '<li><em>Ladies</em></li>' : '<li><a href="/tournaments/map/ladies">Ladies</a></li>';
                echo ($this->player_type == PlayerType::MIXED) ? '<li><em>Mixed</em></li>' : '<li><a href="/tournaments/map/mixed">Mixed</a></li>';
                echo ($this->player_type == PlayerType::JUNIOR_MIXED) ? '<li><em>Junior</em></li>' : '<li><a href="/tournaments/map/junior">Junior</a></li>';
                ?>
            </ul>
            <p class="follow-on"><span class="or">or</span> <a href="/tournaments/add">add your tournament</a></p>
        </nav></div>
        <div class="map">
            <div id="map"><p>Loading map&#8230;</p> <p><small>If the map doesn't appear here, you may have JavaScript turned off. 
                Turn it on if you can, or try using the <a href="/tournaments/all">list of tournaments</a>.</small></p></div>            
        </div>
        </div>
        <?php 
        $this->ShowSocialAccounts();
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>