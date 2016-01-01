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

	function OnLoadPageData()
	{
		/* @var $topic ForumTopic */
		/* @var $match Match */

		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item']))
		{
			header('Location: /matches/');
			exit();
		}

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
        $this->review_item->SetLinkedDataUri($this->match->GetLinkedDataUri());

		$topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
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
        }

	}

	function OnPrePageLoad()
	{
		$this->SetOpenGraphType("sport");
		$match_or_tournament = ($this->match->GetMatchType() == MatchType::TOURNAMENT) ? 'tournament' : 'match';
		$this->SetPageTitle($this->match->GetTitle() . ' - ' . $this->match->GetStartTimeFormatted() . ' - stoolball ' . $match_or_tournament);
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());

        require_once("search/match-search-adapter.class.php");
        $adapter = new MatchSearchAdapter($this->match);    
		$this->SetPageDescription($adapter->GetSearchDescription());
	}

	function OnPageLoad()
	{
		/*@var $match Match */
		# display the match
		if ($this->match->GetMatchType() == MatchType::TOURNAMENT)
		{
			require_once('stoolball/tournaments/tournament-control.class.php');
			echo new TournamentControl($this->GetSettings(), $this->match);
		}
		else
		{
			require_once('stoolball/match-control.class.php');
			echo new MatchControl($this->GetSettings(), $this->match);
		}

		# Display review topic listing
        require_once('forums/forum-topic-listing.class.php');
		require_once('forums/forum-comments-topic-navbar.class.php');
		if (!isset($this->topic)) $this->topic = new ForumTopic($this->GetSettings());
		$this->topic->SetReviewItem($this->review_item);
		$o_review_topic = new ForumTopicListing($this->GetSettings(), AuthenticationManager::GetUser(), $this->topic);
        $o_review_topic->SetNavbar(new ForumCommentsTopicNavbar($this->topic, null));
        echo $o_review_topic;

	    $this->ShowSocial();
    	$this->AddSeparator();

		# add/edit/delete options
		$user_is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);
		$user_is_owner = (AuthenticationManager::GetUser()->GetId() == $this->match->GetAddedBy()->GetId());
		$is_tournament = ($this->match->GetMatchType() == MatchType::TOURNAMENT);
        if ($this->match->GetMatchType() == MatchType::TOURNAMENT or $this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH) 
        {
            $tournament_url = ($is_tournament ? $this->match->GetNavigateUrl() : $this->match->GetTournament()->GetNavigateUrl()) . "/statistics";
        }
            
		$panel = new UserEditPanel($this->GetSettings(), 'this match');
        
		
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
            if ($user_is_admin or $user_is_owner) {
                $panel->AddLink('add or remove teams', $this->match->EditTournamentTeamsUrl());
            }

            $panel->AddLink('add or remove matches', $this->match->GetEditTournamentMatchesUrl());
            if (count($this->match->GetMatchesInTournament())) 
            {
                $panel->AddLink('update results', $this->match->GetNavigateUrl() . "/matches/results");                
            }
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
        if ($this->has_player_stats)
        {
            $panel->AddLink("view statistics for this tournament", $tournament_url, "small");
        }
        else if ($this->match->GetMatchType() == MatchType::TOURNAMENT or $this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH) 
        {
            $panel->AddLink("add player statistics now", "/play/manage/website/how-to-add-match-results/", "small");
        }
		echo $panel;


        if ($this->has_player_stats)
        {
            require_once('stoolball/statistics-highlight-table.class.php');
            echo new StatisticsHighlightTable($this->best_batting, $this->most_runs, $this->best_bowling, $this->most_wickets, $this->most_catches, "tournament");
            ?>
<div class="box statsAd large">
    <div>
        <div>
            <div>
                <div>
                    <a href="<?php echo htmlentities($tournament_url, ENT_QUOTES, "UTF-8", false); ?>">View statistics for <span>this tournament</span> </a>
                </div>
            </div>
        </div>
    </div>
</div>
            <?php
        }
        else if ($this->match->GetMatchType() == MatchType::TOURNAMENT or $this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH) 
        {
            ?>
<div class="box statsAd large">
    <div>
        <div>
            <div>
                <div>
                    <a href="/play/manage/website/how-to-add-match-results/">Add player <span>statistics now</span> </a>
                </div>
            </div>
        </div>
    </div>
</div>
            <?php
        }
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>