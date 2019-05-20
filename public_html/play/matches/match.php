<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/user-edit-panel.class.php');
require_once('forums/review-item.class.php');
require_once('forums/topic-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The match to display
	 *
	 * @var Match
	 */
	private $match;

	/**
	 * The comments topic for the match
	 *
	 * @var ForumTopic
	 */
	private $topic;
	private $review_item;
    private $best_batting;
    private $best_bowling;
    private $most_runs;
    private $most_wickets;
    private $most_catches;
    private $has_player_stats;

    public function OnPageInit() {
        
        # check parameter
        if (!isset($_GET['item']) or !is_numeric($_GET['item']))
        {
            header('Location: /matches/');
            exit();
        }       
    }

	public function OnLoadPageData()
	{
		/* @var $topic ForumTopic */
		/* @var $match Match */

		# new data manager
		$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# get match
		$match_manager->ReadByMatchId(array($_GET['item']));
        $match_manager->ExpandMatchScorecards();
		$this->match = $match_manager->GetFirst();

		# must have found a match
		if (!$this->match instanceof Match)
		{
			header('Location: /matches/');
			exit();
		}

        # Update search engine
        if ($this->match->GetSearchUpdateRequired())
        { 
            require_once ("search/match-search-adapter.class.php");
            $this->SearchIndexer()->DeleteFromIndexById("match" . $this->match->GetId());
            $adapter = new MatchSearchAdapter($this->match);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
            $this->SearchIndexer()->CommitChanges();
    
            $match_manager->SearchUpdated($this->match->GetId());
        }

		# tidy up
		unset($match_manager);

		# Get comments
		$this->review_item = new ReviewItem($this->GetSettings());
		$this->review_item->SetId($this->match->GetId());
		$this->review_item->SetType(ContentType::STOOLBALL_MATCH);
		$this->review_item->SetTitle($this->match->GetTitle());
        $this->review_item->SetNavigateUrl("https://" . $this->GetSettings()->GetDomain() . $this->review_item->GetNavigateUrl());
        $this->review_item->SetLinkedDataUri($this->match->GetLinkedDataUri());

		$topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
        
        if ($this->IsPostback()) {
            $this->SavePostedComments($topic_manager);
        }
        
		$topic_manager->ReadCommentsForReviewItem($this->review_item);
		$this->topic = $topic_manager->GetFirst();
		unset($topic_manager);
		
        if ($this->match->GetMatchType() == MatchType::TOURNAMENT or $this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH) 
        { 
            # Get stats highlights
            require_once('stoolball/statistics/statistics-manager.class.php');
            $statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
         
            if ($this->match->GetMatchType() == MatchType::TOURNAMENT)
            {
                $statistics_manager->FilterByTournament(array($this->match->GetId()));
            }
            else 
            {
                $statistics_manager->FilterByTournament(array($this->match->GetTournament()->GetId()));
            }
            
            $statistics_manager->FilterMaxResults(1);
            $this->best_batting = $statistics_manager->ReadBestBattingPerformance();
            $this->best_bowling = $statistics_manager->ReadBestBowlingPerformance();
            $this->most_runs = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
            $this->most_wickets = $statistics_manager->ReadBestPlayerAggregate("wickets", true);
            $this->most_catches = $statistics_manager->ReadBestPlayerAggregate("catches", true);

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
        }

	}


    function SavePostedComments(TopicManager $topic_manager)
    {
        /* @var $message ForumMessage */

        if (isset($_POST['message']) and trim($_POST['message']) and !$this->IsRefresh()) {
            $message = $topic_manager->SaveComment($this->review_item, $_POST['message']);

            # send subscription emails
            require_once ('forums/subscription-manager.class.php');
            $o_subs = new SubscriptionManager($this->GetSettings(), $this->GetDataConnection());
            $o_subs->SendCommentsSubscriptions($this->review_item, $message);
            
            # add subscription if appropriate
            if (isset($_POST['subscribe'])) {
                $o_subs->SaveSubscription($this->review_item->GetId(), $this->review_item->GetType(), AuthenticationManager::GetUser()->GetId());
            }
        }
    }

	function OnPrePageLoad()
	{
		$this->SetOpenGraphType("sport");
		$match_or_tournament = ($this->match->GetMatchType() == MatchType::TOURNAMENT) ? 'tournament' : 'match';
        $match_title = $this->match->GetTitle();
        if ($this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH) {
            $match_title .= " in the " . $this->match->GetTournament()->GetTitle();
        } 
		$this->SetPageTitle($match_title . ' - ' . $this->match->GetStartTimeFormatted() . ' - stoolball ' . $match_or_tournament);
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());

        require_once("search/match-search-adapter.class.php");
        $adapter = new MatchSearchAdapter($this->match);    
		$this->SetPageDescription($adapter->GetSearchDescription());
        
        $this->LoadClientScript("match.js", true);
	}

	function OnPageLoad()
	{
        $is_tournament = ($this->match->GetMatchType() == MatchType::TOURNAMENT);

        $class = $is_tournament ? "match" : "match vevent";
        ?>
        <div class="$class" typeof="schema:SportsEvent" about="<?php echo Html::Encode($this->match->GetLinkedDataUri()) ?>">
        <?php                                

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => '');       

        if ($is_tournament)
        {
            $tabs['Tournament statistics'] = $this->match->GetNavigateUrl() . '/statistics';
            
            # Make sure the reader knows this is a tournament, and the player type
            $says_tournament = (strpos(strtolower($this->match->GetTitle()), 'tournament') !== false);
            $player_type = PlayerType::Text($this->match->GetPlayerType());
            $says_player_type = (strpos(strtolower($this->match->GetTitle()), strtolower(rtrim($player_type, '\''))) !== false);
    
            $page_title = $this->match->GetTitle() . ", " . Date::BritishDate($this->match->GetStartTime(), false);
            if (!$says_tournament and !$says_player_type)
            {
                $page_title .= ' (' . $player_type . ' stoolball tournament)';
            }
            else if (!$says_tournament)
            {
                $page_title .= ' stoolball tournament';
            }
            else if (!$says_player_type)
            {
                $page_title .= ' (' . $player_type . ')';
            }
    
            $heading = new XhtmlElement('h1', $page_title);
            $heading->AddAttribute("property", "schema:name");
            echo $heading;
		}
		else
		{
		    if ($this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH) {
                $tabs['Match statistics'] = $this->match->GetNavigateUrl() . '/statistics';
                $tabs['Tournament statistics'] = $this->match->GetTournament()->GetNavigateUrl() . '/statistics';
                
                $page_title = $this->match->GetTitle() . " in the " . $this->match->GetTournament()->GetTitle();
                
            } else {
                $tabs['Statistics'] = $this->match->GetNavigateUrl() . '/statistics';
                
                $page_title = $this->match->GetTitle();
            }

            $o_title = new XhtmlElement('h1', Html::Encode($page_title));
            $o_title->AddAttribute("property", "schema:name");
    
            # hCalendar
            $o_title->SetCssClass('summary');
            $o_title_meta = new XhtmlElement('span', ' (stoolball)');
            $o_title_meta->SetCssClass('metadata');
            $o_title->AddControl($o_title_meta);

            if ($this->match->GetMatchType() !== MatchType::TOURNAMENT_MATCH) {
                $o_title->AddControl(", " . Date::BritishDate($this->match->GetStartTime(), false));
            }
            
            echo $o_title;
		}
        
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php

        if ($is_tournament)
        {
            require_once('stoolball/tournaments/tournament-control.class.php');
            echo new TournamentControl($this->GetSettings(), $this->match);            
        }
        else {
            require_once('stoolball/matches/match-control.class.php');
            echo new MatchControl($this->GetSettings(), $this->match);            
        }

        $this->DisplayComments();
	    $this->ShowSocial();

            ?>
            </div>
        </div>
        </div>
        <?php
    	$this->AddSeparator();

		# add/edit/delete options
		$user_is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);
		$user_is_owner = ($this->match->GetAddedBy() instanceof User and AuthenticationManager::GetUser()->GetId() == $this->match->GetAddedBy()->GetId());
            
		$panel = new UserEditPanel($this->GetSettings(), 'this match');
        $panel->AddCssClass("with-tabs");
		
		if ($user_is_admin or $user_is_owner)
		{
			$link_text = $is_tournament ? 'tournament' : 'match';
			$panel->AddLink('edit this ' . $link_text, $this->match->GetEditNavigateUrl());
		}
		else if ($this->match->GetMatchType() != MatchType::PRACTICE and !$is_tournament)
		{
			$panel->AddLink('update result', $this->match->GetEditNavigateUrl());
		}

        if ($is_tournament)
        {
            $panel->AddCssClass("with-tabs");
            if ($user_is_admin or $user_is_owner) {
                $panel->AddLink('add or remove teams', $this->match->EditTournamentTeamsUrl());
            }

            $panel->AddLink('add or remove matches', $this->match->GetEditTournamentMatchesUrl());
            if (count($this->match->GetMatchesInTournament())) 
            {
                $panel->AddLink('update results', $this->match->GetNavigateUrl() . "/matches/results");                
            }
        }

        if ($this->match->GetStartTime() < gmdate('U') and AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES)) 
        {
            $panel->AddLink('recalculate statistics', $this->match->GetNavigateUrl() . "/statistics/recalculate"); 
        }

		if ($user_is_admin or $user_is_owner)
		{
            if ($is_tournament) {
                $panel->AddLink('edit where to list this tournament', $this->match->EditTournamentCompetitionsUrl());
            }

			$link_text = $is_tournament ? 'tournament' : 'match';
			$panel->AddLink('delete this ' . $link_text, $this->match->GetDeleteNavigateUrl());
		}
		if ($this->match->GetMatchType() != MatchType::TOURNAMENT_MATCH and $this->match->GetStartTime() > time())
		{
            $link_text = $is_tournament ? 'tournament' : 'match';
			$panel->AddLink("add $link_text to your calendar", $this->match->GetCalendarNavigateUrl());
		}
		echo $panel;


        if ($this->has_player_stats)
        {
            $tournament = $is_tournament ? $this->match : $this->match->GetTournament();
            require_once('stoolball/statistics-highlight-table.class.php');
            echo new StatisticsHighlightTable($this->best_batting, $this->most_runs, $this->best_bowling, $this->most_wickets, $this->most_catches, "tournament");
            echo '<p class="playerSummaryMore"><a href="' . $tournament->GetNavigateUrl() . '/statistics">Tournament statistics</a></p>';
        }
	}

    private function DisplayComments() {
        
        # Display review topic listing
        require_once('forums/forum-topic-listing.class.php');
        require_once('forums/forum-comments-topic-navbar.class.php');
        if (!isset($this->topic)) $this->topic = new ForumTopic($this->GetSettings());
        $this->topic->SetReviewItem($this->review_item);

        $signed_in = AuthenticationManager::GetUser()->IsSignedIn();
        
        echo '<div id="comments-topic"';
        if ($signed_in) {
            echo ' class="signed-in"';
        }
        echo '>';
        if ($this->topic->GetCount() or !$signed_in) {
            echo '<h2>Comments</h2>';
        }
        
        $navbar = new ForumCommentsTopicNavbar($this->topic, $this->GetAuthenticationManager());
        echo $navbar;

        $o_review_topic = new ForumTopicListing($this->GetSettings(), AuthenticationManager::GetUser(), $this->topic);
        echo $o_review_topic;
        
        if ($this->topic->GetCount() and !$signed_in) {
            echo $navbar;
        }
        
        # Add comment
        if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::ForumAddMessage()))
        {
            # create form
            $this->LoadClientScript("/scripts/tiny_mce/jquery.tinymce.js");
            $this->LoadClientScript("/scripts/tinymce.js");
        
            require_once ('forums/forum-message-form.class.php');
            $o_form = new ForumMessageForm($this->GetCsrfToken());
            echo $o_form->GetForm();
            
            echo '<p class="privacy-notice">Please read our <a href="https://www.stoolball.org.uk/about/privacy-notice-match-results-and-comments/">privacy notice about match results and comments</a>.</p>'; 
        }
        
        echo '</div>';
        
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>