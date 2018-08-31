<?php
require_once('collection.class.php');
require_once('collection-builder.class.php');
require_once('data/sql.class.php');
require_once('data/audit-data.class.php');

abstract class DataManager extends Collection
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	var $o_settings;
	/**
	 * Connection to database
	 *
	 * @var MySqlConnection
	 */
	private $o_db;
	private $i_max_items = 0;
	private $i_start_date = 0;
	private $i_end_date = 0;

	/**
	 * @return DataManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Create a DataManager to read and write structured data
	 */
	function DataManager(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		$this->o_settings = $o_settings;
		$this->o_db = $o_db;

		parent::Collection();
	}


	/**
	 * Gets the configurable settings for the current site
	 *
	 * @return SiteSettings
	 */
	protected function GetSettings()
	{
		return $this->o_settings;
	}

	/**
	 * Gets a reference to the data source
	 *
	 * @return MySqlConnection
	 */
	public function GetDataConnection()
	{
		return $this->o_db;
	}

	/**
	 * @access public
	 * @return void
	 * @desc Read all items from the db
	 */
	function ReadAll()
	{
		return $this->ReadById();
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the items matching the supplied ids, or all items
	 */
	function ReadById($a_ids=null) { die('DataManager::ReadById() is an abstract method'); }

	/**
	 * Locks the specified database tables for writing
	 *
	 * @param string[] $a_tables
	 */
	protected function Lock($a_tables)
	{
		$a_tables = $this->EnsureStringArray($a_tables);
		if (count($a_tables))
		{
			$s_sql = 'LOCK TABLES ' . join(' WRITE, ', $a_tables) . ' WRITE';
			$this->GetDataConnection()->query($s_sql);
		}
	}

	/**
	 * Unlocks the database tables which were most recently locked
	 *
	 */
	protected function Unlock()
	{
		$this->GetDataConnection()->query('UNLOCK TABLES');
	}

	/**
	 * @return void
	 * @param int $maximum
	 * @desc Sets the maximum number of objects which should be returned by this data manager, if supported
	 */
	public function FilterByMaximumResults($maximum)
	{
		$this->i_max_items = (int)$maximum;
	}

	/**
	 * @return int
	 * @desc Gets the maximum number of objects which should be returned by this data manager, if supported
	 */
	public function FilterByMaximumResultsValue()
	{
		return $this->i_max_items;
	}

	/**
	 * Sets the date from before which data should not be returned
	 *
	 * @param int $timestamp
	 */
	public function FilterByDateStart($timestamp)
	{
		$this->i_start_date = (int)$timestamp;
	}

	/**
	 * Gets the date from before which data should not be returned
	 *
	 * @return int
	 */
	public function FilterByDateStartValue()
	{
		return $this->i_start_date;
	}


	/**
	 * Sets the date after which data should not be returned
	 *
	 * @param int $timestamp
	 */
	public function FilterByDateEnd($timestamp)
	{
		$this->i_end_date = (int)$timestamp;
	}

	/**
	 * Gets the date after which data should not be returned
	 *
	 * @return int
	 */
	public function FilterByDateEndValue()
	{
		return $this->i_end_date;
	}


	/**
	 * @return bool
	 * @param int[] $a_numeric
	 * @desc Checks that the passed variable is an array of numeric values; removes it if not
	 */
	function ValidateNumericArray($a_numeric)
	{
		if ($a_numeric == null or !is_array($a_numeric)) $a_numeric = array();

		$b_valid = true;
		foreach ($a_numeric as $key => $value)
		{
			$b_valid = ($b_valid and is_numeric($value));
			if (!$b_valid) unset($a_numeric[$key]);
		}
		return $b_valid;
	}

	/**
	 * @return bool
	 * @param Category[] $a_categories
	 * @desc Checks that the passed variable is an array of Category objects; dies if not
	 */
	function ValidateCategoryArray(&$a_categories)
	{
		$s_error = 'Category data not valid. Database will not be accessed.';
		if (!is_array($a_categories)) die($s_error);

		$b_valid = true;
		foreach ($a_categories as $o_category)
		{
			$b_valid = $b_valid and $o_category instanceof Category;
			if (!$b_valid) die($s_error);
		}
		return $b_valid;
	}

	/**
	 * @return string[]
	 * @param string[] $a_strings
	 * @desc Checks that the passed variable is an array of strings; converts values to strings if not
	 */
	function EnsureStringArray($a_strings)
	{
		if ($a_strings == null or !is_array($a_strings)) return array();

		$i_count = count($a_strings);
		for ($i = 0; $i < $i_count; $i++)
		{
			$a_strings[$i] = (string)$a_strings[$i];
		}
		return $a_strings;
	}

	/**
	 * Adds a condition to a WHERE clause
	 *
	 * @param string $s_clause
	 * @param string $s_test
	 * @param int $i_operator
	 * @return string
	 */
	protected function SqlAddCondition($s_clause, $s_test, $i_operator=1)
	{
		if ($s_clause)
		{
			if ($i_operator == DataManager::SqlAnd()) $s_clause .= 'AND ';
			else if ($i_operator == DataManager::SqlOr()) $s_clause .= 'OR ';
		}
		else
		{
			$s_clause = '';
		}

		$s_clause .= '(' . $s_test . ') ';
		return $s_clause;
	}

	/**
	 * Adds conditions to a SQL query
	 *
	 * @param string $s_query
	 * @param string $s_clause
	 * @return string
	 */
	protected function SqlAddWhereClause($s_query, $s_clause)
	{
		if ($s_clause) $s_query .= ' WHERE ' . $s_clause;
		return $s_query;
	}

	/**
	 * Adds a condition to a WHERE clause limiting a given field to a date range
	 *
	 * @param string $s_clause
	 * @param string $s_field_name
	 * @param int $i_operator
	 */
	protected function SqlAddDateRange($s_clause, $s_field_name, $i_operator=1)
	{
		if ($this->FilterByDateStartValue() > 0)
		{
			$s_clause = $this->SqlAddCondition($s_clause, $s_field_name . ' >= ' . $this->FilterByDateStartValue(), $i_operator);
		}
		if ($this->FilterByDateEndValue() > 0)
		{
			$s_clause = $this->SqlAddCondition($s_clause, $s_field_name . ' <= ' . $this->FilterByDateEndValue(), $i_operator);
		}
		return $s_clause;
	}

	/**
	 * Represents a Boolean AND
	 *
	 * @return int
	 */
	protected static function SqlAnd()
	{
		return 1;
	}

	/**
	 * Represents a Boolean OR
	 *
	 * @return int
	 */
	protected static function SqlOr()
	{
		return 2;
	}

	/**
	 * Protect a string and enclose in quotes ready for a SQL query
	 * @param string $text
	 */
	protected function SqlString($text)
	{
		return "'" . $this->GetDataConnection()->EscapeString($text) . "'";
	}


    /**
     * Remove any malicious HTML from a string and enclose in quotes ready for a SQL query
     * @param string $text
     */
    protected function SqlHtmlString($text, $allowed_elements=null)
    {
        if ($text)
        {
            # Strip empty paragraphs which are common when using Tiny MCE. May be due to something
            # on my computer since I have not seen this with Tiny MCE elsewhere.
            $text = str_replace("<p>&nbsp;</p>\r\n","",$text);
            $text = str_replace("<p>&nbsp;</p>","",$text);

            # Sanitise the HTML
            require_once $_SERVER['DOCUMENT_ROOT'] . '/../htmlpurifier/HTMLPurifier.auto.php';
            $this->purifier = new HTMLPurifier();
            $this->purifier->config->set('AutoFormat.RemoveEmpty', true);

            if (is_array($allowed_elements)) {
                $this->purifier->config->set('HTML.Allowed', implode(',',$allowed_elements));
            } else {
                # Default config if none specified
                $this->purifier->config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
            }

            $text = $this->purifier->purify($text);

            # Since this is likely to be text edited using Tiny MCE, remove the extra
            # span tags inserted by the Firefox Mouseless Browsing add-on
            $text = preg_replace('/<span style="[^"]+">[0-9]+<\/span>/', "",$text);
            
            # Strip empty links and divs
            $text = preg_replace('/<a>([^<]*)<\/a>/', "$1", $text);
            $text = preg_replace('/<div>([^<]*)<\/div>/', "$1<br />", $text);
        }

        return "'" . $this->GetDataConnection()->EscapeString($text) . "'";
    }

    /**
     * Log and execute a query
     *
     * @param string $sql
     */
    protected function LoggedQuery($sql)
    {
        # Clear out any audit records older than six months, otherwise the database will get too big
        $six_months_ago = gmdate('U') - (60*60*24*31*6);
        $this->GetDataConnection()->query("DELETE FROM nsa_audit WHERE date_changed < $six_months_ago");

        $log_sql = "INSERT INTO nsa_audit SET 
        user_id = " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId(), true) . ",
        date_changed = " . gmdate('U') . ", 
        query_sql = " . Sql::ProtectString($this->GetDataConnection(), $sql) . ", 
        request_url = " . Sql::ProtectString($this->GetDataConnection(), $_SERVER['REQUEST_URI']);
        $this->GetDataConnection()->query($log_sql);
        
        # Run the actual query last, so that the insert ID and rows affected are available if required
        $this->GetDataConnection()->query($sql);
    }
}
?>