<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a player into an entry for the search index
 */
class PlayerSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(Player $player) {
        $this->searchable = new SearchItem();
    }
    
    /**
     * Gets a searchable item representing the player in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
