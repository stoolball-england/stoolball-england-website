<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    private $competition_id;
    private $season_id;
    
    /**
     * @var Competition 
     */ 
    private $competition;
    
    /**
     * @var Season
     */
    private $season;
    private $has_map;
    
	function OnPageInit()
	{
		$this->SetHasGoogleMap(true);
		if (isset($_GET['competition']) and is_numeric($_GET['competition'])) $this->competition_id = (int)$_GET['competition'];
        if (isset($_GET['season']) and is_numeric($_GET['season'])) $this->season_id = (int)$_GET['season'];
		parent::OnPageInit();
	}
    
    public function OnLoadPageData()
    {
        require_once('stoolball/competition-manager.class.php');
        $manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
        if ($this->season_id) {
            $manager->ReadById(null, array($this->season_id));
        } else {
            $manager->ReadById(array($this->competition_id));
        }
        $this->competition = $manager->GetFirst();
        $this->season = $this->competition->GetWorkingSeason();
        unset($manager);
        
        # If the competition was requested, redirect to the current season
        if ($this->competition_id) {
            http_response_code(303);
            header("Location: " . $this->season->GetMapUrl());
            return;
        }

        $this->has_map = count($this->season->GetTeams());
        
        # Get other seasons
        require_once('stoolball/season-manager.class.php');
        $a_comp_ids = array($this->competition->GetId());
        $o_season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
        $o_season_manager->ReadByCompetitionId($a_comp_ids);
        $a_other_seasons = $o_season_manager->GetItems();

        $this->competition->SetSeasons(array());
        foreach ($a_other_seasons as $season)
        {
            if ($season->GetId() == $this->season->GetId())
            {
                $this->competition->AddSeason($this->season, true);
            } 
            else 
            {
                $this->competition->AddSeason($season, false);                  
            }
        }
    
    }
    
	function OnPrePageLoad()
	{        
        $this->SetPageTitle("Map of the " . $this->season->GetCompetitionName());
		$this->SetPageDescription("See a map of all the stoolball teams playing in the " . $this->season->GetCompetitionName() . ".");
		
		$this->LoadClientScript("markerclusterer_compiled.js");
		$this->LoadClientScript('maps-3.js');
		$this->LoadClientScript("map.js.php?season=" . $this->season->GetId(), true);
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
		
        $season = $this->competition->GetWorkingSeason();
        ?>
        <div class="tab-option tab-inactive"><p><a href="<?php echo Html::Encode($season->GetNavigateUrl());?>">Summary</a></p></div>
        <?php
        if ($season->MatchTypes()->Contains(MatchType::LEAGUE))
        {
            ?>
            <div class="tab-option tab-inactive"><p><a href="<?php echo Html::Encode($season->GetTableUrl());?>">Table</a></p></div>
            <?php
        }
        ?>
        <div class="tab-option tab-active large"><h2>Map</h2></div>
        <div class="tab-option tab-inactive"><p><a href="<?php echo Html::Encode($this->season->GetStatisticsUrl());?>">Statistics</a></p></div>
            <div class="box tab-box">
                <div class="dataFilter large"></div>
    
        <?php    
        if ($this->has_map)
        {
            ?> 
                <div id="map" class="map"><p>Loading map&#8230;</p>
                <p><small>If the map doesn't appear here, you may have JavaScript turned off. 
                    Turn it on if you can, or view the <a href="<?php echo Html::Encode($this->competition->GetNavigateUrl());?>"><?php echo Html::Encode($this->competition->GetName());?> page</a>.</small></p></div>
                <div class="box-content">
                <?php $this->ShowOtherSeasons(); ?>
                </div>
                <?php
        }
        else 
        {
            ?>
            <div class="box-content">
            <p>There is no map for this season as there are no teams.</p>
            <?php $this->ShowOtherSeasons(); ?>
            </div>
            <?php    
        }
        ?>
        
            </div>

        <?php       
        $this->ShowSocialAccounts();        
    }

    private function ShowOtherSeasons()
    {
        # Check for other seasons. Check is >2 because current season is there twice - added above
        if (count($this->competition->GetSeasons()) > 2)
        {
            require_once("stoolball/season-list-control.class.php");
            echo new XhtmlElement('h2', 'Other seasons in the ' . Html::Encode($this->competition->GetName()),"screen");
            $season_list = new SeasonListControl($this->competition->GetSeasons());
            $season_list->SetExcludedSeasons(array($this->season));
            $season_list->AddCssClass("screen");
            $season_list->SetUrlMethod('GetMapUrl');
            echo $season_list;
        }
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>