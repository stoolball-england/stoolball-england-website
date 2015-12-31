<?php
require_once("search/search-provider.interface.php");
require_once("data/sql.class.php");

/**
 * Searches data added to MySQL by MySqlSearchIndexer
 */
class MySqlSearchProvider implements ISearchProvider {

    private $connection;
    private $total = 0;
    
    public function __construct(MySqlConnection $connection) {
        
        $this->connection = $connection;
    }
    
    /**
     * Searches the index for the given search term
     * @return SearchItem[]
     */
    public function Search(SearchQuery $query) {

        $terms = $query->GetSanitisedTerms();
        $len = count($terms);
        for ($i = 0; $i < $len; $i++) {
            $terms[$i] = Sql::ProtectString($this->connection, $terms[$i]);
            $terms[$i] = "LIKE '%" . trim($terms[$i], "'") . "%'";
        }

        $sql = "SELECT url, title, description, related_links_html 
                FROM
                (
                    SELECT SUM(field_weight) + weight_of_type + weight_within_type AS weight,
                           url, title, description, related_links_html 
                    FROM
                    (
                        SELECT search_index_id, 500 as field_weight, weight_of_type, weight_within_type, url, title, description, related_links_html 
                        FROM nsa_search_index 
                        WHERE title " . implode(" AND title ", $terms) . " 
                        
                        UNION
                        
                        SELECT search_index_id, 500 as field_weight, weight_of_type, weight_within_type, url, title, description, related_links_html 
                        FROM nsa_search_index 
                        WHERE keywords " . implode(" AND keywords ", $terms) . "  
                        
                        UNION
                        
                        SELECT search_index_id, 50 as field_weight, weight_of_type, weight_within_type, url, title, description, related_links_html 
                        FROM nsa_search_index 
                        WHERE description " . implode(" AND description ", $terms) . "  
                        
                        UNION
                        
                        SELECT search_index_id, 1 as field_weight, weight_of_type, weight_within_type, url, title, description, related_links_html 
                        FROM nsa_search_index 
                        WHERE full_text " . implode(" AND full_text ", $terms) . "  
                    )
                    AS unsorted_results
                    GROUP BY search_index_id
                    ORDER BY SUM(field_weight) DESC, SUM(weight_of_type) DESC, SUM(weight_within_type) DESC
                ) 
                AS weighted_results
                ORDER BY weight DESC";

        # Get the total results without paging
        $total = $this->connection->query("SELECT COUNT(*) AS total FROM ($sql) AS total");
        $row = $total->fetch();
        $this->total = $row->total;
        
        # Add paging and get the data
        if ($query->GetFirstResult() && $query->GetPageSize()) {
            $sql .= " LIMIT " . Sql::ProtectNumeric($query->GetFirstResult()-1, false, false) . "," . Sql::ProtectNumeric($query->GetPageSize(), false, false);
        }        
        
        $query_results = $this->connection->query($sql);

        require_once("search/search-item.class.php");
        $search_results = array(); 
        while ($row = $query_results->fetch()) {         
            $result = new SearchItem();
            $result->Url($row->url);
            $result->Title($row->title);
            $result->Description($row->description);
            $result->RelatedLinksHtml($row->related_links_html);
            $search_results[] = $result;
        }
    
        return $search_results;
    }

    /**
     * Gets the total number of results in the set retrieved by the last search
     */
    public function TotalResults() {
        return $this->total;   
    }
}
?>