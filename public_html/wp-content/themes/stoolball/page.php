<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPrePageLoad()
	{
		$this->SetPageTitle(wp_title('', false) . ' &#8211; ' . get_bloginfo('name', 'display'));
		$this->SetPageDescription(get_post_meta(get_the_ID(), "Description", true));
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
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
		if (have_posts()) : while (have_posts()) : the_post();

		# Load Google Maps script if specified in a custom property
		$script = get_post_meta(get_the_ID(), "Use Google Maps", true);
		if ($script) $this->SetHasGoogleMap(true);

		# Load a script for the page if specified in a custom property
		$script = get_post_meta(get_the_ID(), "Javascript", true);
		if ($script) $this->LoadClientScript($script);
		?>
		<h1><?php the_title(); ?></h1>
		<div class="post" id="post-<?php the_ID(); ?>">
			<?php
			$content = get_the_content();
			$content = apply_filters('the_content', $content);
			$content = str_replace(']]>', ']]&gt;', $content);
            
            # Remove a <br /> that can get inserted after an image
			$content = preg_replace('/(<img[^>]*>)<br \/>/', "$1", $content);
            
            # Allow pictures to resize with screen on the mobile. Not ideal as should use thumbnails, but better than horizontal scrolling.
			$content = preg_replace('/ style="[^"]+"/', "", $content);
            $content = preg_replace('/<img([^>]*) height=["0-9]+/', "<img$1", $content);
            echo $content;
			wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
		</div>
		<?php endwhile; endif;
		$this->AddSeparator();
		$this->BuySomething();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
