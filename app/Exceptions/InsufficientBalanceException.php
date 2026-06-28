<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a user's wallet can't cover the (server-computed) posting fee.
 * Carries a 402 status so controllers' generic catch returns Payment Required,
 * and the required/shortfall numbers for the client to act on.
 */
class InsufficientBalanceException extends Exception
{
    public function __construct(
        private readonly int $required = 0,
        private readonly float $balance = 0,
    ) {
        parent::__construct('Insufficient wallet balance. Please buy credits.');
    }

    public function getStatusCode(): int
    {
        return 402;
    }

    public function getRequired(): int
    {
        return $this->required;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getShortfall(): float
    {
        return max(0, $this->required - $this->balance);
    }
}
