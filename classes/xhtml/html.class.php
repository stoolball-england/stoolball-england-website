<?php
/**
 * Utility functions for working with HTML
 */
class Html
{
    /**
     * HTML encode a string
     */
    public static function Encode($text)
    {
        # Don't use ENT_HTML5 as it encodes / as &sol;, breaking links in IE8
        $text = htmlentities($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED, "UTF-8", false);
        return $text;
    }
}
?>