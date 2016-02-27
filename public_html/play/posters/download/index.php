<?php
/**
 * Creates a stoolball poster based on submitted data
 */

// Include the main TCPDF library
date_default_timezone_set('Europe/London');
require_once('tcpdf-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/tecnickcom/tcpdf/tcpdf.php');

// Validate the requested design
$design = $_POST["design"];
if (!preg_match("/^[a-z0-9-]+$/", $design) or !file_exists("../designs/$design.class.php") or !file_exists("../designs/$design.jpg")) {
    http_response_code(400);
    exit();
} 

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
        $value = $_POST[$field];
        $value = mb_strimwidth($value, 0, $maxlength, '', "UTF-8");
        return htmlspecialchars($value);
    }
    else return '';
}

// Sanitise the submitted data, and trim to the longest length allowed by any design
// (because maxlength is not applied as you switch designs, so it can be longer than the poster is designed for)
$title = sanitisePostedData('title', 18);
$slogan = sanitisePostedData('slogan', 27);
$name = sanitisePostedData('name', 80);
$details = nl2br(sanitisePostedData('details', 300));

// Add the title and background image 
$pdf->SetTitle($title);
$pdf->Image("../designs/$design.jpg", 10, 10, 190, 277, 'JPG', '', '', false);

// Pass the data to the specific poster design to update $pdf
require_once("../designs/$design.class.php");
$poster = new PosterDesign($pdf, $title, $slogan, $name, $details);

// Close and output PDF document
$pdf->Output('stoolball-poster.pdf', 'I');
