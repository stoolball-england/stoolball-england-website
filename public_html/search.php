<?php
header("Location: https://www.google.co.uk/#hl=en&sclient=psy-ab&q=site:www.stoolball.org.uk+" . urlencode($_GET['q']) . "&oq=test&fp=cbdaf9abbd69e150");
exit();

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

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
			require_once($_SERVER["DOCUMENT_ROOT"] . "/../Zend/Search/Lucene.php");
			require_once($_SERVER["DOCUMENT_ROOT"] . "/../StandardAnalyzer/Analyzer/Standard/English.php");

			Zend_Search_Lucene_Analysis_Analyzer::setDefault( new StandardAnalyzer_Analyzer_Standard_English() );
			$index = Zend_Search_Lucene::open($_SERVER["DOCUMENT_ROOT"] . "/../lucene/index");

			try
			{
			     $results = $index->find($this->term_sanitised);
            }
            catch (Zend_Search_Lucene_Exception $e)
            {
                $results = array();
            }
        }

		$total = count($results);
		
		if ($total)
		{
			# set up paging based on user's preference
            require_once('data/paged-results.class.php');           
			$paging = new PagedResults();
			$paging->SetPageSize(20);
			$paging->SetResultsTextSingular('result');
			$paging->SetResultsTextPlural('results');
			$paging->SetTotalResults($total);
			
			# trim results not needed for this page
			# Note: Lucene actually recommends retrieving all results and discarding those not needed
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
    
                try
                {
    				echo '<dt>';
    	 			echo '<a href="' . htmlentities($result->url, ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($result->title, ENT_QUOTES, "UTF-8", false) . "</a> ";
     				echo "</dt>";
    				echo '<dd>';
                    echo "<p>" . $protector->ApplyEmailProtection(htmlentities($result->description, ENT_QUOTES, "UTF-8", false), AuthenticationManager::GetUser()->IsSignedIn()) . "</p>";
    				switch ($result->type)
                    {
                        case "team":
                            $team = new Team($this->GetSettings());
                            $team->SetShortUrl(trim($result->url, "/"));
                            echo '<ul>';
                            echo '<li><a href="' . $team->GetStatsNavigateUrl() . '">Statistics</a></li>';
                            echo '<li><a href="' . $team->GetPlayersNavigateUrl() . '">Players</a></li>';
                            echo '<li><a href="' . $team->GetCalendarNavigateUrl() . '">Match calendar</a></li>';
                            echo '</ul>';
                            break;
                        case "ground":
                            $ground = new Ground($this->GetSettings());
                            $ground->SetShortUrl(trim($result->url, "/"));
                            echo '<ul>';
                            echo '<li><a href="' . $ground->GetStatsNavigateUrl() . '">Statistics</a></li>';
                            echo '</ul>';
                            break;
                        case "competition":
                            $competition = new Competition($this->GetSettings());
                            $competition->SetShortUrl(trim($result->url, "/"));
                            echo '<ul>';
                            echo '<li><a href="' . $competition->GetStatisticsUrl() . '">Statistics</a></li>';
                            echo '</ul>';
                            break;
                        case "match":
                            $match = new Match($this->GetSettings());
                            $match->SetShortUrl(trim($result->url, "/"));
                            echo '<ul>';
                            echo '<li><a href="' . $match->GetCalendarNavigateUrl() . '">Add to calendar</a></li>';
                            echo '</ul>';
                            break;
                        
                    }
                    echo '<p class="url">' . htmlentities($this->DisplayUrl($result->url), ENT_QUOTES, "UTF-8", false) . "</p>";
                    echo "</dd>";
                 }
                 catch (Zend_Search_Lucene_Exception $e) 
                 {
                         # If for some reason a field is not defined, just ignore it and continue
                     continue;
                 }
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
        $url = str_replace("http://" . $this->GetSettings()->GetDomain(), "", $url);
        if (strpos($url, "http://") === 0)
        {
            return substr($url, 7);
        } 
        else 
        {
            return $this->GetSettings()->GetDomain() . $url;
        }
    }
}

new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>