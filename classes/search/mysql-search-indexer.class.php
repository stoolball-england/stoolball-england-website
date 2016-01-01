<?php
require_once 'search/search-index-provider.interface.php';
require_once 'data/sql.class.php';

/**
 * Store an index of search results in MySQL
 */
class MySqlSearchIndexer implements ISearchIndexProvider {
    
    /**
     * @var MySqlConnection
     */
    private $connection;
    private $delete_queue = array();
    private $index_queue = array();
    
    public function __construct(MySqlConnection $connection) {
        $this->connection = $connection;
    }
    
    /**
     * Queues the item to be added to the index when CommitChanges is called
     */    
    public function Index(SearchItem $item){
        
        $this->index_queue[] = "INSERT INTO nsa_search_index SET 
            search_index_id = " . Sql::ProtectString($this->connection, $item->SearchItemId()) . ",
            indexed_item_type = " . Sql::ProtectString($this->connection, $item->SearchItemType()) . ",
            url = " . Sql::ProtectString($this->connection, $item->Url()) . ",
            title = " . Sql::ProtectString($this->connection, $item->Title()) . ",
            keywords = " . Sql::ProtectString($this->connection, $item->Keywords()) . ",
            description = " . Sql::ProtectString($this->connection, $item->Description()) . ",
            related_links_html = " . Sql::ProtectString($this->connection, $item->RelatedLinksHtml()) . ",
            full_text = " . Sql::ProtectString($this->connection, $item->FullText()) . ",
            weight_of_type = " . Sql::ProtectNumeric($item->WeightOfType(), false, false) . ",
            weight_within_type = " . Sql::ProtectNumeric($item->WeightWithinType(), false, false);
    }
    
    /**
     * Queues an item for removal from the index when CommitChanges is called
     */
    public function DeleteFromIndexById($id){
        
        $this->delete_queue[] = "DELETE FROM nsa_search_index WHERE search_index_id = " . Sql::ProtectString($this->connection, $id);
    }
    
    /**
     * Queues all items of the given type for removal from the index when CommitChanges is called
     */
    public function DeleteFromIndexByType($type){

        $this->delete_queue[] = "DELETE FROM nsa_search_index WHERE indexed_item_type = " . Sql::ProtectString($this->connection, $type);        
    }
    
    /**
     * Updates the index by running first queued deletions, then queued additions
     */
    public function CommitChanges() {
        
        foreach ($this->delete_queue as $sql) {
            $this->connection->query($sql);
        }        
        foreach ($this->index_queue as $sql) {
            $this->connection->query($sql);
        }        
    }
}
?>