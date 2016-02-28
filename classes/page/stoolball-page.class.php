<?php
require_once('page/page.class.php');
require_once('category/category.class.php');
require_once('category/category-collection.class.php');
require_once('context/stoolball-settings.class.php');
require_once('authentication/authentication-control.class.php');
require_once('markup/xhtml-markup.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/match-list-control.class.php');

class StoolballPage extends Page
{
	var $a_next_matches;
	private $i_constraint_type;
	private $b_col1_open = false;
	private $b_col2_open = false;
	private $open_graph_type = "article";
    private $css_root = "";
    private $resource_root = "";
    private $css_version = 41;
	
	# override constructor to accept settings for this site
	function StoolballPage(SiteSettings $o_settings, $i_permission_required, $obsolete = false)
	{
		$this->i_constraint_type = StoolballPage::ConstrainNone();
        if (!SiteContext::IsDevelopment())
        {
            $this->css_root = 'https://www.stoolball.org.uk';
            $this->resource_root = 'https://www.stoolball.org.uk';
        }
		parent::Page($o_settings, $i_permission_required);
	}

	# override base method to get data required by every page on the site
	function OnLoadSiteData()
	{
		$this->SetCategories($this->GetAllCategories());

		$o_match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
		$o_match_manager->ReadNext();
		$this->a_next_matches = $o_match_manager->GetItems();
		unset($o_match_manager);
	}

