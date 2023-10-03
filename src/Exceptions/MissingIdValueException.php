<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class MissingIdValueException extends RuntimeException
{
    public function __construct(
        string $message = 'Must set ID column',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
