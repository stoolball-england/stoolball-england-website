<?php
/*
 Plugin Name: Stoolball England
 Plugin URI: http://www.stoolball.org.uk/
 Description: WordPress customisations for the Stoolball England website
 Author: Rick Mason
 */

// In case we're running standalone, for some odd reason
if (function_exists('add_filter'))
{
	ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '../' . PATH_SEPARATOR . ABSPATH . '../classes/');
	require_once('context/stoolball-settings.class.php');
	require_once('authentication/authentication-manager.class.php');
    require_once('email/email-address-protector.class.php');

	// Create an instance of the plugin code
	$stoolball_plugin = new StoolballWordPressPlugin(new StoolballSettings());
}

/**
 * WordPress customisations for the Stoolball England website
 *
 */
class StoolballWordPressPlugin
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $settings;
	private $a_matches = array();

	/**
	 * Creates an instance of StoolballWordPressPlugin
	 *
	 * @param SiteSettings $settings
	 */
	public function __construct(SiteSettings $settings)
	{
		$this->settings = $settings;

		# Hook up to display of content, assign priority of > 10 so that it runs after the default filters,
		# ie: it runs after the TinyMCE code has been converted into XHTML.
		add_filter('the_excerpt', array($this, 'TheExcerpt'), 20);
		add_filter('the_content', array($this, 'TheContent'), 20);

		# Customise the limits on the WordPress query - limit to 4 entries on home page
		add_filter('post_limits', array($this, 'PostLimits'));

		add_filter('the_permalink', array($this, 'GetPageLink'));
		add_filter('page_link', array($this, 'GetPageLink'));
		add_filter('term_link', array($this, 'GetPageLink'));
		add_filter('get_archives_link', array($this, 'GetArchivesLink'));

		# Apply house style rules to content as it's saved
		add_filter('content_save_pre', array($this, 'ContentSavePre'));
        
        # Allow additional file types to be uploaded
        add_filter('upload_mimes', array($this, 'AllowUploadFileTypes'));

		# Limit queries to the 'Stoolball news' category
		add_action('pre_get_posts', array($this, 'PreGetPosts'));

        # Add pages and posts to search as they are published
        add_action('publish_page', array($this, 'PublishPage'));
        add_action('publish_post', array($this, 'PublishPost'));
        add_action('wp_trash_post', array($this, 'TrashPost'));

		# Remove Windows Live Writer tagging support
		remove_action('wp_head', 'wlwmanifest_link');

		# Remove other blog client support
		remove_action('wp_head', 'rsd_link');

		# Remove Generator meta tag, identifying WordPress
		remove_action('wp_head', 'wp_generator');
	}

	/**
	 * Used as regex parameter to convert a one-line list of meeting attendees into an unordered list
	 * @param string[] $matches
	 * @return string
	 */
	private function ListPeople_MatchEvaluator($matches)
	{
		$list = '<ul><li>' . $matches[3] . '</li></ul>';
		$list = str_replace(', ', '</li><li>', $list);
		$list = str_replace(' and ', '</li><li>', $list);
		$list = str_replace(' Officers)', ' Officer)', $list);
		$list = preg_replace('/([a-z]+)(<\/li>\s*<li>[^<(]+)(\([A-Za-z 0-9 ]+\))/', '$1 $3$2$3', $list); # give Becky and Ruth own job title instead of one combined credit
		$list = str_replace('.', '', $list);
		return '<h2>' . $matches[1] . ' ' . $matches[2] . '</h2>' . $list;
	}

	/**
	 * Apply house style rules as the content is saved
	 *
	 * @param string $content
	 * @return string
	 */
	public function ContentSavePre($content)
	{
		# Remove comments from the start of the text, because otherwise they can be taken to be the first paragraph.
		# Can't unilaterally remove all comments because WordPress "more" feature is implemented as a comment.
		$content = trim($content);
		while (strpos($content, "<!--") === 0)
		{
			$end_pos = strpos($content, "-->");
			if ($end_pos === false) break; # avoid infinite loop
			$content = trim(substr($content,$end_pos+3));
		}

		# Expand lists of people at the start of meeting minutes. Matches from the prompt up to the end of the line
		$content = preg_replace_callback('/(\n[0-9]+\.)\s*(Present|Apologies for absence):(.*)/', array($this,'ListPeople_MatchEvaluator'), $content);

		$searches = array();
		$replaces = array();

		# Expand abbreviations and fix capitalisation/punctuation.
		# Use space rather than \b before SC and SE to avoid affecting links to filenames
		$searches[] = '/ SC\b/';
		$replaces[] = 'Stoolball Club';

        $searches[] = '/ SE\b/';
        $replaces[] = 'Stoolball England';

		$searches[] = '/([a-z,;\'-]\s+)Leagues and Associations/'; # lowercase where it follows another word
		$replaces[] = '$1leagues and associations';

		$searches[] = '/Leagues and Associations/'; # if previous didn't match, likely start of a sentence so leave the L uppercase
		$replaces[] = 'Leagues and associations';

		# lowercase stoolball where it falls between two all-lowercase words, or at the end of a sentence after a lowercase word
		$searches[] = '/(\b[a-z]+\b\s+)Stoolball\b([.?!]|\s+\b[a-z])/';
		$replaces[] = '$1stoolball$2';

		$searches[] = '/[S|s]toolball [E|e]ngland/'; # ...but always capitalise Stoolball England
		$replaces[] = 'Stoolball England';

		$searches[] = '/e-mail/';
		$replaces[] = 'email';

		$searches[] = '/web site/';
		$replaces[] = 'website';

		# Use Stoolball England email addresses
		foreach ($this->settings->GetPublicEmailAliases() as $private_email => $public_email) {
            $searches[] = '/\b' . $private_email . '\b/';
            $replaces[] = $public_email;
		}

		# Tidy headings
		$searches[] = '/(<h[2-6]>.*?)<strong>(.*?)<\/strong>(.*?<\/h[2-6]>)/'; # strong within a heading (usually bold text converted to heading)
		$replaces[] = '$1$2$3';

		# When pasting minutes, the numbered headings need a space after the number
		$searches[] = '/(\n[0-9]+\.)([A-Z])/';
		$replaces[] = '$1 $2';

		# When pasting minutes, automatically spot headers
		$searches[] = "/(\n)([0-9]+\.\s*.+):/";
		$replaces[] = "$1<h2>$2</h2>" . chr(13) . chr(13);

		# When pasting minutes, tidy common phrase
		$searches[] = '/([0-9]+\.\s*Minutes of the last .* meeting): The Minutes/';
		$replaces[] = '<h2>$1</h2>' . chr(13) . chr(13) . 'The minutes';

		# No colons after headings
		$searches[] = '/(<h[2-6]>.*?):\s*(<\/h[2-6]>)/';
		$replaces[] = '$1$2';

		# Unwanted attributes
		$searches[] = '/ (align|lang)=[^ >]+/'; # broad match for characters used because not sure how to match "
		$replaces[] = '';

		# Removing image height so that images can resize down in narrow windows using max-width 100%
		$searches[] = '/<img([^>]*) height=["0-9]+/';
		$replaces[] = '<img$1';

		# Unwanted styles
		# NOTE: Can leave ugly, unwanted spans with empty style attributes
		$searches[] = '/(font-size|margin-left|margin-bottom|margin-top|margin-right|font-family)\s*:\s*[A-Za-z-0-9,;]+;?/';
		$replaces[] = '';

		# Empty tags
		$searches[] = '/<p( [^>]*)?>\s*<\/p>/';
		$replaces[] = '';

		$searches[] = '/<span>([^<]*)<\/span>/';
		$replaces[] = '$1';

		# No superscript for ordinals
		$searches[] = '/([0-9]+)<sup>?(st|nd|rd|th)\s*<\/sup>/';
		$replaces[] = '$1$2';

		# No tabs or multiple spaces
        $searches[] = '/&nbsp;/';
        $replaces[] = ' ';
		
        $searches[] = '/[\t ]+/';
		$replaces[] = ' ';

		# Fix time punctuation
		$searches[] = '/\b([0-9]+)(\.|:)([0-9]+)\s*(a|p)\s*\.?\s*m\.?\b/'; # with minutes
		$replaces[] = '$1.$3$4m';

		$searches[] = '/\b([0-9]+)\s*(a|p)\s*\.?\s*m\.?\b/'; # without minutes
		$replaces[] = '$1$2m';

		$searches[] = '/\b([0-9]+)\.00(am|pm)\b/'; # remove .00 minutes
		$replaces[] = '$1$2';

		$searches[] = '/\b([0-9]{1,2})(\.[0-9]{1,2})?(am|pm).(\s+[a-z])/'; # remove stray . after am/pm when followed by lowercase
		$replaces[] = '$1$2$3$4';

		# Fix dates
		$searches[] = '/(January|February|March|April|May|June|July|August|September|October|November|December)\s+([0-9]{1,2})(st|nd|rd|th)?\b/'; # We're not American
		$replaces[] = '$2 $1';

		$searches[] = '/([0-9]{1,2})(<sup>)?(st|nd|rd|th)\s*(<\/sup>)?\s*(January|February|March|April|May|June|July|August|September|October|November|December)/'; # Remove ordinals
		$replaces[] = '$1 $5';

		$searches[] = '/(January|February|March|April|May|June|July|August|September|October|November|December),\s+([0-9]{4})\b/'; # Remove comma between month and year
		$replaces[] = '$1 $2';

		$searches[] = '/(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday),\s+([0-9]{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)/'; # Remove comma between day and date
		$replaces[] = '$1 $2 $3';

		# Fix Kay's signoff
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).1.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 January $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).2.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 February $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).3.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 March $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).4.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 April $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).5.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 May $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).6.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 June $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).7.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 July $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).8.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 August $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).9.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 September $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).10.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 October $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).11.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 November $2';
		$searches[] = '/KRP\/JEP\/([0-9]{1,2}).12.(20[0-9][0-9])/';
		$replaces[] = 'John and Kay Price' . chr(13) . '$1 December $2';

		$content = preg_replace($searches, $replaces, $content);

		# Expand common abbreviations at first mention
		# $content = $this->ExpandAbbreviation('SE', 'Stoolball England', $content);

		return $content;
	}

	/**
	 * Expand the first use of an abbreviation, if not already expanded
	 *
	 * @param string $abbreviation
	 * @param string $expansion
	 * @param string $text
	 * @return string
	 */
	private function ExpandAbbreviation($abbreviation, $expansion, $text)
	{
		$pos_abbr = strpos($text, $abbreviation);
		$pos_expansion = strpos($text, $expansion);

		# if abbreviation used and expansion not present or not within 100 characters after the abbreviation...
		if ((!($pos_abbr === false)) and ($pos_expansion === false or $pos_expansion > ($pos_abbr + strlen($abbreviation) + 100)))
		{
			# Insert expansion before abbreviation, and bracket the abbreviation
			$text = substr($text, 0, $pos_abbr) . $expansion . ' (' . $abbreviation . ')' . substr($text, $pos_abbr+strlen($abbreviation));
		}
		# if abbreviation used and expansion before but not within 50 characters of the abbreviation...
		elseif ((!($pos_abbr === false)) and (!($pos_expansion === false)) and $pos_expansion < ($pos_abbr - strlen($pos_expansion) - 50))
		{
			# Insert abbreviation after expansion, and bracket the abbreviation
			$text = substr($text, 0, $pos_expansion) . $expansion . ' (' . $abbreviation . ')' . substr($text, $pos_expansion+strlen($expansion));
		}

		return $text;
	}

	/**
	 * Limit most queries to only the 'Stoolball news' category
	 *
	 * @link http://codex.wordpress.org/Custom_Queries
	 */
	public function PreGetPosts()
	{
		global $wp_query;
		$news_category_id = 1;

		// Figure out if we need to exclude category - exclude from
		// archives (except category archives), feeds, and home page
		if( is_home() || is_feed() ||
		( is_archive() && !is_category() && !is_tag()))
		{
			$wp_query->query_vars['cat'] = $news_category_id;
		}
	}

	/**
	 * Customise the limits on the WordPress query - limit to 4 entries on home page
	 *
	 * @param string $limits
	 * @return string
	 * @link http://codex.wordpress.org/Custom_Queries
	 */
	public function PostLimits($limits)
	{
		return is_home() ? 'LIMIT 0, 4' : $limits;
	}

	/**
	 * Customise the display of post excerpts as they are being rendered by the browser
	 *
	 * @param string $excerpt
	 * @return string
	 */
	public function TheExcerpt($excerpt='')
	{
        $protector = new EmailAddressProtector($this->settings);
        $excerpt = $protector->ApplyEmailProtection($excerpt, (is_object(AuthenticationManager::GetUser()) and AuthenticationManager::GetUser()->IsSignedIn()));
        
        $excerpt = str_replace("http://www.stoolball.org.uk", "https://www.stoolball.org.uk", $excerpt);
        
		return $excerpt;
	}

	/**
	 * Customise the display of posts as they are being rendered by the browser
	 *
	 * @param string $content
	 * @return string
	 */
	public function TheContent($content='')
	{
		$searches = array();
		$replaces = array();

		# Use title as alt where there's a title and an empty alt attribute
		$searches[] = '/ title="([^"]+)"([^>]*) alt=""/';
		$replaces[] = '$2 alt="$1"';

		# Remove title where there's also a populated alt attribute
		$searches[] = '/ title="[^"]+"([^>]* alt="[^"]+")/';
		$replaces[] = '$1';

		# Remove link to original image, which has no navigation
		$searches[] = '/<a href="[^"]+.(jpg|gif|png|jpeg)"[^>]*>(<img [^>]*>)<\/a>/';
		$replaces[] = '$2';

		# Strip these by providing no replacement text
		$searches[] = '/ title="[a-z0-9-]*"/'; # remove meaningless title attributes containing filenames
		$replaces[] = '';

		$content = preg_replace($searches, $replaces, $content);
        $content = str_replace("http://www.stoolball.org.uk", "https://www.stoolball.org.uk", $content);

		if (is_home())
		{
			# Take out and remember images
			$content = preg_replace_callback('/(<img[^>]*>)/', array($this, 'ExtractImage'), $content);

			$strip_these = array(
			'/<h[0-9][^>]*>.*?<\/h[0-9]>/', # Remove headings
			'/<p class="wp-caption-text">.*?<\/p>/',  # Remove image caption
			'/<a[^>]* class="more-link[^>]*>.*?<\/a>/',  # Remove more link
			'/style="width: [0-9]+px;?"/',  # Remove image width
			'/<div[^>]*><\/div>/' # Remove empty dvis (can be left behind when stripping images with captions)
			);

			$content = preg_replace($strip_these, '', $content);

			# Don't want home page to be too long, so cut off after first para
			$pos = strpos($content, '</p>');
			if ($pos)
			{
				$pos = $pos+4; # length of </p>
				$content = substr($content, 0, $pos);
			}

			# If there were images, put the first one at the start of the text
			if (count($this->a_matches))
			{
				# Remove unused class, width and height
				$image = preg_replace('/ (class|width|height)="[A-Za-z0-9-_ ]+"/', '', $this->a_matches[0]);

				# Try to isolate the src attribute and swop it for the corresponding thumbnail
				$pos = strpos($image, ' src="');
				if ($pos !== false)
				{
					$pos = $pos+6; # move to start to attr value
					$len = strpos($image, '"', $pos);
					if ($len !== false)
					{
						# Get path to image on server
						$wordpress_image_folder = $this->settings->GetServerRoot();
						if (SiteContext::IsDevelopment()) $wordpress_image_folder .= "../"; # on dev setup, WordPress image uploads are outside web root

						require_once 'Zend/Uri.php';
						$uri = Zend_Uri::factory(substr($image, $pos, $len-$pos));
						/* @var $uri Zend_Uri_Http */

						# Change it to the thumbnail path and see whether the thumbnail exists
						$thumbnail = $wordpress_image_folder . $uri->getPath();
						$pos = strrpos($thumbnail, '.');
						if ($pos !== false)
						{
							$thumbnail = substr($thumbnail, 0, $pos) . "-150x150" . substr($thumbnail, $pos);

							if (file_exists($thumbnail))
							{
								# if it does exist, update the original image tag with the thumbnail suffix and size
								# important to do all these checks because thumbnails don't exist for images smaller than 150px
								$image = preg_replace('/\.(jpg|jpeg|gif|png)"/', '-150x150.$1" width="150" height="150"', $image);
							}
						}
					}
				}

				# Add image before content
				$content = $image . $content;
			}
		}
		else
		{
			# Increase image width by 10px (caption must be 100%, so space around image must be margin on inner element, not padding on this element)
			$content = preg_replace_callback('/class="wp-caption([A-Za-z0-9-_ ]*)" style="width: ([0-9]+)px;?"/', array($this, 'ReplaceImageWidth'), $content);

			# Add extra XHTML around photo captions as hook for CSS
			$content = preg_replace('/(<p class="wp-caption-text">[^<]*<\/p>)/', '<div class="photoCaption">$1</div>', $content);
		}

        $protector = new EmailAddressProtector($this->settings);
        $content = $protector->ApplyEmailProtection($content, (is_object(AuthenticationManager::GetUser()) and AuthenticationManager::GetUser()->IsSignedIn()));

		return $content;
	}

	/**
	 * Match evaluator to increase the width of an image by 10px (to allow for padding)
	 *
	 * @param string[] $matches
	 * @return string
	 */
	private function ReplaceImageWidth($matches)
	{
		$width = ((int)$matches[2]) + 10;
		return 'class="wp-caption' . $matches[1] . '" style="width: ' . $width . 'px"';
	}

	/**
	 * Match evaluator to save an image tag and replace it with an empty string
	 *
	 * @param string[] $matches
	 * @return string
	 */
	private function ExtractImage($matches)
	{
		$this->a_matches[] = $matches[1];
		return '';
	}

	/**
	 * Update the XHTML of the monthly archive navigation
	 *
	 * @param string $xhtml
	 * @return string
	 */
	public function GetArchivesLink($xhtml)
	{
		if (is_date() or is_category())
		{
			if (is_month())
			{
				# Unlink the current month
				$xhtml = preg_replace('/<a [^>]*>(' . apply_filters('the_time', get_the_time('F Y'), 'F Y') . ')<\/a>/', '<em>$1</em>', $xhtml, 1);
			}

			# Remove pointless title attributes
			$xhtml = preg_replace("/ title='[A-Za-z0-9 ]+'/", '', $xhtml);
		}

		return $xhtml;
	}

	/**
	 * Remove protocol and host from links to make them relative to the current site root
	 * @param string $url
	 */
	public function GetPageLink($url)
	{
		if (strpos($url, "http:") === 0)
		{
			$url = substr($url, 7);
			$url = substr($url, strpos($url, "/"));
		}
		return $url;
	}


	/**
	 * Lists child pages of the current WordPress page
	 *
	 * @return void
	 */
	public static function ListChildPages() {
		global $post;        
        
        // Query WordPress for child pages
        $query = new WP_Query();
        $pages = $query->query(array('post_type' => 'page', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC', 'post_parent' => $post->ID));
                
        // echo what we get back from WP to the browser
        foreach($pages as $page)
        {
            $output .= '<h2><a href="' . get_page_link($page->ID) . '">' . $page->post_title . '</a></h2>';

            $description = get_post_meta($page->ID, 'Description', true);
            if($description) $output .= "<p>$description</p>";
        }

        echo $output;
        
	}

    /**
     * Add a WordPress page to search results when it is published
     * @param $page_id int
     */
    public function PublishPage($page_id)
    {
        $page = get_page($page_id);
        
        require_once("search/mysql-search-indexer.class.php");
        require_once("search/search-item.class.php");
        require_once("data/mysql-connection.class.php");
        $search = new MySqlSearchIndexer(new MySqlConnection($this->settings->DatabaseHost(), $this->settings->DatabaseUser(), $this->settings->DatabasePassword(), $this->settings->DatabaseName()));
        $search->DeleteFromIndexById("page" . $page_id);
        $item = new SearchItem("page", $page_id, get_permalink($page_id), $page->post_title, get_post_meta($page_id, "Description", true));
        $item->FullText($page->post_content);
        $search->Index($item);
        $search->CommitChanges();
    }
    
    /**
     * Add a WordPress post to search results when it is published
     * @param $post_id int
     */
    public function PublishPost($post_id)
    {
        $post = get_post($post_id);

        require_once("search/mysql-search-indexer.class.php");
        require_once("search/blog-post-search-adapter.class.php");
        require_once("search/search-item.class.php");
        require_once("data/mysql-connection.class.php");
        $search = new MySqlSearchIndexer(new MySqlConnection($this->settings->DatabaseHost(), $this->settings->DatabaseUser(), $this->settings->DatabasePassword(), $this->settings->DatabaseName()));
        $search->DeleteFromIndexById("post" . $post_id);

        $item = new SearchItem("post", $post_id, get_permalink($post_id), $post->post_title);
        $item->ContentDate(new DateTime($post->post_date));
        $item->FullText($post->post_content);
        $adapter = new BlogPostSearchAdapter($item);
        $search->Index($adapter->GetSearchableItem());

        $search->CommitChanges();        
    }

    /**
     * Remove a WordPress post or page from search results when it is moved to the trash
     * @param $post_id int
     */
    public function TrashPost($post_id)
    {
        require_once("search/mysql-search-indexer.class.php");
        require_once("data/mysql-connection.class.php");
        $search = new MySqlSearchIndexer(new MySqlConnection($this->settings->DatabaseHost(), $this->settings->DatabaseUser(), $this->settings->DatabasePassword(), $this->settings->DatabaseName()));
        
        $post = get_post($post_id);
        if ($post and $post->post_type == 'post')
        {
            $search->DeleteFromIndexById("post" . $post_id);
        }        
        else if ($post and $post->post_type == 'page')
        {
            $search->DeleteFromIndexById("page" . $post_id);
        }
    }
    
    /**
     * Allow additional file types to be uploaded 
     */
    public function AllowUploadFileTypes($existing_mimes=array())
    {
        //allow EPS files
        $existing_mimes['eps'] = 'application/postscript';
        $existing_mimes['dot'] = 'application/msword'; 
        return $existing_mimes;
    }
}
?>
