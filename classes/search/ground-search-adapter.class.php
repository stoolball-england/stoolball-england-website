<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a ground into an entry for the search index
 */
class GroundSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(Ground $ground) {
        $this->searchable = new SearchItem();
    }
    
    /**
     * Gets a searchable item representing the ground in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
