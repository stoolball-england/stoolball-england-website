<?php
/**
 * A publisher which can publish an exception to a target
 */
interface IExceptionPublisher
{

     /**
     * Publish an exception to a target
     *
     * @param Throwable $e
     * @param array $a_additional_info
     */
    function Publish(Throwable $e, array $a_additional_info); 
}
?>