<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPrePageLoad()
	{
		# set page title
		global $post; # Make the WordPress $post object available
		if ( !empty($post->post_excerpt) )
		{
			$this->SetPageTitle(strip_tags($post->post_excerpt)); // this is the "caption"
		}
		else
		{
			$this->SetPageTitle('Image in ' . get_the_title($post->post_parent));
		}
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
		global $post; # Make the WordPress $post object available

		if (have_posts()) : while (have_posts()) : the_post(); ?>
	
			<div class="post" id="post-<?php the_ID(); ?>">
				<h1><?php echo HTML::Encode($this->GetPageTitle()); ?></h1>
					<p class="date"><?php the_time('l j F Y') ?>, <?php the_time() ?></p>
	
				<div class="entry">
					<p>This image is used in <a href="<?php echo get_permalink($post->post_parent); ?>" rev="attachment"><?php echo get_the_title($post->post_parent); ?></a>.</p>
					<p class="photo"><a href="<?php echo wp_get_attachment_url($post->ID); ?>"><?php echo wp_get_attachment_image( $post->ID, 'medium' ); ?></a></p>
	                <div class="photoCaption"><?php if ( !empty($post->post_excerpt) ) the_excerpt(); // this is the "caption" ?></div>
	
					<?php the_content("Read more<span class=\"aural\"> on " . the_title('', '', false) . '</span>&#8230;'); ?>
	
					<?php the_tags('<p class="tags">Tags: ', ', ', '</p>'); ?>
	
				</div>
	
			</div>
	
		<?php endwhile;
		else:
		require('section-404.php');
		endif;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
