<?php

namespace Bete\Exception;

class FatalErrorException extends \ErrorException
{

    public function __construct($message = '', $code = 0, $severity = 900, 
        $filename = '', $lineno = 0, $previous = null)
    {
        parent::__construct($message, $code, $severity, $filename, $lineno, $previous);
    }
}
