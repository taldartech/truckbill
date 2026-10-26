<?php

namespace App\Exceptions;

use RuntimeException;

class SubscriptionCatalogUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'Unable to load subscription pricing. Please try again later.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
