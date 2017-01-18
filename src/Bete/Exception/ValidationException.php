<?php

namespace Bete\Exception;

class ValidationException extends \Exception
{
    protected $code = 1001;

    public $validator;

    public function __construct($validator)
    {
        parent::__construct('The given data failed to pass validation.');

        $this->validator = $validator;
    }
}
