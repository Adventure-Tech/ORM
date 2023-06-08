<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class MissingValueForColumnException extends RuntimeException
{
    public function __construct(string $property, int $code = 0, ?Throwable $previous = null)
    {
        $message = 'Forgot to set non-nullable property "' . $property . '"';
        parent::__construct($message, $code, $previous);
    }
}
