<?php
class MySqlRawData
{
    /**

    * Query resource

    * @access private

    * @var resource

    */

    private $query;


    /**

    * MySQLResult constructor

    * @param resource query (MySQL query resource)

    * @access public

    */

    function MySqlRawData($query) {

        $this->query=$query;

    }

    /**

    * Returns the number of rows selected

    * @return int

    * @access public

    */

    function size () {

        return mysqli_num_rows($this->query);

    }

    /**

    * Fetches a row from the result

    * @return object

    */

    public function fetch () {

        if (!$this->query) return false;

        if ( $row=mysqli_fetch_object($this->query) ) {

            return $row;

        } else if ( $this->size() > 0 ) {

            mysqli_data_seek($this->query,0);

            return false;

        } else {

            return false;

        }

    }

    /**

     * Disposes of the database result resource

     *

     */

    public function closeCursor()

    {

        if ($this->query) mysqli_free_result($this->query);

    }
    
}
?>