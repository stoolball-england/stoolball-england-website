<?php
require_once("base-poster-design.class.php");

class PosterDesign extends BasePosterDesign
{
    public function __construct(TCPDF $pdf, $title, $slogan, $name, $details) {
            
        $pdf->SetTextColor(23,48,80);
        $pdf->SetFont('league-gothic', '', 100, '', true);
        $pdf->writeHTMLCell(170, 200, 20, 40, $this->buildHTML('h1', $title), 0, 1, 0, true, 'C');
        
        $pdf->SetTextColor(51,81,71);
        $pdf->SetFont('AlegreyaSans-MediumItalic', '', 55, '', true);
        $pdf->writeHTMLCell(170, 200, 20, 67, $this->buildHTML('p', $slogan, 65), 0, 1, 0, true, 'C');
        
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFont('AlegreyaSans-ExtraBold', '', 30, '', true);
        $pdf->writeHTMLCell(170, 100, 20, 225, $this->buildHTML('h2', $name, 35), 0, 1, 0, true, 'C');
        
        $pdf->SetFont('AlegreyaSans-Regular', '', 18, '', true);
        $pdf->writeHTMLCell(170, 100, 20, 245, $this->buildHTML('p', $details, 25), 0, 1, 0, true, 'C');
    }
}
?>