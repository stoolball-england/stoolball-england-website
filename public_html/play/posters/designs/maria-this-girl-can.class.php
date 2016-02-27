<?php
require_once("base-poster-design.class.php");

class PosterDesign extends BasePosterDesign
{
    public function __construct(TCPDF $pdf, $title, $slogan, $name, $details) {
            
        $pdf->SetTextColor(255,255,255);
        $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>.5, 'depth_h'=>.5, 'color'=>array(0,0,0), 'opacity'=>.5, 'blend_mode'=>'Multiply'));

        $pdf->SetFont('league-gothic', '', 100, '', true);
        $pdf->writeHTMLCell(170, 200, 20, 40, $this->buildHTML('h1', $title), 0, 1, 0, true, 'C');
        
        $pdf->SetFont('AlegreyaSans-MediumItalic', '', 55, '', true);
        $pdf->writeHTMLCell(170, 200, 20, 63, $this->buildHTML('p', $slogan, 65), 0, 1, 0, true, 'C');
        
        $pdf->setTextShadow(array('enabled'=>false));
        $pdf->SetFont('AlegreyaSans-ExtraBold', '', 25, '', true);
        $pdf->writeHTMLCell(120, 100, 18, 241, $this->buildHTML('h2', $name, 30), 0, 1, 0);
        
        $pdf->SetFont('AlegreyaSans-Regular', '', 16, '', true);
        $pdf->writeHTMLCell(175, 100, 18, 254, $this->buildHTML('p', $details, 20), 0, 1, 0);
    }
}
?>