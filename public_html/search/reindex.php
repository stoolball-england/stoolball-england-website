<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');
set_time_limit(0);

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	private $process;

	public function OnPostback()
	{
		if (isset($_POST["teams"])) $this->IndexTeams();
		if (isset($_POST["grounds"])) $this->IndexGrounds();
		if (isset($_POST["players"])) $this->IndexPlayers();
		if (isset($_POST["competitions"])) $this->IndexCompetitions();
		if (isset($_POST["matches"])) $this->IndexMatches();
		if (isset($_POST["pages"])) $this->IndexPages();
		if (isset($_POST["posts"])) $this->IndexPosts();
		if (isset($_POST["other"])) $this->IndexOtherPages();
	}


	private function IndexTeams()
	{
		$this->SearchIndexer()->DeleteFromIndexByType("team");

		require_once('stoolball/team-manager.class.php');
        require_once('search/team-search-adapter.class.php');
		$manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
		$manager->FilterByActive(true);
		$manager->ReadAll();
		$teams = $manager->GetItems();
		unset($manager);

		foreach ($teams as $team)
		{
		    $adapter = new TeamSearchAdapter($team);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
		}
		$this->SearchIndexer()->CommitChanges();
	}

	private function IndexGrounds()
	{
		require_once('stoolball/ground-manager.class.php');
        require_once ("stoolball/team-manager.class.php");
        require_once 'search/ground-search-adapter.class.php';

        $this->SearchIndexer()->DeleteFromIndexByType("ground");

		$manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$manager->FilterByActive(true);
		$manager->ReadAll();
		$grounds = $manager->GetItems();
		unset($manager);

        $team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());

		foreach ($grounds as $ground)
		{
		    /* @var $ground Ground */
            
            # Get teams based at the ground
            $team_manager->FilterByGround(array($ground->GetId()));
            $team_manager->FilterByActive(true);
            $team_manager->ReadTeamSummaries();
            $teams = $team_manager->GetItems();
            $ground->Teams()->SetItems($teams);

            $adapter = new GroundSearchAdapter($ground);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
		}
		$this->SearchIndexer()->CommitChanges();
		unset ($team_manager);
	}

	private function IndexPlayers()
	{
        require_once("data/process-manager.class.php");
        $this->process = new ProcessManager("players", 500);

        if ($this->process->ReadyToDeleteAll())
        {
            $this->SearchIndexer()->DeleteFromIndexByType("player");
        }

        # Get all players, but exclude unused extras
        $player = $this->GetSettings()->GetTable('Player');
        $player_batch = $this->GetDataConnection()->query("SELECT player_id FROM $player WHERE total_matches >= 1 ORDER BY player_id" . $this->process->GetQueryLimit());
        $player_ids = array();
        while ($row = $player_batch->fetch())
        {
            $player_ids[] = $row->player_id;
        }
        if (count($player_ids))
        {
            require_once('stoolball/player-manager.class.php');
            require_once('search/player-search-adapter.class.php');
            $manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
            foreach ($player_ids as $player_id) 
            {
                $manager->ReadPlayerById($player_id);
                $players = $manager->GetItems();
        
                foreach ($players as $player)
                {
                    $adapter = new PlayerSearchAdapter($player);
                    $this->SearchIndexer()->Index($adapter->GetSearchableItem());
                    $this->process->OneMoreDone();
                }
            }
            $this->SearchIndexer()->CommitChanges();
            unset($manager);
        }
	}

	private function IndexCompetitions()
	{
		require_once('stoolball/competition-manager.class.php');
        require_once('search/competition-search-adapter.class.php');

        $this->SearchIndexer()->DeleteFromIndexByType("competition");

		$manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
		$manager->ReadAll();
		$results = $manager->GetItems();
		unset($manager);

		foreach ($results as $result)
		{
		    $adapter = new CompetitionSearchAdapter($result);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
		}
		$this->SearchIndexer()->CommitChanges();
	}


	private function IndexMatches()
	{
		require_once("data/process-manager.class.php");
		$this->process = new ProcessManager("matches", 200);

		if ($this->process->ReadyToDeleteAll())
		{
			$this->SearchIndexer()->DeleteFromIndexByType("match");
		}

		$match = $this->GetSettings()->GetTable('Match');
		$match_batch = $this->GetDataConnection()->query("SELECT match_id FROM $match ORDER BY start_time, match_id" . $this->process->GetQueryLimit());
		$match_ids = array();
		while ($row = $match_batch->fetch())
		{
			$match_ids[] = $row->match_id;
		}
		if (count($match_ids))
		{
			require_once('stoolball/match-manager.class.php');
            require_once 'search/match-search-adapter.class.php';
			$manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
			$manager->ReadByMatchId($match_ids);
			$results = $manager->GetItems();
			unset($manager);

			foreach ($results as $match)
			{
			    $adapter = new MatchSearchAdapter($match);
                $this->SearchIndexer()->Index($adapter->GetSearchableItem());
				$this->process->OneMoreDone();
			}
			$this->SearchIndexer()->CommitChanges();
		}
	}

	private function IndexPosts()
	{
        require_once("search/blog-post-search-adapter.class.php");
        
		$this->SearchIndexer()->DeleteFromIndexByType("post");

		$results = $this->GetDataConnection()->query("SELECT id, post_date, CONCAT(DATE_FORMAT(post_date, '/%Y/%m/'), post_name, '/') AS url, post_title, post_content FROM nsa_wp_posts WHERE post_type = 'post' AND post_status = 'publish'");

		while($row = $results->fetch())
		{
		    $item = new SearchItem("post", "post" . $row->id, $row->url, $row->post_title);
            $item->FullText($row->post_content);
            $item->ContentDate(new DateTime($row->post_date));
            $adapter = new BlogPostSearchAdapter($item);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
		}
		$this->SearchIndexer()->CommitChanges();
	}

	private function IndexPages()
	{
        require_once("search/search-item.class.php");

		$this->SearchIndexer()->DeleteFromIndexByType("page");

		# Use INNER JOIN to enable pages to be hidden from search by not having a description, eg Play! which is not really a WordPress page
		$results = $this->GetDataConnection()->query("
		SELECT id, post_name, post_parent, post_title, meta_value AS description, post_content
		FROM nsa_wp_posts p INNER JOIN nsa_wp_postmeta pm ON (p.id = pm.post_id AND meta_key = 'Description')
		WHERE post_type = 'page' AND post_status = 'publish'");

		while($row = $results->fetch())
		{
			$url = "/" . $row->post_name . "/";
			$parent = $row->post_parent;

			while ($parent != "0")
			{
				$url_result = $this->GetDataConnection()->query("SELECT post_name, post_parent FROM nsa_wp_posts WHERE id = " . $parent);
				$url_row = $url_result->fetch();
				$url = "/" . $url_row->post_name . $url;
				$parent = $url_row->post_parent;
			}

            $item = new SearchItem("page", $row->id, $url, $row->post_title, $row->description);
            $item->FullText($row->post_content);
            $this->SearchIndexer()->Index($item);
		}
		$this->SearchIndexer()->CommitChanges();
	}

	private function IndexOtherPages()
	{
	    require_once("search/search-item.class.php");
        
		$this->SearchIndexer()->DeleteFromIndexByType("other");

		$docs = array();
		$contact = new SearchItem("other", "/contact/", "/contact/", "Contact us", "Phone or email us about anything to do with stoolball, or contact us on Facebook or Twitter.", "phone email twitter facebook contact address");
        $contact->RelatedLinksHtml('<ul><li><a href="https://facebook.com/stoolball">Stoolball England on Facebook</a></li><li><a href="https://twitter.com/stoolball">Stoolball England on Twitter</a></li></ul>');
        $docs[] = $contact;

		$docs[] = new SearchItem("other", "/teams/map", "/teams/map", "Map of stoolball teams", "See a map of all the stoolball teams currently playing.", "where clubs teams map");

		$docs[] = new SearchItem("other", "/tournaments", "/tournaments/all", "Go to a tournament", "See all the stoolball tournaments taking place in the next year.", "where map events");

		$docs[] = new SearchItem("other", "/play/yourteam.php", "/play/yourteam.php", "Tell us about your team", "Ask us to add your stoolball team if it's not listed, or update your page if it is.", "club listing list");

		$docs[] = new SearchItem("other", "/you/emails.php", "/you/emails.php", "Email alerts", "Change the email alerts you get when someone adds a comment.", "spam unsubscribe");

		$docs[] = new SearchItem("other", "/you/essential.php", "/you/essential.php", "Essential information", "Change your name, email address or password.");

		$docs[] = new SearchItem("other", "/you/personal.php", "/you/personal.php", "More about you", "Tell others where you're from, who you are and what you like.");

		$docs[] = new SearchItem("other", "play/statistics/individual-scores", "/play/statistics/individual-scores", "All individual scores",
		"See the highest scores by individuals in a single stoolball innings. Filter by team, ground, date and more.", "hundreds centuries batting batsmen batters statistics");

		$docs[] = new SearchItem("other", "/play/statistics/most-runs", "/play/statistics/most-runs", "Most runs",
		"Find out who has scored the most runs overall in all stoolball matches. Filter by team, ground, date and more.", "hundreds centuries batting batsmen batters statistics");

        $docs[] = new SearchItem("other", "/play/statistics/most-scores-of-100", "/play/statistics/most-scores-of-100", "Most scores of 100 or more",
        "Find out who has scored the most hundreds in all stoolball matches. Filter by team, ground, date and more.", "fifties hundreds centuries batting batsmen batters statistics");

        $docs[] = new SearchItem("other", "/play/statistics/most-scores-of-50", "/play/statistics/most-scores-of-50", "Most scores of 50 or more",
        "Find out who has scored the most fifties in all stoolball matches. Filter by team, ground, date and more.", "fifties hundreds centuries batting batsmen batters statistics");
        
		$docs[] = new SearchItem("other", "/play/statistics/batting-average", "/play/statistics/batting-average", "Batting averages statistics",
		"A batsman's average measures how many runs he or she typically scores before getting out. Filter by team, ground, date and more.", "average batting batsmen batters statistics");

        $docs[] = new SearchItem("other", "/play/statistics/batting-strike-rate", "/play/statistics/batting-strike-rate", "Batting strike rates",
        "A batsman's strike rate measures how many runs he or she typically scores per 100 balls faced. Filter by team, ground, date and more.", 
        "strike rate batting batsmen batters statistics");

		$docs[] = new SearchItem("other", "/play/statistics/bowling-performances", "/play/statistics/bowling-performances", "All bowling performances",
		"See the best wicket-taking performances in all stoolball matches. Filter by team, ground, date and more.", "bowling bowler figures statistics");

		$docs[] = new SearchItem("other", "/play/statistics/most-wickets", "/play/statistics/most-wickets", "Most wickets",
		"If a player is out caught, caught and bowled, bowled, body before wicket or for hitting the ball twice the wicket is credited to the bowler. Filter by team, ground, date and more."
		, "wickets bowling figures bowler statistics");

        $docs[] = new SearchItem("other", "/play/statistics/most-5-wickets", "/play/statistics/most-5-wickets", "Most times taking 5 wickets in an innings",
        "If a player is out caught, caught and bowled, bowled, body before wicket or for hitting the ball twice the wicket is credited to the bowler. Filter by team, ground, date and more."
        , "five wicket wickets haul bowling figures bowler statistics");

        $docs[] = new SearchItem("other", "/play/statistics/most-wickets-by-bowler-and-catcher", "/play/statistics/most-wickets-by-bowler-and-catcher", "Most wickets by a bowling and catching combination",
        "This measures which combination of bowler and catcher has taken the most wickets. Catches taken by a bowler off their own bowling are not counted. Filter by team, ground, date and more."
        , "wickets bowling figures bowler statistics catching catcher");

		$docs[] = new SearchItem("other", "/play/statistics/bowling-average", "/play/statistics/bowling-average", "Bowling averages",
		"A bowler's average measures how many runs he or she typically concedes before taking a wicket. Filter by team, ground, date and more.", 
		"average bowling figures bowler statistics");

		$docs[] = new SearchItem("other", "/play/statistics/economy-rate", "/play/statistics/economy-rate", "Economy rates",
		"A bowler's economy rate measures how many runs he or she typically concedes in each over. Filter by team, ground, date and more.", 
		"economy bowling figures bowler statistics");

		$docs[] = new SearchItem("other", "/play/statistics/bowling-strike-rate", "/play/statistics/bowling-strike-rate", "Bowling strike rates",
		"A bowler's strike rate measures how many deliveries he or she typically bowls before taking a wicket. Filter by team, ground, date and more.", 
		"economy wickets bowling figures bowler statistics");

		$docs[] = new SearchItem("other", "/play/statistics/most-catches", "/play/statistics/most-catches", "Most catches",
		"This measures the number of catches taken by a fielder, not how often a batsman has been caught out. Filter by team, ground, date and more.", 
		"fielding catching catches statistics");

        $docs[] = new SearchItem("other", "/play/statistics/most-catches-in-innings", "/play/statistics/most-catches-in-innings", "Most catches in an innings",
        "This measures the number of catches taken by a fielder, not how often a batsman has been caught out. Filter by team, ground, date and more.", 
        "fielding catching catches statistics");

		$docs[] = new SearchItem("other", "/play/statistics/most-run-outs", "/play/statistics/most-run-outs", "Most run-outs",
		"This measures the number of run-outs completed by a fielder, not how often a batsman has been run-out. Filter by team, ground, date and more.", 
		"run outs runouts run-outs fielding statistics");

        $docs[] = new SearchItem("other", "/play/statistics/most-run-outs-in-innings", "/play/statistics/most-run-outs-in-innings", "Most run-outs in an innings",
        "This measures the number of run-outs completed by a fielder, not how often a batsman has been run-out. Filter by team, ground, date and more.", 
        "run outs runouts run-outs fielding statistics");

        		$docs[] = new SearchItem("other", "/play/statistics/most-player-of-match", "/play/statistics/most-player-of-match", "Most player of the match nominations",
		"Find out who has won the most player of the match awards for their outstanding performances on the pitch. Filter by team, ground, date and more.", 
		"man of the match mom awards batting bowling fielding catches statistics tables");

        $docs[] = new SearchItem("other", "/play/statistics/player-of-match", "/play/statistics/player-of-match", "Player of the match nominations",
        "All of the matches where players were awarded player of the match for their outstanding performances on the pitch. Filter by team, ground, date and more.", 
        "man of the match mom awards batting bowling fielding catches statistics tables");

        $docs[] = new SearchItem("other", "/play/statistics/player-performances", "/play/statistics/player-performances", "Player performances",
        "All of the match performances by a stoolball player, summarising their batting, bowling and fielding in the match. Filter by team, ground, date and more.", 
        "batting bowling fielding catches statistics tables");

   		$docs[] = new SearchItem("other", "spreadshirt", "https://shop.spreadshirt.co.uk/stoolball", "Gift shop - hoodies, t-shirts, hats, bags and more",
		"Buy hoodies, t-shirts, hats, bags, umbrellas, teddy bears and a lot more in our stoolball gift shop. Like us on Facebook or follow us on Twitter to find out about special offers.", 
        "gifts presents clothing buy bags t-shirts polo hats merchandise wear clothes shopping shop");

		$facebook = new SearchItem("other", "facebook", "https://facebook.com/stoolball", "Stoolball England on Facebook",
		"Find us on Facebook to keep up with stoolball news, and get extra updates and special offers from our gift shop.", 
        "twitter news like photo picture contact");
        $facebook->RelatedLinksHtml('<ul><li><a href="https://twitter.com/stoolball">Stoolball England on Twitter</a></li><li><a href="https://youtube.com/stoolballengland">Stoolball England on YouTube</a></li></ul>');
        $docs[] = $facebook;

		$twitter = new SearchItem("other", "twitter", "https://twitter.com/stoolball", "Stoolball England on Twitter",
		"Follow us on Twitter to keep up with stoolball news, and get extra updates and special offers from our gift shop.", 
        "facebook follow tweet contact");
        $twitter->RelatedLinksHtml('<ul><li><a href="https://facebook.com/stoolball">Stoolball England on Facebook</a></li><li><a href="https://youtube.com/stoolballengland">Stoolball England on YouTube</a></li></ul>');
        $docs[] = $twitter;

		$youtube = new SearchItem("other", "youtube", "https://youtube.com/stoolballengland", "Stoolball England on YouTube",
		"Subscribe to our YouTube channel to see the best stoolball videos.", "video youtube");
        $youtube->RelatedLinksHtml('<ul><li><a href="https://facebook.com/stoolball">Stoolball England on Facebook</a></li><li><a href="https://twitter.com/stoolball">Stoolball England on Twitter</a></li></ul>');
        $docs[] = $youtube;

		foreach ($docs as $doc) {
		    /* @var $doc SearchItem */
		    $doc->WeightOfType(1200);
		    $this->SearchIndexer()->Index($doc);
        }
		$this->SearchIndexer()->CommitChanges();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle("Regenerate search index");
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', $this->GetPageTitle());

		if (isset($this->process))
		{
			$this->process->ShowProgress();
		}
        else if ($this->IsPostback()) {
            echo "<p>Done.</p>";
        }


?>
<form action="reindex.php" method="POST">
	<div>
		<input type="submit" name="teams" value="Index teams" />
		<br />
		<input type="submit" name="grounds" value="Index grounds" />
		<br />
		<input type="submit" name="players" value="Index players" />
		<br />
		<input
		type="submit" name="competitions" value="Index competitions" />
		<br />
		<input type="submit" name="matches" value="Index matches" />
		<br />
		<input
		type="submit" name="posts" value="Index WordPress posts" />
		<br />
		<input type="submit" name="pages" value="Index WordPress pages" />
		<br />
		<input
		type="submit" name="other" value="Index other pages" />
	</div>
</form>
<?php
}
}

new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_SEARCH, false);
?>