	/**
	 * Instantiate elements used to build up template
	 *
	 */
	protected function InstantiateTemplate()
	{
		# Register JQuery early so it's loaded before other scripts
		# IMPORTANT: The latest JQuery 1.X does not work with the auto complete used here, so that would need upgrading too.
		$this->LoadClientScript($this->GetContext()->IsDevelopment() ? '/scripts/lib/jquery-1.7.2.min.js' : 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
		$this->LoadClientScript($this->resource_root . "/scripts/stoolball.4.js");
	}

	/**
	 * @return void
	 * @desc Event which fires after sitewide processing finished, but before page-specific processing begins
	 */
	function OnPageInit()
	{
		if (!$this->GetPageTitle()) $this->SetPageTitle($this->GetSettings()->GetSiteName());

		parent::OnPageInit();
	}


	# get category data for site from db, and return as CategoryCollection
	function GetAllCategories($b_show_hidden=0)
	{
		require_once('category/category-manager.class.php');
		$category_manager = new CategoryManager($this->GetSettings(), $this->GetDataConnection());
		$category_manager->ReadAll();
		$categories = $category_manager->GetItems();
		unset($category_manager);

		return new CategoryCollection($categories);
	}

	/**
	 * Sets the type for the Open Graph Protocol
	 * @param string $type
	 */
	public function SetOpenGraphType($type)
	{
		$this->open_graph_type = (string)$type;
	}

	/**
	 * Gets the type for the Open Graph Protocol
	 */
	public function GetOpenGraphType()
	{
		return $this->open_graph_type;
	}

	public function OnCloseHead()
	{
        $css_mobile = $this->css_root . "/css/mobile.$this->css_version.css";
        $css_medium = $this->css_root . "/css/medium.$this->css_version.css";
        $css_desktop = $this->css_root .= "/css/stoolball.$this->css_version.css";
        ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="canonical" href="https://<?php echo $this->GetSettings()->GetDomain() . htmlspecialchars($_SERVER["REQUEST_URI"]);?>" />
<link rel="stylesheet" href="<?php echo $css_mobile ?>" />
<link rel="stylesheet" href="<?php echo $css_medium ?>" media="only screen and (min-width: 500px)" class="mqMedium" />
<link rel="stylesheet" href="<?php echo $css_desktop ?>" media="only screen and (min-width: 900px)" class="mqLarge" />
<!--[if (lte IE 8) & !(IEMobile 7) ]><link rel="stylesheet" href="<?php echo $css_medium ?>" class="mqIE mqMedium" /><![endif]-->
<!--[if (lte IE 8) & !(IEMobile 7) ]><link rel="stylesheet" href="<?php echo $css_desktop ?>" class="mqIE mqLarge" /><![endif]-->
<link rel="start" href="/" title="Go to home page" />
<link rel="shortcut icon" href="<?php echo $this->resource_root ?>/favicon.ico" />
<meta property="og:type" content="<?php echo htmlentities($this->GetOpenGraphType(), ENT_QUOTES, "UTF-8", false); ?>" />
<meta property="og:url" content="<?php echo htmlentities("https://" . $this->GetSettings()->GetDomain() . $_SERVER['REQUEST_URI'], ENT_QUOTES, "UTF-8", false); ?>" />
<meta property="og:image" content="<?php echo $this->resource_root ?>/images/features/facebook-ident.png" />
<meta property="og:site_name" content="Stoolball England" />
<meta property="fb:app_id" content="259918514021950" />
<meta name="twitter:site" content="@stoolball" />
<script src="<?php echo $this->resource_root ?>/scripts/lib/modernizr.js"></script>
<link rel="search" href="/search/" title="Go to Stoolball England\'s search page" />
<link href='https://fonts.googleapis.com/css?family=Passion+One' rel='stylesheet' type='text/css' />
		<?php

		parent::OnCloseHead();
	}

	/**
	 * Fires immediately after the body element is opened
	 *
	 */
	protected function OnBodyOpened()
	{
        $mobile_logo = '<img src="' . $this->resource_root . '/images/logos/138x40-trans.png" width="138" height="40" alt="Go to the Stoolball England home page" class="small screen logo-small" />
                        <img src="' . $this->resource_root . '/images/logos/210x61-trans.png" width="210" height="61" alt="Go to the Stoolball England home page" class="print logo" />';
            
        if (SiteContext::IsWordPress() and is_home())
        {
            echo $mobile_logo;  
        }
        else 
        {
            echo '<a href="/">' . $mobile_logo . '</a>'; 
        }
		
		?>
<form id="handle" action="/search/" method="get" class="large"><div><input id="search" type="search" name="q" value="<?php if (isset($_GET['q'])) echo htmlentities($_GET['q'], ENT_QUOTES, "UTF-8", false); ?>" />
	<input type="submit" value="Search" id="go" /></div>
</form>
<div id="bat1"></div>
		<?php
		if ($this->GetContentCssClass())
		{
			echo '<div id="board" class="' . $this->GetContentCssClass() . '">';
		}
		else
		{
			echo '<div id="board">';
		}
			?>
<div id="boardLeft">
	<div id="boardRight">
		<?php

		# build navbar and add to control tree
		$o_current = $this->GetContext()->GetByHierarchyLevel(2);

		$b_got_category = is_object($o_current);
		$b_section_home = ($this->GetContext()->GetDepth() == 2 and str_replace("/", "", $_SERVER['REQUEST_URI']) == $o_current->GetUrl());

		$o_news = new XhtmlElement('a', 'News');
		$o_rules = new XhtmlElement('a', 'Rules');
		$o_play = new XhtmlElement('a', 'Play!');
        $schools = new XhtmlElement('a', 'Schools');
		$o_about = new XhtmlElement('a', 'About <span class="large"> Us</span>');
        $search = new XhtmlElement('a', 'Search');

		if ($b_got_category and ($o_current->GetUrl() == 'news') or (SiteContext::IsWordPress() and  (is_single() or (is_archive() and !is_category()))))
		{
			if (!$b_section_home)
			{
				$o_news->AddAttribute('href', '/news');
                $o_news->AddAttribute('role', 'menuitem');
				$o_news->SetCssClass('current');
			}
			else $o_news->SetElementName('em');
		}
		else $o_news->AddAttribute('href', '/news');

		if ($b_got_category and $o_current->GetUrl() == 'rules')
		{
			if (!$b_section_home)
			{
				$o_rules->AddAttribute('href', '/rules');
                $o_rules->AddAttribute('role', 'menuitem');
				$o_rules->SetCssClass('current');
			}
			else $o_rules->SetElementName('em');
		}
		else $o_rules->AddAttribute('href', '/rules');

		if ($b_got_category and ($o_current->GetUrl() == 'play'))
		{
			if (!$b_section_home)
			{
				$o_play->AddAttribute('href', '/play');
                $o_play->AddAttribute('role', 'menuitem');
				$o_play->SetCssClass('current');
			}
			else $o_play->SetElementName('em');
		}
		else $o_play->AddAttribute('href', '/play');

        if ($b_got_category and $o_current->GetUrl() == 'schools')
        {
            if (!$b_section_home)
            {
                $schools->AddAttribute('href', '/schools');
                $schools->AddAttribute('role', 'menuitem');
                $schools->SetCssClass('current');
            }
            else $schools->SetElementName('em');
        }
        else $schools->AddAttribute('href', '/schools');
        
        if ($b_got_category and $o_current->GetUrl() == 'about')
		{
			if (!$b_section_home)
			{
				$o_about->AddAttribute('href', '/about');
                $o_about->AddAttribute('role', 'menuitem');
				$o_about->SetCssClass('current');
			}
			else $o_about->SetElementName('em');
		}
		else $o_about->AddAttribute('href', '/about');

        if ($b_got_category and $o_current->GetUrl() == 'search')
        {
            if (!$b_section_home)
            {
                $search->AddAttribute('href', '/search');
                $search->AddAttribute('role', 'menuitem');
                $search->SetCssClass('current');
            }
            else $search->SetElementName('em');
        }
        else $search->AddAttribute('href', '/search');
        ?>
        <nav>
        <ul class="menu screen" role="menubar">
        <?php
		echo '';
		echo '<li id="news">' . $o_news . '</li>';
		echo '<li id="rules">' . $o_rules . '</li>';
		echo '<li id="play">' . $o_play . '</li>';
        echo '<li id="schools">' . $schools . '</li>';
		echo '<li id="about">' . $o_about . '</li>';
        echo '<li class="search small">' . $search . '</li>';
		echo '';
		?>
		</ul>
        </nav>
	   <div id="bat2" class="large">
			<div id="involved"></div>
			<ul>
				<li><a href="/rules/what-is-stoolball">What is stoolball?</a></li>
				<li><a href="/rules">Learn the rules</a></li>
				<li><a href="/teams/map">Map of teams</a></li>
			</ul>
		</div>

		<div id="content" class="content">
		<?php

		# add content
		if ($this->i_constraint_type != StoolballPage::ConstrainNone())
		{
			switch ($this->i_constraint_type)
			{
				case StoolballPage::ConstrainText():
					echo '<div id="constraint" class="constrainText">';
					break;
				case StoolballPage::ConstrainColumns():
				case StoolballPage::ConstrainBox():
                    echo '<div id="constraint" class="constrainColumns">';
					break;
			}
		}
		if ($this->i_constraint_type == StoolballPage::ConstrainColumns())
		{
			echo('<div class="supportedContentContainer"><div class="supportedContent">');
			$this->b_col1_open = true;
		}
		$this->Render();
	}

	/**
	 * Sets how to constrain the width of the main content
	 *
	 * @param int $i_type
	 */
	public function SetContentConstraint($i_type)
	{
		$this->i_constraint_type = (int)$i_type;
	}

	/**
	 * Do not constrain the width of the main content
	 *
	 * @return int
	 */
	public static function ConstrainNone() { return 0; }

	/**
	 * Constrain the width of the main content, suitable for text
	 *
	 * @return int
	 */
	public static function ConstrainText() { return 1; }

    /**
     * Constrain the width of the main content, suitable for boxed content with no sidebar
     *
     * @return int
     */
    public static function ConstrainBox() { return 3; }

	/**
	 * Constrain the width of the main content, suitable for text with a sidebar
	 *
	 * @return int
	 */
	public static function ConstrainColumns() { return 2; }

	/**
	 * Closes the current open column and starts a new one
	 *
	 */
	public function AddSeparator()
	{
		if ($this->b_col1_open)
		{
			$this->Render();
			?>
		</div>
	</div>
	<div class="supportingContent">
	<?php
	$this->b_col1_open = false;
	$this->b_col2_open = true;
		}
	}

	/**
	 * Output social media links once per page
	 */
	protected function ShowSocial()
	{
		?>
		<div class="social screen">
			<a href="https://twitter.com/share" class="twitter-share-button" data-count="horizontal" data-via="Stoolball">Tweet</a>
			<script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
			<div id="fb-root"></div>
			<div class="fb-like" data-send="true" data-width="450" data-show-faces="true" data-font="arial"></div>
		</div>
		<?php
    }


    /**
     * Output social media links once per page
     */
    protected function ShowSocialAccounts()
    {
        ?>
        <div class="social screen">
            <a href="https://twitter.com/Stoolball" class="twitter-follow-button">Follow @Stoolball</a>
            <script src="https://platform.twitter.com/widgets.js"></script>
    
            <span id="fb-root"></span>
            <script src="https://connect.facebook.net/en_GB/all.js#appId=259918514021950&amp;xfbml=1"></script>
            <fb:like href="https://www.facebook.com/pages/Stoolball-England/150788078331608" send="true" layout="button_count" width="150" show_faces="false" font="arial"></fb:like>
        </div>
        <?php
    }

	/**
	 * @return void
	 * @desc Fires before closing the body element of the page
	 */
	protected function OnBodyClosing()
	{
		# Close open divs
		if ($this->b_col1_open) echo('</div></div>');
		if ($this->b_col2_open) echo('</div>');
		if ($this->i_constraint_type != StoolballPage::ConstrainNone()) echo ('</div>'); # Close <div id="constraint">
		?>
	</div>
	<div id="sidebar">
	<?php
        if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::VIEW_ADMINISTRATION_PAGE)) 
        {
            $o_menu_link = new XhtmlElement('a', 'Menu');
            $o_menu_link->AddAttribute('href', '/yesnosorry/');
            echo new XhtmlElement('p', $o_menu_link,"screen");
        }

