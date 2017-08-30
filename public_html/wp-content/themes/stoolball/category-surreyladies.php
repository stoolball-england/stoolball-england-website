<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    private $title;
    private $best_batting;
    private $best_bowling;
    private $most_runs;
    private $most_wickets;
    private $most_catches;
    private $has_player_stats;
    
    function OnLoadPageData()
    {
        require_once('stoolball/competition-manager.class.php');
        $comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
        $comp_manager->ReadCompetitionsInCategories(array(17,33,45,61,62,72,73));
        $competitions = $comp_manager->GetItems();
        unset($comp_manager);
        
        $season_ids = array();
        foreach ($competitions as $competition)
        {
            /*@var $competition Competition */
            $season = $competition->GetLatestSeason();
            $season_ids[] = $season->GetId();
        }
        
        # Fixtures
        require_once('stoolball/match-manager.class.php');
        $match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
        $match_manager->FilterByMaximumResults(5);
        $match_manager->FilterByDateStart(gmdate('U'));
        $match_manager->ReadBySeasonId($season_ids);
        $this->matches = $match_manager->GetItems();
        unset($match_manager);
    
        
        # Get stats highlights
        require_once('stoolball/statistics/statistics-manager.class.php');
        $statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
        $statistics_manager->FilterBySeason($season_ids);
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

        unset($statistics_manager);
    }
    
    function OnPrePageLoad()
    {
        # set page title
        if (have_posts())
        {

            $post = $posts[0]; // Hack. Set $post so that the_date() works.

            /* If this is a category archive */
            if (is_category())
            {
                $this->title = single_cat_title('', false);                
                $this->SetPageTitle($this->title);
            }
            /* If this is a tag archive */
            elseif( is_tag() )
            {
                $this->title = " tagged with '" . single_tag_title('', false) . "'";
                $this->SetPageTitle('Stoolball news ' . $this->title);
            }
            /* If this is a daily archive */
            elseif (is_day())
            {
                $this->title = "on " . apply_filters('the_time', get_the_time('j F Y'), 'j F Y');       
                $this->SetPageTitle('Stoolball news ' . $this->title);
            }
            /* If this is a monthly archive */
            elseif (is_month())
            {
                $this->title = 'in ' . apply_filters('the_time', get_the_time('F Y'), 'F Y');
                $this->SetPageTitle('Stoolball news ' . $this->title);
            }
            /* If this is a yearly archive */
            elseif (is_year())
            {
                $this->title = 'in ' . apply_filters('the_time', get_the_time('Y'), 'Y');
                $this->SetPageTitle('Stoolball news ' . $this->title);
            }
            /* If this is an author archive */
            elseif (is_author())
            {
                $this->title = "by author";
                $this->SetPageTitle('Stoolball news ' . $this->title);
            }
            /* If this is a paged archive */
            elseif (isset($_GET['paged']) && !empty($_GET['paged']))
            {
                $this->SetPageTitle('Stoolball news');
            }
        }

        $this->SetOpenGraphType("blog");
        $this->SetContentConstraint($this->ConstrainColumns());
        $this->SetContentCssClass('hasLargeImage');
    }

    function OnCloseHead()
    {
        ?>
<link rel="alternate" type="application/rss+xml"
    title="<?php bloginfo('name'); ?> RSS feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
        <?php
        parent::OnCloseHead();
    }

    function OnPageLoad()
    {
        ?>
        <h1>Surrey Ladies Stoolball Association</h1>
        
        <ul class="nav surrey">
            <li><a href="/surreya">A League</a></li>
            <li><a href="/surreyb">B League</a></li>
            <li><a href="/surreyknockoutcup">Knockout Cup</a></li>
            <li><a href="/competitions/county">County stoolball</a></li>
            </ul>
                
        <?php
        
        if (have_posts()) :

        echo '<div class="large">';
        include('section-news-prevnext.php');
        echo '</div>';

        echo '<div class="nav" typeof="schema:Blog sioctypes:Weblog" about="http://www.stoolball.org.uk/news#blog">' . 
          '<div rel="schema:about"><h2 typeof="schema:Thing" about="http://dbpedia.org/resource/Stoolball" class="aural">' . Html::Encode($this->title) . ' <span property="schema:name">stoolball</span> news</h2></div>';

            echo '<div rel="schema:blogPosts">';
            while (have_posts()) : the_post(); ?>
<div class="post" typeof="schema:BlogPosting sioctypes:BlogPost" about="<?php echo get_permalink() ?>" rel="sioc:has_container" rev="sioc:container_of" resource="http://www.stoolball.org.uk/news#blog">
    <div about="<?php echo get_permalink() ?>">
    <h2 id="post-<?php the_ID(); ?>">
        <a href="<?php echo get_permalink() ?>" rel="bookmark schema:url" property="schema:headline dcterms:title"><?php the_title(); ?> </a>
    </h2>
    <p class="date large" property="schema:datePublished dcterms:issued" datatype="xsd:date" content="<?php the_time('c'); ?>">
    <?php the_time('l j F Y'); edit_post_link('Edit post', ' <span class="edit">', '</span>'); ?>
    </p>
    <div class="entry large">
    <?php
    if (has_excerpt())
    {
        the_excerpt();
        ?>
        <p class="more-link">
            <a href="<?php the_permalink() ?>">Read more<span class="aural"> on <?php the_title(); ?> </span>&#8230;</a>
        </p>
        <?php
    }
    else
    {
        $content = get_the_content("Read more<span class=\"aural\"> on " . the_title('', '', false) . '</span>&#8230;');
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);
    
        # Allow pictures to resize with screen on the mobile. Not ideal as should use thumbnails, but better than horizontal scrolling.
        $content = preg_replace('/ style="[^"]+"/', "", $content);
        $content = preg_replace('/<img([^>]*) height=["0-9]+/', "<img$1", $content);
        echo $content;
    }
    ?>
    </div>

    <?php include("section-news-tags.php"); ?>
</div>
</div>
    <?php endwhile;
    echo '</div></div>';
    include('section-news-prevnext.php');

        else :
        require('section-404.php');
        endif; ?>

        <?php
            $this->AddSeparator();
        
        if (count($this->matches))
        {
            echo new XhtmlElement('h2', 'Upcoming matches');
            echo new MatchListControl($this->matches);
        }
        
            
        if ($this->has_player_stats)
        {
            require_once('stoolball/statistics-highlight-table.class.php');
            echo new StatisticsHighlightTable($this->best_batting, $this->most_runs, $this->best_bowling, $this->most_wickets, $this->most_catches, "this season");
        }
            
        if ( dynamic_sidebar('surreyladies') ) : else : endif; 
    }
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>