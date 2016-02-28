<?php
/**
 * Base class for a statistic
 */
abstract class Statistic
{
    private $url_segment;
    private $title;
    private $description;
    private $show_description = false;
    private $item_type_singular;
    private $item_type_plural;
    private $supports_filter_by_player = false;
    private $supports_filter_by_batting_postition = false;
    private $supports_paged_results = true;
    private $column_headers = array();
    private $css_class = "";
    
    /**
     * Sets the URL segment which identifies this statistic
     */
    protected function SetUrlSegment($url_segment) {
        $this->url_segment = (string)$url_segment;
    }
    
    /**
     * Gets the URL segment which identifies this statistic
     */
    public function GetUrlSegment() {
        return $this->url_segment;
    }

    /**
     * Sets the display name of the statistic
     */
    protected function SetTitle($title) {
        $this->title = (string)$title;
    }
    
    /**
     * Gets the display name of the statistic
     */
    public function Title() {
        return $this->title;
    }
    
    /**
     * Sets the description of the statistic when seen in a list
     */
    protected function SetDescription($description) {
        $this->description = (string)$description;
    }
    
    /**
     * Gets the description of the statistic when seen in a list
     */
    public function Description() {
        return $this->description;
    }

    /**
     * Sets whether to show the description of the statistic above the results
     */
    protected function SetShowDescription($show) {
        $this->show_description = (string)$show;
    }
    
    /**
     * Gets whether to show the description of the statistic above the results
     */
    public function ShowDescription() {
        return $this->show_description;
    }      
    /**
     * Sets the custom column headers for this statistic when displaying results in a table
     */
    protected function SetColumnHeaders(array $column_headers) {
        $this->column_headers = $column_headers;
    }
    
    /**
     * Gets the custom column headers for this statistic when displaying results in a table
     */
    public function ColumnHeaders() {
        return $this->column_headers;
    }
        
    /**
     * Sets whether this statistic can be filtered by player
     */
    protected function SetSupportsFilterByPlayer($supported) {
        $this->supports_filter_by_player = (bool)$supported;
    }
    
    /**
     * Gets whether this statistic can be filtered by player
     */
    public function SupportsFilterByPlayer() {
        return $this->supports_filter_by_player;
    }
        
    /**
     * Sets whether this statistic can be filtered by batting position
     */
    protected function SetSupportsFilterByBattingPosition($supported) {
        $this->supports_filter_by_batting_postition = (bool)$supported;
    }
    
    /**
     * Gets whether this statistic can be filtered by batting position
     */
    public function SupportsFilterByBattingPosition() {
        return $this->supports_filter_by_batting_postition;
    }
    
    /**
     * Sets whether this statistic's results can be divided into pages
     */
    protected function SetSupportsPagedResults($supported) {
        $this->supports_paged_results = (bool)$supported;
    }
    
    /**
     * Gets whether this statistic's results can be divided into pages
     */
    public function SupportsPagedResults() {
        return $this->supports_paged_results;
    }
        
    /**
     * Sets the singular description of what a returned result represents
     */
    protected function SetItemTypeSingular($item_type) {
        $this->item_type_singular = (string)$item_type;
    }
    
    /**
     * Gets the singular description of what a returned result represents
     */
    public function ItemTypeSingular() {
        return $this->item_type_singular;
    }
        
    /**
     * Sets the plural description of what a returned result represents
     */
    protected function SetItemTypePlural($item_type) {
        $this->item_type_plural = (string)$item_type;
    }
    
    /**
     * Gets the plural description of what a returned result represents
     */
    public function ItemTypePlural() {
        return $this->item_type_plural;
    }
    
    /**
     * Sets the CSS class to apply when the statistic is displayed
     */
    protected function SetCssClass($css_class) {
        $this->css_class = (string)$css_class;
    }
    
    /**
     * Gets the CSS class to apply when the statistic is displayed
     */
    public function CssClass() {
        return $this->css_class;
    }
    
    /**
     * Gets the statistical data from the data source
     */
    public abstract function ReadStatistic();
}
?>