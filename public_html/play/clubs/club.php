<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/clubs/club-manager.class.php');
require_once('stoolball/team-list-control.class.php');
require_once('stoolball/match-manager.class.php');

class CurrentPage extends StoolballPage 
{
	
	/**
	 * The club to view
	 *
	 * @var Club
	 */
	private $club;
    private $matches;
	
	function OnLoadPageData()
	{
		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->Redirect();
		
		# new data manager
		$o_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());

		# get teams
		$o_manager->ReadById(array($_GET['item']));
		$this->club = $o_manager->GetFirst();
        unset($o_manager);
		
		# must have found a team
		if (!$this->club instanceof Club) $this->Redirect();

        # get matches
    	$teams = $this->club->GetItems();
    	if (count($teams) > 0)
    	{
    		$team_ids = array();
    		foreach ($teams as $team)
    		{
    			$team_ids[] = $team->GetId();
    		}
    
    		$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
    		$a_season_dates = Season::SeasonDates();
    		$match_manager->FilterByDateStart($a_season_dates[0]);
    		$match_manager->FilterByTeam($team_ids);
    		$match_manager->ReadMatchSummaries();
    		$this->matches = $match_manager->GetItems();
    		unset($match_manager);
    
    	}

	}

	function OnPrePageLoad()
	{
	$this->SetPageTitle($this->club->GetName());
	$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
	echo new XhtmlElement(
'h1', Html::Encode($this->club->GetName()));
		
		$a_teams = $this->club->GetItems();
		if (count($a_teams) > 0)
		{
			echo new TeamListControl($a_teams);
		}
        
        # Match list
        if (count($this->matches))
        {
            echo new XhtmlElement('h2', 'Matches this season');
            echo new MatchListControl($this->matches);
        }
        ?>
        <p><a href="<?php echo $this->club->GetMatchesRssUrl(); ?>" rel="alternate" type="application/rss+xml" class="rss">Subscribe to new or updated matches</a></p>
        <?php
        
        if ($this->club->GetClubmarkAccredited()) {
            ?>
            <p><img src="/images/logos/clubmark.png" alt="Clubmark accredited" width="150" height="29" /></p>
            <p>This is a <a href="http://www.sportenglandclubmatters.com/club-mark/">Clubmark accredited</a> stoolball club.</p>
            <?php
        }
        
        $has_facebook_group_url = ($this->club->GetFacebookUrl() and strpos($this->club->GetFacebookUrl(), '/groups/') !== false);
        $has_facebook_page_url = ($this->club->GetFacebookUrl() and !$has_facebook_group_url);

        if ($has_facebook_group_url or $this->club->GetTwitterAccount() or $this->club->GetInstagramAccount())
        {
            ?>
            <div class="social screen">
            <?php
            if ($has_facebook_group_url)
            {
            ?>
                <a href="<?php echo Html::Encode($this->club->GetFacebookUrl()); ?>" class="facebook-group"><img src="/images/play/find-us-on-facebook.png" alt="Find us on Facebook" width="137" height="22" /></a>
            <?php
            }
            if ($this->club->GetTwitterAccount())
            {
            ?>
                <a href="https://twitter.com/<?php echo Html::Encode(substr($this->club->GetTwitterAccount(), 1)); ?>" class="twitter-follow-button">Follow <?php echo Html::Encode($this->club->GetTwitterAccount()); ?></a>
                <script src="https://platform.twitter.com/widgets.js"></script>
            <?php
            }
            if ($this->club->GetInstagramAccount())
            {
                ?>
                <a href="https://www.instagram.com/<?php echo Html::Encode(trim($this->club->GetInstagramAccount(),'@')); ?>/?ref=badge" class="instagram"><img src="//badges.instagram.com/static/images/ig-badge-view-24.png" alt="Instagram" /></a>
                <?php
            }
            ?>
            </div>
            <?php
        }
        
        
        $can_edit = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS);
        
		if ($can_edit or $has_facebook_page_url) {
		    $this->AddSeparator();
		}
        
		if ($can_edit) 
		{
		    $club_or_school = $this->club->GetTypeOfClub() == Club::SCHOOL ? "school" : "club";
			require_once('stoolball/user-edit-panel.class.php');
			$panel = new UserEditPanel($this->GetSettings(), "this $club_or_school");
			$panel->AddLink("edit this $club_or_school", $this->club->GetEditClubUrl());
            if ($this->club->GetTypeOfClub() == Club::SCHOOL) {
                $panel->AddLink("edit this school as a club", $this->club->GetEditClubUrl() . "/club");
            }
			$panel->AddLink("delete this $club_or_school", $this->club->GetDeleteClubUrl());
			echo $panel;
		}

		if ($has_facebook_page_url) {
            $this->ShowFacebookPage($this->club->GetFacebookUrl(), $this->club->GetName());
        }
        
		        
		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>