        # Add WordPress edit page link if relevant
		if (SiteContext::IsWordPress() and AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::VIEW_WORDPRESS_LOGIN))
		{			
			global $post;
			$b_wordpress_edit_allowed = true;
	
			echo '<ul>';
			if ( $post->post_type == 'page' ) 
			{
				if ( !current_user_can( 'edit_page', $post->ID ) ) $b_wordpress_edit_allowed = false;
			}
			else 
			{
				if ( !current_user_can( 'edit_post', $post->ID ) ) $b_wordpress_edit_allowed = false;
			}
	
			if ($b_wordpress_edit_allowed)
			{
				echo '<li><a href="' . apply_filters( 'edit_post_link', get_edit_post_link( $post->ID ), $post->ID ) . '">Edit page</a></li>';	
			}
	
			if (function_exists('wp_register'))
			{
				wp_register();
				echo '<li>';
				wp_loginout();
				echo '</li>';
				wp_meta();
			}
			echo '</ul>';
		}

	echo '<h2 id="your" class="large">Your stoolball.org.uk</h2>';
	$authentication = new AuthenticationControl($this->GetSettings(), AuthenticationManager::GetUser());
    $authentication->SetXhtmlId('authControl');
    echo $authentication;
	
	# Add admin options
	$this->Render();

	# Add next matches
	$num_matches = count($this->a_next_matches);
	if ($num_matches)
	{
		$next_alt = ($num_matches > 1) ? "Next $num_matches matches" : 'Next match';
		echo '<h2 id="next' . $num_matches . '" class="large"><span></span>' . $next_alt . '</h2>';
		$o_match_list = new MatchListControl($this->a_next_matches);
		$o_match_list->UseMicroformats(false); # Because parse should look at other match lists on page
		$o_match_list->AddCssClass("large");
		echo $o_match_list;
		echo '<p class="large"><a href="/matches">All matches and tournaments</a></p>';
	
	}
	$this->Render();
	?>
	</div>
	<div id="boardBottom">
		<div>
			<div></div>
		</div>
	</div>
