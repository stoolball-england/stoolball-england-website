<?php
class Sql
{
	private function __construct() { die('Unable to instantiate static class Sql'); }

	/**
	* @return string
	* @param MySqlConnection $conn
	* @param string $s_text
	* @param bool $allow_html
	* @desc Protect and quote string going into the db from SQL injection attacks. Assumes Magic Quotes are not in use.
	*/
	public static function ProtectString(MySqlConnection $conn, $s_text, $allow_html=true)
	{
		# no need for htmlspecialchars() because htmlentities() is applied to all data coming in
		if (!$allow_html) $s_text = strip_tags($s_text);
		return "'" . $conn->EscapeString($s_text) . "'";
	}

	/**
	* @return int
	* @param int $i_number
	* @param bool $b_allow_null
	* @param bool $b_add_equals_operator
	* @desc Protect numeric values from SQL injection attacks
	*/
	public static function ProtectNumeric($i_number, $b_allow_null=false, $b_add_equals_operator=false)
	{
		if ($b_allow_null and ($i_number === null or $i_number === '')) return $b_add_equals_operator ? ' IS NULL ' : ' NULL ';
		else return $b_add_equals_operator ? ' = ' . (int)$i_number : (int)$i_number;
	}

	/**
	* @return float
	* @param float $f_number
	* @param bool $b_allow_null
	* @param bool $for_insert
	* @desc Protect numeric values from SQL injection attacks
	*/
	public static function ProtectFloat($f_number, $b_allow_null=false, $for_insert=false)
	{
		if ($b_allow_null and ($f_number === null or $f_number === ''))
		{
			return $for_insert ? " = NULL " : ' IS NULL ';
		}
		else
		{
			return ' = ' . floatval($f_number);
		}
	}

	/**
	* @return int
	* @param bool $b_bool
	* @param bool $b_allow_null
	* @param bool $b_add_equals_operator
	* @desc Ensures that the boolean value is always an integer (since MySQL doesn't have a bit data type)
	*/
	public static function ProtectBool($b_bool, $b_allow_null=false, $b_add_equals_operator=false)
	{
		if ($b_allow_null and ($b_bool === null or $b_bool === '')) return $b_add_equals_operator ? ' IS NULL ' : ' NULL ';
		else return $b_add_equals_operator ? ' = ' . (int)(bool)$b_bool : (int)(bool)$b_bool;
	}
}
?>