<?php
class TextBoxMode
{
	/**
	* @return int
	* @desc Render TextBox as an &lt;input type="text" /&gt; XHTML element
	*/
	public static function SingleLine() { return 0; }

	/**
	* @return int
	* @desc Render TextBox as an &lt;textarea&gt&lt;/textarea&gt; XHTML element
	*/
	public static function MultiLine() { return 1; }

	/**
	* @return int
	* @desc Render TextBox as an &lt;input type="password" /&gt; XHTML element
	*/
	public static function Password() { return 2; }

	/**
	* @return int
	* @desc Render TextBox as an &lt;input type="file" /&gt; XHTML element
	*/
	public static function File() { return 3; }

	/**
	* @return int
	* @desc Render TextBox as an &lt;input type="hidden" /&gt; XHTML element
	*/
	public static function Hidden() { return 4; }
    
    /**
    * @return int
    * @desc Render TextBox as an &lt;input type="number" /&gt; XHTML element
    */
    public static function Number() { return 5; }
}
?>