<?php
/**
 * Creates a stoolball poster based on submitted data
 */

// Include the main TCPDF library
date_default_timezone_set('Europe/London');
require_once('tcpdf-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/tecnickcom/tcpdf/tcpdf.php');

/* create new PDF document
 * Page orientation (P=portrait, L=landscape)
 * Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch].
 */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// set document information
$pdf->SetAuthor('Stoolball England');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set auto page breaks
$pdf->SetAutoPageBreak(false, 0);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

function sanitisePostedData($field, $maxlength) {
    if (isset($_POST[$field])) {
        $value = trim($_POST[$field]);
        $value = mb_strimwidth($value, 0, $maxlength, '', "UTF-8");
        return htmlspecialchars($value);
    }
    else return '';
}

function buildHTML($element, $text, $line_height=0) {
    $line_height = (int)$line_height;
    if ($line_height) {
        $line_height = $line_height . 'em';
    }
    $html = <<<EOD
<$element style="line-height:$line_height">$text</h1>
EOD;
return $html;
}

// Add the background image, and draw a box for the text 
$pdf->Image('../designs/connie.jpg', 0, 0, 210, 297, 'JPG', '', '', false);
$pdf->Rect(10, 237, 190, 50, 'F', '', array(59,118,210));
$pdf->SetTextColor(255,255,255);

$title = sanitisePostedData('title', 18);
$pdf->SetTitle($title);
$pdf->SetFont('league-gothic', '', 100, '', true);
$pdf->writeHTMLCell(200, 200, 20, 35, buildHTML('h1', $title), 0, 1, 0);

$slogan = sanitisePostedData('slogan', 27);
$pdf->SetFont('league-gothic', '', 70, '', true);
$pdf->writeHTMLCell(80, 200, 130, 135, buildHTML('p', $slogan, 80), 0, 1, 0);

$name = sanitisePostedData('name', 40);
$pdf->SetFont('AlegreyaSans-ExtraBold', '', 25, '', true);
$pdf->writeHTMLCell(200, 100, 20, 247, buildHTML('h2', $name), 0, 1, 0);

$details = nl2br(sanitisePostedData('details', 300));
$pdf->SetFont('AlegreyaSans-Regular', '', 16, '', true);
$pdf->writeHTMLCell(175, 100, 20, 253, buildHTML('p', $details, 20), 0, 1, 0);

// Close and output PDF document
$pdf->Output('stoolball-poster.pdf', 'I');
