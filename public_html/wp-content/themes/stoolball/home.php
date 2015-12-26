<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');
require_once('media/image-manager.class.php');

class CurrentPage extends StoolballPage
{
	private $best_batting;
	private $best_bowling;
	private $most_runs;
	private $most_wickets;
	private $most_catches;
	private	$best_batting_count;
	private	$best_bowling_count;
	private	$most_runs_count;
	private	$most_wickets_count;
	private	$most_catches_count;
	private $highlight_label;

	function OnLoadPageData()
	{
		# Set up to get stats for current season
		$four_months = 	60 * 60 * 24 * 30 * 4;
		$season_dates = Season::SeasonDates();
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterMaxResults(1);

		do
		{
		# get stats
		$statistics_manager->FilterAfterDate($season_dates[0]);
		$this->best_batting = $statistics_manager->ReadBestBattingPerformance();
		$this->best_bowling = $statistics_manager->ReadBestBowlingPerformance();
		$this->most_runs = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
		$this->most_wickets = $statistics_manager->ReadBestPlayerAggregate("wickets");
		$this->most_catches = $statistics_manager->ReadBestPlayerAggregate("catches");

		# See what stats we've got
		$this->best_batting_count = count($this->best_batting);
		$this->best_bowling_count = count($this->best_bowling);
		$this->most_runs_count = count($this->most_runs);
		$this->most_wickets_count = count($this->most_wickets);
		$this->most_catches_count = count($this->most_catches);
		$has_player_stats = ($this->best_batting_count or $this->best_bowling_count or $this->most_runs_count or $this->most_wickets_count or $this->most_catches_count);

		if ($has_player_stats)
		{
		$start_year = gmdate("Y", $season_dates[0]);
		$end_year = gmdate("Y", $season_dates[1]);
		$this->highlight_label = ($start_year == $end_year ? $start_year : $start_year . "/" . substr($end_year, 2)) . " season";
		}

		# If there aren't any for this season (if it's the start of the season), go back 4 months and try again to get previous season
		$season_dates = Season::SeasonDates(($season_dates[0]-$four_months));
		}
		while (!$has_player_stats);

		unset($statistics_manager);
		
		# fire checks
		$images = new ImageManager($this->GetSettings(), $this->GetDataConnection());
		$images->NotifyUncheckedImages($this->GetSettings()->GetTechnicalContactEmail(), 'https://' . $this->GetSettings()->GetDomain() . '/yesnosorry/gallerycheck.php');
		unset($images);
	}

	function OnPrePageLoad()
	{
		$this->SetOpenGraphType("website");
		$this->SetPageTitle('Stoolball England - play the sport of stoolball');
        $this->SetPageDescription("Stoolball England is the governing body of stoolball. We develop and promote the sport, set the rules, and supply equipment.");
		$this->SetContentConstraint($this->ConstrainColumns());
		$this->SetContentCssClass('home');
	}

	function OnCloseHead()
	{
		?>
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
		<?php
		parent::OnCloseHead();
	}


