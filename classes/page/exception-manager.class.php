<?php
require_once ('context/site-context.class.php');

/**
 * Manager to catch and publish all errors and exceptions
 *
 */
class ExceptionManager
{
	private $publishers = array();

	/**
	 * Creates a new ExceptionManager and registers a default publisher
	 *
	 * @return ExceptionManager
	 */
	public function ExceptionManager(array $publishers)
	{
	    foreach ($publishers as $publisher) {
		  $this->AddPublisher($publisher);
        }

		# Wire up default error handlers to pass all errors to registered publishers
		# This also ensures there are no uncaught errors so long as this class has been
		# instantiated
		set_error_handler(array($this, 'ErrorHandler'));
		set_exception_handler(array($this, 'ExceptionHandler'));
	}

	/**
	 * Removes any existing error publishers, including the default publisher
	 *
	 */
	public function ClearPublishers()
	{
		$this->publishers = array();
	}

	/**
	 * Add a new publisher to handle errors, whether explictly published or caught by
	 * the generic handler
	 *
	 * @param IExceptionPublisher $publisher
	 */
	public function AddPublisher(IExceptionPublisher $publisher)
	{
		$this->publishers[] = $publisher;
	}

	/**
	 * Publishes the supplied exception using the registered publishers
	 *
	 * @param Exception $e
	 * @param array $a_additional_info
	 */
	public function Publish(Exception $e, array $a_additional_info = null)
	{
		if (is_null($a_additional_info))
        {
			$a_additional_info = array();
        }
		foreach ($this->publishers as $publisher)
		{
			$publisher->Publish($e, $a_additional_info);
		}

		$friendly_error_file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'exception.html';
		if (file_exists($friendly_error_file))
		{
			require_once ($friendly_error_file);
		}
		die();
	}

	/**
	 * Error handler which publishes all errors thrown by code which doesn't use
	 * structured exception handling
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param array $errcontext
	 */
	public function ErrorHandler($errno, $errstr, $errfile, $errline, array $errcontext)
	{
		$additional_info = array('File' => $errfile, 'Line' => $errline, 'Context' => $errcontext);
		$e = new Exception($errstr, $errno);
		switch ($e->getCode())
		{
			# Not serious, ignore
			case E_STRICT :
				break;

			# Not serious, log and carry on unless on a WordPress page.
			# WordPress developers don't fix these, so if this is a WordPress page ignore it.
			case E_NOTICE :
			case E_USER_NOTICE :
				if (!function_exists('wp'))
				{
					$this->Publish($e, $additional_info);
				}
				break;

			# Not serious, log and carry on
			case E_WARNING :
			case E_USER_WARNING :
				$this->Publish($e, $additional_info);
				break;

			# Serious, log and die
			default :
				$this->Publish($e, $additional_info);
				if (!headers_sent())
					header("HTTP/1.1 500 Internal Server Error");
				die('<p class="validationSummary">Sorry, there\'s a problem with this page. Please try again later.</p>');
		}
	}

	/**
	 * Error handler which publishes all otherwise-uncaught exceptions
	 *
	 * @param Exception $e
	 */
	public function ExceptionHandler(Exception $e)
	{
		$this->Publish($e);
	}
}
?>