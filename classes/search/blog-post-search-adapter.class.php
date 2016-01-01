<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a blog post into an entry for the search index
 */
class BlogPostSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(SearchItem $item) {
                   
        if (!$item->Description()) {
            $item->Description($item->FullText());
        }
        
        $description = strip_tags(trim($item->Description()));
        $break = strpos($description, "\n");
        if ($break !== false and $break > 0)
        {
            $description = substr($description, 0, $break - 1);
        }
        $item->Description($description);
        
        # Assign more weight to newer articles
        $weight = (intval($item->ContentDate()->format('U'))/60/60/24/365);
        $item->WeightWithinType($weight); 
        
        $this->searchable = $item;
   }
    
    /**
     * Gets a searchable item representing the blog post in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}
