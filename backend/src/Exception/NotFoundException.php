<?php

namespace App\Exception;

class NotFoundException extends ApiException
{
    public function __construct(string $resource)
    {
        parent::__construct($resource . ' not found', 404, 'NOT_FOUND');
    }
}
