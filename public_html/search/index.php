<?php
header("Location: https://www.google.co.uk/#hl=en&sclient=psy-ab&q=site:www.stoolball.org.uk+" . urlencode($_GET['q']) . "&oq=test&fp=cbdaf9abbd69e150");
exit();

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once("search/search-provider.interface.php");

class FakeSearchProvider implements ISearchProvider {
        /**
     * Searches the index for the given search term
     * @return SearchableItem[]
     */
    public function Search(SearchTerm $search_term) {
        
        require_once("search/search-item.class.php");
        $results = array();
        $results[] = new SearchItem("fake", 1, "/fake1", "Fake result 1", "It's fake");
        $results[] = new SearchItem("fake", 2, "/fake2", "Fake result 2", "It's faker");
        
        return $results;
    }
}

class CurrentPage extends StoolballPage
{
	private $term;
	private $term_sanitised;

	function OnPrePageLoad()
	{
		if (isset($_GET['q']))
		{
			$this->term = $_GET['q'];
			$this->term_sanitised = preg_replace('/[^A-Z0-9- :"?*~.\[\]{}()\^+-|!&]/i', "", $this->term);
            $this->term_sanitised = trim($this->term_sanitised, '\\'); # Backslash causes error if at end of string
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


        $results = array();
		if ($this->term_sanitised)
		{
		    require_once("search/search-term.class.php");
		    $index = new FakeSearchProvider();
	        $results = $index->Search(new SearchTerm($this->term_sanitised));
        }

		$total = count($results);
		
		if ($total)
		{
		    require_once('data/paged-results.class.php');           
			$paging = new PagedResults();
			$paging->SetPageSize(20);
			$paging->SetResultsTextSingular('result');
			$paging->SetResultsTextPlural('results');
			$paging->SetTotalResults($total);
			
			# trim results not needed for this page
			$results = $paging->TrimRows($results);

			# write the paging navbar
			$paging_bar = $paging->GetNavigationBar();
			echo $paging_bar;

            # Load files used for custom formats
            require_once("stoolball/team.class.php");
            require_once("stoolball/ground.class.php");
            require_once("stoolball/competition.class.php");
            require_once("stoolball/match.class.php");
            require_once('email/email-address-protector.class.php');

            $protector = new EmailAddressProtector($this->GetSettings());

			echo '<dl class="search">';
			foreach ($results as $result)
			{
                /* @var $result SearchItem */
				echo '<dt>';
	 			echo '<a href="' . htmlentities($result->Url(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($result->Title(), ENT_QUOTES, "UTF-8", false) . "</a> ";
 				echo "</dt>";
				echo '<dd>';
                echo "<p>" . $protector->ApplyEmailProtection(htmlentities($result->Description(), ENT_QUOTES, "UTF-8", false), AuthenticationManager::GetUser()->IsSignedIn()) . "</p>";
                echo $result->RelatedLinksHtml();
                echo '<p class="url">' . htmlentities($this->DisplayUrl($result->Url()), ENT_QUOTES, "UTF-8", false) . "</p>";
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