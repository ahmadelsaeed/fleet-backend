<?php

namespace App\Exceptions;

use RuntimeException;

class SeatConflictException extends RuntimeException
{
    public function __construct(string $message = 'This seat is no longer available for the selected segment.')
    {
        parent::__construct($message);
    }
}
