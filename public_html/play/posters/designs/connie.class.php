<?php
require_once("base-poster-design.class.php");

class PosterDesign extends BasePosterDesign
{
    public function __construct(TCPDF $pdf, $title, $slogan, $name, $details) {
            
        $pdf->SetTextColor(255,255,255);

        $pdf->SetFont('league-gothic', '', 100, '', true);
        $pdf->writeHTMLCell(200, 200, 18, 35, $this->buildHTML('h1', $title), 0, 1, 0);
        
        $pdf->SetFont('league-gothic', '', 70, '', true);
        $pdf->writeHTMLCell(80, 200, 130, 135, $this->buildHTML('p', $slogan, 80), 0, 1, 0);
        
        $pdf->SetFont('AlegreyaSans-ExtraBold', '', 25, '', true);
        $pdf->writeHTMLCell(180, 100, 18, 241, $this->buildHTML('h2', $name, 30), 0, 1, 0);
        
        $pdf->SetFont('AlegreyaSans-Regular', '', 16, '', true);
        $pdf->writeHTMLCell(180, 100, 18, 254, $this->buildHTML('p', $details, 20), 0, 1, 0);
    }
}
?>