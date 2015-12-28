<?php
require_once('data/content-type.enum.php');

class ReviewItem
{
	/**
	 * Configurable sitewide settings
	 *
	 * @var SiteSettings
	 */
	var $o_settings;
	var $i_id;
	private $i_type;
	var $s_title;
	private $s_date;
	private $s_navigate_url;
    private $linked_data_uri;

	function __construct(SiteSettings $o_settings)
	{
		$this->o_settings = $o_settings;
	}

	/**
	* @return void
	* @param int $i_input
	* @desc Sets the unique database identifier or the item being rated
	*/
	function SetId($i_input)
	{
		if (is_numeric($i_input)) $this->i_id = (int)$i_input;
	}

	/**
	* @return int
	* @desc Gets the unique database identifier of the item being rated
	*/
	function GetId()
	{
		return $this->i_id;
	}

	/**
	* @return void
	* @param int $i_type
	* @desc Sets the ContentType of the item being rated
	*/
	function SetType($i_type)
	{
		$this->i_type = (int)$i_type;
	}

	/**
	* @return int
	* @desc Gets the type of the item being rated
	*/
	function GetType()
	{
		return $this->i_type;
	}

	/**
	* @return void
	* @param string $s_input
	* @desc Sets the name or title of the item being rated
	*/
	function SetTitle($s_input)
	{
		if (is_string($s_input)) $this->s_title = $s_input;
	}

	/**
	* @return string
	* @desc Gets the name or title of the item being rated
	*/
	function GetTitle()
	{
		return $this->s_title;
	}


	function SetDate($s_input)
	{
		if (is_string($s_input)) $this->s_date = $s_input;
	}

	function GetDate()
	{
		return $this->s_date;
	}

	/**
	 * Sets the URL to view the item associated with the comments
	 *
	 * @param string $s_input
	 */
	function SetNavigateUrl($s_input)
	{
		$this->s_navigate_url = trim((string)$s_input);
	}


	/**
	 * Gets the URL to view the item associated with the comments
	 *
	 * @param bool $b_relative
	 */
	function GetNavigateUrl($b_relative=true)
	{
		return $this->s_navigate_url;
	}
    
    /**
     * Sets the linked data URI of the item being reviewed
     */
    public function SetLinkedDataUri($uri) {
        $this->linked_data_uri = (string)$uri;
    }
    
    /**
     * Gets the linked data URI of the item being reviewed
     */
    public function GetLinkedDataUri() {
        return $this->linked_data_uri;
    }
}
?>