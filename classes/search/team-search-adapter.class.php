<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a team into an entry for the search index
 */
class TeamSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(Team $team) {
        $this->searchable = new SearchItem();
    }
    
    /**
     * Gets a searchable item representing the team in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
