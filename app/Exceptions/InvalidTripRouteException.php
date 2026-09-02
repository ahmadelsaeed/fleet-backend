<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidTripRouteException extends RuntimeException
{
    public function __construct(string $message = 'Invalid trip route.')
    {
        parent::__construct($message);
    }
}
