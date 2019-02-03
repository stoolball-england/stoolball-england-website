<?php
require_once("exception-publisher.interface.php");

/**
 * Email details when exceptions occur
 */
class EmailExceptionPublisher implements IExceptionPublisher
{
    private $emailAddress;
    private $email_transport;
    
    public function __construct($emailAddress, Swift_Transport_AbstractSmtpTransport $email_transport) {
        $this->emailAddress = $emailAddress;
        $this->email_transport = $email_transport;
    }
    
     /**
     * Display errors in development and email them in release mode
     *
     * @param Throwable $e
     * @param array $a_additional_info
     */
    public function Publish(Throwable $e, array $a_additional_info)
    {
        // define an assoc array of error string
        // in reality the only entries we should
        // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
        // E_USER_WARNING and E_USER_NOTICE
        $error_types = array(E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parsing Error', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core Error', E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice');
        if (defined('E_STRICT'))
            $error_types[E_STRICT] = 'Runtime Notice';
        if (defined('E_RECOVERABLE_ERROR'))
            $error_types[E_RECOVERABLE_ERROR] = 'Catchable Fatal Error';

        # Build up information about the error
        $body = $e->getMessage() . "\n\n";
        if (!stristr($e->getFile(), 'exception-manager.class'))
            $body .= $e->getFile() . ', line ' . $e->getLine() . "\n\n";
        $trace = $e->getTraceAsString();
        if ($trace)
            $body .= $trace . "\n\n";

        if (array_key_exists($e->getCode(), $error_types))
        {
            $body .= 'Code: ' . $error_types[$e->getCode()] . "\n";
        }

        # Get details of anything in additional_info
        ob_start();
        var_dump($a_additional_info);
        $body .= ob_get_contents();
        ob_end_clean();

        # What's the context?
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            foreach ($_POST as $key => $value)
                $body .= '$POST[\'' . $key . '\']: ' . $value . "\n";
        }
        else
        {
            foreach ($_GET as $key => $value)
                $body .= '$GET[\'' . $key . '\']: ' . $value . "\n";
        }
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) and is_string($value)) {
                $body .= '$SERVER[\'' . $key . '\']: ' . $value . "\n";
            }
        }

        $mailer = new Swift_Mailer($this->email_transport);
        $message = (new Swift_Message('Error: ' . $e->getMessage()))
        ->setFrom(['errors@' . $_SERVER['HTTP_HOST'], 'errors@' . $_SERVER['HTTP_HOST']])
        ->setTo([$this->emailAddress])
        ->setBody($body);
        $mailer->send($message);
    }
}
?>