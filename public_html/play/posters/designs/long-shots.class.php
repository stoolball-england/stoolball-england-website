<?php
require_once("base-poster-design.class.php");

class PosterDesign extends BasePosterDesign
{
    public function __construct(TCPDF $pdf, $title, $slogan, $name, $details) {
            
        $pdf->SetTextColor(255,255,255);

        $pdf->SetFont('AlegreyaSans-ExtraBold', '', 25, '', true);
        $pdf->writeHTMLCell(172, 100, 22, 242, $this->buildHTML('h2', $name, 30), 0, 1, 0);
        
        $pdf->SetFont('AlegreyaSans-Regular', '', 16, '', true);
        $pdf->writeHTMLCell(172, 100, 22, 255, $this->buildHTML('p', $details, 20), 0, 1, 0);
    }
}
?>