<?php
require_once("base-poster-design.class.php");

class PosterDesign extends BasePosterDesign
{
    public function __construct(TCPDF $pdf, $title, $slogan, $name, $details) {
            
        $pdf->SetTextColor(255,255,255);
        $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>.5, 'depth_h'=>.5, 'color'=>array(0,0,0), 'opacity'=>.5, 'blend_mode'=>'Multiply'));

        $pdf->SetFont('BubblegumSans', '', 85, '', true);
        $pdf->writeHTMLCell(170, 200, 20, 35, $this->buildHTML('h1', $title), 0, 1, 0, true, 'C');
        
        $pdf->SetTextColor(247,220,102);
        $pdf->SetFont('BubblegumSans', '', 48, '', true);
        $pdf->writeHTMLCell(80, 200, 110, 55, $this->buildHTML('p', $slogan, 58), 0, 1, 0, true, 'C');
        
        $pdf->SetTextColor(0,0,0);
        $pdf->setTextShadow(array('enabled'=>false));
        $pdf->SetFont('AlegreyaSans-ExtraBold', '', 25, '', true);
        $pdf->writeHTMLCell(180, 100, 18, 243, $this->buildHTML('h2', $name, 30), 0, 1, 0);
        
        $pdf->SetFont('AlegreyaSans-Regular', '', 16, '', true);
        $pdf->writeHTMLCell(180, 100, 18, 256, $this->buildHTML('p', $details, 20), 0, 1, 0);
    }
}
?>