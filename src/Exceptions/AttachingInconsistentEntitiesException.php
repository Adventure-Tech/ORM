<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class AttachingInconsistentEntitiesException extends RuntimeException
{
    public function __construct(string $message = 'All entities in collection must be of correct type', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
