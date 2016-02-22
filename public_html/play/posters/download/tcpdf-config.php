<?php
 
/**
 * Define the following constant to ignore the default configuration file.
 */
define ('K_TCPDF_EXTERNAL_CONFIG', true);

/**
 * Cache directory for temporary files (full path).
 */
define ('K_PATH_CACHE', sys_get_temp_dir().'/');

/**
 * If true allows to call TCPDF methods using HTML syntax
 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
 */
define('K_TCPDF_CALLS_IN_HTML', false);

/**
 * If true and PHP version is greater than 5, then the Error() method throw new exception instead of terminating the execution.
 */
define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
?>