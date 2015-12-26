<?php
/**
 * A publisher which can publish an exception to a target
 */
interface IExceptionPublisher
{

     /**
     * Publish an exception to a target
     *
     * @param Exception $e
     * @param array $a_additional_info
     */
    function Publish(Exception $e, array $a_additional_info); 
}
?>