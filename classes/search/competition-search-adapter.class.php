<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a competition into an entry for the search index
 */
class CompetitionSearchAdapter implements ISearchAdapter {
        
    private $competition;
    private $searchable;
    
    public function __construct(Competition $competition) {
            
        $this->competition = $competition;
        
        $season = $competition->GetLatestSeason();
        $teams = $season->GetTeams();
        $keywords = array();
        $content = array();
        $keywords[] = $competition->GetName();
        foreach ($teams as $team)
        {
            $keywords[] = $team->GetName();
            $keywords[] = $team->GetGround()->GetAddress()->GetLocality();
            $keywords[] = $team->GetGround()->GetAddress()->GetTown();
        }
        $content[] = $competition->GetIntro();
        $content[] = $competition->GetContact();
        
        $this->searchable = new SearchItem("competition", "competition" . $competition->GetId(), $competition->GetNavigateUrl(), $competition->GetName());
        $this->searchable->WeightOfType(20);
        $this->searchable->Description($this->GetSearchDescription());
        $this->searchable->Keywords(implode(" ", $keywords));
        $this->searchable->FullText(implode(" ", $content));
        $this->searchable->RelatedLinksHtml('<ul>' .
                                            '<li><a href="' . $competition->GetStatisticsUrl() . '">Statistics</a></li>' .
                                            '</ul>');
    }
    
    /**
     * Gets text to use as the description of this competition in search results
     */
    public function GetSearchDescription() 
    {
        $description = "A stoolball competition";
        $season = $this->competition->GetLatestSeason();
        $teams = $season->GetTeams();
        $teams_count = count($teams);
        if ($teams_count)
        {
            $description .= " played by ";
            if ($teams_count > 10)
            {
                $description .= "teams including ";
                $teams_count = 10;
            } 
            for ($i = 0; $i < $teams_count; $i++) 
            {
                $description .= $teams[$i]->GetName();
                if ($i < ($teams_count-2)) $description .= ", ";
                if ($i == ($teams_count-2)) $description .= " and ";
            }
        }
        else if ($this->competition->GetIntro())
        {
            $description = trim($this->competition->GetIntro());
            $description = XhtmlMarkup::ApplyLinks($description, true);
            $description = XhtmlMarkup::ApplyLists($description, true);
            $description = XhtmlMarkup::ApplySimpleTags($description, true);
            
            $break = strpos($description, "\n");
            if ($break !== false and $break > 0) $description = substr($description, 0, $break-1);
        } 
        $description .= ".";    

        return $description;
    }
    
    /**
     * Gets a searchable item representing the competition in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
