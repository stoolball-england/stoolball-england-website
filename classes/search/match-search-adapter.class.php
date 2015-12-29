<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a match into an entry for the search index
 */
class MatchSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(Match $match) {
        $this->searchable = new SearchItem();
    }
    
    /**
     * Gets a searchable item representing the match in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
