<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    private $title;
    
	function OnPrePageLoad()
	{
		# set page title
		if (have_posts())
		{

			$post = $posts[0]; // Hack. Set $post so that the_date() works.

			/* If this is a category archive */
			if (is_category())
			{
				$this->SetPageTitle(single_cat_title('', false));
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
		if (have_posts()) :

		echo '<div class="large">';
		include('section-news-prevnext.php');
        echo '</div>';

		echo '<div class="nav" typeof="schema:Blog sioctypes:Weblog" about="http://www.stoolball.org.uk/news#blog">' . 
		  '<div rel="schema:about"><h1 typeof="schema:Thing" about="http://dbpedia.org/resource/Stoolball"><span property="schema:name">Stoolball</span> news ' . htmlentities($this->title, ENT_QUOTES, "UTF-8", false) . '</h1></div>';

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
			$this->BuySomething();
			?>
	<div class="large">
    	<h2>Follow us</h2>
    	<p>
    		<a href="https://twitter.com/Stoolball" class="twitter-follow-button" data-show-count="false">Follow @Stoolball</a>
    		<script src="https://platform.twitter.com/widgets.js" type="text/javascript"></script>
    
    		<span id="fb-root"></span>
    		<script src="https://connect.facebook.net/en_GB/all.js#appId=259918514021950&amp;xfbml=1"></script>
    		<fb:like href="https://www.facebook.com/stoolball" send="true" layout="button_count" width="150" show_faces="false" font="arial"></fb:like>
    
    		<a href="http://feeds.feedburner.com/stoolballnews" rel="alternate" type="application/rss+xml" class="rss">Subscribe in a reader</a>
    	</p>
	</div>
			<?php  require('section-news-navigation.php');
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>