<?php
/**
 * A provider which retrieve items from a search index 
 */
interface ISearchProvider {
    /**
     * Searches the index for the given search term
     * @return SearchItem[]
     */
    public function Search(SearchQuery $query);
    
    /**
     * Gets the total number of results in the set retrieved by the last search
     */
    public function TotalResults();
}
?>