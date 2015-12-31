<?php
/**
 * Content which can be indexed and found by an ISearchProvider
 */
class SearchItem
{
    private $type;
    private $id;
    private $url;
    private $title;
    private $description;
    private $keywords;
    private $text;
    private $related_links_html; 
    private $weight_within_type = 1;
    private $weight_of_type = 1;
    private $content_date;
    
    public function __construct($type=null, $id=null, $url=null, $title=null, $description=null, $keywords=null) {
            
        $this->SearchItemType($type);
        $this->SearchItemId($id);
        $this->Url($url);
        $this->Title($title);
        $this->Description($description);
        $this->Keywords($keywords);
    }
    
    /**
     * Gets or sets the type, or group, to which this content belongs
     */
	public function SearchItemType($type = null)
	{
        if (is_null($type)) {
            return $this->type;
        } else {
            $this->type = (string)$type;
        }
	}

    /**
     * Gets or sets the id, unique within the type, of this content
     */
	public function SearchItemId($id = null)
	{
        if (is_null($id)) {
            return $this->id;
        } else {
            $this->id = (string)$id;
        }
	}

    /**
     * Gets or sets the URL of the content
     */
	public function Url($url = null)
	{
        if (is_null($url)) {
            return $this->url;
        } else {
            $this->url = (string)$url;
        }
	}

    /**
     * Gets or sets the title of the content
     */
	public function Title($title = null)
	{
        if (is_null($title)) {
            return $this->title;
        } else {
            $this->title = (string)$title;
        }
	}

    /**
     * Gets or sets the description to be displayed in search results
     */
	public function Description($description = null)
	{
        if (is_null($description)) {
            return $this->description;
        } else {
            $this->description = (string)$description;
        }
	}

    /**
     * Gets or sets the keywords to be prioritise this item in search results
     */
    public function Keywords($keywords = null)
    {
        if (is_null($keywords)) {
            return $this->keywords;
        } else {
            $this->keywords = (string)$keywords;
        }
    }

    /**
     * Gets or sets the full text to be indexed
     */
	public function FullText($text=null)
	{
        if (is_null($text)) {
            return $this->text;
        } else {
            $this->text = (string)$text;
        }
	}

    /**
     * Gets or sets the HTML for any related links to display in the search result
     */
    public function RelatedLinksHtml($html=null)
    {
        if (is_null($html)) {
            return $this->related_links_html;
        } else {
            $this->related_links_html = (string)$html;
        }
    }
    
    /**
     * Gets or sets the weighting of all results of this type, where 1 is normal
     */
    public function WeightOfType($weight=null)
    {
        if (is_null($weight)) {
            return $this->weight_of_type;
        } else {
            $this->weight_of_type = (string)$weight;
        }
    }
    
    /**
     * Gets or sets the weighting within all results of this type, where 1 is normal
     */
    public function WeightWithinType($weight=null)
    {
        if (is_null($weight)) {
            return $this->weight_within_type;
        } else {
            $this->weight_within_type = (string)$weight;
        }
    }    
        
    /**
     * Gets or sets the date of the content if applicable
     */
    public function ContentDate(DateTime $content_date=null)
    {
        if (is_null($content_date)) {
            return $this->content_date;
        } else {
            $this->content_date = $content_date;
        }
    }
}
?>