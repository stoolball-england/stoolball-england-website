<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/team-manager.class.php');
require_once('stoolball/team-list-control.class.php');

class CurrentPage extends StoolballPage
{
    private $areas;
    private $teams;
	private $player_type;
    private $administrative_area;
    
	function OnLoadPageData()
	{
		# new data manager
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $team_manager->FilterByActive(true);
        
        # Check for player type
        $this->player_type = null;
        if (isset($_GET['player']))
        {
            if ($_GET['player'] == "past")
            {
                $this->player_type = 0;
                $team_manager->FilterByActive(false);
            } 
            else 
            {
                $this->player_type = PlayerType::Parse($_GET['player']);        
                $a_player_types = is_null($this->player_type) ? null : array($this->player_type);
                if ($this->player_type == PlayerType::JUNIOR_MIXED) 
                {
                    $a_player_types[] = PlayerType::GIRLS;
                    $a_player_types[] = PlayerType::BOYS;
                }
                $team_manager->FilterByPlayerType($a_player_types);
           }
        }
		
        if (isset($_GET['area']) and $_GET["area"])
        {
            $this->administrative_area = preg_replace('/[^a-z]/',"",strtolower($_GET['area']));
            if ($this->administrative_area)
            {
                $team_manager->FilterByAdministrativeArea(array($this->administrative_area));      
            } 
        } 
        
        # Get the counties for the filter
        $this->areas = $team_manager->ReadAdministrativeAreas();

        if ($this->administrative_area)
        {
            # read all teams matching the applied filter
            $team_manager->ReadTeamSummaries();
            $this->teams = $team_manager->GetItems();            
        }
		unset($team_manager);
	}

