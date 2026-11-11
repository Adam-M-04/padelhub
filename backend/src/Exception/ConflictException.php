<?php

namespace App\Exception;

class ConflictException extends ApiException
{
    public function __construct(string $message, string $errorCode = 'CONFLICT')
    {
        parent::__construct($message, 409, $errorCode);
    }
}
