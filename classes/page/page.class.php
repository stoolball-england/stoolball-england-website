<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');
require_once('data/mysql-connection.class.php');
require_once('context/site-settings.class.php');
require_once('authentication/permission-type.enum.php');
require_once('xhtml/xhtml-anchor.class.php');
require_once('xhtml/placeholder.class.php');
require_once('context/site-context.class.php');

class Page
{
	/**
	 * All the content categories in the current site, ordered pseudo-hierarchically
	 *
	 * @var CategoryCollection
	 */
	private $o_site_categories;
	/**
	 * What situation is the current page in?
	 *
	 * @var SiteContext
	 */
	private $site_context;
	/**
	 * Configurable settings for the whole website
	 *
	 * @var SiteSettings
	 */
	private $settings;
	private $o_db;
	/**
	 * The website's authentication controls
	 *
	 * @var AuthenticationManager
	 */
	private $o_authentication;
	private $a_client_scripts = array();
	private $s_page_title;
	private $page_description;
	private $s_content_css_class;
	private $a_validated = array();
	private $b_valid;
	private $b_has_googlemap = false;
	private $open_graph_title;

	/**
	 * Manager for handling errors on the page
	 *
	 * @var ExceptionManager
	 */
	private $errors;

	public function __construct(SiteSettings $settings, $i_permission_required, $obsolete = false)
	{
		$start_time = microtime(true);

		# store settings
		$this->settings = $settings;
		
		# set up error handling, unless local and using xdebug

		if (!(SiteContext::IsDevelopment()))
		{
            require_once('page/exception-manager.class.php');
            require_once('page/email-exception-publisher.class.php');
			$this->errors = new ExceptionManager(array(new EmailExceptionPublisher($settings->GetTechnicalContactEmail(), $settings->GetEmailTransport())));
            
            # Use production settings recommended by http://perishablepress.com/advanced-php-error-handling-via-htaccess/
            # Not possible to set in .htaccess because PHP is running as a CGI. This is the next best thing.
            ini_set("display_startup_errors", "off");
            ini_set("display_errors", "off");
            ini_set("html_errors", "off");
            ini_set("log_errors", "on");
            ini_set("ignore_repeated_errors", "off");
            ini_set("ignore_repeated_source", "off");
            ini_set("report_memleaks", "on");
            ini_set("track_errors", "on");
            ini_set("docref_root", "0");
            ini_set("docref_ext", "0");
            ini_set("error_reporting", "-1");
            ini_set("log_errors_max_len", "0");
            ini_set("error_log", $_SERVER['DOCUMENT_ROOT'] . "/php-errors.log");            
		}

		### fire page events ###

		# set up INI options
		date_default_timezone_set('Europe/London');
		$this->OnSiteInit();

		# start output buffering - gzip compression is handled separately by mod_deflate
		ob_start();

		# get db connection
		$this->OnConnectDatabase();

		# set up authentication immediately - even if it's just a generic user
		$this->OnAuthenticate($i_permission_required);

		# Set CSRF token before we shut off session writes
		if ((!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token']) and !$this->IsPostback()) { 
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
		}

		if ($this->SessionWriteClosing()) {
			session_write_close();
		}

		# get site-wide data from db
		$this->OnLoadSiteData();

		# prepare site-wide data
		$this->OnCreateSiteContext();

		# create template elements
		$this->InstantiateTemplate();

		# site ready; now start page-specific code
		$this->OnPageInit();

		# process posted data
		if ($this->IsPostback())
		{
			$this->OnPostback();
		}

		# get page-specific data from db (can use site context)
		$this->OnLoadPageData();

		# close connection to db
		$this->OnDisconnectDatabase();

		# start by building up parts of page that are the same for every page
		$this->OnOpenHead();

		# event which fires before main business logic of page
		$this->OnPrePageLoad();

		# Support WordPress hook
		if (SiteContext::IsWordPress()) wp_head();

		# business logic needed for body of page
		$this->OnCloseHead();

		echo '<body>';
		$this->OnBodyOpened();

		# business logic needed for body of page
		$this->OnPageLoad();

		# render main content
		$this->Render();

		$this->OnBodyClosing();

		if (SiteContext::IsDevelopment())
		{
			$time_taken = (string) round(microtime(true)-$start_time, 4);
			echo '<ul id="debug" class="screen"><li>Peak memory: ' . round(memory_get_peak_usage()/1024) . 'K</li>
			<li>Time: ' . $time_taken . ' secs</li></ul>';
		}

		# Support WordPress hook
		if (SiteContext::IsWordPress()) wp_footer();

		# Output scripts at end of page
		foreach ($this->a_client_scripts as $s_script) echo '<script src="' . $s_script . '"></script>';

		echo '</body></html>';

		# End output buffering
		ob_end_flush();

		# Errors after this point are not in my code! (WordPress 2.6 generates a notice by knowingly calling ob_end_flush beyond the point when there's no buffer)
		restore_error_handler();
		restore_exception_handler();
	}

	/**
	 * @return void
	 * @desc Event which fires before the database is connected, and applies to the whole site
	 */
	function OnSiteInit()
	{
        $charset = 'utf-8';
		if (!headers_sent()) header("Content-Type: text/html; charset=$charset");
	}

	protected function OnAuthenticate($i_permission_required)
	{
		# set up authentication
        require_once('authentication/authentication-manager.class.php');
		require_once('authentication/mysql-auto-sign-in.class.php');
		$this->o_authentication = new AuthenticationManager($this->settings, $this->GetDataConnection(), $i_permission_required);
        $this->o_authentication->SetAutoSignInProvider(new MySqlAutoSignIn($this->GetDataConnection()));

		# check for remembered sign-in
		$this->o_authentication->SignInIfRemembered();

		# Change of browser is suspicious, might be session fixation. Sign out immediately.
		# Note that this call must come after SignInIfRemembered, probably because it sets a cookie
		# overwriting the attempt by SignOut() to kill the cookie.
		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			if (isset($_SESSION['prev_user_agent']) and $_SERVER['HTTP_USER_AGENT'] !== $_SESSION['prev_user_agent'])
			{
				$this->o_authentication->SignOut();
			}

			# Save user agent as check for next request
			$_SESSION['prev_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		# Ensure we have a user
		AuthenticationManager::EnsureUser();

		# if authentication required for this page, check and redirect to sign in if necessary
		if (!$this->o_authentication->HasPermissionForPage(AuthenticationManager::GetUser())) $this->o_authentication->GetPermission();
	}

	/**
	 * Executes before disabling any further writes to session state for the current request and releasing the session lock
	 * @returns bool true to disable further writes; false otherwise
	 */
	protected function SessionWriteClosing()
	{
		return true;
	}

	function OnConnectDatabase()
	{
		$this->o_db = new MySqlConnection($this->settings->DatabaseHost(), $this->settings->DatabaseUser(), $this->settings->DatabasePassword(), $this->settings->DatabaseName());
	}

	function OnLoadSiteData() { /* abstract method */	}

    /**
     * Create a context based on the  site categories
     */
	function OnCreateSiteContext()
    {
        $categories = $this->GetCategories();
        if ($categories instanceof CategoryCollection) $this->site_context = new SiteContext($categories);
    }
    
	function OnPostback() { /* abstract method */ }

	/**
	 * Instantiate elements used to build up template
	 *
	 */
	protected function InstantiateTemplate() { /* abstract method */ }

	/**
	 * @return void
	 * @desc Event which fires after sitewide processing finished, but before page-specific processing begins
	 */
	function OnPageInit() { /* abstract method */ }
	function OnLoadPageData() { /* abstract method */ }
	function OnDisconnectDatabase()
	{
		$this->o_db->Disconnect();
	}

	/**
	 * @return void
	 * @desc Event which fires before any XHTML is sent to the response
	 */
	function OnPrePageLoad() { /* abstract method */ }

	function OnPageLoad() { /* abstract method */ }

	/**
	 * Adds a separator between groups of content
	 *
	 */
	public function AddSeparator()
	{
		$this->Render();
	}

	/**
	 * Gets the configurable settings for the current site
	 *
	 * @return SiteSettings
	 */
	public function GetSettings()
	{
		return $this->settings;
	}

	/**
	 * Gets a string unique to the session which can be used in forms to prevent CSRF attacks
	 */
	protected function GetCsrfToken()
	{
		# SESSION['csrf_token'] should always be set, but avoid an error just in case
		return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : "";
	}

	/**
	 * Gets a reference to the data source used by the current site
	 *
	 * @return MySqlConnection
	 */
	public function GetDataConnection()
	{
		return $this->o_db;
	}

	/**
	 * Gets an instance of the AuthenticationManager used to manage the user's permissions
	 *
	 * @return AuthenticationManager
	 */
	public function GetAuthenticationManager()
	{
		return $this->o_authentication;
	}

	/**
	 * Gets the current context within the site
	 *
	 * @return SiteContext
	 */
	public function GetContext()
	{
		return $this->site_context;
	}

	/**
	 * Sets the current site's categories
	 *
	 * @param CategoryCollection $categories
	 */
	public function SetCategories(CategoryCollection $categories)
	{
		$this->o_site_categories = $categories;
	}

	/**
	 * Gets the current site's categories
	 *
	 * @return CategoryCollection
	 */
	public function GetCategories()
	{
		return $this->o_site_categories;
	}

	/**
	 * @return void
	 * @param string $s_class
	 * @desc Sets a CSS class name which is applied to the main content area of the page, providing context for the CSS cascade
	 */
	function SetContentCssClass($s_class) { $this->s_content_css_class = (string)$s_class; }

	/**
	 * @return string
	 * @desc Gets a CSS class name which is applied to the main content area of the page, providing context for the CSS cascade
	 */
	function GetContentCssClass() { return $this->s_content_css_class; }

	/**
	 * Sends the content built up so far to the client
	 *
	 */
	public function Render()
	{
		ob_flush();
	}

	/**
	 * @return bool
	 * @desc Is this request apparently the result of a posted form
	 */
	function IsPostback()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	 * Gets whether the current request is a refresh or re-post of the last
	 *
	 * @return bool
	 */
	public function IsRefresh()
	{
		return $this->o_authentication->UserHasRefreshed();
	}

	/**
	 * Redirect the user to the specified page, or return false if it's too late
	 *
	 * @param string $url
	 * @return bool
	 */
	public function Redirect($url='index.php')
	{
		if (!headers_sent())
		{
			header('Location: ' . $url);
			exit();
		}
		else return false;
	}

	/**
	 * Writes page up to the opening <head> tag and standard head elements
	 *
	 * @return string
	 */
	protected function OnOpenHead()
	{
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en" prefix="rdfs: http://www.w3.org/2000/01/rdf-schema# xsd: http://www.w3.org/2001/XMLSchema# dcterms: http://purl.org/dc/terms/ schema: http://schema.org/ og: http://opengraphprotocol.org/schema/ fb: http://www.facebook.com/2008/fbml sioc: http://rdfs.org/sioc/ns# sioctypes: http://rdfs.org/sioc/types# awol: http://bblfish.net/work/atom-owl/2006-06-06/AtomOwl.html#">
<head>
<meta charset="utf-8" /><?php
	}

	/**
	 * Write scripts and close </head> element
	 *
	 * @return string
	 */
	public function OnCloseHead()
	{
	    echo '<meta name="twitter:card" content="summary" />' . "\n";
        
		$page_title = $this->GetOpenGraphTitle();
		if (!$page_title) $page_title = $this->GetPageTitle();
		echo '<meta property="og:title" content="' . htmlentities($page_title, ENT_QUOTES, "UTF-8", false) . '" />
              <meta property="dcterms:title" content="' . htmlentities($page_title, ENT_QUOTES, "UTF-8", false) . '" />
              <meta name="twitter:title" content="' . htmlentities($page_title, ENT_QUOTES, "UTF-8", false) . '" />' . "\n";
        
		$page_title = $this->GetPageTitle();
		if (strpos($page_title, $this->GetSettings()->GetSiteName()) === false) $page_title .= " &#8211; " . $this->GetSettings()->GetSiteName();
		echo '<title>' . htmlentities($page_title, ENT_QUOTES, "UTF-8", false) . '</title>' . "\n";

		if ($this->page_description)
		{
			$page_description = htmlentities($this->page_description, ENT_QUOTES, "UTF-8", false);
            $page_description = str_replace("\n", " ", str_replace("\r", " ", $page_description));
			echo '<meta name="description" content="' . $page_description . '" />
			 	  <meta property="og:description" content="' . $page_description . '" />
			 	  <meta property="dcterms:description" content="' . $page_description . '" />
			 	  <meta name="twitter:description" content="' . $page_description . '" />' . "\n";
		}

		echo '</head>';
	}

	/**
	 * Fires immediately after the body element is opened
	 *
	 */
	protected function OnBodyOpened() {	}

	/**
	 * @return void
	 * @desc Fires before closing the body element of the page
	 */
	protected function OnBodyClosing() {}

	/**
	 * @return void
	 * @param string $s_script
	 * @desc Loads a Javascript from the site's script folder
	 */
	function LoadClientScript($s_script, $b_current_folder = false)
	{
		$s_folder = '';
		if ($b_current_folder)
		{
			$s_url = $_SERVER['PHP_SELF'];
			$s_folder = substr($s_url, 0, strlen($s_url) - strlen(basename($s_url)));
		}

		$s_script = $s_folder . trim((string)$s_script);
		if (!in_array($s_script, $this->a_client_scripts, true)) $this->a_client_scripts[] = $s_script;
	}

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the text which will appear in the browser's title bar
	 */
	public function SetPageTitle($s_input)
	{
		$this->s_page_title = trim((string)$s_input);
	}

	/**
	 * @return string
	 * @desc Gets the text which will appear in the browser's title bar
	 */
	public function GetPageTitle()
	{
		return $this->s_page_title;
	}

	/**
	 * @return void
	 * @param string $description
	 * @desc Sets the page description used in search results
	 */
	public function SetPageDescription($description)
	{
		$this->page_description = (string)$description;
	}

	/**
	 * @return string
	 * @desc Gets the page description used in search results
	 */
	public function GetPageDescription()
	{
		return $this->page_description;
	}

	/**
	 * Sets the title for the Open Graph Protocol
	 * @param string $title
	 */
	public function SetOpenGraphTitle($title)
	{
		$this->open_graph_title = (string)$title;
	}

	/**
	 * Gets the title for the Open Graph Protocol
	 */
	public function GetOpenGraphTitle()
	{
		return $this->open_graph_title;
	}

	/**
	 * @return bool
	 * @param XhtmlForm $o_control
	 * @desc Add a XhtmlForm to the list of controls to validate
	 */
	function RegisterControlForValidation($o_control)
	{
		if ($o_control instanceof XhtmlForm)
		{
			$o_control->AddValidationResources($this->settings, $this->GetDataConnection());
			$this->a_validated[] = $o_control;
			return true;
		}
		else return false;
	}

	/**
	 * @return XhtmlForm[]
	 * @desc Gets the list of controls to validate
	 */
	function GetValidatedControls()
	{
		return $this->a_validated;
	}

	/**
	 * @return bool
	 * @desc Test whether all registered XhtmlForms are valid
	 */
	public function IsValid()
	{
		/* @var $o_control XhtmlForm */

		if ($this->b_valid == null)
		{
			$b_valid = true;

			foreach ($this->a_validated as $o_control)
			{
				if ($o_control instanceof DataEditControl)
				{
					$b_valid = ($b_valid and $o_control->IsValidSubmit());
				}
				else
				{
					$b_valid = ($b_valid and $o_control->IsValid());
				}

				# don't break out of loop because need to collect all error messages
			}

			$this->b_valid = $b_valid;
		}

		return $this->b_valid;
	}

	/**
	 * Gets whether the current request is from the Technorati Microformat parser
	 *
	 * @return bool
	 */
	public function IsMicroformatParseRequest()
	{
		return !(empty($_SERVER['HTTP_USER_AGENT']) or stripos($_SERVER['HTTP_USER_AGENT'], 'Stoolball England microformats parser') === false);

	}

	/**
	 * Sets whether the current page may contain a Google map
	 *
	 * @param bool $b_google
	 */
	public function SetHasGoogleMap($b_google)
	{
		$this->b_has_googlemap = (bool)$b_google;
	}

	/**
	 * Gets whether the current page may contain a Google map
	 *
	 * @return bool
	 */
	public function GetHasGoogleMap()
	{
		return $this->b_has_googlemap;
	}
}
?>