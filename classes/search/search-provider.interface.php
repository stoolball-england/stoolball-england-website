<?php
/**
 * A provider which retrieve items from a search index 
 */
interface ISearchProvider {
    /**
     * Searches the index for the given search term
     * @return SearchableItem[]
     */
    public function Search(SearchTerm $search_term);
}
?>