	function OnPageLoad()
	{
		?>
<div typeof="schema:NGO" about="<?php echo $this->GetSettings()->GetOrganisationLinkedDataUri(); ?>" class="aural-when-large">
	<h1>Welcome to <span property="schema:name">Stoolball England</span></h1>
</div>

<ul class="nav small">
<li><a href="/search">Search</a></li>
<li><a href="/rules/what-is-stoolball">What is stoolball?</a></li>
<li><a href="/rules">Rules of stoolball</a></li>
<li><a href="/teams">Find a team</a></li>
<li><a href="/competitions">Leagues and competitions</a></li>
<li><a href="/tournaments">Tournaments</a></li>
<li><a href="/play/statistics">Statistics</a></li>
<li><a href="/forum">Forum</a></li>
<li><a href="/play/equipment/">Equipment</a></li>
<li><a href="/play/coaching/">Coaching</a></li>
<li><a href="/play/manage">Manage your team</a></li>
<li><a href="/play/schools">Schools</a></li>
<li><a href="/history">History</a></li>
<li><a href="/about">About Stoolball England</a></li>
</ul>
        <?php
		if (have_posts()) :

		?>
<h2 class="aural">Stoolball news</h2>
<div class="articleList large">
	<ol>
	<?php
	$first_done = false;

	while (have_posts()) : the_post();

	if (!$first_done)
	{
		$first_done = true;
		?>
		<li class="articleListItem articleListTopItem post">
			<h2 id="post-<?php the_ID(); ?>">
				<a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?> </a>
			</h2>

			<div class="entry">
			<?php
			if (has_excerpt())
			{
				the_excerpt();
			}
			else
			{
				the_content("Read more<span class=\"aural\"> on " . the_title('', '', false) . '</span>&#8230;');
			}
			?>
			</div>
		</li>

		<?php
	}
	else
	{
		?>
		<li><a href="<?php the_permalink() ?>"><?php the_title(); ?> </a></li>
		<?php
	}
	endwhile;

	?>
	</ol>
	<p class="subscribe">
		<a href="https://twitter.com/Stoolball" class="twitter-follow-button">Follow @Stoolball</a>
		<script src="https://platform.twitter.com/widgets.js" type="text/javascript"></script>

		<span id="fb-root"></span>
		<script src="https://connect.facebook.net/en_GB/all.js#appId=259918514021950&amp;xfbml=1"></script>
		<fb:like href="https://www.facebook.com/stoolball" send="true" layout="button_count" width="150" show_faces="false" font="arial"></fb:like>

		<a href="http://feeds.feedburner.com/stoolballnews" rel="alternate" type="application/rss+xml" class="rss">Subscribe</a>
	</p>
</div>
	<?php
	endif;
	?>

<div class="audienceColumn large">
	<div class="audience box">
		<div class="box-content players">
			<h2>Players &amp; umpires</h2>
			<ul>
				<li><a href="/play/statistics/">Statistics</a></li>
				<li><a href="/scorers">How to score</a></li>
				<li><a href="/play/coaching/coaching-manual-for-stoolball/">Coaching manual</a></li>
				<li><a href="https://stoolball.spreadshirt.co.uk">T-shirts, bags &amp; mugs</a></li>
			</ul>
			<div class="more">
				<a href="/teams">Find your team</a>
			</div>
		</div>
	</div>
	<div class="audience box">
        <div class="box-content schools">
			<h2>Schools &amp; juniors</h2>
			<ul>
				<li><a href="/play/schools/key-stage-3-lesson-plans/">Lesson plans</a></li>
				<li><a href="/play/schools/coaching-in-schools/">Coaching in schools</a></li>
				<li><a href="/play/schools/free-stoolball-equipment/">Free equipment</a></li>
				<li><a href="/teams/junior">Junior teams</a></li>
			</ul>
			<div class="more">
				<a href="/play/schools">More for schools</a>
			</div>
        </div>
	</div>
</div>
<div class="audienceColumn audienceColumn2 large">
	<div class="audience box">
        <div class="box-content manage">
			<h2>Manage your team</h2>
			<ul>
				<li><a href="/play/manage/start-a-new-stoolball-team/">Start a team</a></li>
				<li><a href="/play/manage/promote-stoolball-and-find-new-players/">Find players</a></li>
				<li><a href="/insurance">Insurance</a></li>
				<li><a href="/play/manage/website/">Add your results</a></li>
			</ul>
			<div class="more">
				<a href="/play/manage">Manage your team</a>
			</div>
		</div>
	</div>
	<div class="audience box">
		<div class="box-content history">
			<h2>Stoolball's history</h2>
			<ul>
				<li><a href="/history/story/">Stoolball's story</a></li>
				<li><a href="/history/story/glynde-butterflies/">Book: Glynde 1866-87</a></li>
				<li><a href="/history/baseballs-origins-in-stoolball/">Baseball roots</a></li>
				<li><a href="/history/documents-photos-and-videos/">Old photos</a></li>
			</ul>
			<div class="more">
				<a href="/history">More history</a>
			</div>
		</div>
	</div>
</div>

<div class="box volunteer large">
	<div class="box-content">
		<h2>Volunteers</h2>
		<p>
			Help us promote stoolball &#8211; be a <a href="/about/volunteer-in-stoolball/">volunteer</a>.
		</p>
	</div>
</div>

	<?php $this->AddSeparator();
		/*$this->BuySomething();?>
<h2 style="margin: 0">
	<img src="/images/10-teams.gif" alt="10 new teams" width="185" height="78" />
</h2>
<div class="box display-box">
	<ul class="articleList">
		<li><a href="http://www.stoolball.org.uk/ashurstblackham">Ashurst and Blackham</a></li>
		<li><a href="http://www.stoolball.org.uk/broadbridgeheath">Broadbridge Heath</a></li>
		<li><a href="http://www.stoolball.org.uk/firleblues">Firle Blues</a></li>
		<li><a href="http://www.stoolball.org.uk/glyndebutterflies">Glynde Butterflies</a></li>
		<li><a href="http://www.stoolball.org.uk/lowerbeedinglemurs">Lower Beeding Lemurs</a></li>
		<li><a href="http://www.stoolball.org.uk/selsey">Selsey</a></li>
		<li><a href="http://www.stoolball.org.uk/southstreetbonfiresociety">South Street Bonfire Society</a></li>
		<li><a href="http://www.stoolball.org.uk/southwater">Southwater</a></li>
		<li><a href="http://www.stoolball.org.uk/warnham">Warnham</a></li>
		<li><a href="http://www.stoolball.org.uk/worthing">Worthing</a></li>
	</ul>
	<p>
		&#8230; and <a href="http://www.stoolball.org.uk/amey">Amey</a>'s moved!
	</p>
</div>
	<?php */?>

	<div class="playerStats large">
	<h2 class="aural">Player statistics for this season</h2>
	<?php
	require_once('stoolball/statistics-highlight-table.class.php');
	echo new StatisticsHighlightTable($this->best_batting, $this->most_runs, $this->best_bowling, $this->most_wickets, $this->most_catches, $this->highlight_label);
	?>
	<p class="more">
	See all <a href="/play/statistics/">statistics</a>
	</p>
	<p>
	Done better? Read how to <a href="/play/manage/website/">add your results</a>
	</p>
	</div>

	<?php
	echo '<a class="promo large" href="/shop"><img alt="Bats &pound;36, Balls &pound;7, Wickets &pound;150, Scorebooks &pound;4. Buy yours now." width="185" height="214" src="/images/equipment/bat-ad-' . rand(1,2) . '.jpg" /></a>';
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>