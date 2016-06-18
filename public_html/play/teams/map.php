<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    private $player_type;
    private $player_type_text;

	function OnPageInit()
	{
		$this->SetHasGoogleMap(true);
		parent::OnPageInit();
	}

	function OnPrePageLoad()
	{        
        # Check for player type
        $this->player_type = null;
        if (isset($_GET['player']))
        {
            if ($_GET['player'] == "past")
            {
                $this->player_type = 0;
                $this->player_type_text = "Past ";
            } 
            else 
            {
                $this->player_type = PlayerType::Parse($_GET['player']);
                $this->player_type_text = PlayerType::Text($this->player_type) . " ";
                if ($this->player_type == PlayerType::JUNIOR_MIXED) 
                {
                    $this->player_type_text = "Junior ";
                }
            }    
        } 

        $this->SetPageTitle("Map of " . strtolower($this->player_type_text) . " stoolball teams");
		$this->SetPageDescription("See a map of all the " . strtolower($this->player_type_text) . "stoolball teams currently playing.");
		
		$this->LoadClientScript("/scripts/lib/markerclusterer_compiled.js");
		$this->LoadClientScript('/scripts/maps-3.js');
		$this->LoadClientScript("map.js.php?player=" . $this->player_type, true);
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false));
		
        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('List of teams' => '/teams/all', 'Map of teams' => '');
        echo new Tabs($tabs);
 
        ?>
        <div class="box tab-box">
        <div class="dataFilter"><nav><p class="follow-on large">Show me:</p>
            <ul>
                <?php 
                echo ($this->player_type === null) ? '<li><em>Current teams</em></li>' : '<li><a href="/teams/map">Current teams</a></li>';
                echo ($this->player_type === PlayerType::LADIES) ? '<li><em>Ladies</em></li>' : '<li><a href="/teams/map/ladies">Ladies</a></li>';
                echo ($this->player_type === PlayerType::MIXED) ? '<li><em>Mixed</em></li>' : '<li><a href="/teams/map/mixed">Mixed</a></li>';
                echo ($this->player_type === PlayerType::JUNIOR_MIXED) ? '<li><em>Junior</em></li>' : '<li><a href="/teams/map/junior">Junior</a></li>';
                echo ($this->player_type === 0) ? '<li><em>Past teams</em></li>' : '<li><a href="/teams/map/past">Past teams</a></li>';
                ?>
            </ul>
            <p>Can't find it? <a href="<?php echo $this->GetSettings()->GetUrl('TeamAdd');?>">Tell us about your team</a> 
                <span class="or">or</span> <a href="/play/manage/start-a-new-stoolball-team/">start a new team</a></p>
        </nav></div>
            <div id="map" class="map"><p>Loading map&#8230;</p> <p><small>If the map doesn't appear here, you may have JavaScript turned off. Turn it on if you can, or try using the <a href="/teams">List of stoolball teams</a>.</small></p></div>
        </div>
        <?php 
        $this->ShowSocialAccounts();
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>