<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once("search/mysql-search-provider.class.php");

class CurrentPage extends StoolballPage
{
	private $term;
	private $term_sanitised;
	private $results = array();
    
    /**
     * @var PagedResults
     */
    private $paging;
    
    public function OnPageInit()
    {
        if (isset($_GET['q']))
        {
            $this->term = $_GET['q'];
            $this->term_sanitised = preg_replace('/[^A-Z0-9- :"?*~.\[\]{}()\^+-|!&]/i', "", $this->term);
            $this->term_sanitised = trim($this->term_sanitised, '\\'); # Backslash causes error if at end of string
        }
            
        require_once('data/paged-results.class.php');           
        $this->paging = new PagedResults();
        $this->paging->SetPageSize(20);    
        $this->paging->SetResultsTextSingular('result');
        $this->paging->SetResultsTextPlural('results');
    }
	
	public function OnLoadPageData() {
	        
        if ($this->term_sanitised)
        {
            require_once("search/search-query.class.php");
            $index = new MySqlSearchProvider($this->GetDataConnection());
            $query = new SearchQuery($this->term_sanitised);
            $query->SetFirstResult($this->paging->GetFirstResultOnPage());
            $query->SetPageSize($this->paging->GetPageSize());
            $this->results = $index->Search($query);
            $this->paging->SetTotalResults($index->TotalResults());
        }
	}

	function OnPrePageLoad()
	{
		if ($this->term)
		{
			$this->SetPageTitle("Search for '" . htmlentities($this->term, ENT_QUOTES, "UTF-8", false) . "'");
		}
		else
		{
			$this->SetPageTitle("Search");
		}
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', $this->GetPageTitle());

        # If no search term, show a search form (intended for mobile)
        if (!$this->term)
        {
            ?>
            <form action="/search" method="get"><div>
            <input type="search" name="q" />
            <input type="submit" value="Search" />
            </div></form>
            <?php
            return ;
        } 

		if ($this->paging->GetTotalResults())
		{
			# write the paging navbar
			$paging_bar = $this->paging->GetNavigationBar();
			echo $paging_bar;

            # Load files used for custom formats
            require_once('email/email-address-protector.class.php');

            $protector = new EmailAddressProtector($this->GetSettings());

			echo '<dl class="search">';
			foreach ($this->results as $result)
			{
                /* @var $result SearchItem */
				echo '<dt>';
	 			echo '<a href="' . htmlentities($result->Url(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($result->Title(), ENT_QUOTES, "UTF-8", false) . "</a> ";
 				echo "</dt>";
				echo '<dd>';
                echo "<p>" . $protector->ApplyEmailProtection(htmlentities($result->Description(), ENT_QUOTES, "UTF-8", false), AuthenticationManager::GetUser()->IsSignedIn()) . "</p>";
                echo $result->RelatedLinksHtml();
                echo '<p class="url">' . htmlentities($this->DisplayUrl($result->Url()), ENT_QUOTES, "UTF-8", false) . "</p>";
                if (isset($_GET['debug'])) {
                    echo '<ul class="weight">' .
                         '<li>Matched field weight: <strong>' . $result->WeightOfMatchedField() . '</strong></li>' .
                         '<li>Weight of result type: <strong>' . $result->WeightOfType() . '</strong></li>' .
                         '<li>Weight within type: <strong>' . $result->WeightWithinType() . '</strong></li>' .
                         '<li>Weight: <strong>' . $result->Weight() . '</strong></li>' .
                         '</ul>';
                }
                echo "</dd>";
			}
			echo '</dl>';
			
			echo $paging_bar;
			
		} 
		else 
		{
			?>
			<p>Sorry, we didn't find anything matching your search.</p>
			<p>Please check your spelling, or try rewording your search.</p>
			<p>If you still can't find what you're looking for, please <a href="/contact/"> contact us</a>.</p>
			<?php
		}
        
        $this->AddSeparator();
        $this->BuySomething();
	}
    
    private function DisplayUrl($url) 
    {
        $url = str_replace("https://" . $this->GetSettings()->GetDomain(), "", $url);
        if (strpos($url, "https://") === 0)
        {
            return substr($url, 8);
        } 
        else 
        {
            return $this->GetSettings()->GetDomain() . $url;
        }
    }
}

new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>