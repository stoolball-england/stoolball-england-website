<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/team-manager.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/season-manager.class.php');
require_once('stoolball/team-edit-panel.class.php');
require_once('stoolball/team-name-control.class.php');
require_once('markup/xhtml-markup.class.php');
require_once('stoolball/club.class.php');
require_once('stoolball/season-list-control.class.php');
require_once('stoolball/match-list-control.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The team to display
	 *
	 * @var Team
	 */
	private $team;
	private $a_matches;
	private $best_batting;
	private $best_bowling;
	private $most_runs;
	private $most_wickets;
	private $most_catches;
	private $has_player_stats;
	private $seasons;
	private $season_key;
    private $is_one_time_team;
    
	function OnLoadPageData()
	{
		/* @var Team $team */

		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->Redirect();

		# new data manager
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
		$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# get teams
        $team_manager->FilterByTeamType(array());
		$team_manager->ReadById(array($_GET['item']));
		$this->team = $team_manager->GetFirst();

        # must have found a team
        if (!$this->team instanceof Team)   $this->Redirect('/teams/');

        # Update search engine
        if ($this->team->GetSearchUpdateRequired())
        { 
            require_once("search/team-search-adapter.class.php");
            $this->SearchIndexer()->DeleteFromIndexById("team" . $this->team->GetId());
            $adapter = new TeamSearchAdapter($this->team);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
            $this->SearchIndexer()->CommitChanges();
            
            $team_manager->SearchUpdated($this->team->GetId());
        }
		unset($team_manager);

        $this->is_one_time_team = ($this->team->GetTeamType() == Team::ONCE);
                
		# get matches and match stats
		if (!$this->is_one_time_team)
        { 
    		$a_season_dates = Season::SeasonDates();
    		$this->season_key = date('Y', $a_season_dates[0]);
    		if ($this->season_key != date('Y', $a_season_dates[1])) $this->season_key .= "/" . date('y', $a_season_dates[1]);
    
            $match_manager->FilterByDateStart($a_season_dates[0]);
        }
        
        $match_manager->FilterByTeam(array($this->team->GetId()));
        $match_manager->ReadMatchSummaries();
		$this->a_matches = $match_manager->GetItems();
		unset($match_manager);

		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByTeam(array($this->team->GetId()));
		$statistics_manager->FilterMaxResults(1);
		$this->best_batting = $statistics_manager->ReadBestBattingPerformance();
		$this->best_bowling = $statistics_manager->ReadBestBowlingPerformance();
		$this->most_runs = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
		$this->most_wickets = $statistics_manager->ReadBestPlayerAggregate("wickets");
		$this->most_catches = $statistics_manager->ReadBestPlayerAggregate("catches");

		# See what stats we've got available
		$best_batting_count = count($this->best_batting);
		$best_bowling_count = count($this->best_bowling);
		$best_batters = count($this->most_runs);
		$best_bowlers = count($this->most_wickets);
		$best_catchers = count($this->most_catches);
		$this->has_player_stats = ($best_batting_count or $best_batters or $best_bowling_count or $best_bowlers or $best_catchers);

		if (!$this->has_player_stats)
		{
			$player_of_match = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
			$this->has_player_stats = (bool)count($player_of_match);
		}
		unset($statistics_manager);

		# Get whether to show add league/cup links
		$season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());
		$season_manager->ReadCurrentSeasonsByTeamId(array($this->team->GetId()), array(MatchType::CUP, MatchType::LEAGUE));
		$this->seasons = $season_manager->GetItems();
		unset($season_manager);
	}

	function OnPrePageLoad()
	{
        $this->SetOpenGraphType("sports_team");
        $this->SetPageTitle($this->team->GetName() . ' stoolball team');

	    require_once("search/team-search-adapter.class.php");
        $adapter = new TeamSearchAdapter($this->team);
		$this->SetPageDescription($adapter->GetSearchDescription());

		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
        if (!$this->is_one_time_team) {
            $this->LoadClientScript("/scripts/lib/chart.min.js");
            $this->LoadClientScript("/scripts/chart.js?v=2");
            $this->LoadClientScript("team.js", true);
            ?><!--[if lte IE 8]><script src="/scripts/lib/excanvas.compiled.js"></script><![endif]--><?php 
        }
 	}

	function OnPageLoad()
	{
        /* @var $team Team */
        $team = $this->team;

		# display the team
		echo '<div class="team" typeof="schema:SportsTeam" about="' . htmlentities($this->team->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '">';

		echo new TeamNameControl($this->team, 'h1');

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => '');       
        if ($this->has_player_stats) {
            $tabs['Players'] = $this->team->GetPlayersNavigateUrl();
        }
        $tabs['Statistics'] = $this->team->GetStatsNavigateUrl();
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php
		
        if (!$this->is_one_time_team)
        { 
    		# add club name
    		if (!is_null($team->GetClub()))
    		{
    			$o_club_para = new XhtmlElement('p');
    			$o_club_para->AddControl('Part of ');
    			$o_club_link = new XhtmlElement('a', htmlentities($team->GetClub()->GetName(), ENT_QUOTES, "UTF-8", false));
    			$o_club_link->AddAttribute('href', $team->GetClub()->GetNavigateUrl());
    			$o_club_para->AddControl($o_club_link);
    			$o_club_para->AddControl('.');
    
    			echo $o_club_para;
    		}
        }
        
		# Add intro
		if ($team->GetIntro())
		{
			$s_intro = htmlentities($team->GetIntro(), ENT_QUOTES, "UTF-8", false);
			$s_intro = XhtmlMarkup::ApplyParagraphs($s_intro);
			$s_intro = XhtmlMarkup::ApplyLists($s_intro);
			$s_intro = XhtmlMarkup::ApplySimpleXhtmlTags($s_intro, false);
			$s_intro = XhtmlMarkup::ApplyLinks($s_intro);
			if (strpos($s_intro, '<p>') > -1)
			{
				echo '<div property="schema:description">' . $s_intro . '</div>';
			}
			else
			{
				echo '<p property="schema:description">' . $s_intro . '</p>';
			}
		}
        
 		######################
		### When and where ###
		######################

    	echo new XhtmlElement('h2', 'When and where');
                
        if (!$this->is_one_time_team)
        {         	
    		# Add not playing, if relevant
    		if (!$team->GetIsActive())
    		{
    			echo new XhtmlElement('p', new XhtmlElement('strong', 'This team doesn\'t play any more.'));
    		}
        }
        
		# add ground
		$ground = $team->GetGround();
		if ($ground->GetId() and $ground->GetName())
		{
			echo '<p rel="schema:location">This team plays at <a typeof="schema:Place" about="' . htmlentities($ground->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . 
			'" property="schema:name" rel="schema:url" href="' . htmlentities($ground->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) . '">' . 
			htmlentities($ground->GetNameAndTown(), ENT_QUOTES, "UTF-8", false) . '</a>.</p>';
		}

        if (!$this->is_one_time_team)
        {           
    		# Add playing times
    		if ($team->GetPlayingTimes())
    		{
    			$s_times = htmlentities($team->GetPlayingTimes(), ENT_QUOTES, "UTF-8", false);
    			$s_times = XhtmlMarkup::ApplyParagraphs($s_times);
                $s_times = XhtmlMarkup::ApplyLists($s_times);
    			$s_times = XhtmlMarkup::ApplySimpleXhtmlTags($s_times, false);
    			$s_times = XhtmlMarkup::ApplyLinks($s_times);
    			echo $s_times;
    		}
        }
        
		# Match list
		if (count($this->a_matches))
		{
			if ($this->is_one_time_team)
			{
			    $came = "came";
                if (count($this->a_matches) == 1 and $this->a_matches[0]->GetStartTime() > gmdate("U"))
                {
                    $came = "will come";
                } 
			    echo "<p>This team $came together once to play in a tournament.</p>";
		    }
            else 
            {
                echo new XhtmlElement('h2', 'Matches this season');
            }

			echo new MatchListControl($this->a_matches);
		}

		if (!$this->is_one_time_team)     
        {
    		#################
    		###  Seasons  ###
    		#################
    
    		$seasons = array();
    		foreach ($team->Seasons() as $team_in_season)
    		{
    			$seasons[] = $team_in_season->GetSeason();
    		}
    
    		if (count($seasons))
    		{
    			$season_list = new SeasonListControl($seasons);
    			$season_list->SetShowCompetition(true);
    			echo $season_list;
    		}
        }

		#################
		###   Cost    ###
		#################

		if ($team->GetCost())
		{
			echo new XhtmlElement('h2', 'How much does it cost?');
			$cost = XhtmlMarkup::ApplyParagraphs(htmlentities($team->GetCost(), ENT_QUOTES, "UTF-8", false));
            $cost = XhtmlMarkup::ApplyLists($cost);
            $cost = XhtmlMarkup::ApplySimpleXhtmlTags($cost, false);
            $cost = XhtmlMarkup::ApplyLinks($cost);
            echo $cost;
		}

		#####################
		### Find out more ###
		#####################

        if ($team->GetContact() or $team->GetWebsiteUrl())
        { 
		    echo new XhtmlElement('h2', 'Contact details');
        }
		
		# Add contact details
		$s_contact = $team->GetContact();
		if ($s_contact)
		{
            # protect emails before escaping HTML, because it's trying to recognise the actual HTML tags
            require_once('email/email-address-protector.class.php');
            $protector = new EmailAddressProtector($this->GetSettings());
            $s_contact = $protector->ApplyEmailProtection($s_contact, AuthenticationManager::GetUser()->IsSignedIn());
            
            $s_contact = htmlentities($s_contact, ENT_QUOTES, "UTF-8", false);
			$s_contact = XhtmlMarkup::ApplyParagraphs($s_contact);
			$s_contact = XhtmlMarkup::ApplyLists($s_contact);
            $s_contact = XhtmlMarkup::ApplySimpleXhtmlTags($s_contact, false);
			$s_contact = XhtmlMarkup::ApplyLinks($s_contact);

			echo $s_contact;
		}


		# Add website
		$s_website = $team->GetWebsiteUrl();
		if ($s_website)
		{
			echo new XhtmlElement('p', new XhtmlAnchor("Visit " . htmlentities($team->GetName(), ENT_QUOTES, "UTF-8", false) . "'s website", $s_website));
		}
        
       if ($team->GetClub() instanceof Club and $team->GetClub()->GetClubmarkAccredited()) {
            ?>
            <p><img src="/images/logos/clubmark.png" alt="Clubmark accredited" width="150" height="29" /></p>
            <p>This is a <a href="http://www.sportenglandclubmatters.com/club-mark/">Clubmark accredited</a> stoolball club.</p>
            <?php
        }
    
        

        if (!$this->is_one_time_team)
        { 
    
    		# Prompt for more contact information, unless it's a repreaentative team when we don't expect it
    		if (!$s_contact and !$s_website and $team->GetTeamType() !== Team::REPRESENTATIVE)
    		{
    			if ($team->GetPrivateContact())
    			{
    				?>
    <p>We may be able to contact this team, but we don't have permission to publish their details. If you'd like to play for them, <a href="/contact">contact us</a> and we'll pass your details on.</p>
    				<?php
    			}
    			else
    			{
    				?>
    <p>This team hasn't given us any contact details. If you'd like to play for them, try contacting their opposition teams or the secretary of their league, and ask them to pass on your details.</p>
    				<?php
    			}
    			?>
    <p>If you play for this team, please <a href="<?php  echo $this->GetSettings()->GetUrl('TeamAdd'); ?>">help us to improve this page</a>.</p>
    			<?php
    		}
		}

        
        if ($this->team->GetClub() and $this->team->GetClub()->GetTwitterAccount()) {
            ?>
            <div class="social screen">
                <a href="https://twitter.com/<?php echo Html::Encode(substr($this->team->GetClub()->GetTwitterAccount(), 1)); ?>" class="twitter-follow-button">Follow <?php echo Html::Encode($this->team->GetClub()->GetTwitterAccount()); ?></a>
                <script src="https://platform.twitter.com/widgets.js"></script>
            </div>
            <?php
        } else {            
            $this->ShowSocial();
        }

        ?>
        </div>
        </div>
        </div>
        <?php 
		$this->AddSeparator();

		$o_panel = new TeamEditPanel($this->GetSettings(), $this->team, $this->seasons, $this->a_matches);
        $o_panel->AddCssClass("with-tabs");
		echo $o_panel;

		$this->Render();

		### Build charts ###

		# Show top players, except for once-only teams which have a very short page for this to sit alongside
        if ($this->has_player_stats and !$this->is_one_time_team)
		{
			require_once('stoolball/statistics-highlight-table.class.php');
			echo new StatisticsHighlightTable($this->best_batting, $this->most_runs, $this->best_bowling, $this->most_wickets, $this->most_catches, "All seasons");
		}

		// Show graphs of results, except for once-only teams which have a very short page for this to sit alongside
		if (!$this->is_one_time_team)
		{         
            ?>
             <span class="chart-js-template supporting-chart" id="all-results-chart"></span>
            <?php
		}
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>