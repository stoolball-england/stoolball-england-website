<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/' . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/../");

require_once('context/stoolball-settings.class.php');
require_once('data/mysql-connection.class.php');
require_once('http/short-url-manager.class.php');

$settings = new StoolballSettings();
$db = new MySqlConnection($settings->DatabaseHost(), $settings->DatabaseUser(), $settings->DatabasePassword(), $settings->DatabaseName());
$short_url_manager = new ShortUrlManager($settings, $db);
$real_url = $short_url_manager->ParseRequestUrl();
$db->Disconnect();

if (is_array($real_url))
{
	$hidden_get_vars = array_combine($real_url['param_names'], $real_url['param_values']);
	$_GET = array_merge($_GET, $hidden_get_vars);
	$_SERVER['PHP_SELF'] = '/' . $real_url['script'];
	require($real_url['script']);
}
else
{
	# Hard-coded URLs which redirect to WordPress and so can't be in .htaccess
	if (strtolower(trim($_SERVER['REQUEST_URI'], '/')) == "insurance")
	{
		header("Location: /manage/insurance/");
		exit();
	}

	# If page requested starting with /news, make WordPress think it was /category/news
	if (substr(strtolower(trim($_SERVER['REQUEST_URI'], '/')), 0, 4) == "news")
	{
		if ($_SERVER['REQUEST_URI'] == "/news") $_SERVER['REQUEST_URI'] = "/news/"; # Keeps the /category bit invisible if just /news requested
		$_SERVER['REQUEST_URI'] = "/category" . $_SERVER['REQUEST_URI'];
	}

    # Does it look suspicious?
    if (is_suspicious_request($_SERVER['REQUEST_URI']))
    {
        require_once(implode(DIRECTORY_SEPARATOR, array($_SERVER['DOCUMENT_ROOT'], 'wp-content', 'themes', 'stoolball', '404.php')));
        die();
    }

	# Pass request to WordPress
	require('index.php');
}

function is_suspicious_request($request_uri)
{
    # .php files which actually exist never got referred to short_urls.php, 
    # so any remaining .php requests are trying to get something they shouldn't    
    return (strpos(strtolower($request_uri), '.php') !== false);
}
?>