	function OnPrePageLoad()
	{
        if ($this->player_type === null) $title = 'Stoolball teams';
        if ($this->player_type === PlayerType::LADIES) $title = "Ladies' stoolball teams";
        if ($this->player_type === PlayerType::MIXED) $title = 'Mixed stoolball teams';
        if ($this->player_type === PlayerType::JUNIOR_MIXED) $title = "Junior stoolball teams"; 
        if ($this->player_type === 0) $title = 'Past stoolball teams';

        if (is_array($this->teams) and count($this->teams))
        {
            $title .= " in " . $this->teams[0]->GetGround()->GetAddress()->GetAdministrativeArea();
        } 

        $this->SetPageTitle($title);

		$this->SetContentConstraint(StoolballPage::ConstrainBox());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false));
		
        ?>
        <div class="small">
        <?php

		if (is_null($this->player_type) and !$this->administrative_area)
		{
		?>
            <nav>
            <ul class="nav">
            	<li><a href="/teams/map">Map of stoolball teams</a></li>
            	<li><a href="/teams/mixed">Mixed teams</a></li>
            	<li><a href="/teams/ladies">Ladies' teams</a></li>
            	<li><a href="/teams/junior">Junior teams</a></li>
            	<li><a href="/teams/past">Past teams</a></li>
            	<li><a href="/play/manage/start-a-new-stoolball-team/">Start a team</a></li>
            	<li><a href="<?php  echo $this->GetSettings()->GetUrl('TeamAdd'); ?>">Tell us about your team</a></li>
            </ul>
            </nav>

    		 <?php
 		}
    
        $this->DisplayTeams();
        ?>
        </div>
        <?php		
                
        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('List of teams' => '', 'Map of teams' => '/teams/map');
        echo new Tabs($tabs, 'large');
        ?>
        <div class="box tab-box large">
        <div class="dataFilter"><nav>
                        <?php 
            if ($this->administrative_area)
            { 
                $total_teams = count($this->teams);
                $teams_text = ($total_teams == 1) ? "team" : "teams";
                echo "<p class=\"total\">$total_teams $teams_text</p>";
            }

            ?>
            <p class="follow-on large">Show me:</p>
            <div class="multiple-filters follow-on">
                <ul class="first-filter">
                    <?php
                    $area  = ($this->administrative_area) ? "/" . $this->administrative_area : "";
                    echo ($this->player_type === null) ? '<li><em>Current teams</em></li>' : '<li><a href="/teams/all' . $area . '">Current teams</a></li>';
                    echo ($this->player_type === PlayerType::LADIES) ? '<li><em>Ladies</em></li>' : '<li><a href="/teams/ladies' . $area . '">Ladies</a></li>';
                    echo ($this->player_type === PlayerType::MIXED) ? '<li><em>Mixed</em></li>' : '<li><a href="/teams/mixed' . $area . '">Mixed</a></li>';
                    echo ($this->player_type === PlayerType::JUNIOR_MIXED) ? '<li><em>Junior</em></li>' : '<li><a href="/teams/junior' . $area . '">Junior</a></li>';
                    echo ($this->player_type === 0) ? '<li><em>Past teams</em></li>' : '<li><a href="/teams/past' . $area . '">Past teams</a></li>';
                    ?>
                </ul>
            <?php 
            $html = '<ul class="following-filter">';
            foreach ($this->areas as $area) 
            {
                $area_url = preg_replace('/[^a-z]/',"",strtolower($area));
                if ($area_url == $this->administrative_area)
                {
                    $html .= '<li><em>' . htmlentities($area, ENT_QUOTES, "UTF-8", false) . '</em></li>';
                } 
                else
                {                    
                    $html .= '<li><a href="/teams/' . (isset($_GET['player']) ? HTML::Encode(preg_replace('/[^a-z]/',"",$_GET['player'])) : "all") . '/' . $area_url . '">' . 
                                htmlentities($area, ENT_QUOTES, "UTF-8", false) . '</a></li>';
                }
            }
            $html .= "</ul>";
            echo $html;
            ?>
            </div>
            <p>Can't find it? <a href="<?php echo $this->GetSettings()->GetUrl('TeamAdd');?>">Tell us about your team</a> 
                <span class="or">or</span> <a href="/play/manage/start-a-new-stoolball-team/">start a new team</a></p>
        </nav></div>

        <div class="box-content play">
        <?php 		
        $this->DisplayTeams();     
        ?>
        </div>
        </div>
        <?php 

		$add_teams =  (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS));
		$add_grounds = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_GROUNDS));
		
		if ($add_teams or $add_grounds)  
		{
		      $this->AddSeparator();

        	require_once('stoolball/user-edit-panel.class.php');
			$panel = new UserEditPanel($this->GetSettings(), '');	
			if ($add_teams) $panel->AddLink('add a team', '/teams/add');
			if ($add_grounds) $panel->AddLink('add a ground', '/play/grounds/groundedit.php');
			echo $panel;
		}
        else 
        {
            $this->ShowSocialAccounts();
        }
	}

    private function DisplayTeams()
    {
        if ($this->administrative_area)
        { 
            $team_list = new TeamListControl($this->teams);
            $team_list->SetShowPlayerType(true);
            $team_list->SetIsNavigationList(true);
            $team_list->SetNoTeamsMessage('There are no teams which match your selection.');
            echo $team_list;
        }
        else if (is_array($this->areas) and count($this->areas))  
        {
            $html = '<ul class="nav';
            if (is_null($this->player_type))
            {
                $html .= " large";
            } 
            $html .= '">';
            foreach ($this->areas as $area) 
            {
                $html .= '<li><a href="/teams/' . (isset($_GET['player']) ? html::Encode(preg_replace('/[^a-z]/',"",$_GET['player'])) : "all") . '/' . HTML::Encode(preg_replace('/[^a-z]/',"",strtolower($area))) . '">' . 
                            htmlentities($area, ENT_QUOTES, "UTF-8", false) . '</a></li>';
            }
            $html .= "</ul>";
            echo $html;
        } 
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>