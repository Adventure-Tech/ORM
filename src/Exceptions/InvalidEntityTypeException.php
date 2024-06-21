<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class InvalidEntityTypeException extends RuntimeException // TODO
{
    public function __construct(string $message = 'Invalid entity type', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
