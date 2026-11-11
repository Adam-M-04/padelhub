<?php

namespace App\Exception;

class ValidationException extends ApiException
{
    public function __construct(array $details)
    {
        parent::__construct('Validation failed', 400, 'VALIDATION_ERROR', $details);
    }
}
