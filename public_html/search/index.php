<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once("search/mysql-search-provider.class.php");

class CurrentPage extends StoolballPage
{
	private $query;
	private $results = array();
    
    /**
     * @var PagedResults
     */
    private $paging;
    
    public function OnPageInit()
    {
        if (isset($_GET['q']) and $_GET['q'])
        {
            require_once("search/search-query.class.php");
            $this->query = new SearchQuery($_GET['q']);
            
            require_once('data/paged-results.class.php');           
            $this->paging = new PagedResults();
            $this->paging->SetPageSize(20);    
            $this->paging->SetResultsTextSingular('result');
            $this->paging->SetResultsTextPlural('results');
            $this->query->SetFirstResult($this->paging->GetFirstResultOnPage());
            $this->query->SetPageSize($this->paging->GetPageSize());
        }
    }
	
	public function OnLoadPageData() {
	        
        if ($this->query instanceof SearchQuery)
        {
            $index = new MySqlSearchProvider($this->GetDataConnection());
            $this->results = $index->Search($this->query);
            $this->paging->SetTotalResults($index->TotalResults());
        }
	}

	function OnPrePageLoad()
	{
		if ($this->query instanceof SearchQuery)
		{
			$this->SetPageTitle("Search for '" . htmlentities($this->query->GetOriginalTerm(), ENT_QUOTES, "UTF-8", false) . "'");
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
        if (!$this->query instanceof SearchQuery)
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
            require_once('search/search-highlighter.class.php');

            $protector = new EmailAddressProtector($this->GetSettings());
            $highlighter = new SearchHighlighter();

			echo '<dl class="search">';
			foreach ($this->results as $result)
			{
                /* @var $result SearchItem */
				echo '<dt>';
	 			
	 			$title = htmlentities($result->Title(), ENT_QUOTES, "UTF-8", false);
                $title = $highlighter->Highlight($this->query->GetSanitisedTerms(), $title);
	 			echo '<a href="' . htmlentities($result->Url(), ENT_QUOTES, "UTF-8", false) . '">' . $title . "</a> ";
 				echo "</dt>";
				echo '<dd>';
				
                $description = htmlentities($result->Description(), ENT_QUOTES, "UTF-8", false);
                $description = $protector->ApplyEmailProtection($description, AuthenticationManager::GetUser()->IsSignedIn());
                $description = $highlighter->Highlight($this->query->GetSanitisedTerms(), $description);                
                echo "<p>" . $description . "</p>";
                
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