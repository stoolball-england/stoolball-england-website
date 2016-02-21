<?php
//============================================================+
// File name   : example_001.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 001 for TCPDF class
//               Default Header and Footer
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Default Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
date_default_timezone_set('Europe/London');
require_once('tcpdf-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/tecnickcom/tcpdf/tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Stoolball England');
$pdf->SetTitle('Play stoolball');
$pdf->SetSubject('Play stoolball');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);


// set margins
$pdf->SetMargins(0,0,0);

// set auto page breaks
$pdf->SetAutoPageBreak(false, 0);
// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

$pdf->Image('connie.jpg', 0, 0, 210, 297, 'JPG', '', '', false);

$pdf->SetFont('Tandelle', '', 110, '', true);
#$pdf->SetFont('Oswald', '', 70, '', true);
$title = strip_tags($_POST['title']);
$html = <<<EOD
<h1 style="color:#fff;margin:0;line-height:1em">$title</h1>
EOD;
$pdf->writeHTMLCell(200, 200, 20, 35, $html, 0, 1, 0, true, '', true);


$pdf->SetFont('Tandelle', '', 90, '', true);
$teaser = strip_tags($_POST['teaser']);
$html = <<<EOD
<div  style="color:#fff;margin:0;line-height:85em"><p>$teaser</p></div>
EOD;
$pdf->writeHTMLCell(80, 200, 130, 75, $html, 0, 1, 0, true, '', true);


$pdf->Rect(10, 237, 190, 50, 'F', '', array(59,118,210));

// Set some content to print
$pdf->SetFont('AlegreyaSans-ExtraBold', '', 25, '', true);
$name = strip_tags($_POST['name']);
$html = <<<EOD
<h2 style="color:#fff;margin:0;line-height:1em">$name</h2>
EOD;
$pdf->writeHTMLCell(200, 100, 20, 247, $html, 0, 1, 0, true, '', true);

$pdf->SetFont('AlegreyaSans-Regular', '', 16, '', true);
$details = nl2br(strip_tags($_POST['details']));
$html = <<<EOD
<p style="color:#fff;margin:0;line-height:20em;">$details</p>
EOD;
$pdf->writeHTMLCell(175, 100, 20, 253, $html, 0, 1, 0, true, '', true);


// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('stoolball-poster.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
