<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiErrorResponse extends JsonResponse
{
    public function __construct(int $statusCode, string $errorCode, string $message, ?array $details = null)
    {
        $error = [
            'code' => $errorCode,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        parent::__construct(['error' => $error], $statusCode);
    }
}
