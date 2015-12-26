<?php
class ValidatorMode
{
	/**
	* @return int
	* @desc A single field, supplied as a string, must validate
	*/
	public static function SingleField() { return 0; }

	/**
	* @return int
	* @desc Any one of the fields in the supplied string array must validate
	*/
	public static function AnyField() { return 1; }

	/**
	* @return int
	* @desc All fields in the supplied string array must validate
	*/
	public static function AllFields() { return 2; }

	/**
	* @return int
	* @desc All fields in the supplied string array must validate, or none should
	*/
	public static function AllOrNothing() { return 3; }
	
	/**
	* @return int
	* @desc The validator operates on multiple fields
	*/
	public static function MultiField() { return 4; }
}
?>