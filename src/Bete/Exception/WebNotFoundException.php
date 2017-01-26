<?php

namespace Bete\Exception;

class WebNotFoundException extends WebException
{
    public function __construct($message = null, $code = 0, 
        Exception $previous = null)
    {
        parent::__construct(404, $message, $code, $previous);
    }
}