</div></div></div>
<div id="post"></div>
	<?php

	if (!SiteContext::IsDevelopment() and !AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::EXCLUDE_FROM_ANALYTICS))
	{
		?>
<script>
var _gaq=[['_setAccount','UA-1597472-1'],['_trackPageview'],['_setDomainName','.stoolball.org.uk'],['_trackPageLoadTime']];
(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];g.async=1;
g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
s.parentNode.insertBefore(g,s)}(document,'script'));
</script>
		<?php
	}

		if ($this->GetHasGoogleMap())
		{
			// Load Google AJAX Search API for geocoding (Google Maps API v3 Geocoding is still rubbish 29 Oct 2009)
			$s_key = (SiteContext::IsDevelopment()) ? 'ABQIAAAA1HxejNRwsLuCM4npXmWXVRRQNEa9vqBL8sCMeUxGvuXwQDty9RRFMSpVT9x-PVLTvFTpGlzom0U9kQ' : 'ABQIAAAA1HxejNRwsLuCM4npXmWXVRSqn96GdhD_ATNWxDNgWa3A5EXWHxQrao6MHCS6Es_c2v0t0KQ7iP-FTg';
			echo '<script src="https://www.google.com/uds/api?file=uds.js&amp;v=1.0&amp;key=' . $s_key . '"></script>';

			// Load Google Maps API v3
			echo '<script src="https://maps.google.co.uk/maps/api/js?sensor=false"></script>';
		}
	}

	/**
	 * Display an advert for something we sell
	 */
	protected function BuySomething()
	{
		$spreadshirt = ((gmdate("U") % 10) >0);
		if ($spreadshirt)
		{
			$folder= SiteContext::IsWordPress() ? ABSPATH : $_SERVER['DOCUMENT_ROOT'];
			
			// File downloaded from http://api.spreadshirt.net/api/v1/shops/629531/articles?limit=50
			$articles = simplexml_load_file($folder . "/spreadshirt.xml");
			$article =  $articles->article[rand(0, 49)];
			echo '<div class="spreadshirt large">';
			#echo '<h2><img src="/images/christmas.gif" alt="Stoolball at Christmas" width="204" height="90" /></h2>';
			echo '<h2><img src="/images/gifts.gif" alt="Visit our stoolball gift shop" width="204" height="90" /></h2>';
			echo '<div class="spreadshirt-box">' .
			'<a href="https://shop.spreadshirt.co.uk/stoolball/-A' . Html::Encode($article['id']) . '">' .
			'<img src="' . Html::Encode($article->resources->resource->attributes("http://www.w3.org/1999/xlink")->href) . '" alt="' . Html::Encode($article->name) . '" width="159" height="159" />' .
			'<p>' . Html::Encode($article->name) . '</p>' .
			'<p class="price">&pound;' . Html::Encode(number_format((float)$article->price->vatIncluded, 2, '.', '')) . '</p>
			<p class="buy"><span>Buy it now</span></p></a></div></div>';
		}
		else
		{
			echo '<a class="promo large" href="/shop"><img alt="Bats &pound;39, Balls &pound;7, Wickets &pound;150, Scorebooks &pound;4. Buy yours now." width="185" height="214" src="' . $this->resource_root . '/images/equipment/bat-ad-' . rand(1,2) . '.jpg" /></a>';
		}
	}

    private $indexer;

    /**
     * Gets the search indexer for the site 
     * @return ISearchIndexProvider
     */
    protected function SearchIndexer() {
        if (is_null($this->indexer)) {
            require_once("search/mysql-search-indexer.class.php");
            $this->indexer = new MySqlSearchIndexer($this->GetDataConnection());
        }
        return $this->indexer;
    }
}
?>