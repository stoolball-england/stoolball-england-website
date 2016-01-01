<?php
/**
 * Builds details from a strongly typed class into an entry for the search index
 */
interface ISearchAdapter {
    
    /**
     * Gets a searchable item representing the strongly typed class in the search index
     * @return SearchableItem
     */
	public function GetSearchableItem();
}
?>