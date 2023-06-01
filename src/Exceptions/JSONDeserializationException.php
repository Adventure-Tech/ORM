<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class JSONDeserializationException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid JSON deserialized',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
