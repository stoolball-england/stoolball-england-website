<?php
require_once 'search/search-index-provider.interface.php';
class FakeSearchIndexer implements ISearchIndexProvider {
        /**
     * Queues the item to be added to the index when CommitChanges is called
     */    
    public function Index(SearchItem $item){}
    
    /**
     * Queues an item for removal from the index when CommitChanges is called
     */
    public function DeleteFromIndexById($id){}
    
    /**
     * Queues all items of the given type for removal from the index when CommitChanges is called
     */
    public function DeleteFromIndexByType($type){}
    
    /**
     * Updates the index by running first queued deletions, then queued additions
     */
    public function CommitChanges() {}
}
?>