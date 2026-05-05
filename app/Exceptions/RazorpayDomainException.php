<?php

namespace App\Exceptions;

use RuntimeException;

class RazorpayDomainException extends RuntimeException
{
    private int $statusCode;

    public function __construct(string $message, int $statusCode = 422, ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
