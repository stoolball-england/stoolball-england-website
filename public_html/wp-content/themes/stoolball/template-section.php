<?php
/*
 Template Name: Section index
 */
?>
<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle(wp_title('', false) . ' &#8211; ' . get_bloginfo('name', 'display'));
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
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
		if (have_posts()) : while (have_posts()) : the_post(); ?>
<h1>
<?php the_title(); ?>
</h1>

<?php
# Get the content the way WordPress would
$content = get_the_content();
$content = apply_filters('the_content', $content);
$content = str_replace(']]>', ']]&gt;', $content);

# See if there's a "more" divider, and separate any text after it
preg_match('/<span id="more-[0-9]+"><\/span>/', $content, $matches);
$after = "";
if (count($matches))
{
	$more = $matches[0];
	$content = str_replace("<p>$more", "$more<p>", $content);	# there's a good chance "more" is immediately inside a paragraph
	$content = preg_replace('/<p>\s*<\/p>/', "", $content); # kill empty paragraphs
	$pos = strpos($content, $more);
	$after = substr($content, $pos+strlen($more));
	$content = substr($content, 0, $pos);
}

# That was the old way. Problem was you couldn't add content only at the end, because WordPress strips out the "More" when it's the first thing.
# Now look for a class on the h2
$pos = strpos($content, '<h2 class="after">');
if ($pos !== false)
{
	$after = substr($content, $pos);
	$content = substr($content, 0, $pos);
}

# Display the content, the child pages, then anything after the more divider
echo '<div class="nav">' . 
     $content;

    StoolballWordPressPlugin::ListChildPages();

echo $after . 
     '</div>';


endwhile; endif;

$this->AddSeparator();
$this->BuySomething();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
