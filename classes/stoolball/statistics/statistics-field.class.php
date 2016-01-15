<?php
/**
 * Internal class used by StatisticsManager to specify an additional field used to customise a query 
 */
class StatisticsField 
{
    private $field_name;
    private $display_header;
    private $sort_ascending;
    private $transform_function_for_csv;
    
    /**
     * @param string $field_name The field name in the data source
     * @param string $display_header A falsy value means a field should not be selected
     * @param bool $sort_ascending A null value means don't use this field to sort
     * @param Closure $transform_function_for_csv A function to transform a value when output to CSV, or null to leave it unchanged
     */
    public function __construct($field_name, $display_header, $sort_ascending, $transform_function_for_csv)
    {
        $this->field_name = (string)$field_name;
        $this->display_header = (string)$display_header;
        $this->sort_ascending = $sort_ascending;
        $this->transform_function_for_csv = $transform_function_for_csv;
    }
    
    /**
     * Gets the field name to select, sort or filter by
     */
    public function FieldName() {
        return $this->field_name;
    }
    
    /**
     * Gets the header for the field in a table of data. If falsy, the field is not for selection or display.
     */
    public function DisplayHeader() {
        return $this->display_header;
    }
    
    /**
     * Gets whether to sort by this field in ascending or descending order, or not at all if this method returns null
     */
    public function SortAscending() {
        return $this->sort_ascending;
    }
    
    /**
     * If the field data should be transformed when output to CSV, this does it using a Closure passed to the constructor.
     * Otherwise it simply returns the existing unchanged value.
     */
    public function TransformValueForCsv($value) {
        if ($this->transform_function_for_csv instanceof Closure) {
            $transform_function_for_csv = $this->transform_function_for_csv;
            return $transform_function_for_csv($value);
        } else {
            return $value;
        }
    }
}
?>