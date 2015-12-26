<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle(get_bloginfo('name', 'display'));
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
		echo '<h1>' . HTML::Encode($this->GetPageTitle()) . '</h1>';
		?>
	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div class="post" id="post-<?php the_ID(); ?>">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
				<p class="date"><?php the_time('l j F Y') ?> <!-- by <?php the_author() ?> --></p>

				<div class="entry">
					<?php
					if (has_excerpt())
					{
						the_excerpt();
						?>
						<p class="more-link"><a href="<?php the_permalink() ?>">Read more<span class="aural"> on <?php the_title(); ?></span>&#8230;</a></p>
						<?php
					}
					else
					{
						the_content("Read more<span class=\"aural\"> on " . the_title('', '', false) . '</span>&#8230;');
					}
					?>
				</div>

				<?php the_tags('<p class="tags">Tags: ', ', ', '</p>'); ?>
				<?php edit_post_link('Edit', '<p>', '</p>'); ?>

			</div>

		<?php endwhile;

		else:
		require('section-404.php');

		endif;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>