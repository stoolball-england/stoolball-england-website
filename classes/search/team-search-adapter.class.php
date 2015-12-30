<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a team into an entry for the search index
 */
class TeamSearchAdapter implements ISearchAdapter {
        
    private $team;
    private $searchable;
    
    public function __construct(Team $team) {
    
        $this->team = $team;
    
        $this->searchable = new SearchItem();
        $this->searchable->SearchItemId("team" . $team->GetId());
        $this->searchable->SearchItemType("team");
        $this->searchable->Url($team->GetNavigateUrl());
        $this->searchable->Title($team->GetNameAndType());
        $this->searchable->Description($this->GetSearchDescription());
        
        $keywords = array($team->GetName(), $team->GetGround()->GetAddress()->GetLocality(), $team->GetGround()->GetAddress()->GetTown());
        $this->searchable->Keywords(implode(" ", $keywords));
        
        $content = array($team->GetGround()->GetAddress()->GetAdministrativeArea(), $team->GetIntro(), $team->GetPlayingTimes(), $team->GetCost(), $team->GetContact());
        $this->searchable->FullText(implode(" ", $content));
        
        $this->searchable->RelatedLinksHtml('<ul>' .
                        '<li><a href="' . $team->GetStatsNavigateUrl() . '">Statistics</a></li>' .
                        '<li><a href="' . $team->GetPlayersNavigateUrl() . '">Players</a></li>' .
                        '<li><a href="' . $team->GetCalendarNavigateUrl() . '">Match calendar</a></li>' .
                        '</ul>');
    }
    
    /**
     * Gets text to use as the description of this team in search results
     */
    public function GetSearchDescription() 
    {
        $description = "A stoolball team";
        
        if ($this->team->GetGround() instanceof Ground and $this->team->GetGround()->GetAddress()->GetAdministrativeArea()) 
        {
            $description .= " in " . $this->team->GetGround()->GetAddress()->GetAdministrativeArea();
        }
        
        $seasons = $this->team->Seasons()->GetItems();
        $seasons_count = $this->team->Seasons()->GetCount();
        if ($seasons_count)
        {
            $competitions = array();
            foreach ($seasons as $team_in_season) 
            {
                $competitions[$team_in_season->GetSeason()->GetCompetition()->GetId()] = $team_in_season->GetSeason()->GetCompetition();
            }
            
            $description .= " playing in ";
            $keys = array_keys($competitions);
            $competitions_count = count($keys);
            for ($i = 0; $i < $competitions_count; $i++) 
            {
                $description .= $competitions[$keys[$i]]->GetName();
                if ($i < ($competitions_count-2)) $description .= ", ";
                if ($i == ($competitions_count-2)) $description .= " and ";
            }
        }
        else 
        {
            $description .= " playing friendlies or tournaments";
        } 
        $description .= ".";    
        return $description;
    }
    
    /**
     * Gets a searchable item representing the team in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
