<?php

namespace UKFast\SimpleSDK\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public $errors;

    public function __construct($errors)
    {
        $this->errors = $errors;

        parent::__construct('Validation error');
    }
}
