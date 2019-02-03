<?php
require_once ('data/mysql-raw-data.class.php');

class MySqlConnection
{
	    /**

    * MySQL Resource link identifier stored here

    * @access private

    * @var string

    */

    private $dbConn;


    /**

    * Stores error messages for connection errors

    * @access private

    * @var string

    */

    private $connectError;

    /**

    * MySQL constructor

    * @param string host (MySQL server hostname)

    * @param string dbUser (MySQL User Name)

    * @param string dbPass (MySQL User Password)

    * @param string dbName (Database to select)

    * @access public

    */

    function __construct($host,$dbUser,$dbPass,$dbName) {

        // Make connection to MySQL server

        if (!$this->dbConn = mysqli_connect($host, $dbUser, $dbPass, $dbName)) {

            $this->connectError=true;
            throw new Exception('Could not connect to server');
        }

        @mysqli_set_charset($this->dbConn, "utf8");

    }

    /**

    * Checks for MySQL errors

    * @return boolean

    * @access public

    */

    function isError () {

        if ( $this->connectError )

            return true;


            if (!$this->dbConn) return true;


            $error=mysqli_error ($this->dbConn);

        if ( empty ($error) )

            return false;

        else

            return true;

    }

    /**

     * Returns an instance of MySqlRawData containing raw data

     * @param $s_sql string the database query to run

     * @return MySqlRawData

     */

    public function query($sql) {

        if (!$this->dbConn) throw new Exception ('Query failed: No database connection. SQL: '.$sql);

        if (!$queryResource=mysqli_query($this->dbConn, $sql))

        {

            $error = $this->dbConn ? mysqli_error($this->dbConn) : '';

            trigger_error ('Query failed: '.$error.

                           ' SQL: '.$sql);

        }

        $o_data = new MySqlRawData($queryResource);

        return $o_data;

    }

    /**

     * Disconnect the current database connection

     * @return void

     * @access public

     */

    public function Disconnect()

    {

        if ($this->dbConn) mysqli_close($this->dbConn);

    }

    

    /**

     * Protect a string ready to be inserted into a database query

     * @param $text string

     */

    public function EscapeString($text) 

    {

        if (!$this->dbConn) return $text;

        return mysqli_real_escape_string($this->dbConn,$text);

    }

    

        /**

    * Returns the ID of the last row inserted

    * @return int

    * @access public

    */

    public function insertID () {

        return mysqli_insert_id($this->dbConn);

    }

    /**

     * Gets the number of rows affected by a INSERT/UPDATE/DELETE statement

     *

     * @return int

     */

    public function GetAffectedRows() { return mysqli_affected_rows($this->dbConn); }

    

}
?>