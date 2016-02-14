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


// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(0,0,0);

// set auto page breaks
$pdf->SetAutoPageBreak(false, 0);
// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 14, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

$pdf->Image('images/background.jpg', 0, 0, 210, 297, 'JPG', '', '', false);


// Set some content to print
$html = <<<EOD
<div style="color:#fff">
<h1 style="margin:0">Anytown Stoolball Club</h1>
<p style="margin:0">Tuesdays and Thursdays from May to August</p>
<p style="margin:0">Call Jo Bloggs on 01234 567890 or email jo.bloggs@example.org</p>
</div>
EOD;

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(200, 100, 20, 235, $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('stoolball-poster.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
