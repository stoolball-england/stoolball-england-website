<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a competition into an entry for the search index
 */
class CompetitionSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(Competition $competition) {
        $this->searchable = new SearchItem();
    }
    
    /**
     * Gets a searchable item representing the competition in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
