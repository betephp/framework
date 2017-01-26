<?php

namespace Bete\Exception;

class WebException extends \Exception
{

    public $statusCode;

    public function __construct($statusCode = 500, $message = null, $code = 0, 
        Exception $previous = null)
    {
        $this->statusCode = $statusCode;

        parent::__construct($message, $code, $previous);
    }
}
