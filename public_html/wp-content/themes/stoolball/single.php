<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPrePageLoad()
	{
		$this->SetPageTitle(wp_title('', false) . ' &#8211; ' . get_bloginfo('name', 'display'));

		if (has_excerpt())
		{
			$this->SetPageDescription(get_the_excerpt());
		}
		else
		{
			$description = wp_get_single_post(get_the_id())->post_content;
			$description = strip_tags($description);
			$break = strpos($description, "\n");
			if ($break !== false and $break > 0) $description = substr($description, 0, $break-1);
			$this->SetPageDescription($description);
		}


    	$this->SetContentConstraint($this->ConstrainColumns());
		$this->SetContentCssClass('hasLargeImage');
	}

	function OnCloseHead()
	{
		?>
<link rel="alternate" type="application/rss+xml"
	title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
		<?php
		parent::OnCloseHead();
	}

	function OnPageLoad()
	{
		global $post; # Make the WordPress $post object available

		if (have_posts()) : while (have_posts()) : the_post(); ?>

<div typeof="schema:BlogPosting sioctypes:BlogPost" class="post" id="post-<?php the_ID(); ?>" about="<?php echo get_permalink(); ?>" rel="sioc:has_container" rev="sioc:container_of" resource="http://www.stoolball.org.uk/news#blog">
<div about="<?php echo get_permalink() ?>">
	<h1 property="schema:headline dcterms:title"><?php the_title(); ?></h1>
	<p class="date" property="schema:datePublished dcterms:issued" datatype="xsd:date" content="<?php the_time('c') ?>">
	<?php the_time('l j F Y') ?>, <?php the_time() ?>
    </p>

	<div class="entry" property="schema:articleBody">
	    <div rel="awol:content">
	        <div typeof="awol:Content"><meta property="awol:type" content="text/html" /><div property="awol:body">
	<?php
	$content = get_the_content("Read more<span class=\"aural\"> on " . the_title('', '', false) . '</span>&#8230;');
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);

	# Allow pictures to resize with screen on the mobile. Not ideal as should use thumbnails, but better than horizontal scrolling.
	$content = preg_replace('/ style="[^"]+"/', "", $content);
	$content = preg_replace('/<img([^>]*) height=["0-9]+/', "<img$1", $content);
	echo $content;
    ?>
            </div></div>
        </div>
    </div>
    <?php
	wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number'));
	include("section-news-tags.php"); 
	?>
</div>
</div>
	<?php endwhile; else:

	require('section-404.php');

	endif;

	$this->ShowSocial();
	$this->AddSeparator();
	$this->BuySomething();
	require('section-news-navigation.php');

	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>