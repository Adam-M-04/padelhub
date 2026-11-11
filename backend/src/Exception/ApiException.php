<?php

namespace App\Exception;

class ApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private int $statusCode,
        private string $errorCode,
        private ?array $details = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }
}
