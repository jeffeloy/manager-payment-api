<?php

namespace App\Exceptions;

use Exception;

class CountryCurrencyUnavailableException extends Exception
{
    public function __construct(string $message = 'Country currency service is currently unavailable.')
    {
        parent::__construct($message);
    }
}
