<?php
/**
 * Base class for specific TCPDF poster designs to inherit
 */
abstract class BasePosterDesign {
    protected function buildHTML($element, $text, $line_height=0) {
        $line_height = (int)$line_height;
        if ($line_height) {
            $line_height = $line_height . 'em';
        }
        return "<$element style=\"line-height:$line_height\">$text</h1>";
    }
}
?>