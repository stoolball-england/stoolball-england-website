<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPageInit()
	{
		$this->SetHasGoogleMap(true);
		parent::OnPageInit();
	}

	function OnPrePageLoad()
	{        
        $this->SetPageTitle("Map of schools playing stoolball");
		$this->SetPageDescription("See a map of all the schools currently playing stoolball.");
		
		$this->LoadClientScript("/scripts/lib/markerclusterer_compiled.js");
		$this->LoadClientScript('/scripts/maps-3.js');
		$this->LoadClientScript("map.js.php?team-type=" .implode(",", array(Team::SCHOOL_YEAR, Team::SCHOOL_YEARS, Team::SCHOOL_CLUB, Team::SCHOOL_OTHER)), true);
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));
		
       ?>
       <p>Stoolball is played in schools across England, and in other areas of the UK. We know there are many more schools playing, and we'd like to add them to our map. 
           If your school plays stoolball and isn't shown here, please let us know.</p>
       <div class="dataFilter"><nav>
            <p>Can't find it? <a href="<?php echo $this->GetSettings()->GetUrl('TeamAdd');?>">Tell us about your school</a> 
                <span class="or">or</span> <a href="/rules/starter-stoolball/">see how to start playing</a></p>
       </nav></div>
       <div id="map" class="map"><p>Loading map&#8230;</p> <p><small>If the map doesn't appear here, you may have JavaScript turned off. Turn it on if you can, or try using the <a href="/teams">List of stoolball teams</a>.</small></p></div>
       <?php 
       $this->ShowSocialAccounts();
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>