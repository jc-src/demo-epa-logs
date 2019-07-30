<?php
/**
 * Class ExceptionHandler
 */
class ExceptionHandler extends Exception
{
    public function __construct($message = "", $status = 0) {
        parent::__construct($message);
        Response::return(['error' => $message, 'code' => $status], $status);
    }
}