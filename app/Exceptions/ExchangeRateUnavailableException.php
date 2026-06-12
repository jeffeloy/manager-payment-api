<?php

namespace App\Exceptions;

use Exception;

class ExchangeRateUnavailableException extends Exception
{
    public function __construct(string $message = 'Exchange rate service is currently unavailable.')
    {
        parent::__construct($message);
    }
}
