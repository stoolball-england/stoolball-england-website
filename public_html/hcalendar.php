<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/' . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../');

/**
 * Action to take if anything, *anything*, unexpected is encountered
 * @return unknown_type
 */
function Abort()
{
	# If there was a problem, this page doesn't exist!
	header("HTTP/1.1 404 Not Found");

	# Display the standard 404 page, which usually has its status set by WordPress
	require_once("wp-content/themes/stoolball/404.php");
	die();
}

# Ensure all errors, old and new, lead to Abort()
//set_error_handler('Abort');
try
{
	# First check if we have the required querystring parameter
	//if (!isset($_GET['page']) or !$_GET['page']) Abort();
	$url = 'https://stoolball.local/play/competitions/competition.php?item=462';

	# Request the page to be parsed
	/* @var $uri Zend_Uri_Http */
	require_once 'Zend/Http/Client.php';
	$user_agent = "Stoolball England microformats parser";
	if (isset($_SERVER['HTTP_USER_AGENT'])) $user_agent = $_SERVER['HTTP_USER_AGENT'] . "; $user_agent";

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );   
	$body = curl_exec($curl);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($curl);
	
	# Check we got a non-error status code
	if ($httpcode >= 400) Abort();

	# Check we got some body content to parse
	if (!$body) Abort();

	# Load the XSLT file, which is from suda.co.uk
	$xslFile = new DOMDocument();
	$xslFile->load($_SERVER['DOCUMENT_ROOT'] . '/xhtml2vcal.xsl');

    # Remove HTML the parser does not know how to deal with
    $body = preg_replace('/<\/?nav>/',"",$body);
    
	# Load the HTML to parse
	$pageXhtml = new DOMDocument();
	$pageXhtml->loadHTML($body);

	# Do the transform
	$xslt = new XSLTProcessor();
	$xslt->importStylesheet($xslFile);
	$xslt->setParameter("", "Source", isset($_GET['shorturl']) ? "http://" . $_SERVER['HTTP_HOST'] .  "/" . $_GET['shorturl'] : $uri->__toString());
	$body = $xslt->transformToXml($pageXhtml);

	# Return the result as a vCalendar
	header("Content-Disposition: attachment; filename=stoolball-" . (isset($_GET['shorturl']) ? $_GET['shorturl'] : "calendar"));
	header('Content-Type: text/calendar; charset=utf-8');
	echo $body;
}
catch (Exception $e)
{
	Abort();
}
?>